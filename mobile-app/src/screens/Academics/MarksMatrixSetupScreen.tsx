import React, { useEffect, useMemo, useState } from 'react';
import {
    Alert,
    SafeAreaView,
    ScrollView,
    StyleSheet,
    Text,
    TouchableOpacity,
    View,
} from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { academicsApi } from '@api/academics.api';
import { Button } from '@components/common/Button';
import { Card } from '@components/common/Card';
import { SPACING, FONT_SIZES } from '@constants/theme';

type SimpleOption = { id: number; name: string; code?: string | null; classroom_id?: number };

interface Props {
    navigation: any;
}

export const MarksMatrixSetupScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(true);
    const [examTypes, setExamTypes] = useState<SimpleOption[]>([]);
    const [classrooms, setClassrooms] = useState<SimpleOption[]>([]);
    const [streams, setStreams] = useState<SimpleOption[]>([]);
    const [selectedExamType, setSelectedExamType] = useState<number | null>(null);
    const [selectedClassroom, setSelectedClassroom] = useState<number | null>(null);
    const [selectedStream, setSelectedStream] = useState<number | null>(null);

    const loadContext = async (classroomId?: number) => {
        try {
            setLoading(true);
            const res = await academicsApi.getMarksMatrixContext(classroomId);
            if (res.success && res.data) {
                setExamTypes(res.data.exam_types || []);
                setClassrooms(res.data.classrooms || []);
                setStreams((res.data.streams || []).map((s) => ({ ...s })));
            }
        } catch (e: any) {
            Alert.alert('Error', e?.message || 'Failed to load mark entry setup.');
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        void loadContext();
    }, []);

    useEffect(() => {
        if (!selectedClassroom) {
            setStreams([]);
            setSelectedStream(null);
            return;
        }
        setSelectedStream(null);
        void loadContext(selectedClassroom);
    }, [selectedClassroom]);

    const selectedExamTypeName = useMemo(
        () => examTypes.find((e) => e.id === selectedExamType)?.name || 'Not selected',
        [examTypes, selectedExamType]
    );
    const selectedClassroomName = useMemo(
        () => classrooms.find((c) => c.id === selectedClassroom)?.name || 'Not selected',
        [classrooms, selectedClassroom]
    );
    const selectedStreamName = useMemo(() => {
        if (!selectedStream) return 'All streams';
        return streams.find((s) => s.id === selectedStream)?.name || 'All streams';
    }, [streams, selectedStream]);

    const handleContinue = () => {
        if (!selectedExamType || !selectedClassroom) {
            Alert.alert('Select context', 'Please select exam type and class.');
            return;
        }

        navigation.navigate('MarksMatrixEntry', {
            examTypeId: selectedExamType,
            classroomId: selectedClassroom,
            streamId: selectedStream ?? undefined,
        });
    };

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Bulk Marks Setup</Text>
                <View style={{ width: 24 }} />
            </View>

            <ScrollView contentContainerStyle={styles.content}>
                <Card style={styles.summaryCard}>
                    <Text style={[styles.summaryTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>Selected context</Text>
                    <Text style={[styles.summaryLine, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Exam type: {selectedExamTypeName}</Text>
                    <Text style={[styles.summaryLine, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Class: {selectedClassroomName}</Text>
                    <Text style={[styles.summaryLine, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Stream: {selectedStreamName}</Text>
                </Card>

                <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>1) Select exam type</Text>
                <View style={styles.grid}>
                    {examTypes.map((t) => (
                        <TouchableOpacity
                            key={t.id}
                            onPress={() => setSelectedExamType(t.id)}
                            style={[
                                styles.pill,
                                {
                                    borderColor: selectedExamType === t.id ? colors.primary : (isDark ? colors.borderDark : colors.borderLight),
                                    backgroundColor: selectedExamType === t.id ? `${colors.primary}22` : (isDark ? colors.surfaceDark : colors.surfaceLight),
                                },
                            ]}
                        >
                            <Text style={{ color: isDark ? colors.textMainDark : colors.textMainLight, fontWeight: '600' }}>
                                {t.name}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </View>

                <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>2) Select class</Text>
                <View style={styles.grid}>
                    {classrooms.map((c) => (
                        <TouchableOpacity
                            key={c.id}
                            onPress={() => setSelectedClassroom(c.id)}
                            style={[
                                styles.pill,
                                {
                                    borderColor: selectedClassroom === c.id ? colors.primary : (isDark ? colors.borderDark : colors.borderLight),
                                    backgroundColor: selectedClassroom === c.id ? `${colors.primary}22` : (isDark ? colors.surfaceDark : colors.surfaceLight),
                                },
                            ]}
                        >
                            <Text style={{ color: isDark ? colors.textMainDark : colors.textMainLight, fontWeight: '600' }}>
                                {c.name}
                            </Text>
                        </TouchableOpacity>
                    ))}
                </View>

                {selectedClassroom && streams.length > 0 && (
                    <>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            3) Select stream (optional)
                        </Text>
                        <View style={styles.grid}>
                            <TouchableOpacity
                                onPress={() => setSelectedStream(null)}
                                style={[
                                    styles.pill,
                                    {
                                        borderColor: selectedStream === null ? colors.primary : (isDark ? colors.borderDark : colors.borderLight),
                                        backgroundColor: selectedStream === null ? `${colors.primary}22` : (isDark ? colors.surfaceDark : colors.surfaceLight),
                                    },
                                ]}
                            >
                                <Text style={{ color: isDark ? colors.textMainDark : colors.textMainLight, fontWeight: '600' }}>All streams</Text>
                            </TouchableOpacity>
                            {streams.map((s) => (
                                <TouchableOpacity
                                    key={s.id}
                                    onPress={() => setSelectedStream(s.id)}
                                    style={[
                                        styles.pill,
                                        {
                                            borderColor: selectedStream === s.id ? colors.primary : (isDark ? colors.borderDark : colors.borderLight),
                                            backgroundColor: selectedStream === s.id ? `${colors.primary}22` : (isDark ? colors.surfaceDark : colors.surfaceLight),
                                        },
                                    ]}
                                >
                                    <Text style={{ color: isDark ? colors.textMainDark : colors.textMainLight, fontWeight: '600' }}>{s.name}</Text>
                                </TouchableOpacity>
                            ))}
                        </View>
                    </>
                )}

                <Button title={loading ? 'Loading...' : 'Load Bulk Entry'} onPress={handleContinue} loading={loading} fullWidth />
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    title: { fontSize: FONT_SIZES.xl, fontWeight: '700' },
    content: { padding: SPACING.xl, paddingBottom: SPACING.xxl, gap: SPACING.md },
    summaryCard: { marginBottom: SPACING.sm },
    summaryTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: 6 },
    summaryLine: { fontSize: FONT_SIZES.sm, marginBottom: 2 },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: 6 },
    grid: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING.sm, marginBottom: SPACING.sm },
    pill: {
        borderWidth: 1,
        borderRadius: 999,
        paddingVertical: SPACING.xs,
        paddingHorizontal: SPACING.md,
    },
});
