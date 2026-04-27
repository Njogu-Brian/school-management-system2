import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    FlatList,
    SafeAreaView,
    TouchableOpacity,
    RefreshControl,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { isTeacherRole } from '@utils/roleUtils';
import { Card } from '@components/common/Card';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { LoadErrorBanner } from '@components/common/LoadErrorBanner';
import { academicsApi } from '@api/academics.api';
import { Exam } from 'types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface ExamsListScreenProps {
    navigation: any;
}

export const ExamsListScreen: React.FC<ExamsListScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();

    const [exams, setExams] = useState<Exam[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [listError, setListError] = useState<string | null>(null);

    const fetchExams = useCallback(async () => {
        try {
            setLoading(true);
            setListError(null);

            const response = await academicsApi.getExams({
                per_page: 50,
            });

            if (response.success && response.data) {
                setExams(response.data.data);
            } else {
                setExams([]);
                setListError(response.message || 'Failed to load exams');
            }
        } catch (error: any) {
            setExams([]);
            setListError(error.message || 'Failed to load exams');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        fetchExams();
    }, []);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchExams();
    };

    const handleExamPress = (exam: Exam) => {
        if ((user?.role && isTeacherRole(user.role)) || user?.role === 'admin' || user?.role === 'super_admin' || user?.role === 'secretary') {
            navigation.navigate('ExamMarksSetup', { examId: exam.id });
        } else {
            navigation.navigate('ViewMarks', { examId: exam.id });
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'completed':
                return colors.success;
            case 'ongoing':
                return colors.primary;
            case 'published':
                return colors.success;
            default:
                return isDark ? colors.textSubDark : colors.textSubLight;
        }
    };

    const renderExamCard = ({ item }: { item: Exam }) => (
        <Card>
            <View style={styles.examCard}>
                <TouchableOpacity
                    style={styles.examInfo}
                    onPress={() => handleExamPress(item)}
                    activeOpacity={0.7}
                >
                    <Text style={[styles.examName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {item.name}
                    </Text>
                    <Text style={[styles.examType, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {item.exam_type_name}
                    </Text>

                    <View style={styles.dateRow}>
                        <View style={styles.dateInfo}>
                            <Icon name="event" size={16} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.dateText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {formatters.formatDate(item.start_date)} - {formatters.formatDate(item.end_date)}
                            </Text>
                        </View>
                    </View>

                    {item.total_marks ? (
                        <Text style={[styles.totalMarks, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Total Marks: {item.total_marks}
                        </Text>
                    ) : null}
                </TouchableOpacity>

                <View style={styles.statusContainer}>
                    <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) + '20' }]}>
                        <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
                            {formatters.capitalize(item.status)}
                        </Text>
                    </View>
                    <TouchableOpacity
                        onPress={() => navigation.navigate('ExamDetail', { examId: item.id })}
                        hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}
                        accessibilityRole="button"
                        accessibilityLabel="Exam details"
                    >
                        <Icon name="info-outline" size={22} color={colors.primary} />
                    </TouchableOpacity>
                    <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                </View>
            </View>
        </Card>
    );

    if (loading) {
        return (
            <SafeAreaView
                style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading exams..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Examinations
                </Text>
                {(user?.role === 'admin' || user?.role === 'super_admin') && (
                    <TouchableOpacity
                        style={styles.addButton}
                        onPress={() => navigation.navigate('CreateExam')}
                    >
                        <Icon name="add" size={24} color={colors.primary} />
                    </TouchableOpacity>
                )}
            </View>

            {listError ? (
                <View style={{ paddingHorizontal: SPACING.xl, paddingBottom: SPACING.sm }}>
                    <LoadErrorBanner
                        message={listError}
                        onRetry={() => {
                            setRefreshing(true);
                            fetchExams();
                        }}
                        surfaceColor={isDark ? colors.surfaceDark : colors.surfaceLight}
                        borderColor={isDark ? colors.borderDark : colors.borderLight}
                        textColor={isDark ? colors.textMainDark : colors.textMainLight}
                        subColor={isDark ? colors.textSubDark : colors.textSubLight}
                        accentColor={colors.primary}
                    />
                </View>
            ) : null}

            {/* Exams List */}
            <FlatList
                data={exams}
                renderItem={renderExamCard}
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
                        icon="school"
                        title="No Exams"
                        message="No examinations have been scheduled yet"
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
    title: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    addButton: {
        padding: SPACING.sm,
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    examCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    examInfo: {
        flex: 1,
        gap: 4,
    },
    examName: {
        fontSize: FONT_SIZES.md,
        fontWeight: 'bold',
    },
    examType: {
        fontSize: FONT_SIZES.sm,
        fontStyle: 'italic',
    },
    dateRow: {
        marginTop: 4,
    },
    dateInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    dateText: {
        fontSize: FONT_SIZES.xs,
    },
    totalMarks: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
    },
    statusContainer: {
        alignItems: 'flex-end',
        gap: SPACING.xs,
    },
    statusBadge: {
        paddingHorizontal: SPACING.sm,
        paddingVertical: 4,
        borderRadius: 12,
    },
    statusText: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
    },
});
