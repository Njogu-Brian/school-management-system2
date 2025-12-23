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
import { Exam } from '../types/academics.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
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

    const fetchExams = useCallback(async () => {
        try {
            setLoading(true);

            const response = await academicsApi.getExams({
                per_page: 50,
            });

            if (response.success && response.data) {
                setExams(response.data.data);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load exams');
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
        // Teachers can enter marks, students/parents can view marks
        if (user?.role === 'teacher' || user?.role === 'admin' || user?.role === 'super_admin') {
            navigation.navigate('ExamDetail', { examId: exam.id });
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
                return '#10b981';
            default:
                return isDark ? colors.textSubDark : colors.textSubLight;
        }
    };

    const renderExamCard = ({ item }: { item: Exam }) => (
        <Card onPress={() => handleExamPress(item)}>
            <View style={styles.examCard}>
                <View style={styles.examInfo}>
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

                    {item.total_marks && (
                        <Text style={[styles.totalMarks, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Total Marks: {item.total_marks}
                        </Text>
                    )}
                </View>

                <View style={styles.statusContainer}>
                    <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) + '20' }]}>
                        <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
                            {formatters.capitalize(item.status)}
                        </Text>
                    </View>
                    <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                </View>
            </View>
        </Card>
    );

    if (loading) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading exams..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
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
