import React, { useEffect } from 'react';
import { View, StyleSheet, ScrollView } from 'react-native';
import Animated, {
    useAnimatedStyle,
    useSharedValue,
    withRepeat,
    withSequence,
    withTiming,
} from 'react-native-reanimated';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, BORDER_RADIUS } from '@constants/theme';

function PulseBar({
    width,
    height,
    radius = BORDER_RADIUS.md,
}: {
    width: number | `${number}%`;
    height: number;
    radius?: number;
}) {
    const { isDark, colors } = useTheme();
    const o = useSharedValue(0.45);

    useEffect(() => {
        o.value = withRepeat(withSequence(withTiming(1, { duration: 750 }), withTiming(0.45, { duration: 750 })), -1);
    }, [o]);

    const style = useAnimatedStyle(() => ({
        opacity: o.value,
    }));

    const baseColor = isDark ? colors.borderDark : colors.borderLight;

    return (
        <Animated.View
            style={[
                {
                    width,
                    height,
                    borderRadius: radius,
                    backgroundColor: baseColor,
                },
                style,
            ]}
        />
    );
}

type Layout = 'default' | 'marks';

interface ListLoadingSkeletonProps {
    layout?: Layout;
}

/**
 * Stitch-style loading placeholders (pulse bars). Theme-aware for light/dark + portal colors.
 */
export const ListLoadingSkeleton: React.FC<ListLoadingSkeletonProps> = ({ layout = 'default' }) => {
    const { isDark, colors } = useTheme();
    const sc = {
        card: isDark ? colors.surfaceDark : colors.surfaceLight,
        border: isDark ? colors.borderDark : colors.borderLight,
    };

    if (layout === 'marks') {
        return (
            <ScrollView contentContainerStyle={styles.marksWrap} showsVerticalScrollIndicator={false}>
                <View style={styles.marksHeaderRow}>
                    <PulseBar width="55%" height={22} />
                    <PulseBar width={120} height={28} radius={BORDER_RADIUS.full} />
                </View>
                <View style={[styles.searchShell, { backgroundColor: sc.card, borderColor: sc.border }]}>
                    <PulseBar width={20} height={20} radius={10} />
                    <PulseBar width="40%" height={14} />
                </View>
                <View style={styles.chipRow}>
                    <PulseBar width={72} height={32} radius={BORDER_RADIUS.full} />
                    <PulseBar width={88} height={32} radius={BORDER_RADIUS.full} />
                    <PulseBar width={64} height={32} radius={BORDER_RADIUS.full} />
                </View>
                {[0, 1, 2, 3, 4].map((i) => (
                    <View key={i} style={[styles.matrixRow, { borderColor: sc.border, backgroundColor: sc.card }]}>
                        <View style={styles.matrixRowTop}>
                            <PulseBar width={36} height={36} radius={18} />
                            <View style={{ flex: 1, gap: SPACING.xs }}>
                                <PulseBar width="70%" height={16} />
                                <PulseBar width="45%" height={12} />
                            </View>
                        </View>
                        <View style={styles.matrixCells}>
                            <PulseBar width={56} height={40} />
                            <PulseBar width={56} height={40} />
                            <PulseBar width={56} height={40} />
                        </View>
                    </View>
                ))}
            </ScrollView>
        );
    }

    return (
        <View style={styles.defaultWrap}>
            <View style={styles.headerSkel}>
                <PulseBar width={40} height={40} radius={20} />
                <PulseBar width={120} height={22} />
                <PulseBar width={32} height={32} radius={16} />
            </View>
            <PulseBar width="50%" height={26} />
            <View style={[styles.searchShell, { backgroundColor: sc.card, borderColor: sc.border }]}>
                <PulseBar width={20} height={20} radius={10} />
                <PulseBar width="35%" height={14} />
            </View>
            <View style={styles.chipRow}>
                <PulseBar width={64} height={28} radius={14} />
                <PulseBar width={80} height={28} radius={14} />
                <PulseBar width={72} height={28} radius={14} />
            </View>
            {[0, 1, 2].map((i) => (
                <View key={i} style={[styles.cardSkel, { backgroundColor: sc.card, borderColor: sc.border }]}>
                    <View style={{ flexDirection: 'row', gap: SPACING.md }}>
                        <PulseBar width={48} height={48} radius={24} />
                        <View style={{ flex: 1, gap: SPACING.sm }}>
                            <PulseBar width="75%" height={18} />
                            <PulseBar width="50%" height={14} />
                            <PulseBar width="100%" height={12} />
                        </View>
                    </View>
                </View>
            ))}
        </View>
    );
};

const styles = StyleSheet.create({
    defaultWrap: {
        gap: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    headerSkel: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
    },
    searchShell: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
    },
    chipRow: {
        flexDirection: 'row',
        gap: SPACING.sm,
        flexWrap: 'wrap',
    },
    cardSkel: {
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
    },
    marksWrap: {
        gap: SPACING.md,
        paddingBottom: SPACING.xxl,
    },
    marksHeaderRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    matrixRow: {
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        padding: SPACING.md,
        gap: SPACING.md,
    },
    matrixRowTop: {
        flexDirection: 'row',
        gap: SPACING.md,
        alignItems: 'center',
    },
    matrixCells: {
        flexDirection: 'row',
        gap: SPACING.sm,
    },
});
