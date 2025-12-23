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
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { Input } from '@components/common/Input';
import { authApi } from '@api/auth.api';
import { validators } from '@utils/validators';
import { SPACING, FONT_SIZES } from '@constants/theme';

interface ForgotPasswordScreenProps {
    navigation: any;
}

type TabType = 'email' | 'otp';

export const ForgotPasswordScreen: React.FC<ForgotPasswordScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [activeTab, setActiveTab] = useState<TabType>('email');
    const [email, setEmail] = useState('');
    const [phone, setPhone] = useState('');
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<{ email?: string; phone?: string }>({});

    const validate = (): boolean => {
        const newErrors: { email?: string; phone?: string } = {};

        if (activeTab === 'email') {
            const emailError = validators.email(email);
            if (emailError) newErrors.email = emailError;
        } else {
            const phoneError = validators.phone(phone);
            if (phoneError) newErrors.phone = phoneError;
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async () => {
        if (!validate()) return;

        setLoading(true);
        try {
            if (activeTab === 'email') {
                const response = await authApi.resetPasswordEmail({ email: email.trim() });
                Alert.alert('Success', response.message || 'Reset link sent to your email');
                navigation.goBack();
            } else {
                const response = await authApi.resetPasswordOTP({ phone: phone.trim() });
                Alert.alert('Success', response.message || 'OTP sent to your phone');
                navigation.navigate('OTPVerification', { phone: phone.trim() });
            }
        } catch (err: any) {
            Alert.alert('Error', err.message || 'Failed to send reset instructions');
        } finally {
            setLoading(false);
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
                    {/* Header */}
                    <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
                        <Text style={[styles.backText, { color: colors.primary }]}>← Back</Text>
                    </TouchableOpacity>

                    <View style={styles.content}>
                        <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Reset Password
                        </Text>
                        <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Choose how you want to reset your password
                        </Text>

                        {/* Tab Selector */}
                        <View style={[styles.tabContainer, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                            <TouchableOpacity
                                style={[
                                    styles.tab,
                                    activeTab === 'email' && { backgroundColor: colors.primary },
                                ]}
                                onPress={() => setActiveTab('email')}
                            >
                                <Text
                                    style={[
                                        styles.tabText,
                                        {
                                            color: activeTab === 'email' ? '#ffffff' : isDark ? colors.textSubDark : colors.textSubLight,
                                        },
                                    ]}
                                >
                                    Email
                                </Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[
                                    styles.tab,
                                    activeTab === 'otp' && { backgroundColor: colors.primary },
                                ]}
                                onPress={() => setActiveTab('otp')}
                            >
                                <Text
                                    style={[
                                        styles.tabText,
                                        {
                                            color: activeTab === 'otp' ? '#ffffff' : isDark ? colors.textSubDark : colors.textSubLight,
                                        },
                                    ]}
                                >
                                    OTP
                                </Text>
                            </TouchableOpacity>
                        </View>

                        {/* Form */}
                        <View style={styles.form}>
                            {activeTab === 'email' ? (
                                <Input
                                    label="Email Address"
                                    placeholder="Enter your email"
                                    value={email}
                                    onChangeText={(text) => {
                                        setEmail(text);
                                        if (errors.email) setErrors({ ...errors, email: undefined });
                                    }}
                                    error={errors.email}
                                    keyboardType="email-address"
                                    autoCapitalize="none"
                                    icon="email"
                                />
                            ) : (
                                <Input
                                    label="Phone Number"
                                    placeholder="Enter your phone number"
                                    value={phone}
                                    onChangeText={(text) => {
                                        setPhone(text);
                                        if (errors.phone) setErrors({ ...errors, phone: undefined });
                                    }}
                                    error={errors.phone}
                                    keyboardType="phone-pad"
                                    icon="phone"
                                />
                            )}

                            <Button
                                title={activeTab === 'email' ? 'Send Reset Link' : 'Send OTP'}
                                onPress={handleSubmit}
                                loading={loading}
                                fullWidth
                                style={styles.submitButton}
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
    backButton: {
        padding: SPACING.md,
    },
    backText: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    content: {
        flex: 1,
        paddingHorizontal: SPACING.xl,
    },
    title: {
        fontSize: FONT_SIZES.xxxl,
        fontWeight: 'bold',
        marginBottom: SPACING.xs,
    },
    subtitle: {
        fontSize: FONT_SIZES.md,
        marginBottom: SPACING.xl,
    },
    tabContainer: {
        flexDirection: 'row',
        borderRadius: 8,
        padding: 4,
        marginBottom: SPACING.xl,
    },
    tab: {
        flex: 1,
        paddingVertical: SPACING.sm,
        borderRadius: 6,
        alignItems: 'center',
    },
    tabText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    form: {
        width: '100%',
    },
    submitButton: {
        marginTop: SPACING.md,
    },
});
