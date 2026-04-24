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
    ScrollView,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { isTeacherRole } from '@utils/roleUtils';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { StatusBadge } from '@components/common/StatusBadge';
import { Input } from '@components/common/Input';
import { Button } from '@components/common/Button';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { studentsApi } from '@api/students.api';
import { Student, StudentFilters, Class, Stream } from '@types/student.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { BRAND, SCREEN } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StudentsListScreenProps {
    navigation: any;
    route?: { params?: { title?: string; hint?: string } };
}

type ScopeType = 'school' | 'class' | 'stream' | 'search';

// Filter-first pattern: avoid listing the whole school on open.
// User picks a scope, then results load only after pressing Apply.
export const StudentsListScreen: React.FC<StudentsListScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const hideAdminActions = user?.role ? isTeacherRole(user.role) : false;

    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const surface = isDark ? colors.surfaceDark : BRAND.surface;
    const border = isDark ? colors.borderDark : BRAND.border;

    const [scope, setScope] = useState<ScopeType>('school');
    const [selectedClassId, setSelectedClassId] = useState<number | null>(null);
    const [selectedStreamId, setSelectedStreamId] = useState<number | null>(null);
    const [searchQuery, setSearchQuery] = useState('');

    const [classes, setClasses] = useState<Class[]>([]);
    const [streams, setStreams] = useState<Stream[]>([]);

    const [students, setStudents] = useState<Student[]>([]);
    const [loading, setLoading] = useState(false);
    const [refreshing, setRefreshing] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);
    const [applied, setApplied] = useState(false);

    // Load classes list once for the picker.
    useEffect(() => {
        (async () => {
            try {
                const res = await studentsApi.getClasses();
                if (res.success && res.data) setClasses(res.data);
            } catch {
                /* non-fatal */
            }
        })();
    }, []);

    // Load streams whenever class selection changes.
    useEffect(() => {
        if (!selectedClassId) {
            setStreams([]);
            setSelectedStreamId(null);
            return;
        }
        (async () => {
            try {
                const res = await studentsApi.getStreams(selectedClassId);
                if (res.success && res.data) setStreams(res.data);
            } catch {
                setStreams([]);
            }
        })();
    }, [selectedClassId]);

    const buildFilters = useCallback((): StudentFilters => {
        const f: StudentFilters = { per_page: 20 };
        if (scope === 'class' && selectedClassId) f.class_id = selectedClassId;
        if (scope === 'stream' && selectedStreamId) {
            f.stream_id = selectedStreamId;
            if (selectedClassId) f.class_id = selectedClassId;
        }
        if (scope === 'search' && searchQuery.trim()) f.search = searchQuery.trim();
        return f;
    }, [scope, selectedClassId, selectedStreamId, searchQuery]);

    const fetchStudents = useCallback(
        async (pageNum: number = 1) => {
            try {
                if (pageNum === 1) setLoading(true);
                const response = await studentsApi.getStudents({
                    ...buildFilters(),
                    page: pageNum,
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
                Alert.alert('Error', error?.message || 'Failed to load students');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [buildFilters]
    );

    const handleApply = () => {
        if (scope === 'class' && !selectedClassId) {
            Alert.alert('Pick a class', 'Select a class first.');
            return;
        }
        if (scope === 'stream' && !selectedStreamId) {
            Alert.alert('Pick a stream', 'Select a class and stream first.');
            return;
        }
        if (scope === 'search' && !searchQuery.trim()) {
            Alert.alert('Search term', 'Type at least one character.');
            return;
        }
        setApplied(true);
        setPage(1);
        fetchStudents(1);
    };

    const handleReset = () => {
        setApplied(false);
        setStudents([]);
        setSearchQuery('');
        setSelectedClassId(null);
        setSelectedStreamId(null);
    };

    const handleRefresh = () => {
        if (!applied) return;
        setRefreshing(true);
        fetchStudents(1);
    };

    const handleLoadMore = () => {
        if (!loading && hasMore && applied) {
            fetchStudents(page + 1);
        }
    };

    const renderStudentCard = ({ item }: { item: Student }) => (
        <Card onPress={() => navigation.navigate('StudentDetail', { studentId: item.id })}>
            <View style={styles.studentCard}>
                <Avatar name={item.full_name} imageUrl={item.avatar} size={50} />
                <View style={styles.studentInfo}>
                    <Text style={[styles.studentName, { color: textMain }]}>{item.full_name}</Text>
                    <View style={styles.row}>
                        <Icon name="badge" size={14} color={textSub} />
                        <Text style={[styles.admissionNumber, { color: textSub }]}>{item.admission_number}</Text>
                    </View>
                    <View style={styles.row}>
                        <Icon name="school" size={14} color={textSub} />
                        <Text style={[styles.className, { color: textSub }]}>
                            {item.class_name} {item.stream_name && `- ${item.stream_name}`}
                        </Text>
                    </View>
                </View>
                <View style={styles.statusContainer}>
                    <StatusBadge status={item.status} />
                    <Icon name="chevron-right" size={24} color={textSub} />
                </View>
            </View>
        </Card>
    );

    const scopeTab = (id: ScopeType, label: string, icon: string) => {
        const active = scope === id;
        return (
            <TouchableOpacity
                key={id}
                onPress={() => setScope(id)}
                style={[
                    styles.scopeTab,
                    {
                        backgroundColor: active ? colors.primary : surface,
                        borderColor: active ? colors.primary : border,
                    },
                ]}
            >
                <Icon name={icon} size={16} color={active ? '#fff' : textMain} />
                <Text style={[styles.scopeTabText, { color: active ? '#fff' : textMain }]}>{label}</Text>
            </TouchableOpacity>
        );
    };

    const renderFilterCard = () => (
        <View style={[styles.filterCard, { backgroundColor: surface, borderColor: border }]}>
            <Text style={[styles.filterTitle, { color: textMain }]}>Choose what to load</Text>
            <Text style={[styles.filterHint, { color: textSub }]}>
                The list stays empty until you apply a filter, so the screen opens instantly.
            </Text>

            <View style={styles.scopeRow}>
                {scopeTab('school', 'Whole school', 'school')}
                {scopeTab('class', 'By class', 'class')}
                {scopeTab('stream', 'By stream', 'groups')}
                {scopeTab('search', 'Search', 'search')}
            </View>

            {scope === 'class' || scope === 'stream' ? (
                <View style={styles.pickerBlock}>
                    <Text style={[styles.label, { color: textSub }]}>Class</Text>
                    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                        {classes.map((c) => {
                            const active = selectedClassId === c.id;
                            return (
                                <TouchableOpacity
                                    key={c.id}
                                    onPress={() => setSelectedClassId(c.id)}
                                    style={[
                                        styles.chip,
                                        {
                                            backgroundColor: active ? colors.primary : surface,
                                            borderColor: active ? colors.primary : border,
                                        },
                                    ]}
                                >
                                    <Text style={{ color: active ? '#fff' : textMain }}>{c.name}</Text>
                                </TouchableOpacity>
                            );
                        })}
                    </ScrollView>
                </View>
            ) : null}

            {scope === 'stream' && selectedClassId ? (
                <View style={styles.pickerBlock}>
                    <Text style={[styles.label, { color: textSub }]}>Stream</Text>
                    <ScrollView horizontal showsHorizontalScrollIndicator={false} contentContainerStyle={styles.chipRow}>
                        {streams.map((st) => {
                            const active = selectedStreamId === st.id;
                            return (
                                <TouchableOpacity
                                    key={st.id}
                                    onPress={() => setSelectedStreamId(st.id)}
                                    style={[
                                        styles.chip,
                                        {
                                            backgroundColor: active ? colors.primary : surface,
                                            borderColor: active ? colors.primary : border,
                                        },
                                    ]}
                                >
                                    <Text style={{ color: active ? '#fff' : textMain }}>{st.name}</Text>
                                </TouchableOpacity>
                            );
                        })}
                    </ScrollView>
                </View>
            ) : null}

            {scope === 'search' ? (
                <View style={styles.pickerBlock}>
                    <Text style={[styles.label, { color: textSub }]}>Name or admission number</Text>
                    <Input
                        placeholder="e.g. Mary Wanjiku or ADM-0123"
                        value={searchQuery}
                        onChangeText={setSearchQuery}
                        icon="search"
                        containerStyle={{ marginBottom: 0 }}
                    />
                </View>
            ) : null}

            <View style={styles.actionsRow}>
                <Button title="Apply" onPress={handleApply} style={{ flex: 1 }} />
                {applied ? (
                    <Button title="Reset" onPress={handleReset} variant="outline" style={{ flex: 1 }} />
                ) : null}
                {!hideAdminActions ? (
                    <TouchableOpacity
                        style={[styles.addButton, { backgroundColor: surface, borderColor: border }]}
                        onPress={() => navigation.navigate('AddStudent')}
                    >
                        <Icon name="add" size={22} color={colors.primary} />
                    </TouchableOpacity>
                ) : null}
            </View>
        </View>
    );

    return (
        <SafeAreaView
            style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : BRAND.bg }]}
        >
            <FlatList
                data={applied ? students : []}
                renderItem={renderStudentCard}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={{ paddingHorizontal: SCREEN.paddingHorizontal, paddingBottom: SPACING.xl }}
                ListHeaderComponent={renderFilterCard}
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
                    !applied ? (
                        <View style={{ paddingVertical: SPACING.xl }}>
                            <EmptyState
                                icon="filter-list"
                                title="Pick a filter"
                                message="Choose a scope above and tap Apply to load students."
                            />
                        </View>
                    ) : loading ? (
                        <LoadingState message="Loading students..." />
                    ) : (
                        <EmptyState
                            icon="school"
                            title="No students found"
                            message="No students match your filter."
                        />
                    )
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
    container: { flex: 1 },
    filterCard: {
        padding: SPACING.md,
        borderRadius: 12,
        borderWidth: 1,
        marginTop: SPACING.md,
        marginBottom: SPACING.md,
        gap: SPACING.sm,
    },
    filterTitle: {
        fontSize: FONT_SIZES.md,
        fontWeight: '700',
    },
    filterHint: {
        fontSize: FONT_SIZES.xs,
    },
    scopeRow: {
        flexDirection: 'row',
        flexWrap: 'wrap',
        gap: SPACING.xs,
        marginTop: SPACING.sm,
    },
    scopeTab: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
        paddingHorizontal: SPACING.sm,
        paddingVertical: 6,
        borderRadius: 20,
        borderWidth: 1,
    },
    scopeTabText: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
    },
    pickerBlock: {
        marginTop: SPACING.sm,
        gap: 4,
    },
    label: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
        marginBottom: 4,
    },
    chipRow: {
        gap: SPACING.xs,
        paddingVertical: 2,
        paddingRight: SPACING.md,
    },
    chip: {
        paddingHorizontal: SPACING.md,
        paddingVertical: 6,
        borderRadius: 20,
        borderWidth: 1,
    },
    actionsRow: {
        flexDirection: 'row',
        gap: SPACING.sm,
        marginTop: SPACING.md,
        alignItems: 'center',
    },
    addButton: {
        width: 44,
        height: 44,
        borderRadius: 10,
        borderWidth: 1,
        alignItems: 'center',
        justifyContent: 'center',
    },
    studentCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    studentInfo: { flex: 1, gap: 4 },
    studentName: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    row: { flexDirection: 'row', alignItems: 'center', gap: 4 },
    admissionNumber: { fontSize: FONT_SIZES.xs },
    className: { fontSize: FONT_SIZES.xs },
    statusContainer: { alignItems: 'flex-end', gap: SPACING.xs },
    footer: { paddingVertical: SPACING.lg },
});
