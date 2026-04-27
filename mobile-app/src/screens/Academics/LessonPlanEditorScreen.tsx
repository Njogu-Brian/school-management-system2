import React, { useMemo, useState } from 'react';
import { Alert, SafeAreaView, ScrollView, StyleSheet, Text, TextInput, TouchableOpacity, View } from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { academicsApi } from '@api/academics.api';
import { LessonPlan, TimetableSlot } from 'types/academics.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';

type Mode = 'create' | 'edit';

interface Props {
    navigation: any;
    route: {
        params:
            | { mode: 'create'; planned_date: string; slot: TimetableSlot }
            | { mode: 'edit'; plan: LessonPlan };
    };
}

function toLines(text: string): string[] {
    return text
        .split('\n')
        .map((s) => s.trim())
        .filter(Boolean);
}

function fromLines(lines?: string[] | null): string {
    return Array.isArray(lines) ? lines.filter(Boolean).join('\n') : '';
}

export const LessonPlanEditorScreen: React.FC<Props> = ({ navigation, route }) => {
    const { isDark, colors } = useTheme();
    const params = route.params as any;
    const mode: Mode = params.mode;

    const existingPlan: LessonPlan | null = mode === 'edit' ? (params.plan as LessonPlan) : null;
    const slot: TimetableSlot | null = mode === 'create' ? (params.slot as TimetableSlot) : null;
    const plannedDate = mode === 'create' ? (params.planned_date as string) : existingPlan?.date;

    const [saving, setSaving] = useState(false);
    const [title, setTitle] = useState(existingPlan?.topic ?? '');
    const [duration, setDuration] = useState(String(existingPlan?.duration_minutes ?? 40));
    const [objectives, setObjectives] = useState(fromLines(existingPlan?.objectives));
    const [activities, setActivities] = useState(fromLines(existingPlan?.activities));
    const [resources, setResources] = useState(fromLines(existingPlan?.resources));

    const bg = isDark ? colors.backgroundDark : BRAND.bg;
    const textMain = isDark ? colors.textMainDark : BRAND.text;
    const textSub = isDark ? colors.textSubDark : BRAND.muted;
    const surface = isDark ? colors.surfaceDark : BRAND.surface;
    const border = isDark ? colors.borderDark : BRAND.border;

    const headerTitle = mode === 'create' ? 'Create lesson plan' : 'Edit lesson plan';

    const contextLabel = useMemo(() => {
        if (mode === 'create' && slot) {
            return [slot.subject_name, slot.start_time + '-' + slot.end_time, plannedDate].filter(Boolean).join(' · ');
        }
        if (existingPlan) {
            return [existingPlan.class_name, existingPlan.subject_name, existingPlan.date].filter(Boolean).join(' · ');
        }
        return '';
    }, [mode, slot, existingPlan, plannedDate]);

    const onSave = async () => {
        if (!plannedDate) {
            Alert.alert('Lesson plan', 'Missing planned date.');
            return;
        }
        if (!title.trim()) {
            Alert.alert('Lesson plan', 'Topic/title is required.');
            return;
        }

        const durationMinutes = Math.max(1, Math.min(480, parseInt(duration || '40', 10) || 40));

        setSaving(true);
        try {
            if (mode === 'create') {
                const res = await academicsApi.createLessonPlan({
                    timetable_id: slot?.id ?? null,
                    planned_date: plannedDate,
                    title: title.trim(),
                    duration_minutes: durationMinutes,
                    learning_objectives: toLines(objectives),
                    activities: toLines(activities),
                    learning_resources: toLines(resources),
                });
                if (res.success && res.data) {
                    navigation.replace('LessonPlanDetail', { planId: (res.data as any).id });
                } else {
                    Alert.alert('Lesson plan', res.message || 'Could not create lesson plan.');
                }
            } else if (existingPlan) {
                const res = await academicsApi.updateLessonPlan(existingPlan.id, {
                    timetable_id: existingPlan.timetable_id ?? null,
                    planned_date: plannedDate,
                    title: title.trim(),
                    duration_minutes: durationMinutes,
                    learning_objectives: toLines(objectives),
                    activities: toLines(activities),
                    learning_resources: toLines(resources),
                });
                if (res.success && res.data) {
                    navigation.replace('LessonPlanDetail', { planId: existingPlan.id });
                } else {
                    Alert.alert('Lesson plan', res.message || 'Could not update lesson plan.');
                }
            }
        } catch (e: any) {
            Alert.alert('Lesson plan', e?.message || 'Could not save lesson plan.');
        } finally {
            setSaving(false);
        }
    };

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.top}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} style={styles.back}>
                    <Icon name="arrow-back" size={24} color={colors.primary} />
                </TouchableOpacity>
                <Text style={[styles.topTitle, { color: textMain }]}>{headerTitle}</Text>
                <View style={{ width: 40 }} />
            </View>

            <ScrollView contentContainerStyle={styles.scroll}>
                {contextLabel ? <Text style={[styles.context, { color: textSub }]}>{contextLabel}</Text> : null}

                <Text style={[styles.label, { color: textMain }]}>Topic</Text>
                <TextInput
                    value={title}
                    onChangeText={setTitle}
                    placeholder="e.g. Fractions: equivalent fractions"
                    placeholderTextColor={textSub}
                    style={[styles.input, { backgroundColor: surface, borderColor: border, color: textMain }]}
                />

                <Text style={[styles.label, { color: textMain }]}>Duration (minutes)</Text>
                <TextInput
                    value={duration}
                    onChangeText={setDuration}
                    keyboardType="number-pad"
                    placeholder="40"
                    placeholderTextColor={textSub}
                    style={[styles.input, { backgroundColor: surface, borderColor: border, color: textMain }]}
                />

                <Text style={[styles.label, { color: textMain }]}>Objectives (one per line)</Text>
                <TextInput
                    value={objectives}
                    onChangeText={setObjectives}
                    multiline
                    placeholder="Objective 1\nObjective 2"
                    placeholderTextColor={textSub}
                    style={[styles.textarea, { backgroundColor: surface, borderColor: border, color: textMain }]}
                />

                <Text style={[styles.label, { color: textMain }]}>Activities (one per line)</Text>
                <TextInput
                    value={activities}
                    onChangeText={setActivities}
                    multiline
                    placeholder="Activity 1\nActivity 2"
                    placeholderTextColor={textSub}
                    style={[styles.textarea, { backgroundColor: surface, borderColor: border, color: textMain }]}
                />

                <Text style={[styles.label, { color: textMain }]}>Resources (one per line)</Text>
                <TextInput
                    value={resources}
                    onChangeText={setResources}
                    multiline
                    placeholder="Textbook pg 12\nFlash cards"
                    placeholderTextColor={textSub}
                    style={[styles.textarea, { backgroundColor: surface, borderColor: border, color: textMain }]}
                />

                <TouchableOpacity
                    onPress={onSave}
                    disabled={saving}
                    style={[styles.saveBtn, { backgroundColor: saving ? colors.borderLight : colors.primary }]}
                >
                    <Text style={styles.saveText}>{saving ? 'Saving…' : 'Save draft'}</Text>
                </TouchableOpacity>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    top: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    back: { padding: SPACING.sm },
    topTitle: { fontSize: FONT_SIZES.lg, fontWeight: '800' },
    scroll: { padding: SPACING.xl, paddingBottom: SPACING.xxl },
    context: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.md },
    label: { fontSize: FONT_SIZES.sm, fontWeight: '800', marginTop: SPACING.md, marginBottom: SPACING.xs },
    input: { borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, paddingVertical: 10, fontSize: FONT_SIZES.md },
    textarea: { borderWidth: 1, borderRadius: 12, paddingHorizontal: 12, paddingVertical: 10, minHeight: 110, fontSize: FONT_SIZES.sm, textAlignVertical: 'top' },
    saveBtn: { marginTop: SPACING.xl, paddingVertical: 14, borderRadius: 14, alignItems: 'center' },
    saveText: { color: '#fff', fontWeight: '900', fontSize: FONT_SIZES.md },
});

