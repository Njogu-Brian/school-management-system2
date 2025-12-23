import React from 'react';
import {
    View,
    Text,
    StyleSheet,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';

interface StatusBadgeProps {
    status: string;
    variant?: 'default' | 'success' | 'warning' | 'error' | 'info';
}

export const StatusBadge: React.FC<StatusBadgeProps> = ({ status, variant }) => {
    const { isDark, colors } = useTheme();

    const getVariantColor = () => {
        if (variant) {
            switch (variant) {
                case 'success':
                    return colors.success;
                case 'warning':
                    return colors.warning;
                case 'error':
                    return colors.error;
                case 'info':
                    return colors.info;
                default:
                    return colors.primary;
            }
        }

        // Auto-detect based on status text
        const statusLower = status.toLowerCase();
        if (statusLower === 'active' || statusLower === 'present' || statusLower === 'paid') {
            return colors.success;
        }
        if (statusLower === 'pending' || statusLower === 'late') {
            return colors.warning;
        }
        if (statusLower === 'inactive' || statusLower === 'absent' || statusLower === 'unpaid' || statusLower === 'archived') {
            return colors.error;
        }
        return colors.primary;
    };

    const color = getVariantColor();

    return (
        <View
            style={[
                styles.badge,
                {
                    backgroundColor: color + '20',
                    borderColor: color + '40',
                },
            ]}
        >
            <Text style={[styles.text, { color }]}>{status}</Text>
        </View>
    );
};

const styles = StyleSheet.create({
    badge: {
        paddingHorizontal: SPACING.sm,
        paddingVertical: 4,
        borderRadius: BORDER_RADIUS.md,
        borderWidth: 1,
    },
    text: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
        textTransform: 'capitalize',
    },
});
