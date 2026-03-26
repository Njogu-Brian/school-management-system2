import React, { useCallback, useEffect, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    TouchableOpacity,
    Modal,
    FlatList,
    Alert,
    ActivityIndicator,
    KeyboardAvoidingView,
    Platform,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Input } from '@components/common/Input';
import { Card } from '@components/common/Card';
import { academicsApi } from '@api/academics.api';
import { studentsApi } from '@api/students.api';
import { Class, Stream, ClassSubject } from '@types/student.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

type PickKind = 'class' | 'stream' | 'subject' | null;

interface Props {
    navigation: { goBack: () => void };
}

export const CreateAssignmentScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;

    const [classes, setClasses] = useState<Class[]>([]);
    const [streams, setStreams] = useState<Stream[]>([]);
    const [subjects, setSubjects] = useState<ClassSubject[]>([]);
    const [loadingMeta, setLoadingMeta] = useState(true);
    const [saving, setSaving] = useState(false);

    const [title, setTitle] = useState('');
    const [instructions, setInstructions] = useState('');
    const [dueDate, setDueDate] = useState('');
    const [maxScore, setMaxScore] = useState('');

    const [classId, setClassId] = useState<number | null>(null);
    const [className, setClassName] = useState('');
    const [streamId, setStreamId] = useState<number | null>(null);
    const [streamName, setStreamName] = useState('Whole class');
    const [subjectId, setSubjectId] = useState<number | null>(null);
    const [subjectName, setSubjectName] = useState('');

    const [pickKind, setPickKind] = useState<PickKind>(null);

    const loadClasses = useCallback(async () => {
        try {
            setLoadingMeta(true);
            const res = await studentsApi.getClasses();
            if (res.success && res.data) {
                setClasses(Array.isArray(res.data) ? res.data : []);
            }
        } catch (e: any) {
            Alert.alert('Classes', e?.message || 'Failed to load classes');
        } finally {
            setLoadingMeta(false);
        }
    }, []);

    useEffect(() => {
        loadClasses();
    }, [loadClasses]);

    const loadClassChildren = useCallback(async (cid: number) => {
        try {
            const [sRes, subRes] = await Promise.all([
                studentsApi.getStreams(cid),
                studentsApi.getClassSubjects(cid),
            ]);
            setStreams(sRes.success && sRes.data ? sRes.data : []);
            setSubjects(subRes.success && subRes.data ? subRes.data : []);
        } catch (e: any) {
            Alert.alert('Class', e?.message || 'Failed to load streams/subjects');
            setStreams([]);
            setSubjects([]);
        }
    }, []);

    useEffect(() => {
        if (classId != null) {
            loadClassChildren(classId);
        } else {
            setStreams([]);
            setSubjects([]);
        }
    }, [classId, loadClassChildren]);

    const openPicker = (k: PickKind) => setPickKind(k);

    const submit = async () => {
        if (!title.trim()) {
            Alert.alert('Validation', 'Enter a title.');
            return;
        }
        if (!dueDate.trim() || !/^\d{4}-\d{2}-\d{2}$/.test(dueDate.trim())) {
            Alert.alert('Validation', 'Due date must be YYYY-MM-DD.');
            return;
        }
        if (classId == null) {
            Alert.alert('Validation', 'Select a class.');
            return;
        }
        if (subjectId == null) {
            Alert.alert('Validation', 'Select a subject.');
            return;
        }
        let max: number | null = null;
        if (maxScore.trim()) {
            max = parseInt(maxScore.replace(/\D/g, ''), 10);
            if (Number.isNaN(max) || max < 1) {
                Alert.alert('Validation', 'Max score must be a positive number.');
                return;
            }
        }

        try {
            setSaving(true);
            const payload: Parameters<typeof academicsApi.createAssignment>[0] = {
                title: title.trim(),
                instructions: instructions.trim() || undefined,
                due_date: dueDate.trim(),
                classroom_id: classId,
                subject_id: subjectId,
                stream_id: streamId ?? undefined,
                max_score: max ?? undefined,
            };
            const res = await academicsApi.createAssignment(payload);
            if (!res.success) {
                Alert.alert('Save', (res as { message?: string }).message || 'Could not create');
                return;
            }
            Alert.alert('Saved', 'Homework has been assigned.', [
                { text: 'OK', onPress: () => navigation.goBack() },
            ]);
        } catch (e: any) {
            Alert.alert('Save', e?.message || 'Network error');
        } finally {
            setSaving(false);
        }
    };

    const renderPickerModal = () => (
        <Modal visible={pickKind !== null} animationType="slide" transparent>
            <View style={styles.modalBackdrop}>
                <View style={[styles.modalSheet, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                    <View style={styles.modalHeader}>
                        <Text style={[styles.modalTitle, { color: textMain }]}>
                            {pickKind === 'class' && 'Select class'}
                            {pickKind === 'stream' && 'Select stream'}
                            {pickKind === 'subject' && 'Select subject'}
                        </Text>
                        <TouchableOpacity onPress={() => setPickKind(null)}>
                            <Icon name="close" size={24} color={textMain} />
                        </TouchableOpacity>
                    </View>
                    <FlatList
                        data={
                            pickKind === 'class'
                                ? classes
                                : pickKind === 'stream'
                                  ? ([{ id: -1, name: 'Whole class', class_id: classId ?? 0 }] as (Stream & {
                                        id: number;
                                    })[]).concat(streams)
                                  : subjects
                        }
                        keyExtractor={(item) => String(item.id)}
                        renderItem={({ item }) => (
                            <TouchableOpacity
                                style={[styles.pickRow, { borderBottomColor: isDark ? colors.borderDark : colors.borderLight }]}
                                onPress={() => {
                                    if (pickKind === 'class') {
                                        setClassId((item as Class).id);
                                        setClassName((item as Class).name);
                                        setStreamId(null);
                                        setStreamName('Whole class');
                                        setSubjectId(null);
                                        setSubjectName('');
                                    } else if (pickKind === 'stream') {
                                        if ((item as Stream).id === -1) {
                                            setStreamId(null);
                                            setStreamName('Whole class');
                                        } else {
                                            setStreamId((item as Stream).id);
                                            setStreamName((item as Stream).name);
                                        }
                                    } else {
                                        setSubjectId((item as ClassSubject).id);
                                        setSubjectName((item as ClassSubject).name);
                                    }
                                    setPickKind(null);
                                }}
                            >
                                <Text style={{ color: textMain, fontSize: FONT_SIZES.md }}>
                                    {(item as Class | Stream | ClassSubject).name}
                                </Text>
                            </TouchableOpacity>
                        )}
                    />
                </View>
            </View>
        </Modal>
    );

    if (loadingMeta && classes.length === 0) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg, justifyContent: 'center' }]}>
                <ActivityIndicator color={colors.primary} />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <KeyboardAvoidingView
                style={layoutStyles.flex1}
                behavior={Platform.OS === 'ios' ? 'padding' : undefined}
            >
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="arrow-back" size={24} color={textMain} />
                    </TouchableOpacity>
                    <Text style={[styles.title, { color: textMain }]}>New homework</Text>
                    <View style={{ width: 40 }} />
                </View>
                <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
                    <Card>
                        <Input label="Title" value={title} onChangeText={setTitle} />
                        <Input
                            label="Instructions (optional)"
                            value={instructions}
                            onChangeText={setInstructions}
                            multiline
                        />
                        <Input
                            label="Due date (YYYY-MM-DD)"
                            value={dueDate}
                            onChangeText={setDueDate}
                            placeholder="2025-12-31"
                        />
                        <Input
                            label="Max score (optional)"
                            value={maxScore}
                            onChangeText={setMaxScore}
                            keyboardType="number-pad"
                        />
                    </Card>
                    <Card>
                        <Text style={[styles.label, { color: textSub }]}>Class</Text>
                        <TouchableOpacity
                            style={[styles.selectBtn, { borderColor: colors.primary }]}
                            onPress={() => openPicker('class')}
                        >
                            <Text style={{ color: textMain }}>{className || 'Tap to select'}</Text>
                            <Icon name="arrow-drop-down" size={24} color={textSub} />
                        </TouchableOpacity>
                        <Text style={[styles.label, { color: textSub, marginTop: SPACING.md }]}>Stream</Text>
                        <TouchableOpacity
                            style={[styles.selectBtn, { borderColor: colors.primary, opacity: classId ? 1 : 0.5 }]}
                            onPress={() => classId && openPicker('stream')}
                            disabled={!classId}
                        >
                            <Text style={{ color: textMain }}>{streamName}</Text>
                            <Icon name="arrow-drop-down" size={24} color={textSub} />
                        </TouchableOpacity>
                        <Text style={[styles.label, { color: textSub, marginTop: SPACING.md }]}>Subject</Text>
                        <TouchableOpacity
                            style={[styles.selectBtn, { borderColor: colors.primary, opacity: classId ? 1 : 0.5 }]}
                            onPress={() => classId && openPicker('subject')}
                            disabled={!classId}
                        >
                            <Text style={{ color: textMain }}>{subjectName || 'Tap to select'}</Text>
                            <Icon name="arrow-drop-down" size={24} color={textSub} />
                        </TouchableOpacity>
                        {classId && subjects.length === 0 ? (
                            <Text style={[styles.warn, { color: colors.warning }]}>
                                No subjects found for you in this class. Ask admin to link you on classroom subjects, or use the web
                                portal.
                            </Text>
                        ) : null}
                    </Card>
                    <TouchableOpacity
                        style={[styles.save, { backgroundColor: colors.primary, opacity: saving ? 0.75 : 1 }]}
                        onPress={submit}
                        disabled={saving}
                    >
                        {saving ? (
                            <ActivityIndicator color="#fff" />
                        ) : (
                            <Text style={styles.saveText}>Create homework</Text>
                        )}
                    </TouchableOpacity>
                </ScrollView>
            </KeyboardAvoidingView>
            {renderPickerModal()}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
    },
    backBtn: { padding: SPACING.sm, marginRight: SPACING.sm },
    title: { flex: 1, fontSize: FONT_SIZES.lg, fontWeight: '700' },
    scroll: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    label: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.xs },
    selectBtn: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        borderWidth: 1,
        borderRadius: 8,
        padding: SPACING.md,
    },
    save: {
        marginTop: SPACING.lg,
        paddingVertical: SPACING.md,
        borderRadius: 8,
        alignItems: 'center',
    },
    saveText: { color: '#fff', fontWeight: '700', fontSize: FONT_SIZES.md },
    modalBackdrop: {
        flex: 1,
        backgroundColor: 'rgba(0,0,0,0.45)',
        justifyContent: 'flex-end',
    },
    modalSheet: {
        maxHeight: '70%',
        borderTopLeftRadius: 16,
        borderTopRightRadius: 16,
        paddingBottom: SPACING.xl,
    },
    modalHeader: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        padding: SPACING.md,
    },
    modalTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    pickRow: { paddingVertical: SPACING.md, paddingHorizontal: SPACING.md, borderBottomWidth: StyleSheet.hairlineWidth },
    warn: { marginTop: SPACING.sm, fontSize: FONT_SIZES.sm },
});
