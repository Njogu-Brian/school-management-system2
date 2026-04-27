import React, { useCallback, useEffect, useState } from 'react';
import { View, Text, StyleSheet, SafeAreaView, ScrollView, TouchableOpacity, ActivityIndicator, Alert } from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { academicsApi } from '@api/academics.api';
import { Assignment } from 'types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params: { assignmentId: number } };
}

export const AssignmentDetailScreen: React.FC<Props> = ({ navigation, route }) => {
    const assignmentId = route.params.assignmentId;
    const { isDark, colors } = useTheme();
    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;

    const [row, setRow] = useState<Assignment | null>(null);
    const [loading, setLoading] = useState(true);

    const load = useCallback(async () => {
        try {
            setLoading(true);
            const res = await academicsApi.getAssignment(assignmentId);
            if (res.success && res.data) {
                setRow(res.data as Assignment);
            } else {
                setRow(null);
            }
        } catch (e: any) {
            Alert.alert('Assignment', e?.message || 'Failed to load');
            setRow(null);
        } finally {
            setLoading(false);
        }
    }, [assignmentId]);

    useEffect(() => {
        load();
    }, [load]);

    return (
        <SafeAreaView style={[layoutStyles.flex1, { backgroundColor: bg }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.back}>
                    <Icon name="arrow-back" size={24} color={textMain} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: textMain }]} numberOfLines={1}>
                    Homework
                </Text>
                <View style={{ width: 40 }} />
            </View>
            {loading ? (
                <ActivityIndicator style={{ marginTop: SPACING.xl }} color={colors.primary} />
            ) : !row ? (
                <Text style={[styles.empty, { color: textSub }]}>Could not load this assignment.</Text>
            ) : (
                <ScrollView contentContainerStyle={styles.scroll}>
                    <Card>
                        <Text style={[styles.h1, { color: textMain }]}>{row.title}</Text>
                        <Text style={[styles.meta, { color: textSub }]}>
                            {row.class_name ? `${row.class_name}` : ''}
                            {row.subject_name ? ` · ${row.subject_name}` : ''}
                            {row.teacher_name ? ` · ${row.teacher_name}` : ''}
                        </Text>
                        <Text style={[styles.meta, { color: textSub }]}>
                            Due {row.due_date ? formatters.formatDate(row.due_date) : 'tbd'}
                            {row.total_marks ? ` · Max ${row.total_marks} marks` : ''}
                        </Text>
                        <Text style={[styles.status, { color: row.status === 'active' ? colors.primary : textSub }]}>
                            {row.status === 'active' ? 'Active' : 'Closed'}
                        </Text>
                    </Card>
                    {!!row.description && (
                        <Card>
                            <Text style={[styles.section, { color: textMain }]}>Instructions</Text>
                            <Text style={[styles.body, { color: textMain }]}>{row.description}</Text>
                        </Card>
                    )}
                </ScrollView>
            )}
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
    back: { padding: SPACING.sm, marginRight: SPACING.sm },
    title: { flex: 1, fontSize: FONT_SIZES.lg, fontWeight: '700' },
    scroll: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    h1: { fontSize: FONT_SIZES.xl, fontWeight: '800', marginBottom: SPACING.sm },
    meta: { fontSize: FONT_SIZES.sm, marginBottom: 4 },
    status: { fontSize: FONT_SIZES.sm, fontWeight: '600', marginTop: SPACING.sm },
    section: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.sm },
    body: { fontSize: FONT_SIZES.md, lineHeight: 22 },
    empty: { textAlign: 'center', marginTop: SPACING.xl, paddingHorizontal: SPACING.lg },
});
