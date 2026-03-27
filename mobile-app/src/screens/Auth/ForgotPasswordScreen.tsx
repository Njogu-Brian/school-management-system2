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
import { Palette } from '@styles/palette';

interface ForgotPasswordScreenProps {
    navigation: any;
}

type TabType = 'email' | 'sms' | 'otp';

export const ForgotPasswordScreen: React.FC<ForgotPasswordScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [activeTab, setActiveTab] = useState<TabType>('email');
    const [identifier, setIdentifier] = useState('');
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<{ identifier?: string }>({});

    const validate = (): boolean => {
        const newErrors: { identifier?: string } = {};
        const value = identifier.trim();

        if (!value) {
            newErrors.identifier = 'Work email or phone is required';
        } else if (value.includes('@')) {
            const emailError = validators.email(value);
            if (emailError) newErrors.identifier = emailError;
        } else {
            const phoneError = validators.phone(value);
            if (phoneError) newErrors.identifier = phoneError;
        }

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async () => {
        if (!validate()) return;

        setLoading(true);
        try {
            if (activeTab === 'email') {
                const response = await authApi.resetPasswordEmail({ identifier: identifier.trim() });
                Alert.alert('Success', response.message || 'Reset link sent to your email');
                navigation.goBack();
            } else if (activeTab === 'sms') {
                const response = await authApi.resetPasswordSmsLink({ identifier: identifier.trim() });
                Alert.alert('Success', response.message || 'Reset link sent by SMS');
                navigation.goBack();
            } else {
                const response = await authApi.resetPasswordOTP({ identifier: identifier.trim() });
                Alert.alert('Success', response.message || 'OTP sent successfully');
                navigation.navigate('OTPVerification', { flow: 'password_reset', identifier: identifier.trim() });
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
                    <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
                        <Text style={[styles.backText, { color: colors.primary }]}>← Back</Text>
                    </TouchableOpacity>

                    <View style={styles.content}>
                        <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            Reset Password
                        </Text>
                        <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Send a reset link by email/SMS, or verify by OTP
                        </Text>

                        <View style={[styles.tabContainer, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                            <TouchableOpacity
                                style={[styles.tab, activeTab === 'email' && { backgroundColor: colors.primary }]}
                                onPress={() => setActiveTab('email')}
                            >
                                <Text
                                    style={[
                                        styles.tabText,
                                        { color: activeTab === 'email' ? Palette.onPrimary : isDark ? colors.textSubDark : colors.textSubLight },
                                    ]}
                                >
                                    Email Link
                                </Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[styles.tab, activeTab === 'sms' && { backgroundColor: colors.primary }]}
                                onPress={() => setActiveTab('sms')}
                            >
                                <Text
                                    style={[
                                        styles.tabText,
                                        { color: activeTab === 'sms' ? Palette.onPrimary : isDark ? colors.textSubDark : colors.textSubLight },
                                    ]}
                                >
                                    SMS Link
                                </Text>
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={[styles.tab, activeTab === 'otp' && { backgroundColor: colors.primary }]}
                                onPress={() => setActiveTab('otp')}
                            >
                                <Text
                                    style={[
                                        styles.tabText,
                                        { color: activeTab === 'otp' ? Palette.onPrimary : isDark ? colors.textSubDark : colors.textSubLight },
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
                                icon="person"
                            />

                            <Button
                                title={activeTab === 'email' ? 'Send Email Link' : activeTab === 'sms' ? 'Send SMS Link' : 'Send OTP'}
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
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
    },
    form: {
        width: '100%',
    },
    submitButton: {
        marginTop: SPACING.md,
    },
});
