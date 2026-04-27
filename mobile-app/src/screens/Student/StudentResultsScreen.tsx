import React, { useCallback, useState } from 'react';
import { Text, View, StyleSheet, ScrollView, RefreshControl } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useTheme } from '@contexts/ThemeContext';
import { EmptyState } from '@components/common/EmptyState';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, RADIUS } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

/**
 * Exam results and report cards for the student. Connect to published results API when available.
 */
export const StudentResultsScreen: React.FC = () => {
    const { isDark, colors } = useTheme();
    const [refreshing, setRefreshing] = useState(false);
    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;
    const accent = colors.secondary ?? colors.primary;

    const onRefresh = useCallback(() => {
        setRefreshing(true);
        setTimeout(() => setRefreshing(false), 600);
    }, []);

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]} edges={['bottom']}>
            <ScrollView
                contentContainerStyle={styles.scroll}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={onRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
                showsVerticalScrollIndicator={false}
            >
                <View style={styles.header}>
                    <View style={[styles.badge, { backgroundColor: accent + '22' }]}>
                        <Icon name="grade" size={22} color={accent} />
                    </View>
                    <Text style={[styles.title, { color: textMain }]}>Results</Text>
                    <Text style={[styles.sub, { color: textSub }]}>
                        View published marks, exams, and report cards when your school releases them.
                    </Text>
                </View>

                <View
                    style={[
                        styles.hint,
                        { backgroundColor: isDark ? colors.surfaceDark : BRAND.surface, borderColor: isDark ? colors.borderDark : BRAND.border },
                    ]}
                >
                    <Icon name="verified" size={20} color={accent} style={{ marginRight: SPACING.sm }} />
                    <Text style={[styles.hintText, { color: textSub }]}>
                        Your teachers publish results through the school office. This screen will list terms and PDF or in-app report cards
                        as soon as they are available to you.
                    </Text>
                </View>

                <EmptyState
                    accent="neutral"
                    icon="receipt-long"
                    title="No results published yet"
                    message="There is nothing to show for this term. When reports are ready, you will also receive an announcement in News."
                />
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    scroll: { paddingBottom: SPACING.xxl, paddingHorizontal: SPACING.lg, paddingTop: SPACING.md },
    header: { marginBottom: SPACING.lg },
    badge: {
        width: 48,
        height: 48,
        borderRadius: RADIUS.button,
        alignItems: 'center',
        justifyContent: 'center',
        marginBottom: SPACING.sm,
    },
    title: { fontSize: FONT_SIZES.xxl, fontWeight: '800' },
    sub: { fontSize: FONT_SIZES.sm, marginTop: SPACING.xs, lineHeight: 20 },
    hint: {
        flexDirection: 'row',
        alignItems: 'flex-start',
        borderWidth: 1,
        borderRadius: RADIUS.card,
        padding: SPACING.md,
        marginBottom: SPACING.lg,
    },
    hintText: { flex: 1, fontSize: FONT_SIZES.sm, lineHeight: 20 },
});
