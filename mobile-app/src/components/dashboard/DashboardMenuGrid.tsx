import React from 'react';
import { View, Text, StyleSheet, TouchableOpacity, useWindowDimensions } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import Icon from 'react-native-vector-icons/MaterialIcons';

export interface DashboardMenuItem {
    id: string;
    title: string;
    icon: string;
    color: string;
    onPress: () => void;
}

interface DashboardMenuGridProps {
    title: string;
    items: DashboardMenuItem[];
}

export const DashboardMenuGrid: React.FC<DashboardMenuGridProps> = ({ title, items }) => {
    const { isDark, colors } = useTheme();
    const { width: winW } = useWindowDimensions();
    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const horizontalPad = SPACING.xl * 2;
    const gap = SPACING.md;
    const tileW = Math.floor((winW - horizontalPad - gap * 2) / 3);

    return (
        <View style={styles.section}>
            <Text style={[styles.sectionTitle, { color: textMain }]}>{title}</Text>
            <View style={styles.grid}>
                {items.map((item) => (
                    <TouchableOpacity
                        key={item.id}
                        style={[
                            styles.tile,
                            { width: tileW },
                            {
                                backgroundColor: isDark ? colors.surfaceDark : BRAND.surface,
                                borderColor: isDark ? colors.borderDark : BRAND.border,
                            },
                        ]}
                        onPress={item.onPress}
                        activeOpacity={0.75}
                    >
                        <View style={[styles.iconWrap, { backgroundColor: item.color + '22' }]}>
                            <Icon name={item.icon} size={26} color={item.color} />
                        </View>
                        <Text style={[styles.tileTitle, { color: textMain }]} numberOfLines={2}>
                            {item.title}
                        </Text>
                    </TouchableOpacity>
                ))}
            </View>
        </View>
    );
};

const styles = StyleSheet.create({
    section: {
        marginBottom: SPACING.lg,
    },
    sectionTitle: {
        fontSize: FONT_SIZES.lg,
        fontWeight: '800',
        marginBottom: SPACING.md,
        letterSpacing: -0.2,
    },
    grid: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.md,
        justifyContent: 'flex-start',
    },
    tile: {
        paddingVertical: SPACING.md,
        paddingHorizontal: SPACING.sm,
        borderRadius: BORDER_RADIUS.xl,
        borderWidth: 1,
        alignItems: 'center',
        gap: SPACING.sm,
    },
    iconWrap: {
        width: 52,
        height: 52,
        borderRadius: BORDER_RADIUS.lg,
        alignItems: 'center',
        justifyContent: 'center',
    },
    tileTitle: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '700',
        textAlign: 'center',
        lineHeight: 16,
    },
});
