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

interface ResetPasswordScreenProps {
    navigation: any;
    route: any;
}

export const ResetPasswordScreen: React.FC<ResetPasswordScreenProps> = ({
    navigation,
    route,
}) => {
    const { isDark, colors } = useTheme();
    const { token } = route.params;

    const [password, setPassword] = useState('');
    const [confirmPassword, setConfirmPassword] = useState('');
    const [showPassword, setShowPassword] = useState(false);
    const [showConfirmPassword, setShowConfirmPassword] = useState(false);
    const [loading, setLoading] = useState(false);
    const [errors, setErrors] = useState<{ password?: string; confirmPassword?: string }>({});

    const validate = (): boolean => {
        const newErrors: { password?: string; confirmPassword?: string } = {};

        const passwordError = validators.password(password);
        if (passwordError) newErrors.password = passwordError;

        const confirmError = validators.confirmPassword(confirmPassword, password);
        if (confirmError) newErrors.confirmPassword = confirmError;

        setErrors(newErrors);
        return Object.keys(newErrors).length === 0;
    };

    const handleSubmit = async () => {
        if (!validate()) return;

        setLoading(true);
        try {
            const response = await authApi.resetPassword({
                token,
                password,
                password_confirmation: confirmPassword,
            });
            Alert.alert('Success', response.message || 'Password reset successfully', [
                { text: 'OK', onPress: () => navigation.navigate('Login') },
            ]);
        } catch (err: any) {
            Alert.alert('Error', err.message || 'Failed to reset password');
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
                    <View style={styles.content}>
                        <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            New Password
                        </Text>
                        <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Please enter your new password
                        </Text>

                        <View style={styles.form}>
                            <Input
                                label="New Password"
                                placeholder="Enter new password"
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

                            <Input
                                label="Confirm Password"
                                placeholder="Re-enter new password"
                                value={confirmPassword}
                                onChangeText={(text) => {
                                    setConfirmPassword(text);
                                    if (errors.confirmPassword) setErrors({ ...errors, confirmPassword: undefined });
                                }}
                                error={errors.confirmPassword}
                                secureTextEntry={!showConfirmPassword}
                                autoCapitalize="none"
                                icon="lock"
                                rightIcon={showConfirmPassword ? 'visibility-off' : 'visibility'}
                                onRightIconPress={() => setShowConfirmPassword(!showConfirmPassword)}
                            />

                            <Button
                                title="Reset Password"
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
    content: {
        flex: 1,
        paddingHorizontal: SPACING.xl,
        justifyContent: 'center',
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
    form: {
        width: '100%',
    },
    submitButton: {
        marginTop: SPACING.md,
    },
});
