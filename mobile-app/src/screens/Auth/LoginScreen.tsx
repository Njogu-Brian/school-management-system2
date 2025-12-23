import React, { useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    KeyboardAvoidingView,
    Platform,
    ScrollView,
    Alert,
    TouchableOpacity,
} from 'react-native';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Input } from '@components/common/Input';
import { validators } from '@utils/validators';
import { SPACING, FONT_SIZES } from '@constants/theme';

interface LoginScreenProps {
    navigation: any;
}

export const LoginScreen: React.FC<LoginScreenProps> = ({ navigation }) => {
    const { login, loading, error } = useAuth();
    const { isDark, colors } = useTheme();

    const [email, setEmail] = useState('');
    const [password, setPassword] = useState('');
    const [rememberMe, setRememberMe] = useState(false);
    const [showPassword, setShowPassword] = useState(false);
    const [errors, setErrors] = useState<{ email?: string; password?: string }>({});

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
            // Navigation will be handled automatically by AppNavigator
        } catch (err: any) {
            Alert.alert('Login Failed', err.message || 'Please check your credentials and try again.');
        }
    };

    return (
        <SafeAreaView
            style={[
                styles.container,
                { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
            ]}
        >
            <KeyboardAvoidingView
                behavior={Platform.OS === 'ios' ? 'padding' : 'height'}
                style={styles.keyboardAvoid}
            >
                <ScrollView
                    contentContainerStyle={styles.scrollContent}
                    keyboardShouldPersistTaps="handled"
                >
                    <View style={styles.content}>
                        {/* Logo/Title */}
                        <View style={styles.header}>
                            <View
                                style={[
                                    styles.logo,
                                    { backgroundColor: colors.primary + '20', borderColor: colors.primary },
                                ]}
                            >
                                <Text style={[styles.logoText, { color: colors.primary }]}>SE</Text>
                            </View>
                            <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                School ERP
                            </Text>
                            <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                Sign in to continue
                            </Text>
                        </View>

                        {/* Login Form */}
                        <View style={styles.form}>
                            <Input
                                label="Email"
                                placeholder="Enter your email"
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
                                placeholder="Enter your password"
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

                            {/* Remember Me & Forgot Password */}
                            <View style={styles.row}>
                                <TouchableOpacity
                                    style={styles.checkbox}
                                    onPress={() => setRememberMe(!rememberMe)}
                                >
                                    <View
                                        style={[
                                            styles.checkboxBox,
                                            {
                                                borderColor: isDark ? colors.borderDark : colors.borderLight,
                                                backgroundColor: rememberMe ? colors.primary : 'transparent',
                                            },
                                        ]}
                                    >
                                        {rememberMe && <Text style={styles.checkmark}>✓</Text>}
                                    </View>
                                    <Text style={[styles.checkboxLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                        Remember me
                                    </Text>
                                </TouchableOpacity>

                                <TouchableOpacity onPress={() => navigation.navigate('ForgotPassword')}>
                                    <Text style={[styles.forgotText, { color: colors.primary }]}>Forgot Password?</Text>
                                </TouchableOpacity>
                            </View>

                            {/* Login Button */}
                            <Button
                                title="Sign In"
                                onPress={handleLogin}
                                loading={loading}
                                fullWidth
                                style={styles.loginButton}
                            />
                        </View>
                    </View>
                </ScrollView>
            </KeyboardAvoidingView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    keyboardAvoid: {
        flex: 1,
    },
    scrollContent: {
        flexGrow: 1,
    },
    content: {
        flex: 1,
        paddingHorizontal: SPACING.xl,
        justifyContent: 'center',
    },
    header: {
        alignItems: 'center',
        marginBottom: SPACING.xxl,
    },
    logo: {
        width: 80,
        height: 80,
        borderRadius: 20,
        borderWidth: 2,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: SPACING.md,
    },
    logoText: {
        fontSize: 32,
        fontWeight: 'bold',
    },
    title: {
        fontSize: FONT_SIZES.xxxl,
        fontWeight: 'bold',
        marginBottom: SPACING.xs,
    },
    subtitle: {
        fontSize: FONT_SIZES.md,
    },
    form: {
        width: '100%',
    },
    row: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        marginBottom: SPACING.lg,
    },
    checkbox: {
        flexDirection: 'row',
        alignItems: 'center',
    },
    checkboxBox: {
        width: 20,
        height: 20,
        borderWidth: 2,
        borderRadius: 4,
        alignItems: 'center',
        justifyContent: 'center',
        marginRight: SPACING.sm,
    },
    checkmark: {
        color: '#ffffff',
        fontSize: 14,
        fontWeight: 'bold',
    },
    checkboxLabel: {
        fontSize: FONT_SIZES.sm,
    },
    forgotText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    loginButton: {
        marginTop: SPACING.md,
    },
});
