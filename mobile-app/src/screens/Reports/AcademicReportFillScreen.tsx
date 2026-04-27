import React, { useEffect, useMemo, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    TextInput,
    TouchableOpacity,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { layoutStyles } from '@styles/common';
import { academicReportsApi } from '@api/academicReports.api';
import type { AcademicReportTemplate, AcademicReportQuestion, AcademicReportAnswerInput } from '@types/academicReports.types';
import { SPACING, BORDER_RADIUS, FONT_SIZES } from '@constants/theme';
import { Button } from '@components/common/Button';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';
import * as DocumentPicker from 'expo-document-picker';
import { useNavigation, useRoute } from '@react-navigation/native';

type RouteParams = { templateId: number };

function coerceOptions(question: AcademicReportQuestion): { label: string; value: string }[] {
    const raw = question.options;
    if (!raw) return [];
    const opts = (raw.options ?? raw) as any;
    if (Array.isArray(opts)) {
        return opts
            .map((o) => {
                if (typeof o === 'string') return { label: o, value: o };
                if (o && typeof o === 'object') {
                    const label = String(o.label ?? o.value ?? '');
                    const value = String(o.value ?? o.label ?? '');
                    if (!label || !value) return null;
                    return { label, value };
                }
                return null;
            })
            .filter(Boolean) as any;
    }
    return [];
}

export const AcademicReportFillScreen = () => {
    const { isDark, colors } = useTheme();
    const navigation = useNavigation<any>();
    const route = useRoute<any>();
    const { templateId } = (route.params ?? {}) as RouteParams;

    const [tpl, setTpl] = useState<AcademicReportTemplate | null>(null);
    const [loading, setLoading] = useState(true);
    const [submitting, setSubmitting] = useState(false);
    const [error, setError] = useState<string | null>(null);

    const [textAnswers, setTextAnswers] = useState<Record<number, string>>({});
    const [singleSelect, setSingleSelect] = useState<Record<number, string>>({});
    const [multiSelect, setMultiSelect] = useState<Record<number, string[]>>({});
    const [files, setFiles] = useState<Record<number, { uri: string; name: string; type: string } | null>>({});

    const questions = useMemo(() => (tpl?.questions ?? []).slice().sort((a, b) => a.display_order - b.display_order), [tpl]);

    const load = async () => {
        try {
            setError(null);
            setLoading(true);
            const data = await academicReportsApi.getTemplate(templateId);
            setTpl(data);
        } catch (e: any) {
            setError(e?.message ?? 'Failed to load report.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        load();
    }, [templateId]);

    const pickFile = async (qid: number) => {
        try {
            const res = await DocumentPicker.getDocumentAsync({ copyToCacheDirectory: true, multiple: false });
            if (res.canceled) return;
            const f = res.assets?.[0];
            if (!f?.uri) return;
            setFiles((prev) => ({
                ...prev,
                [qid]: {
                    uri: f.uri,
                    name: f.name ?? `upload-${Date.now()}`,
                    type: f.mimeType ?? 'application/octet-stream',
                },
            }));
        } catch (e: any) {
            Alert.alert('File picker', e?.message ?? 'Could not pick file.');
        }
    };

    const buildAnswersPayload = (): AcademicReportAnswerInput[] => {
        const out: AcademicReportAnswerInput[] = [];
        for (const q of questions) {
            if (q.type === 'short_text' || q.type === 'long_text') {
                const v = (textAnswers[q.id] ?? '').trim();
                if (v) out.push({ question_id: q.id, value_text: v });
            }
            if (q.type === 'single_select') {
                const v = singleSelect[q.id];
                if (v) out.push({ question_id: q.id, value_json: { value: v } });
            }
            if (q.type === 'multi_select') {
                const v = multiSelect[q.id] ?? [];
                if (v.length) out.push({ question_id: q.id, value_json: { values: v } });
            }
        }
        return out;
    };

    const validateRequired = () => {
        for (const q of questions) {
            if (!q.is_required) continue;
            if (q.type === 'file_upload') continue; // uploaded after submission
            if (q.type === 'short_text' || q.type === 'long_text') {
                if (!((textAnswers[q.id] ?? '').trim())) return q.label;
            }
            if (q.type === 'single_select') {
                if (!singleSelect[q.id]) return q.label;
            }
            if (q.type === 'multi_select') {
                if ((multiSelect[q.id] ?? []).length === 0) return q.label;
            }
        }
        return null;
    };

    const submit = async () => {
        if (!tpl) return;
        const missing = validateRequired();
        if (missing) {
            Alert.alert('Required', `Please answer: ${missing}`);
            return;
        }

        setSubmitting(true);
        try {
            const submission = await academicReportsApi.submit({
                template_id: tpl.id,
                answers: buildAnswersPayload(),
            });

            // Upload any selected files
            for (const q of questions) {
                if (q.type !== 'file_upload') continue;
                const f = files[q.id];
                if (!f) continue;
                await academicReportsApi.uploadFileAnswer({
                    submissionId: submission.id,
                    questionId: q.id,
                    file: f,
                });
            }

            Alert.alert('Submitted', 'Your report has been submitted.');
            navigation.goBack();
        } catch (e: any) {
            Alert.alert('Submit failed', e?.message ?? 'Could not submit report.');
        } finally {
            setSubmitting(false);
        }
    };

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            {error ? <LoadErrorBanner message={error} onRetry={load} /> : null}
            <ScrollView contentContainerStyle={{ padding: SPACING.xl, paddingTop: SPACING.md }}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    {tpl?.title ?? 'Report'}
                </Text>
                {tpl?.description ? (
                    <Text style={[styles.desc, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {tpl.description}
                    </Text>
                ) : null}

                {loading ? (
                    <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight, marginTop: SPACING.lg }}>
                        Loading…
                    </Text>
                ) : null}

                {!loading &&
                    questions.map((q) => {
                        const border = isDark ? colors.borderDark : colors.borderLight;
                        const surface = isDark ? colors.surfaceDark : colors.surfaceLight;
                        const required = q.is_required ? ' *' : '';

                        if (q.type === 'short_text' || q.type === 'long_text') {
                            return (
                                <View key={q.id} style={[styles.block, { backgroundColor: surface, borderColor: border }]}>
                                    <Text style={[styles.label, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {q.label}
                                        <Text style={{ color: colors.error }}>{required}</Text>
                                    </Text>
                                    {q.help_text ? (
                                        <Text style={[styles.help, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {q.help_text}
                                        </Text>
                                    ) : null}
                                    <TextInput
                                        value={textAnswers[q.id] ?? ''}
                                        onChangeText={(t) => setTextAnswers((prev) => ({ ...prev, [q.id]: t }))}
                                        placeholder="Type here…"
                                        placeholderTextColor={isDark ? colors.textSubDark : colors.textSubLight}
                                        style={[
                                            styles.input,
                                            {
                                                color: isDark ? colors.textMainDark : colors.textMainLight,
                                                borderColor: border,
                                                backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight,
                                                minHeight: q.type === 'long_text' ? 96 : 44,
                                                textAlignVertical: q.type === 'long_text' ? 'top' : 'center',
                                            },
                                        ]}
                                        multiline={q.type === 'long_text'}
                                    />
                                </View>
                            );
                        }

                        if (q.type === 'single_select') {
                            const opts = coerceOptions(q);
                            const selected = singleSelect[q.id] ?? '';
                            return (
                                <View key={q.id} style={[styles.block, { backgroundColor: surface, borderColor: border }]}>
                                    <Text style={[styles.label, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {q.label}
                                        <Text style={{ color: colors.error }}>{required}</Text>
                                    </Text>
                                    {opts.map((o) => {
                                        const isSel = selected === o.value;
                                        return (
                                            <TouchableOpacity
                                                key={o.value}
                                                style={[
                                                    styles.choice,
                                                    { borderColor: border, backgroundColor: isSel ? colors.primary + '20' : 'transparent' },
                                                ]}
                                                onPress={() => setSingleSelect((p) => ({ ...p, [q.id]: o.value }))}
                                            >
                                                <Text style={{ color: isDark ? colors.textMainDark : colors.textMainLight }}>{o.label}</Text>
                                            </TouchableOpacity>
                                        );
                                    })}
                                </View>
                            );
                        }

                        if (q.type === 'multi_select') {
                            const opts = coerceOptions(q);
                            const selected = new Set(multiSelect[q.id] ?? []);
                            return (
                                <View key={q.id} style={[styles.block, { backgroundColor: surface, borderColor: border }]}>
                                    <Text style={[styles.label, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {q.label}
                                        <Text style={{ color: colors.error }}>{required}</Text>
                                    </Text>
                                    {opts.map((o) => {
                                        const isSel = selected.has(o.value);
                                        return (
                                            <TouchableOpacity
                                                key={o.value}
                                                style={[
                                                    styles.choice,
                                                    { borderColor: border, backgroundColor: isSel ? colors.primary + '20' : 'transparent' },
                                                ]}
                                                onPress={() => {
                                                    const next = new Set(selected);
                                                    if (next.has(o.value)) next.delete(o.value);
                                                    else next.add(o.value);
                                                    setMultiSelect((p) => ({ ...p, [q.id]: Array.from(next) }));
                                                }}
                                            >
                                                <Text style={{ color: isDark ? colors.textMainDark : colors.textMainLight }}>{o.label}</Text>
                                            </TouchableOpacity>
                                        );
                                    })}
                                </View>
                            );
                        }

                        if (q.type === 'file_upload') {
                            const f = files[q.id];
                            return (
                                <View key={q.id} style={[styles.block, { backgroundColor: surface, borderColor: border }]}>
                                    <Text style={[styles.label, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {q.label}
                                        <Text style={{ color: colors.error }}>{required}</Text>
                                    </Text>
                                    {q.help_text ? (
                                        <Text style={[styles.help, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {q.help_text}
                                        </Text>
                                    ) : null}
                                    <TouchableOpacity
                                        style={[styles.fileBtn, { borderColor: border }]}
                                        onPress={() => pickFile(q.id)}
                                    >
                                        <Text style={{ color: colors.primary, fontWeight: '700' }}>
                                            {f ? 'Change file' : 'Pick a file'}
                                        </Text>
                                    </TouchableOpacity>
                                    {f ? (
                                        <Text style={[styles.help, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            Selected: {f.name}
                                        </Text>
                                    ) : null}
                                </View>
                            );
                        }

                        return null;
                    })}

                <View style={{ marginTop: SPACING.lg }}>
                    <Button title={submitting ? 'Submitting…' : 'Submit'} onPress={submit} disabled={submitting || loading} />
                </View>
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    title: { fontSize: FONT_SIZES.xl, fontWeight: '800' },
    desc: { marginTop: 6, fontSize: FONT_SIZES.sm, lineHeight: 18 },
    block: {
        marginTop: SPACING.md,
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.md,
    },
    label: { fontSize: FONT_SIZES.md, fontWeight: '700' },
    help: { marginTop: 6, fontSize: FONT_SIZES.sm, lineHeight: 18 },
    input: {
        marginTop: SPACING.sm,
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.md,
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        fontSize: FONT_SIZES.md,
    },
    choice: {
        marginTop: SPACING.sm,
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.md,
        padding: SPACING.md,
    },
    fileBtn: {
        marginTop: SPACING.sm,
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.md,
        padding: SPACING.md,
        alignItems: 'center',
    },
});

