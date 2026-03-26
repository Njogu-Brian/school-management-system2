import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    TouchableOpacity,
    ScrollView,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { academicsApi } from '@api/academics.api';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface ExamMarksSetupScreenProps {
    navigation: any;
    route: any;
}

type Option = {
    classroom_id: number;
    classroom_name: string;
    subject_id: number;
    subject_name: string;
};

export const ExamMarksSetupScreen: React.FC<ExamMarksSetupScreenProps> = ({ navigation, route }) => {
    const { examId } = route.params;
    const { isDark, colors } = useTheme();
    const [loading, setLoading] = useState(true);
    const [examName, setExamName] = useState('');
    const [options, setOptions] = useState<Option[]>([]);

    useEffect(() => {
        const load = async () => {
            try {
                const [examRes, optRes] = await Promise.all([
                    academicsApi.getExam(examId),
                    academicsApi.getExamMarkingOptions(examId),
                ]);
                if (examRes.success && examRes.data) {
                    setExamName(examRes.data.name);
                }
                if (optRes.success && optRes.data?.length) {
                    setOptions(optRes.data);
                } else if (examRes.success && examRes.data?.classroom_id && examRes.data?.subject_id) {
                    setOptions([
                        {
                            classroom_id: examRes.data.classroom_id,
                            classroom_name: (examRes.data as any).classroom_name || 'Class',
                            subject_id: examRes.data.subject_id,
                            subject_name: (examRes.data as any).subject_name || 'Subject',
                        },
                    ]);
                }
            } catch (e: any) {
                Alert.alert('Error', e.message || 'Failed to load exam');
            } finally {
                setLoading(false);
            }
        };
        load();
    }, [examId]);

    const goToMarks = (opt: Option) => {
        navigation.navigate('MarksEntry', {
            examId,
            subjectId: opt.subject_id,
            classId: opt.classroom_id,
        });
    };

    if (loading) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <LoadingState message="Loading exam..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]} numberOfLines={2}>
                    Enter results
                </Text>
                <View style={{ width: 24 }} />
            </View>
            <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>{examName}</Text>
            <Text style={[styles.hint, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                Choose class and subject to enter marks.
            </Text>

            <ScrollView contentContainerStyle={styles.list}>
                {options.length === 0 ? (
                    <Text style={[styles.empty, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        No class/subject is linked to this exam. Configure the exam in the web portal first.
                    </Text>
                ) : (
                    options.map((opt) => (
                        <Card key={`${opt.classroom_id}-${opt.subject_id}`} elevated>
                            <TouchableOpacity style={styles.row} onPress={() => goToMarks(opt)} activeOpacity={0.7}>
                                <View style={{ flex: 1 }}>
                                    <Text style={[styles.className, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {opt.classroom_name}
                                    </Text>
                                    <Text style={[styles.subjectName, { color: colors.primary }]}>{opt.subject_name}</Text>
                                </View>
                                <Icon name="chevron-right" size={24} color={colors.primary} />
                            </TouchableOpacity>
                        </Card>
                    ))
                )}
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
    title: { flex: 1, textAlign: 'center', fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    subtitle: { paddingHorizontal: SPACING.xl, fontSize: FONT_SIZES.md, fontWeight: '600' },
    hint: { paddingHorizontal: SPACING.xl, marginTop: SPACING.xs, marginBottom: SPACING.md, fontSize: FONT_SIZES.sm },
    list: { padding: SPACING.xl, paddingBottom: SPACING.xxl },
    row: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md },
    className: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    subjectName: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    empty: { textAlign: 'center', padding: SPACING.xl },
});
