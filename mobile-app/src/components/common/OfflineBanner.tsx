import React from 'react';
import { View, Text, StyleSheet } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES } from '@constants/theme';

export const OfflineBanner: React.FC<{ visible: boolean }> = ({ visible }) => {
    const { isDark, colors } = useTheme();
    if (!visible) {
        return null;
    }
    return (
        <View style={[styles.bar, { backgroundColor: isDark ? '#78350f' : '#fef3c7' }]}>
            <Text style={[styles.text, { color: isDark ? '#fde68a' : '#92400e' }]}>
                You are offline. Some actions will sync when you reconnect.
            </Text>
        </View>
    );
};

const styles = StyleSheet.create({
    bar: {
        paddingVertical: SPACING.sm,
        paddingHorizontal: SPACING.md,
    },
    text: {
        fontSize: FONT_SIZES.xs,
        textAlign: 'center',
        fontWeight: '600',
    },
});
