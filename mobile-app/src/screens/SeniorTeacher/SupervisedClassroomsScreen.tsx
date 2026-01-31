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
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { seniorTeacherApi, SupervisedClassroom } from '@api/seniorTeacher.api';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface SupervisedClassroomsScreenProps {
    navigation: any;
}

export const SupervisedClassroomsScreen: React.FC<SupervisedClassroomsScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const [classrooms, setClassrooms] = useState<SupervisedClassroom[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const load = useCallback(async () => {
        try {
            setLoading(true);
            const response = await seniorTeacherApi.getSupervisedClassrooms();
            if (response.success && response.data) {
                const data = response.data as any;
                setClassrooms(Array.isArray(data) ? data : data?.data ?? []);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load supervised classrooms');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        load();
    }, []);

    const handleRefresh = () => {
        setRefreshing(true);
        load();
    };

    if (loading && !refreshing) {
        return <LoadingState message="Loading supervised classrooms..." />;
    }

    const list = Array.isArray(classrooms) ? classrooms : [];

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                    <Icon name="arrow-back" size={24} color={isDark ? colors.textMainDark : colors.textMainLight} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Supervised Classrooms
                </Text>
            </View>
            {list.length === 0 ? (
                <EmptyState
                    icon="class"
                    title="No supervised classrooms"
                    message="Classrooms you supervise will appear here."
                />
            ) : (
                <FlatList
                    data={list}
                    keyExtractor={(item) => String(item.id)}
                    contentContainerStyle={styles.list}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} />
                    }
                    renderItem={({ item }) => (
                        <Card>
                            <View style={styles.row}>
                                <View style={styles.info}>
                                    <Text style={[styles.name, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {item.name}
                                    </Text>
                                    {(item.grade_level || item.stream) && (
                                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {[item.grade_level, item.stream].filter(Boolean).join(' • ')}
                                        </Text>
                                    )}
                                    {item.student_count != null && (
                                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                            {item.student_count} students
                                        </Text>
                                    )}
                                    {item.teacher_name && (
                                        <Text style={[styles.meta, { color: colors.primary }]}>{item.teacher_name}</Text>
                                    )}
                                </View>
                                <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            </View>
                        </Card>
                    )}
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
    list: { padding: SPACING.md, paddingBottom: SPACING.xxl },
    row: { flexDirection: 'row', alignItems: 'center', justifyContent: 'space-between' },
    info: { flex: 1 },
    name: { fontSize: FONT_SIZES.lg, fontWeight: '600', marginBottom: 4 },
    meta: { fontSize: FONT_SIZES.sm, marginBottom: 2 },
});
