import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { isTeacherRole } from '@utils/roleUtils';
import { Card } from '@components/common/Card';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { academicsApi } from '@api/academics.api';
import { Assignment } from '@types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface DiaryScreenProps {
    navigation: any;
}

export const DiaryScreen: React.FC<DiaryScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();

    const [assignments, setAssignments] = useState<Assignment[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const fetchDiary = useCallback(async () => {
        try {
            setLoading(true);
            const filters: any = { per_page: 50, status: 'active' };
            if (user?.role && isTeacherRole(user.role)) {
                filters.teacher_id = (user as any).teacher_id ?? user.staff_id ?? user.id;
            }
            const response = await academicsApi.getAssignments(filters);
            if (response.success && response.data) {
                const data = response.data as any;
                setAssignments(data?.data ?? data ?? []);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load homework diary');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [user]);

    useEffect(() => {
        fetchDiary();
    }, []);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchDiary();
    };

    if (loading && !refreshing) {
        return <LoadingState message="Loading diary..." />;
    }

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Homework Diary
                </Text>
            </View>
            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                }
            >
                <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                    Assignments you have set. Tap to view submissions or add new homework from Assignments.
                </Text>
                {assignments.length === 0 ? (
                    <EmptyState
                        icon="book"
                        title="No homework entries"
                        message="Your homework diary is empty. Issue homework from the Assignments screen."
                    />
                ) : (
                    assignments.map((item) => (
                        <Card
                            key={item.id}
                            onPress={() => navigation.navigate('Assignments', { assignmentId: item.id })}
                        >
                            <View style={styles.entry}>
                                <View style={styles.entryMain}>
                                    <Text style={[styles.entryTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]} numberOfLines={2}>
                                        {item.title}
                                    </Text>
                                    <Text style={[styles.entryMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                        {item.subject_name} • {item.class_name}
                                    </Text>
                                    <Text style={[styles.entryDue, { color: colors.primary }]}>
                                        Due: {formatters.formatDate(item.due_date)}
                                    </Text>
                                </View>
                                <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            </View>
                        </Card>
                    ))
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    backBtn: { marginRight: SPACING.sm },
    title: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    content: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    subtitle: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.lg },
    entry: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
    entryMain: { flex: 1 },
    entryTitle: { fontSize: FONT_SIZES.md, fontWeight: '600', marginBottom: 4 },
    entryMeta: { fontSize: FONT_SIZES.sm, marginBottom: 2 },
    entryDue: { fontSize: FONT_SIZES.sm, fontWeight: '500' },
});
