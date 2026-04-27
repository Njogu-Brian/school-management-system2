import React, { useEffect, useMemo, useState } from 'react';
import { Alert, SafeAreaView, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useAuth } from '@contexts/AuthContext';
import { useTheme } from '@contexts/ThemeContext';
import { academicsApi } from '@api/academics.api';
import { Timetable, TimetableSlot } from 'types/academics.types';
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';

interface Props {
    navigation: any;
}

const DAYS = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'] as const;

function getDayName(d: Date): string {
    return DAYS[d.getDay()] as string;
}

export const LessonPlanCreateFromTimetableScreen: React.FC<Props> = ({ navigation }) => {
    const { user } = useAuth();
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(true);
    const [timetable, setTimetable] = useState<Timetable | null>(null);

    const today = useMemo(() => new Date(), []);
    const tomorrow = useMemo(() => new Date(Date.now() + 24 * 60 * 60 * 1000), []);
    const todayDay = getDayName(today);
    const tomorrowDay = getDayName(tomorrow);
    const todayStr = today.toISOString().slice(0, 10);
    const tomorrowStr = tomorrow.toISOString().slice(0, 10);

    useEffect(() => {
        const load = async () => {
            try {
                setLoading(true);
                const staffPk = user?.staff_id ?? user?.teacher_id;
                if (!staffPk) {
                    Alert.alert('Lesson plans', 'Your account is not linked to a staff profile yet.');
                    return;
                }
                // Mobile currently uses a fixed term id in TimetableScreen. Keep consistent.
                const termId = 1;
                const res = await academicsApi.getTeacherTimetable(staffPk, termId);
                if (res.success && res.data) {
                    setTimetable(res.data);
                } else {
                    Alert.alert('Lesson plans', res.message || 'Could not load timetable.');
                }
            } catch (e: any) {
                Alert.alert('Lesson plans', e?.message || 'Could not load timetable.');
            } finally {
                setLoading(false);
            }
        };

        void load();
    }, [user]);

    const slotsByDay = useMemo(() => {
        const slots = Array.isArray(timetable?.slots) ? timetable!.slots : [];
        const group: Record<string, TimetableSlot[]> = {};
        for (const s of slots) {
            group[s.day] = group[s.day] || [];
            group[s.day].push(s);
        }
        for (const k of Object.keys(group)) {
            group[k] = group[k].sort((a, b) => a.start_time.localeCompare(b.start_time));
        }
        return group;
    }, [timetable]);

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;

    if (loading) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
                <LoadingState message="Loading timetable…" />
            </SafeAreaView>
        );
    }

    const renderSlot = (slot: TimetableSlot, plannedDate: string) => (
        <Card key={`${plannedDate}-${slot.id}`} style={styles.card}>
            <View style={styles.row}>
                <View style={{ flex: 1 }}>
                    <Text style={[styles.subject, { color: textMain }]}>{slot.subject_name}</Text>
                    <Text style={[styles.meta, { color: textSub }]}>
                        {slot.start_time} - {slot.end_time}
                        {slot.room ? ` · ${slot.room}` : ''}
                    </Text>
                </View>
                <TouchableOpacity
                    onPress={() =>
                        navigation.navigate('LessonPlanEditor', {
                            mode: 'create',
                            planned_date: plannedDate,
                            slot,
                        })
                    }
                    style={[styles.cta, { backgroundColor: colors.primary }]}
                >
                    <Text style={styles.ctaText}>Create</Text>
                </TouchableOpacity>
            </View>
        </Card>
    );

    const todaySlots = slotsByDay[todayDay] || [];
    const tomorrowSlots = slotsByDay[tomorrowDay] || [];

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.top}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} style={styles.back}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.topTitle, { color: textMain }]}>New lesson plan</Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView contentContainerStyle={styles.scroll}>
                <Text style={[styles.section, { color: textMain }]}>Today ({todayStr})</Text>
                {todaySlots.length ? todaySlots.map((s) => renderSlot(s, todayStr)) : <Text style={{ color: textSub }}>No slots today.</Text>}

                <View style={{ height: SPACING.lg }} />

                <Text style={[styles.section, { color: textMain }]}>Tomorrow ({tomorrowStr})</Text>
                {tomorrowSlots.length
                    ? tomorrowSlots.map((s) => renderSlot(s, tomorrowStr))
                    : <Text style={{ color: textSub }}>No slots tomorrow.</Text>}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    top: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    back: { padding: SPACING.sm },
    topTitle: { fontSize: FONT_SIZES.lg, fontWeight: '800' },
    scroll: { padding: SPACING.xl, paddingBottom: SPACING.xxl },
    section: { fontSize: FONT_SIZES.md, fontWeight: '800', marginBottom: SPACING.sm },
    card: { marginBottom: SPACING.sm },
    row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', gap: SPACING.sm },
    subject: { fontSize: FONT_SIZES.md, fontWeight: '800' },
    meta: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    cta: { paddingVertical: 10, paddingHorizontal: 14, borderRadius: 12 },
    ctaText: { color: '#fff', fontWeight: '800' },
});

