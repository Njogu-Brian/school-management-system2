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
import { Card } from '@components/common/Card';
import { StatusBadge } from '@components/common/StatusBadge';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { academicsApi } from '@api/academics.api';
import { Assignment } from '../types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface AssignmentsScreenProps {
    navigation: any;
}

export const AssignmentsScreen: React.FC<AssignmentsScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();

    const [assignments, setAssignments] = useState<Assignment[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [filter, setFilter] = useState<'all' | 'active' | 'closed'>('active');

    const isTeacher = user?.role === 'teacher' || user?.role === 'senior_teacher' || user?.role === 'supervisor' || user?.role === 'admin' || user?.role === 'super_admin';

    const fetchAssignments = useCallback(async () => {
        try {
            setLoading(true);

            const filters: any = {
                per_page: 50,
            };

            if (filter !== 'all') {
                filters.status = filter;
            }

            if (isTeacher) {
                filters.teacher_id = (user as any).teacher_id ?? user.staff_id ?? user.id;
            } else {
                // For students, filter by their class
                filters.class_id = user?.class_id;
            }

            const response = await academicsApi.getAssignments(filters);

            if (response.success && response.data) {
                setAssignments(response.data.data);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load assignments');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [filter, user, isTeacher]);

    useEffect(() => {
        fetchAssignments();
    }, [filter]);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchAssignments();
    };

    const handleAssignmentPress = (assignment: Assignment) => {
        if (isTeacher) {
            navigation.navigate('AssignmentDetail', { assignmentId: assignment.id });
        } else {
            navigation.navigate('ViewAssignment', { assignmentId: assignment.id });
        }
    };

    const isOverdue = (dueDate: string) => {
        return new Date(dueDate) < new Date();
    };

    const renderAssignmentCard = ({ item }: { item: Assignment }) => (
        <Card onPress={() => handleAssignmentPress(item)}>
            <View style={styles.assignmentCard}>
                <View style={styles.assignmentInfo}>
                    <View style={styles.headerRow}>
                        <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {item.title}
                        </Text>
                        {item.status === 'active' && isOverdue(item.due_date) && (
                            <View style={styles.overdueBadge}>
                                <Text style={styles.overdueText}>OVERDUE</Text>
                            </View>
                        )}
                    </View>

                    <Text
                        style={[styles.description, { color: isDark ? colors.textSubDark : colors.textSubLight }]}
                        numberOfLines={2}
                    >
                        {item.description}
                    </Text>

                    <View style={styles.metaRow}>
                        <View style={styles.metaItem}>
                            <Icon name="book" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.metaText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {item.subject_name}
                            </Text>
                        </View>

                        <View style={styles.metaItem}>
                            <Icon name="class" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.metaText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {item.class_name}
                            </Text>
                        </View>
                    </View>

                    <View style={styles.footerRow}>
                        <View style={styles.metaItem}>
                            <Icon
                                name="event"
                                size={14}
                                color={isOverdue(item.due_date) ? colors.error : isDark ? colors.textSubDark : colors.textSubLight}
                            />
                            <Text
                                style={[
                                    styles.metaText,
                                    { color: isOverdue(item.due_date) ? colors.error : isDark ? colors.textSubDark : colors.textSubLight }
                                ]}
                            >
                                Due: {formatters.formatDate(item.due_date)}
                            </Text>
                        </View>

                        <Text style={[styles.marks, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.total_marks} marks
                        </Text>
                    </View>
                </View>

                <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
            </View>
        </Card>
    );

    if (loading) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading assignments..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.header}>
                <Text style={[styles.headerTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    {isTeacher ? 'My Assignments' : 'Homework'}
                </Text>
                {isTeacher && (
                    <TouchableOpacity
                        style={styles.addButton}
                        onPress={() => navigation.navigate('CreateAssignment')}
                    >
                        <Icon name="add" size={24} color={colors.primary} />
                    </TouchableOpacity>
                )}
            </View>

            {/* Filter Tabs */}
            <View style={styles.filterTabs}>
                <TouchableOpacity
                    style={[
                        styles.filterTab,
                        filter === 'all' && { borderBottomColor: colors.primary, borderBottomWidth: 2 },
                    ]}
                    onPress={() => setFilter('all')}
                >
                    <Text
                        style={[
                            styles.filterText,
                            {
                                color: filter === 'all' ? colors.primary : isDark ? colors.textSubDark : colors.textSubLight,
                                fontWeight: filter === 'all' ? 'bold' : 'normal',
                            },
                        ]}
                    >
                        All
                    </Text>
                </TouchableOpacity>

                <TouchableOpacity
                    style={[
                        styles.filterTab,
                        filter === 'active' && { borderBottomColor: colors.primary, borderBottomWidth: 2 },
                    ]}
                    onPress={() => setFilter('active')}
                >
                    <Text
                        style={[
                            styles.filterText,
                            {
                                color: filter === 'active' ? colors.primary : isDark ? colors.textSubDark : colors.textSubLight,
                                fontWeight: filter === 'active' ? 'bold' : 'normal',
                            },
                        ]}
                    >
                        Active
                    </Text>
                </TouchableOpacity>

                <TouchableOpacity
                    style={[
                        styles.filterTab,
                        filter === 'closed' && { borderBottomColor: colors.primary, borderBottomWidth: 2 },
                    ]}
                    onPress={() => setFilter('closed')}
                >
                    <Text
                        style={[
                            styles.filterText,
                            {
                                color: filter === 'closed' ? colors.primary : isDark ? colors.textSubDark : colors.textSubLight,
                                fontWeight: filter === 'closed' ? 'bold' : 'normal',
                            },
                        ]}
                    >
                        Closed
                    </Text>
                </TouchableOpacity>
            </View>

            {/* Assignments List */}
            <FlatList
                data={assignments}
                renderItem={renderAssignmentCard}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContent}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={handleRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
                ListEmptyComponent={
                    <EmptyState
                        icon="assignment"
                        title="No Assignments"
                        message={`No ${filter} assignments found`}
                    />
                }
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    headerTitle: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    addButton: {
        padding: SPACING.sm,
    },
    filterTabs: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.xl,
        marginBottom: SPACING.md,
    },
    filterTab: {
        flex: 1,
        paddingVertical: SPACING.sm,
        alignItems: 'center',
    },
    filterText: {
        fontSize: FONT_SIZES.sm,
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    assignmentCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    assignmentInfo: {
        flex: 1,
        gap: 6,
    },
    headerRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
    },
    title: {
        flex: 1,
        fontSize: FONT_SIZES.md,
        fontWeight: 'bold',
    },
    overdueBadge: {
        backgroundColor: '#ff4444',
        paddingHorizontal: SPACING.xs,
        paddingVertical: 2,
        borderRadius: 4,
    },
    overdueText: {
        color: '#fff',
        fontSize: 10,
        fontWeight: 'bold',
    },
    description: {
        fontSize: FONT_SIZES.sm,
        lineHeight: 18,
    },
    metaRow: {
        flexDirection: 'row',
        gap: SPACING.md,
    },
    footerRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    metaItem: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    metaText: {
        fontSize: FONT_SIZES.xs,
    },
    marks: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
    },
});
