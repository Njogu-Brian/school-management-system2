import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, StyleSheet, ScrollView, SafeAreaView, TouchableOpacity, RefreshControl } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { isTeacherRole } from '@utils/roleUtils';
import { academicsApi } from '@api/academics.api';
import { Exam } from 'types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import { Button } from '@components/common/Button';
import { LoadingState } from '@components/common/EmptyState';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void; navigate: (name: string, params?: object) => void };
    route: { params: { examId: number } };
}

export const ExamDetailScreen: React.FC<Props> = ({ navigation, route }) => {
    const examId = route.params?.examId;
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [exam, setExam] = useState<Exam | null>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    const load = useCallback(async () => {
        if (!examId) return;
        setError(null);
        try {
            const res = await academicsApi.getExam(examId);
            if (res.success && res.data) {
                setExam(res.data);
            } else {
                setExam(null);
                setError(res.message || 'Could not load exam.');
            }
        } catch (e: any) {
            setExam(null);
            setError(e?.message || 'Could not load exam.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [examId]);

    useEffect(() => {
        void load();
    }, [load]);

    const canMark =
        (user?.role && isTeacherRole(user.role)) ||
        user?.role === 'admin' ||
        user?.role === 'super_admin' ||
        user?.role === 'secretary';

    if (loading) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
                <LoadingState message="Loading exam…" />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.top}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} style={styles.back}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.topTitle, { color: textMain }]}>Exam details</Text>
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
                {exam ? (
                    <>
                        <Text style={[styles.title, { color: textMain }]}>{exam.name}</Text>
                        <Text style={[styles.meta, { color: textSub }]}>{exam.exam_type_name || 'Exam'}</Text>
                        {exam.classroom_name ? (
                            <Text style={[styles.row, { color: textSub }]}>Class: {exam.classroom_name}</Text>
                        ) : null}
                        {exam.subject_name ? (
                            <Text style={[styles.row, { color: textSub }]}>Subject: {exam.subject_name}</Text>
                        ) : null}
                        <Text style={[styles.row, { color: textSub }]}>
                            {formatters.formatDate(exam.start_date)} — {formatters.formatDate(exam.end_date)}
                        </Text>
                        <Text style={[styles.row, { color: textSub }]}>Status: {formatters.capitalize(exam.status)}</Text>
                        {exam.total_marks != null ? (
                            <Text style={[styles.row, { color: textSub }]}>Max marks: {exam.total_marks}</Text>
                        ) : null}
                        {canMark ? (
                            <Button
                                title="Marks & grading"
                                onPress={() => navigation.navigate('ExamMarksSetup', { examId: exam.id })}
                                fullWidth
                                style={{ marginTop: SPACING.lg }}
                            />
                        ) : null}
                        <Text style={[styles.hint, { color: textSub }]}>
                            Full scheduling and edits stay in the web portal.
                        </Text>
                    </>
                ) : !error ? (
                    <Text style={{ color: textSub }}>No exam data.</Text>
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
    hint: { fontSize: FONT_SIZES.xs, marginTop: SPACING.xl, lineHeight: 18 },
});
