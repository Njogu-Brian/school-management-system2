import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { isTeacherRole } from '@utils/roleUtils';
import { Card } from '@components/common/Card';
import { StatusBadge } from '@components/common/StatusBadge';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { academicsApi } from '@api/academics.api';
import { LessonPlan } from '@types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface LessonPlansScreenProps {
    navigation: any;
}

export const LessonPlansScreen: React.FC<LessonPlansScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();

    const [plans, setPlans] = useState<LessonPlan[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [filter, setFilter] = useState<'all' | 'draft' | 'approved' | 'completed'>('all');

    const fetchPlans = useCallback(async () => {
        try {
            setLoading(true);
            const filters: any = { per_page: 50 };
            if (user?.role && isTeacherRole(user.role)) {
                filters.teacher_id = (user as any).teacher_id ?? user.staff_id ?? user.id;
            }
            if (filter !== 'all') {
                filters.status = filter;
            }
            const response = await academicsApi.getLessonPlans(filters);
            if (response.success && response.data) {
                const data = response.data as any;
                setPlans(Array.isArray(data?.data) ? data.data : Array.isArray(data) ? data : []);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load lesson plans');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [user, filter]);

    useEffect(() => {
        fetchPlans();
    }, [filter]);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchPlans();
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved':
            case 'completed':
                return colors.success;
            case 'draft':
                return colors.warning || '#f59e0b';
            default:
                return isDark ? colors.textSubDark : colors.textSubLight;
        }
    };

    const renderPlanCard = ({ item }: { item: LessonPlan }) => (
        <Card onPress={() => navigation.navigate('LessonPlanDetail', { planId: item.id })}>
            <View style={styles.cardRow}>
                <View style={styles.cardContent}>
                    <Text style={[styles.topic, { color: isDark ? colors.textMainDark : colors.textMainLight }]} numberOfLines={2}>
                        {item.topic}
                    </Text>
                    <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {item.subject_name} • {item.class_name}
                    </Text>
                    <Text style={[styles.date, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {formatters.formatDate(item.date)} • {item.duration_minutes} min
                    </Text>
                </View>
                <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) + '20' }]}>
                    <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
                        {formatters.capitalize(item.status)}
                    </Text>
                </View>
            </View>
        </Card>
    );

    if (loading && !refreshing) {
        return <LoadingState message="Loading lesson plans..." />;
    }

    const list = Array.isArray(plans) ? plans : [];

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Lesson Plans
                </Text>
            </View>
            <View style={styles.filterRow}>
                {(['all', 'draft', 'approved', 'completed'] as const).map((f) => (
                    <TouchableOpacity
                        key={f}
                        style={[styles.filterChip, filter === f && { backgroundColor: colors.primary }]}
                        onPress={() => setFilter(f)}
                    >
                        <Text style={[styles.filterText, { color: filter === f ? '#fff' : (isDark ? colors.textSubDark : colors.textSubLight) }]}>
                            {formatters.capitalize(f)}
                        </Text>
                    </TouchableOpacity>
                ))}
            </View>
            {list.length === 0 ? (
                <EmptyState
                    icon="menu-book"
                    title="No lesson plans"
                    message={filter !== 'all' ? `No ${filter} lesson plans.` : 'Create lesson plans from the web or add one here when supported.'}
                />
            ) : (
                <FlatList
                    data={list}
                    renderItem={renderPlanCard}
                    keyExtractor={(item) => String(item.id)}
                    contentContainerStyle={styles.list}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                    }
                />
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { flexDirection: 'row', alignItems: 'center', paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm },
    backBtn: { marginRight: SPACING.sm },
    title: { fontSize: FONT_SIZES.xl, fontWeight: 'bold' },
    filterRow: { flexDirection: 'row', flexWrap: 'wrap', paddingHorizontal: SPACING.md, gap: SPACING.sm, marginBottom: SPACING.md },
    filterChip: { paddingHorizontal: SPACING.md, paddingVertical: SPACING.xs, borderRadius: 20 },
    filterText: { fontSize: FONT_SIZES.sm },
    list: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    cardRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'flex-start' },
    cardContent: { flex: 1 },
    topic: { fontSize: FONT_SIZES.md, fontWeight: '600', marginBottom: 4 },
    meta: { fontSize: FONT_SIZES.sm, marginBottom: 2 },
    date: { fontSize: FONT_SIZES.xs },
    statusBadge: { paddingHorizontal: 8, paddingVertical: 4, borderRadius: 8 },
    statusText: { fontSize: FONT_SIZES.xs, fontWeight: '600' },
});
