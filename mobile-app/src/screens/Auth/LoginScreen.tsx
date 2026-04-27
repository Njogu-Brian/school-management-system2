import React, { useState, useEffect, useMemo } from 'react';
import { StatusBar } from 'expo-status-bar';
import {
    View,
    Text,
    StyleSheet,
    KeyboardAvoidingView,
    Platform,
    ScrollView,
    Alert,
    TouchableOpacity,
    Image,
    ImageBackground,
    Linking,
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Input } from '@components/common/Input';
import { validators } from '@utils/validators';
import { SPACING, FONT_SIZES, LOGIN_GRADIENT_LIGHT, LOGIN_GRADIENT_DARK, COLORS, SHADOWS, BORDER_RADIUS } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import { brandingApi } from '@api/branding.api';
import { authApi } from '@api/auth.api';
import type { AppBranding } from 'types/branding.types';
import {
    authenticateWithBiometrics,
    canUseBiometrics,
    getBiometricAuthBundle,
    getBiometricEnabled,
    saveBiometricAuthBundle,
    setBiometricEnabled,
} from '@utils/biometrics';
import { getToken } from '@utils/storage';
import * as WebBrowser from 'expo-web-browser';
import * as Google from 'expo-auth-session/providers/google';
import { GOOGLE_ANDROID_CLIENT_ID, GOOGLE_IOS_CLIENT_ID, GOOGLE_WEB_CLIENT_ID } from '@utils/env';

WebBrowser.maybeCompleteAuthSession();

interface LoginScreenProps {
    navigation: any;
}

