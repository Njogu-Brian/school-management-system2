import React from 'react';
import {
    TouchableOpacity,
    Text,
    StyleSheet,
    ActivityIndicator,
    TouchableOpacityProps,
    ViewStyle,
    TextStyle,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { BORDER_RADIUS, FONT_SIZES, SPACING } from '@constants/theme';

interface ButtonProps extends TouchableOpacityProps {
    title: string;
    loading?: boolean;
    variant?: 'primary' | 'secondary' | 'outline' | 'text';
    size?: 'small' | 'medium' | 'large';
    fullWidth?: boolean;
    icon?: React.ReactNode;
}

export const Button: React.FC<ButtonProps> = ({
    title,
    loading = false,
    variant = 'primary',
    size = 'medium',
    fullWidth = false,
    icon,
    disabled,
    style,
    ...props
}) => {
    const { isDark, colors } = useTheme();

    const getButtonStyle = (): ViewStyle => {
        const baseStyle: ViewStyle = {
            flexDirection: 'row',
            alignItems: 'center',
            justifyContent: 'center',
            borderRadius: BORDER_RADIUS.lg,
            paddingHorizontal: size === 'small' ? SPACING.md : size === 'large' ? SPACING.xl : SPACING.lg,
            paddingVertical: size === 'small' ? SPACING.sm : size === 'large' ? SPACING.md : SPACING.sm + 4,
            opacity: disabled || loading ? 0.6 : 1,
        };

        if (fullWidth) {
            baseStyle.width = '100%';
        }

        switch (variant) {
            case 'primary':
                return {
                    ...baseStyle,
                    backgroundColor: colors.primary,
                };
            case 'secondary':
                return {
                    ...baseStyle,
                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                    borderWidth: 1,
                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                };
            case 'outline':
                return {
                    ...baseStyle,
                    backgroundColor: 'transparent',
                    borderWidth: 2,
                    borderColor: colors.primary,
                };
            case 'text':
                return {
                    ...baseStyle,
                    backgroundColor: 'transparent',
                };
            default:
                return baseStyle;
        }
    };

    const getTextStyle = (): TextStyle => {
        const baseStyle: TextStyle = {
            fontSize: size === 'small' ? FONT_SIZES.sm : size === 'large' ? FONT_SIZES.lg : FONT_SIZES.md,
            fontWeight: '600',
        };

        switch (variant) {
            case 'primary':
                return {
                    ...baseStyle,
                    color: '#ffffff',
                };
            case 'secondary':
                return {
                    ...baseStyle,
                    color: isDark ? colors.textMainDark : colors.textMainLight,
                };
            case 'outline':
            case 'text':
                return {
                    ...baseStyle,
                    color: colors.primary,
                };
            default:
                return baseStyle;
        }
    };

    return (
        <TouchableOpacity
            style={[getButtonStyle(), style]}
            disabled={disabled || loading}
            {...props}
        >
            {loading ? (
                <ActivityIndicator
                    color={variant === 'primary' ? '#ffffff' : colors.primary}
                    size="small"
                />
            ) : (
                <>
                    {icon && <>{icon}</>}
                    <Text style={[getTextStyle(), icon && { marginLeft: SPACING.sm }]}>{title}</Text>
                </>
            )}
        </TouchableOpacity>
    );
};
