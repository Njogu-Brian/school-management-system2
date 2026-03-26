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
} from 'react-native';
import { LinearGradient } from 'expo-linear-gradient';
import { Ionicons } from '@expo/vector-icons';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Input } from '@components/common/Input';
import { validators } from '@utils/validators';
import { SPACING, FONT_SIZES, LOGIN_GRADIENT_LIGHT, LOGIN_GRADIENT_DARK, COLORS, SHADOWS } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { Palette } from '@styles/palette';
import { brandingApi } from '@api/branding.api';
import type { AppBranding } from '@types/branding.types';

interface LoginScreenProps {
    navigation: any;
}

export const LoginScreen: React.FC<LoginScreenProps> = ({ navigation }) => {
    const { login, loading } = useAuth();
    const { isDark, colors } = useTheme();
    const insets = useSafeAreaInsets();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [rememberMe, setRememberMe] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [errors, setErrors] = useState<{ email?: string; password?: string }>({});
    const [branding, setBranding] = useState<AppBranding | null>(null);
    const [logoLoadFailed, setLogoLoadFailed] = useState(false);

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

    const validate = (): boolean => {
        const newErrors: { email?: string; password?: string } = {};
        const emailError = validators.email(email);
        if (emailError) newErrors.email = emailError;
        const passwordError = validators.password(password);
        if (passwordError) newErrors.password = passwordError;
        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleLogin = async () => {
        if (!validate()) return;
        try {
            await login({
                email: email.trim(),
                password,
                remember: rememberMe,
            });
        } catch (err: any) {
            Alert.alert('Login Failed', err.message || 'Please check your credentials and try again.');
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
                            Use your school email and password
                        </Text>

                        <View style={styles.form}>
                            <Input
                                label="Email"
                                placeholder="you@school.edu"
                                value={email}
                                onChangeText={(text) => {
                                    setEmail(text);
                                    if (errors.email) setErrors({ ...errors, email: undefined });
                                }}
                                error={errors.email}
                                keyboardType="email-address"
                                autoCapitalize="none"
                                autoComplete="email"
                                icon="email"
                            />

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

                            <Button title="Sign in" onPress={handleLogin} loading={loading} fullWidth style={styles.loginButton} />
                        </View>
                    </View>
                </ScrollView>
            </KeyboardAvoidingView>
        </>
    );

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
});