export const LoginScreen: React.FC<LoginScreenProps> = ({ navigation }) => {
    const { login, loading, completeLogin } = useAuth();
    const { isDark, colors } = useTheme();
    const insets = useSafeAreaInsets();

    const [identifier, setIdentifier] = useState('');
    const [password, setPassword] = useState('');
    const [otpLoading, setOtpLoading] = useState(false);
    const [loginMode, setLoginMode] = useState<'password' | 'otp'>('password');
    const [rememberMe, setRememberMe] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [errors, setErrors] = useState<{ identifier?: string; password?: string }>({});
    const [showBiometricButton, setShowBiometricButton] = useState(false);
    const [branding, setBranding] = useState<AppBranding | null>(null);
    const [logoLoadFailed, setLogoLoadFailed] = useState(false);
    const [googleLoading, setGoogleLoading] = useState(false);

    const gradientColors = isDark ? LOGIN_GRADIENT_DARK : LOGIN_GRADIENT_LIGHT;

    const loginGradientStops = useMemo((): readonly [string, string, string, string] => {
        const c = branding?.colors;
        if (c?.primary_dark && c?.primary && c?.primary_light) {
            return [c.primary_dark, c.primary, c.primary, c.primary_light];
        }
        if (c?.primary) {
            return [c.primary, c.primary, colors.primaryLight, colors.primaryLight];
        }
        return [...gradientColors] as readonly [string, string, string, string];
    }, [branding?.colors, colors.primaryLight, gradientColors]);

    const loginBackgroundUri = branding?.login_background_url?.trim() || null;

    useEffect(() => {
        let cancelled = false;
        brandingApi
            .getBranding()
            .then((b) => {
                if (!cancelled && b?.school_name) {
                    setBranding(b);
                    setLogoLoadFailed(false);
                }
            })
            .catch(() => {
                /* offline or API unreachable — keep default hero */
            });
        return () => {
            cancelled = true;
        };
    }, []);

    useEffect(() => {
        setLogoLoadFailed(false);
    }, [branding?.logo_url]);

    const displayName = branding?.school_name?.trim() || 'School ERP';
    const showRemoteLogo = Boolean(branding?.logo_url && !logoLoadFailed);
    const hasGoogleClientId =
        Platform.OS === 'android'
            ? Boolean(GOOGLE_ANDROID_CLIENT_ID)
            : Platform.OS === 'ios'
                ? Boolean(GOOGLE_IOS_CLIENT_ID)
                : Boolean(GOOGLE_WEB_CLIENT_ID);

    const GoogleButton = useMemo(() => {
        const GoogleAuthButton: React.FC = () => {
            const [, googleResponse, googlePromptAsync] = Google.useIdTokenAuthRequest({
                androidClientId: GOOGLE_ANDROID_CLIENT_ID,
                iosClientId: GOOGLE_IOS_CLIENT_ID,
                webClientId: GOOGLE_WEB_CLIENT_ID,
                selectAccount: true,
            });

            useEffect(() => {
                (async () => {
                    if (googleResponse?.type !== 'success') return;
                    const idToken = googleResponse?.params?.id_token;
                    if (!idToken) return;
                    setGoogleLoading(true);
                    try {
                        const res = await authApi.loginWithGoogle({ id_token: String(idToken) });
                        if (res.success && res.data) {
                            await completeLogin(res.data);
                        } else {
                            throw new Error(res.message || 'Google sign-in failed');
                        }
                    } catch (err: any) {
                        Alert.alert('Google sign-in failed', err.message || 'Please try again.');
                    } finally {
                        setGoogleLoading(false);
                    }
                })();
                // eslint-disable-next-line react-hooks/exhaustive-deps
            }, [googleResponse]);

            return (
                <Button
                    title="Continue with Google"
                    onPress={async () => {
                        try {
                            await googlePromptAsync();
                        } catch {
                            Alert.alert('Google sign-in', 'Could not open Google sign-in.');
                        }
                    }}
                    loading={googleLoading}
                    variant="outline"
                    fullWidth
                    style={styles.googleButton}
                />
            );
        };

        return GoogleAuthButton;
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [completeLogin, googleLoading]);

    const validate = (): boolean => {
        const newErrors: { identifier?: string; password?: string } = {};
        const id = identifier.trim();
        if (!id) {
            newErrors.identifier = 'Work email or phone number is required';
        } else if (id.includes('@')) {
            const emailError = validators.email(id);
            if (emailError) newErrors.identifier = emailError;
        } else {
            const phoneError = validators.phone(id);
            if (phoneError) newErrors.identifier = phoneError;
        }
        if (loginMode === 'password') {
            const passwordError = validators.password(password);
            if (passwordError) newErrors.password = passwordError;
        }
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleLogin = async () => {
        if (!validate()) return;
        try {
            await login({
                identifier: identifier.trim(),
                password,
                remember: rememberMe,
            });
            const biometricEnabled = await getBiometricEnabled();
            const deviceSupportsBiometrics = await canUseBiometrics();
            if (biometricEnabled && deviceSupportsBiometrics) {
                const token = await getToken();
                if (token) {
                    await saveBiometricAuthBundle(token);
                    setShowBiometricButton(true);
                }
                setShowBiometricButton(true);
            }
        } catch (err: any) {
            Alert.alert('Login Failed', err.message || 'Please check your credentials and try again.');
        }
    };

    const handleRequestOtp = async () => {
        if (!validate()) return;
        setOtpLoading(true);
        try {
            await authApi.requestLoginOTP({ identifier: identifier.trim() });
            navigation.navigate('OTPVerification', {
                flow: 'login',
                identifier: identifier.trim(),
            });
        } catch (err: any) {
            Alert.alert('OTP failed', err.message || 'Could not send OTP.');
        } finally {
            setOtpLoading(false);
        }
    };

    const handleBiometricLogin = async () => {
        const success = await authenticateWithBiometrics('Authenticate to sign in');
        if (!success) return;
        const bundle = await getBiometricAuthBundle();
        if (!bundle?.token) {
            Alert.alert('Biometric login', 'No saved biometric session found. Login once with password.');
            await setBiometricEnabled(false);
            setShowBiometricButton(false);
            return;
        }
        try {
            await authApi.getProfile(); // validate token in secure store
        } catch (err: any) {
            Alert.alert('Biometric login failed', err.message || 'Please sign in with password.');
        }
    };

    const cardBg = isDark ? 'rgba(28,22,41,0.94)' : 'rgba(255,255,255,0.97)';
    const cardBorder = isDark ? 'rgba(26,107,196,0.28)' : `${colors.primary}24`;

    const mainContent = (
        <>
            <StatusBar style="light" />
            {!loginBackgroundUri ? (
                <>
                    <View style={[styles.blob, styles.blob1]} />
                    <View style={[styles.blob, styles.blob2]} />
                    <View style={[styles.blob, styles.blob3]} />
                </>
            ) : null}

            <KeyboardAvoidingView
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                style={styles.keyboardAvoid}
                keyboardVerticalOffset={Platform.OS === 'ios' ? 0 : 24}
            >
                <ScrollView
                    contentContainerStyle={[
                        styles.scrollContent,
                        { paddingTop: Math.max(insets.top, SPACING.lg), paddingBottom: insets.bottom + SPACING.lg },
                    ]}
                    keyboardShouldPersistTaps="handled"
                    showsVerticalScrollIndicator={false}
                >
                    <View style={styles.hero}>
                        <LinearGradient
                            colors={[Palette.onPrimary, branding?.colors?.accent_light ?? colors.accentLight]}
                            style={styles.logoOuter}
                        >
                            {showRemoteLogo ? (
                                <View style={styles.logoImageWrap}>
                                    <Image
                                        source={{ uri: branding!.logo_url! }}
                                        style={styles.logoImage}
                                        resizeMode="contain"
                                        accessibilityLabel={`${displayName} logo`}
                                        onError={() => setLogoLoadFailed(true)}
                                    />
                                </View>
                            ) : (
                                <LinearGradient colors={[colors.primaryLight, colors.primary]} style={styles.logoInner}>
                                    <Ionicons name="school" size={44} color={Palette.onPrimary} />
                                </LinearGradient>
                            )}
                        </LinearGradient>
                        <Text style={styles.brandName} numberOfLines={2}>
                            {displayName}
                        </Text>
                        <Text style={styles.tagline}>Sign in to your workspace</Text>
                    </View>

                    <View
                        style={[
                            styles.card,
                            {
                                backgroundColor: cardBg,
                                borderColor: cardBorder,
                            },
                        ]}
                    >
                        <Text style={[styles.cardTitle, { color: isDark ? colors.textMainDark : BRAND.text }]}>Welcome back</Text>
                        <Text style={[styles.cardSubtitle, { color: isDark ? colors.textSubDark : BRAND.muted }]}>
                            Use work email or phone number
                        </Text>

                        <View style={styles.authTabs}>
                            <TouchableOpacity
                                style={[styles.authTab, loginMode === 'password' && { backgroundColor: colors.primary }]}
                                onPress={() => setLoginMode('password')}
                            >
                                <Text
                                    style={[
                                        styles.authTabText,
                                        { color: loginMode === 'password' ? Palette.onPrimary : colors.primary },
                                    ]}
                                >
                                    Password
                                </Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[styles.authTab, loginMode === 'otp' && { backgroundColor: colors.primary }]}
                                onPress={() => setLoginMode('otp')}
                            >
                                <Text
                                    style={[
                                        styles.authTabText,
                                        { color: loginMode === 'otp' ? Palette.onPrimary : colors.primary },
                                    ]}
                                >
                                    OTP
                                </Text>
                            </TouchableOpacity>
                        </View>

                        <View style={styles.form}>
                            <Input
                                label="Work email or phone"
                                placeholder="you@school.edu or +2547..."
                                value={identifier}
                                onChangeText={(text) => {
                                    setIdentifier(text);
                                    if (errors.identifier) setErrors({ ...errors, identifier: undefined });
                                }}
                                error={errors.identifier}
                                keyboardType="default"
                                autoCapitalize="none"
                                autoComplete="username"
                                icon="person"
                            />

                            {loginMode === 'password' ? (
                                <Input
                                    label="Password"
                                    placeholder="••••••••"
                                    value={password}
                                    onChangeText={(text) => {
                                        setPassword(text);
                                        if (errors.password) setErrors({ ...errors, password: undefined });
                                    }}
                                    error={errors.password}
                                    secureTextEntry={!showPassword}
                                    autoCapitalize="none"
                                    icon="lock"
                                    rightIcon={showPassword ? 'visibility-off' : 'visibility'}
                                    onRightIconPress={() => setShowPassword(!showPassword)}
                                />
                            ) : null}

                            <View style={styles.row}>
                                <TouchableOpacity style={styles.checkbox} onPress={() => setRememberMe(!rememberMe)}>
                                    <View
                                        style={[
                                            styles.checkboxBox,
                                            {
                                                borderColor: isDark ? colors.borderDark : BRAND.border,
                                                backgroundColor: rememberMe ? colors.primary : 'transparent',
                                            },
                                        ]}
                                    >
                                        {rememberMe ? <Text style={styles.checkmark}>✓</Text> : null}
                                    </View>
                                    <Text style={[styles.checkboxLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                        Remember me
                                    </Text>
                                </TouchableOpacity>

                                <TouchableOpacity onPress={() => navigation.navigate('ForgotPassword')}>
                                    <Text style={[styles.forgotText, { color: colors.primary }]}>Forgot password?</Text>
                                </TouchableOpacity>
                            </View>

                            <Button
                                title={loginMode === 'password' ? 'Sign in' : 'Send OTP'}
                                onPress={loginMode === 'password' ? handleLogin : handleRequestOtp}
                                loading={loginMode === 'password' ? loading : otpLoading}
                                fullWidth
                                style={styles.loginButton}
                            />
                            {hasGoogleClientId ? (
                                <GoogleButton />
                            ) : (
                                <Button
                                    title="Continue with Google"
                                    onPress={() => {
                                        Alert.alert(
                                            'Google sign-in not configured',
                                            'Missing Google OAuth client id for this platform. Set EXPO_PUBLIC_GOOGLE_ANDROID_CLIENT_ID (and iOS/web if needed) in mobile-app/.env, then restart the dev server and rebuild the app.',
                                        );
                                    }}
                                    loading={false}
                                    disabled
                                    variant="outline"
                                    fullWidth
                                    style={styles.googleButton}
                                />
                            )}
                            {showBiometricButton ? (
                                <Button
                                    title="Login with Biometrics"
                                    onPress={handleBiometricLogin}
                                    variant="outline"
                                    fullWidth
                                    style={styles.biometricButton}
                                />
                            ) : null}
                            {Platform.OS === 'android' && branding?.android_apk_download_url ? (
                                <TouchableOpacity
                                    style={styles.apkLink}
                                    onPress={() => Linking.openURL(branding.android_apk_download_url!)}
                                >
                                    <Text style={[styles.apkLinkText, { color: colors.primary }]}>
                                        Download Android app (APK)
                                    </Text>
                                </TouchableOpacity>
                            ) : null}
                        </View>
                    </View>
                </ScrollView>
            </KeyboardAvoidingView>
        </>
    );

    useEffect(() => {
        (async () => {
            const [enabled, available, creds] = await Promise.all([
                getBiometricEnabled(),
                canUseBiometrics(),
                getBiometricAuthBundle(),
            ]);
            setShowBiometricButton(enabled && available && !!creds?.token);
        })();
    }, []);

    if (loginBackgroundUri) {
        return (
            <ImageBackground source={{ uri: loginBackgroundUri }} style={styles.gradient} imageStyle={styles.bgImage}>
                <LinearGradient
                    colors={['rgba(0,0,0,0.55)', 'rgba(0,40,80,0.72)', 'rgba(0,26,51,0.5)']}
                    style={StyleSheet.absoluteFillObject}
                    start={{ x: 0, y: 0 }}
                    end={{ x: 0, y: 1 }}
                />
                {mainContent}
            </ImageBackground>
        );
    }

    return (
        <LinearGradient
            colors={[...loginGradientStops]}
            start={{ x: 0, y: 0 }}
            end={{ x: 0.4, y: 1 }}
            style={styles.gradient}
        >
            {mainContent}
        </LinearGradient>
    );
};

const styles = StyleSheet.create({
    gradient: {
        flex: 1,
    },
    bgImage: {
        resizeMode: 'cover',
    },
    blob: {
        position: 'absolute',
        borderRadius: 999,
        opacity: 0.12,
        backgroundColor: Palette.onPrimary,
    },
    blob1: { width: 220, height: 220, top: -40, right: -60 },
    blob2: { width: 160, height: 160, bottom: '28%', left: -50 },
    blob3: { width: 120, height: 120, top: '42%', right: -20 },
    keyboardAvoid: {
        flex: 1,
    },
    scrollContent: {
        flexGrow: 1,
        paddingHorizontal: SPACING.xl,
        justifyContent: 'center',
    },
    hero: {
        alignItems: 'center',
        marginBottom: SPACING.xl,
    },
    logoOuter: {
        padding: 4,
        borderRadius: 28,
        marginBottom: SPACING.lg,
        ...SHADOWS.lg,
    },
    logoInner: {
        width: 88,
        height: 88,
        borderRadius: 24,
        alignItems: 'center',
        justifyContent: 'center',
    },
    logoImageWrap: {
        width: 88,
        height: 88,
        borderRadius: 24,
        overflow: 'hidden',
        backgroundColor: 'rgba(255,255,255,0.98)',
        alignItems: 'center',
        justifyContent: 'center',
    },
    logoImage: {
        width: 80,
        height: 80,
    },
    brandName: {
        width: '100%',
        textAlign: 'center',
        fontSize: FONT_SIZES.xxxl,
        fontWeight: '800',
        color: Palette.onPrimary,
        letterSpacing: -0.5,
        textShadowColor: 'rgba(0,0,0,0.15)',
        textShadowOffset: { width: 0, height: 1 },
        textShadowRadius: 4,
    },
    tagline: {
        width: '100%',
        textAlign: 'center',
        fontSize: FONT_SIZES.md,
        color: 'rgba(255,255,255,0.88)',
        marginTop: SPACING.xs,
        fontWeight: '500',
    },
    card: {
        width: '100%',
        borderRadius: RADIUS.card + 4,
        borderWidth: 1,
        paddingVertical: SPACING.xl,
        paddingHorizontal: SPACING.lg,
        ...SHADOWS.md,
    },
    cardTitle: {
        fontSize: FONT_SIZES.xl,
        fontWeight: '700',
    },
    cardSubtitle: {
        fontSize: FONT_SIZES.sm,
        marginTop: 4,
        marginBottom: SPACING.lg,
    },
    form: {
        width: '100%',
    },
    authTabs: {
        flexDirection: 'row',
        gap: SPACING.sm,
        marginBottom: SPACING.md,
    },
    authTab: {
        flex: 1,
        borderWidth: 1,
        borderColor: COLORS.primary,
        borderRadius: BORDER_RADIUS.md,
        paddingVertical: SPACING.sm,
        alignItems: 'center',
    },
    authTabText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '700',
    },
    row: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.md,
        flexWrap: 'wrap',
        gap: SPACING.sm,
    },
    checkbox: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    checkboxBox: {
        width: 22,
        height: 22,
        borderWidth: 2,
        borderRadius: 6,
        alignItems: 'center',
        justifyContent: 'center',
        marginRight: SPACING.sm,
    },
    checkmark: {
        color: Palette.onPrimary,
        fontSize: 14,
        fontWeight: 'bold',
    },
    checkboxLabel: {
        fontSize: FONT_SIZES.sm,
    },
    forgotText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '700',
    },
    loginButton: {
        marginTop: SPACING.sm,
    },
    googleButton: {
        marginTop: SPACING.sm,
    },
    biometricButton: {
        marginTop: SPACING.sm,
    },
    apkLink: {
        marginTop: SPACING.md,
        alignItems: 'center',
    },
    apkLinkText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
        textDecorationLine: 'underline',
    },
});
