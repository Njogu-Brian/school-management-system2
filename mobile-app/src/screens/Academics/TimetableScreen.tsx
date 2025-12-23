import React, { useState, useEffect } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    SafeAreaView,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { academicsApi } from '@api/academics.api';
import { Timetable, TimetableSlot } from '../types/academics.types';
import { SPACING, FONT_SIZES } from '@constants/theme';

interface TimetableScreenProps {
    navigation: any;
}

const DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

export const TimetableScreen: React.FC<TimetableScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();

    const [timetable, setTimetable] = useState<Timetable | null>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [currentTerm, setCurrentTerm] = useState(1); // Should come from settings/context

    useEffect(() => {
        loadTimetable();
    }, [user]);

    const loadTimetable = async () => {
        try {
            setLoading(true);

            let response;
            if (user?.role === 'teacher' || user?.role === 'super_admin') {
                // Load teacher timetable
                response = await academicsApi.getTeacherTimetable(user.id, currentTerm);
            } else if (user?.role === 'student') {
                // Load student timetable
                response = await academicsApi.getStudentTimetable(user.id, currentTerm);
            } else if (user?.role === 'parent') {
                // For parents, we could load first child's timetable
                // For now, show message
                Alert.alert('Info', 'Please select a child to view their timetable');
                setLoading(false);
                return;
            }

            if (response?.success && response.data) {
                setTimetable(response.data);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load timetable');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const handleRefresh = () => {
        setRefreshing(true);
        loadTimetable();
    };

    const getSlotsByDay = (day: string): TimetableSlot[] => {
        if (!timetable?.slots) return [];
        return timetable.slots
            .filter((slot) => slot.day === day)
            .sort((a, b) => a.start_time.localeCompare(b.start_time));
    };

    const renderTimeSlot = (slot: TimetableSlot) => (
        <View key={slot.id} style={[styles.slotCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
            <View style={styles.timeContainer}>
                <Text style={[styles.time, { color: colors.primary }]}>
                    {slot.start_time} - {slot.end_time}
                </Text>
            </View>
            <View style={styles.slotInfo}>
                <Text style={[styles.subject, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    {slot.subject_name}
                </Text>
                {slot.teacher_name && (
                    <Text style={[styles.teacher, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {slot.teacher_name}
                    </Text>
                )}
                {slot.room && (
                    <Text style={[styles.room, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Room: {slot.room}
                    </Text>
                )}
            </View>
        </View>
    );

    const renderDay = (day: string) => {
        const slots = getSlotsByDay(day);

        return (
            <Card key={day} style={styles.dayCard}>
                <Text style={[styles.dayHeader, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    {day}
                </Text>
                {slots.length > 0 ? (
                    <View style={styles.slotsContainer}>
                        {slots.map(renderTimeSlot)}
                    </View>
                ) : (
                    <Text style={[styles.noClasses, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        No classes scheduled
                    </Text>
                )}
            </Card>
        );
    };

    if (loading) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading timetable..." />
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
                    My Timetable
                </Text>
                {timetable?.class_name && (
                    <Text style={[styles.subtitle, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {timetable.class_name}
                    </Text>
                )}
            </View>

            {/* Timetable */}
            <ScrollView
                contentContainerStyle={styles.content}
                refreshControl={
                    <RefreshControl
                        refreshing={refreshing}
                        onRefresh={handleRefresh}
                        colors={[colors.primary]}
                        tintColor={colors.primary}
                    />
                }
            >
                {DAYS.map(renderDay)}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: {
        flex: 1,
    },
    header: {
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
        borderBottomWidth: 1,
        borderBottomColor: '#e2e8f0',
    },
    title: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    subtitle: {
        fontSize: FONT_SIZES.sm,
        marginTop: 4,
    },
    content: {
        padding: SPACING.xl,
    },
    dayCard: {
        marginBottom: SPACING.md,
    },
    dayHeader: {
        fontSize: FONT_SIZES.lg,
        fontWeight: 'bold',
        marginBottom: SPACING.sm,
        paddingBottom: SPACING.xs,
        borderBottomWidth: 2,
        borderBottomColor: '#3b82f6',
    },
    slotsContainer: {
        gap: SPACING.sm,
    },
    slotCard: {
        flexDirection: 'row',
        padding: SPACING.sm,
        borderRadius: 8,
        borderLeftWidth: 4,
        borderLeftColor: '#3b82f6',
    },
    timeContainer: {
        width: 80,
        justifyContent: 'center',
    },
    time: {
        fontSize: FONT_SIZES.xs,
        fontWeight: '600',
    },
    slotInfo: {
        flex: 1,
        gap: 2,
    },
    subject: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    teacher: {
        fontSize: FONT_SIZES.xs,
    },
    room: {
        fontSize: FONT_SIZES.xs,
    },
    noClasses: {
        textAlign: 'center',
        paddingVertical: SPACING.md,
        fontStyle: 'italic',
    },
});
