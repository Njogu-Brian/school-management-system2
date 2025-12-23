import React from 'react';
import {
    View,
    Text,
    StyleSheet,
    TouchableOpacity,
    ViewStyle,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';

interface CardProps {
    children: React.ReactNode;
    onPress?: () => void;
    style?: ViewStyle;
}

export const Card: React.FC<CardProps> = ({ children, onPress, style }) => {
    const { isDark, colors } = useTheme();

    const content = (
        <View
            style={[
                styles.card,
                {
                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                    borderColor: isDark ? colors.borderDark : colors.borderLight,
                },
                style,
            ]}
        >
            {children}
        </View>
    );

    if (onPress) {
        return (
            <TouchableOpacity onPress={onPress} activeOpacity={0.7}>
                {content}
            </TouchableOpacity>
        );
    }

    return content;
};

const styles = StyleSheet.create({
    card: {
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        marginBottom: SPACING.md,
    },
});
