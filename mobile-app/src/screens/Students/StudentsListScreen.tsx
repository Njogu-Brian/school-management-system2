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
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { StatusBadge } from '@components/common/StatusBadge';
import { Input } from '@components/common/Input';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { studentsApi } from '@api/students.api';
import { Student, StudentFilters } from '@types/student.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StudentsListScreenProps {
    navigation: any;
}

export const StudentsListScreen: React.FC<StudentsListScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [students, setStudents] = useState<Student[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [filters, setFilters] = useState<StudentFilters>({});
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

    // Fetch students
    const fetchStudents = useCallback(
        async (pageNum: number = 1, search?: string) => {
            try {
                if (pageNum === 1) {
                    setLoading(true);
                }

                const response = await studentsApi.getStudents({
                    ...filters,
                    search: search || searchQuery,
                    page: pageNum,
                    per_page: 20,
                });

                if (response.success && response.data) {
                    if (pageNum === 1) {
                        setStudents(response.data.data);
                    } else {
                        setStudents((prev) => [...prev, ...response.data.data]);
                    }

                    setHasMore(response.data.current_page < response.data.last_page);
                    setPage(pageNum);
                }
            } catch (error: any) {
                Alert.alert('Error', error.message || 'Failed to load students');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [filters, searchQuery]
    );

    // Initial load
    useEffect(() => {
        fetchStudents(1);
    }, []);

    // Search with debounce
    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchQuery !== undefined) {
                fetchStudents(1, searchQuery);
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [searchQuery]);

    // Refresh
    const handleRefresh = () => {
        setRefreshing(true);
        fetchStudents(1);
    };

    // Load more
    const handleLoadMore = () => {
        if (!loading && hasMore) {
            fetchStudents(page + 1);
        }
    };

    // Navigate to student detail
    const handleStudentPress = (student: Student) => {
        navigation.navigate('StudentDetail', { studentId: student.id });
    };

    // Render student card
    const renderStudentCard = ({ item }: { item: Student }) => (
        <Card onPress={() => handleStudentPress(item)}>
            <View style={styles.studentCard}>
                <Avatar name={item.full_name} imageUrl={item.avatar} size={50} />

                <View style={styles.studentInfo}>
                    <Text style={[styles.studentName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {item.full_name}
                    </Text>
                    <View style={styles.row}>
                        <Icon name="badge" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.admissionNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.admission_number}
                        </Text>
                    </View>
                    <View style={styles.row}>
                        <Icon name="school" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.className, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.class_name} {item.stream_name && `- ${item.stream_name}`}
                        </Text>
                    </View>
                </View>

                <View style={styles.statusContainer}>
                    <StatusBadge status={item.status} />
                    <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                </View>
            </View>
        </Card>
    );

    // Show loading state
    if (loading && page === 1) {
        return (
            <SafeAreaView
                style={[
                    styles.container,
                    { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
                ]}
            >
                <LoadingState message="Loading students..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[
                styles.container,
                { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
            ]}
        >
            {/* Header */}
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Students
                </Text>
                <TouchableOpacity
                    style={styles.addButton}
                    onPress={() => navigation.navigate('AddStudent')}
                >
                    <Icon name="add" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            {/* Search Bar */}
            <View style={styles.searchContainer}>
                <Input
                    placeholder="Search students..."
                    value={searchQuery}
                    onChangeText={setSearchQuery}
                    icon="search"
                    containerStyle={styles.searchInput}
                />
                <TouchableOpacity
                    style={[
                        styles.filterButton,
                        { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight },
                    ]}
                    onPress={() => {
                        // TODO: Open filter modal
                        Alert.alert('Filters', 'Filter modal coming soon');
                    }}
                >
                    <Icon name="filter-list" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            {/* Students List */}
            <FlatList
                data={students}
                renderItem={renderStudentCard}
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
                onEndReached={handleLoadMore}
                onEndReachedThreshold={0.5}
                ListEmptyComponent={
                    <EmptyState
                        icon="school"
                        title="No Students Found"
                        message="No students match your search criteria"
                    />
                }
                ListFooterComponent={
                    loading && page > 1 ? (
                        <View style={styles.footer}>
                            <LoadingState message="Loading more..." />
                        </View>
                    ) : null
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
    searchContainer: {
        flexDirection: 'row',
        paddingHorizontal: SPACING.xl,
        marginBottom: SPACING.md,
        gap: SPACING.sm,
    },
    searchInput: {
        flex: 1,
        marginBottom: 0,
    },
    filterButton: {
        width: 48,
        height: 48,
        borderRadius: 12,
        alignItems: 'center',
        justifyContent: 'center',
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    studentCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    studentInfo: {
        flex: 1,
        gap: 4,
    },
    studentName: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    row: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    admissionNumber: {
        fontSize: FONT_SIZES.xs,
    },
    className: {
        fontSize: FONT_SIZES.xs,
    },
    statusContainer: {
        alignItems: 'flex-end',
        gap: SPACING.xs,
    },
    footer: {
        paddingVertical: SPACING.lg,
    },
});
