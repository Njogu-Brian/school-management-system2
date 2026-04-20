import React, { useState, useRef, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    TextInput,
    Alert,
    TouchableOpacity,
} from 'react-native';
import * as Clipboard from 'expo-clipboard';
import SmsRetriever from 'react-native-sms-retriever';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Button } from '@components/common/Button';
import { authApi } from '@api/auth.api';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { layoutStyles } from '@styles/common';

interface OTPVerificationScreenProps {
    navigation: any;
    route: any;
}

export const OTPVerificationScreen: React.FC<OTPVerificationScreenProps> = ({
    navigation,
    route,
}) => {
    const { isDark, colors } = useTheme();
    const { completeLogin } = useAuth();
    const flow: 'password_reset' | 'login' = route.params?.flow || 'password_reset';
    const identifier = route.params?.identifier;

    const [otp, setOtp] = useState(['', '', '', '', '', '']);
    const [loading, setLoading] = useState(false);
    const [resending, setResending] = useState(false);
    const [timer, setTimer] = useState(60);

    const inputRefs = useRef<Array<TextInput | null>>([]);

    useEffect(() => {
        const interval = setInterval(() => {
            setTimer((prev) => (prev > 0 ? prev - 1 : 0));
        }, 1000);

        return () => clearInterval(interval);
    }, []);

    // Android: listen for incoming OTP SMS (SMS Retriever API; no SMS permission)
    useEffect(() => {
        if (typeof SmsRetriever?.startSmsRetriever !== 'function') return;

        let mounted = true;
        let subscription: { remove: () => void } | null = null;

        (async () => {
            try {
                const started = await SmsRetriever.startSmsRetriever();
                if (!mounted || !started) return;

                subscription = SmsRetriever.addSmsListener((event: { message?: string }) => {
                    const msg = String(event?.message ?? '');
                    const m = msg.match(/\b(\d{6})\b/);
                    if (m?.[1]) {
                        setOtpFromCode(m[1]);
                        subscription?.remove?.();
                    }
                });
            } catch {
                // ignore (play services missing etc.)
            }
        })();

        return () => {
            mounted = false;
            subscription?.remove?.();
        };
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, []);

    const setOtpFromCode = (raw: string) => {
        const digitsOnly = String(raw ?? '').replace(/\D/g, '').slice(0, 6);
        if (digitsOnly.length === 0) return;
        const otpArray = digitsOnly.split('');
        setOtp(otpArray.concat(Array(6 - otpArray.length).fill('')));
        if (digitsOnly.length >= 6) {
            inputRefs.current[5]?.focus();
        } else {
            inputRefs.current[Math.min(digitsOnly.length, 5)]?.focus();
        }
    };

    const handleChangeText = (text: string, index: number) => {
        // Handle paste/auto-fill
        if (text.length > 1) return setOtpFromCode(text);

        const newOtp = [...otp];
        newOtp[index] = text;
        setOtp(newOtp);

        if (text && index < 5) {
            inputRefs.current[index + 1]?.focus();
        }
    };

    const handlePasteFromClipboard = async () => {
        const clip = await Clipboard.getStringAsync();
        setOtpFromCode(clip);
    };

    const handleKeyPress = (e: any, index: number) => {
        if (e.nativeEvent.key === 'Backspace' && !otp[index] && index > 0) {
            inputRefs.current[index - 1]?.focus();
        }
    };

    const handleVerify = async () => {
        const code = otp.join('');
        if (code.length !== 6) {
            Alert.alert('Error', 'Please enter the complete OTP');
            return;
        }

        setLoading(true);
        try {
            if (flow === 'login') {
                const response = await authApi.verifyLoginOTP({ identifier, code });
                if (response.success && response.data) {
                    await completeLogin(response.data);
                }
            } else {
                const response = await authApi.verifyOTP({ identifier, code });
                if (response.success && response.data) {
                    navigation.navigate('ResetPassword', { token: response.data.token, identifier });
                }
            }
        } catch (err: any) {
            Alert.alert('Error', err.message || 'Invalid OTP');
        } finally {
            setLoading(false);
        }
    };

    // Auto-verify once all 6 digits exist
    useEffect(() => {
        const code = otp.join('');
        if (!loading && code.length === 6 && !code.includes('')) {
            void handleVerify();
        }
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [otp.join('')]);

    const handleResend = async () => {
        setResending(true);
        try {
            if (flow === 'login') {
                await authApi.requestLoginOTP({ identifier });
            } else {
                await authApi.resetPasswordOTP({ identifier });
            }
            Alert.alert('Success', 'OTP sent successfully');
            setTimer(60);
        } catch (err: any) {
            Alert.alert('Error', err.message || 'Failed to resend OTP');
        } finally {
            setResending(false);
        }
    };

    return (
        <SafeAreaView
            style={[
                layoutStyles.flex1,
                styles.container,
                { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
            ]}
        >
            {/* Header */}
            <TouchableOpacity style={styles.backButton} onPress={() => navigation.goBack()}>
                <Text style={[styles.backText, { color: colors.primary }]}>← Back</Text>
            </TouchableOpacity>

            <View style={styles.content}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Enter OTP
                </Text>
                <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    We sent a 6-digit code to {identifier}
                </Text>

                {/* OTP Input */}
                <View style={styles.otpContainer}>
                    {otp.map((digit, index) => (
                        <TextInput
                            key={index}
                            ref={(ref) => (inputRefs.current[index] = ref)}
                            style={[
                                styles.otpInput,
                                {
                                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                                    borderColor: digit
                                        ? colors.primary
                                        : isDark
                                            ? colors.borderDark
                                            : colors.borderLight,
                                    color: isDark ? colors.textMainDark : colors.textMainLight,
                                },
                            ]}
                            value={digit}
                            onChangeText={(text) => handleChangeText(text, index)}
                            onKeyPress={(e) => handleKeyPress(e, index)}
                            keyboardType="number-pad"
                            maxLength={1}
                            selectTextOnFocus
                            autoComplete={index === 0 ? 'sms-otp' : 'off'}
                            textContentType={index === 0 ? 'oneTimeCode' : 'none'}
                        />
                    ))}
                </View>

                <TouchableOpacity onPress={handlePasteFromClipboard} style={styles.pasteButton}>
                    <Text style={[styles.pasteText, { color: colors.primary }]}>Paste code</Text>
                </TouchableOpacity>

                {/* Timer & Resend */}
                <View style={styles.resendContainer}>
                    {timer > 0 ? (
                        <Text style={[styles.timerText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Resend OTP in {timer}s
                        </Text>
                    ) : (
                        <TouchableOpacity onPress={handleResend} disabled={resending}>
                            <Text style={[styles.resendText, { color: colors.primary }]}>
                                {resending ? 'Sending...' : 'Resend OTP'}
                            </Text>
                        </TouchableOpacity>
                    )}
                </View>

                {/* Verify Button */}
                <Button
                    title="Verify OTP"
                    onPress={handleVerify}
                    loading={loading}
                    fullWidth
                    style={styles.verifyButton}
                />
            </View>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
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
        marginBottom: SPACING.xxl,
    },
    otpContainer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginBottom: SPACING.xl,
    },
    otpInput: {
        width: 48,
        height: 56,
        borderWidth: 2,
        borderRadius: BORDER_RADIUS.lg,
        fontSize: FONT_SIZES.xl,
        fontWeight: 'bold',
        textAlign: 'center',
    },
    resendContainer: {
        alignItems: 'center',
        marginBottom: SPACING.xl,
    },
    pasteButton: {
        alignItems: 'center',
        marginTop: -SPACING.md,
        marginBottom: SPACING.lg,
    },
    pasteText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '700',
        textDecorationLine: 'underline',
    },
    timerText: {
        fontSize: FONT_SIZES.sm,
    },
    resendText: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    verifyButton: {
        marginTop: SPACING.md,
    },
});
