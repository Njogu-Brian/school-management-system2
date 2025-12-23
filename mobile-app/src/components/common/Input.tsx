import React, { useState } from 'react';
import {
    TextInput as RNTextInput,
    View,
    Text,
    StyleSheet,
    TextInputProps,
    TouchableOpacity,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { BORDER_RADIUS, FONT_SIZES, SPACING } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface InputProps extends TextInputProps {
    label?: string;
    error?: string;
    icon?: string;
    rightIcon?: string;
    onRightIconPress?: () => void;
    containerStyle?: any;
}

export const Input: React.FC<InputProps> = ({
    label,
    error,
    icon,
    rightIcon,
    onRightIconPress,
    containerStyle,
    style,
    ...props
}) => {
    const { isDark, colors } = useTheme();
    const [isFocused, setIsFocused] = useState(false);

    return (
        <View style={[styles.container, containerStyle]}>
            {label && (
                <Text
                    style={[
                        styles.label,
                        {
                            color: isDark ? colors.textSubDark : colors.textSubLight,
                        },
                    ]}
                >
                    {label}
                </Text>
            )}
            <View
                style={[
                    styles.inputContainer,
                    {
                        backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                        borderColor: error
                            ? colors.error
                            : isFocused
                                ? colors.primary
                                : isDark
                                    ? colors.borderDark
                                    : colors.borderLight,
                    },
                ]}
            >
                {icon && (
                    <Icon
                        name={icon}
                        size={20}
                        color={isDark ? colors.textSubDark : colors.textSubLight}
                        style={styles.icon}
                    />
                )}
                <RNTextInput
                    style={[
                        styles.input,
                        {
                            color: isDark ? colors.textMainDark : colors.textMainLight,
                        },
                        style,
                    ]}
                    placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                    onFocus={() => setIsFocused(true)}
                    onBlur={() => setIsFocused(false)}
                    {...props}
                />
                {rightIcon && (
                    <TouchableOpacity onPress={onRightIconPress} style={styles.rightIcon}>
                        <Icon
                            name={rightIcon}
                            size={20}
                            color={isDark ? colors.textSubDark : colors.textSubLight}
                        />
                    </TouchableOpacity>
                )}
            </View>
            {error && <Text style={[styles.error, { color: colors.error }]}>{error}</Text>}
        </View>
    );
};

const styles = StyleSheet.create({
    container: {
        marginBottom: SPACING.md,
    },
    label: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '500',
        marginBottom: SPACING.xs,
    },
    inputContainer: {
        flexDirection: 'row',
        alignItems: 'center',
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.lg,
        paddingHorizontal: SPACING.md,
        height: 48,
    },
    icon: {
        marginRight: SPACING.sm,
    },
    input: {
        flex: 1,
        fontSize: FONT_SIZES.md,
        paddingVertical: SPACING.sm,
    },
    rightIcon: {
        padding: SPACING.xs,
    },
    error: {
        fontSize: FONT_SIZES.xs,
        marginTop: SPACING.xs,
    },
});
