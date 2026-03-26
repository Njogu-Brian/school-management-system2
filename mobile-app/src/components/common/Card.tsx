import React from 'react';
import {
    View,
    StyleSheet,
    TouchableOpacity,
    ViewStyle,
    Platform,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING } from '@constants/theme';
import { BRAND, RADIUS, CARD_STYLE } from '@constants/designTokens';
import { Palette } from '@styles/palette';

interface CardProps {
    children: React.ReactNode;
    onPress?: () => void;
    style?: ViewStyle;
    elevated?: boolean;
}

export const Card: React.FC<CardProps> = ({ children, onPress, style, elevated = true }) => {
    const { isDark, colors } = useTheme();

    const content = (
        <View
            style={[
                styles.card,
                {
                    backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight,
                    borderColor: isDark ? colors.borderDark : BRAND.border,
                    borderRadius: RADIUS.card,
                },
                elevated &&
                    (Platform.OS === 'ios'
                        ? {
                              shadowColor: Palette.shadowIOS,
                              shadowOffset: CARD_STYLE.shadowOffset,
                              shadowOpacity: CARD_STYLE.shadowOpacity,
                              shadowRadius: CARD_STYLE.shadowRadius,
                          }
                        : { elevation: CARD_STYLE.elevation }),
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
        borderWidth: CARD_STYLE.borderWidth,
        marginBottom: SPACING.md,
    },
});
