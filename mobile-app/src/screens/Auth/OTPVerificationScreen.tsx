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
import { useTheme } from '@contexts/ThemeContext';
import { Button } from '@components/common/Button';
import { authApi } from '@api/auth.api';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';

interface OTPVerificationScreenProps {
    navigation: any;
    route: any;
}

export const OTPVerificationScreen: React.FC<OTPVerificationScreenProps> = ({
    navigation,
    route,
}) => {
    const { isDark, colors } = useTheme();
    const { phone } = route.params;

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

    const handleChangeText = (text: string, index: number) => {
        if (text.length > 1) {
            // Handle paste
            const otpArray = text.slice(0, 6).split('');
            setOtp(otpArray.concat(Array(6 - otpArray.length).fill('')));
            inputRefs.current[5]?.focus();
            return;
        }

        const newOtp = [...otp];
        newOtp[index] = text;
        setOtp(newOtp);

        if (text && index < 5) {
            inputRefs.current[index + 1]?.focus();
        }
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
            const response = await authApi.verifyOTP({ phone, code });
            if (response.success && response.data) {
                navigation.navigate('ResetPassword', { token: response.data.token });
            }
        } catch (err: any) {
            Alert.alert('Error', err.message || 'Invalid OTP');
        } finally {
            setLoading(false);
        }
    };

    const handleResend = async () => {
        setResending(true);
        try {
            await authApi.resetPasswordOTP({ phone });
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
                    We sent a 6-digit code to {phone}
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
                        />
                    ))}
                </View>

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
