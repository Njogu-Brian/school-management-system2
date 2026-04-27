import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, SafeAreaView, TouchableOpacity, RefreshControl } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { academicsApi } from '@api/academics.api';
import { LessonPlan } from 'types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import { LoadingState } from '@components/common/EmptyState';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params: { planId: number } };
}

function bulletList(title: string, items: string[], textMain: string, textSub: string) {
    if (!items?.length) return null;
    return (
        <View style={{ marginBottom: SPACING.lg }}>
            <Text style={[styles.sectionTitle, { color: textMain }]}>{title}</Text>
            {items.map((line, i) => (
                <Text key={i} style={[styles.bullet, { color: textSub }]}>
                    • {line}
                </Text>
            ))}
        </View>
    );
}

export const LessonPlanDetailScreen: React.FC<Props> = ({ navigation, route }) => {
    const planId = route.params?.planId;
    const { isDark, colors } = useTheme();
    const [plan, setPlan] = useState<LessonPlan | null>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const load = useCallback(async () => {
        if (!planId) return;
        setError(null);
        try {
            const res = await academicsApi.getLessonPlan(planId);
            if (res.success && res.data) {
                setPlan(res.data);
            } else {
                setPlan(null);
                setError(res.message || 'Could not load lesson plan.');
            }
        } catch (e: any) {
            setPlan(null);
            setError(e?.message || 'Could not load lesson plan.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [planId]);

    useEffect(() => {
        void load();
    }, [load]);

    if (loading) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
                <LoadingState message="Loading lesson plan…" />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.top}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} style={styles.back}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.topTitle, { color: textMain }]}>Lesson plan</Text>
                <View style={{ width: 40 }} />
            </View>
            <ScrollView
                contentContainerStyle={styles.scroll}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); void load(); }} colors={[colors.primary]} />}
            >
                {error ? (
                    <LoadErrorBanner
                        message={error}
                        onRetry={() => {
                            setLoading(true);
                            void load();
                        }}
                        surfaceColor={isDark ? colors.surfaceDark : BRAND.surface}
                        borderColor={isDark ? colors.borderDark : BRAND.border}
                        textColor={textMain}
                        subColor={textSub}
                        accentColor={colors.primary}
                    />
                ) : null}
                {plan ? (
                    <>
                        <Text style={[styles.title, { color: textMain }]}>{plan.topic}</Text>
                        <Text style={[styles.meta, { color: textSub }]}>
                            {[plan.class_name, plan.subject_name, plan.teacher_name].filter(Boolean).join(' · ')}
                        </Text>
                        {plan.date ? (
                            <Text style={[styles.row, { color: textSub }]}>
                                {formatters.formatDate(plan.date)}
                                {plan.duration_minutes ? ` · ${plan.duration_minutes} min` : ''}
                            </Text>
                        ) : null}
                        <Text style={[styles.row, { color: textSub }]}>Status: {formatters.capitalize(plan.status)}</Text>
                        {bulletList('Objectives', plan.objectives, textMain, textSub)}
                        {bulletList('Activities', plan.activities, textMain, textSub)}
                        {bulletList('Resources', plan.resources, textMain, textSub)}
                        {bulletList('Assessment', plan.assessment_methods, textMain, textSub)}
                        <Text style={[styles.hint, { color: textSub }]}>
                            Create and approve lesson plans in the web portal.
                        </Text>
                    </>
                ) : !error ? (
                    <Text style={{ color: textSub }}>No lesson plan.</Text>
                ) : null}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    top: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    back: { padding: SPACING.sm },
    topTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    scroll: { padding: SPACING.xl, paddingBottom: SPACING.xxl },
    title: { fontSize: FONT_SIZES.xl, fontWeight: '800', marginBottom: SPACING.sm },
    meta: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.md },
    row: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.xs },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.sm },
    bullet: { fontSize: FONT_SIZES.sm, lineHeight: 22, marginBottom: 4 },
    hint: { fontSize: FONT_SIZES.xs, marginTop: SPACING.lg, lineHeight: 18 },
});
