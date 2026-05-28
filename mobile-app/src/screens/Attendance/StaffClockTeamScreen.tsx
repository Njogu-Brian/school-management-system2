import React, { useCallback, useEffect, useState } from 'react';
import { Alert, ScrollView, StyleSheet, Text, TouchableOpacity, View } from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { Card } from '@components/common/Card';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { UserRole } from '@constants/roles';
import { normalizeRole } from '@utils/roleUtils';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { StaffClockHistoryItem, StaffClockRosterItem, staffClockApi } from '@api/staffClock.api';
import { LoadingState } from '@components/common/EmptyState';

export const StaffClockTeamScreen: React.FC = () => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const role = user?.role ? normalizeRole(user.role) : '';
    const isAdmin = role === UserRole.ADMIN || role === UserRole.SUPER_ADMIN;

    const [loading, setLoading] = useState(true);
    const [roster, setRoster] = useState<StaffClockRosterItem[]>([]);
    const [selectedStaffId, setSelectedStaffId] = useState<number | null>(null);
    const [selectedName, setSelectedName] = useState('');
    const [history, setHistory] = useState<StaffClockHistoryItem[]>([]);

    const loadRoster = useCallback(async () => {
        setLoading(true);
        try {
            const res = await staffClockApi.getClockRoster();
            if (res.success && res.data) {
                setRoster(res.data);
                if (res.data.length > 0 && !selectedStaffId) {
                    setSelectedStaffId(res.data[0].id);
                    setSelectedName(res.data[0].full_name);
                }
            } else {
                setRoster([]);
            }
        } catch (error: any) {
            Alert.alert('Error', error?.message || 'Could not load staff list.');
        } finally {
            setLoading(false);
        }
    }, [selectedStaffId]);

    const loadHistory = useCallback(async (staffId: number) => {
        try {
            const res = await staffClockApi.getStaffClockHistory(staffId, 90);
            if (res.success && res.data) {
                setHistory(res.data.history ?? []);
                if (res.data.staff?.full_name) {
                    setSelectedName(res.data.staff.full_name);
                }
            } else {
                setHistory([]);
            }
        } catch (error: any) {
            Alert.alert('Error', error?.message || 'Could not load clock history.');
            setHistory([]);
        }
    }, []);

    useEffect(() => {
        void loadRoster();
    }, [loadRoster]);

    useEffect(() => {
        if (selectedStaffId) {
            void loadHistory(selectedStaffId);
        }
    }, [selectedStaffId, loadHistory]);

    const title = isAdmin ? 'All staff clock history' : 'Team clock history';

    return (
        <SafeAreaView
            edges={['bottom']}
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            <ScrollView contentContainerStyle={styles.content}>
                <Card style={styles.section}>
                    <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {title}
                    </Text>
                    <Text style={[styles.desc, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        Select a staff member to view their clock-in and clock-out records (last 90 days).
                    </Text>
                    {loading ? (
                        <LoadingState message="Loading staff..." />
                    ) : roster.length === 0 ? (
                        <Text style={[styles.desc, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            No staff available for your access level.
                        </Text>
                    ) : (
                        <View style={styles.rosterList}>
                            {roster.map((member) => {
                                const active = selectedStaffId === member.id;
                                return (
                                    <TouchableOpacity
                                        key={member.id}
                                        onPress={() => {
                                            setSelectedStaffId(member.id);
                                            setSelectedName(member.full_name);
                                        }}
                                        style={[
                                            styles.rosterChip,
                                            {
                                                backgroundColor: active ? colors.primary : 'transparent',
                                                borderColor: active ? colors.primary : isDark ? colors.borderDark : colors.borderLight,
                                            },
                                        ]}
                                    >
                                        <Text style={{ color: active ? '#fff' : isDark ? colors.textMainDark : colors.textMainLight }}>
                                            {member.full_name}
                                        </Text>
                                    </TouchableOpacity>
                                );
                            })}
                        </View>
                    )}
                </Card>

                {selectedStaffId ? (
                    <Card style={styles.section}>
                        <Text style={[styles.sectionTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {selectedName || 'Clock history'}
                        </Text>
                        {history.length === 0 ? (
                            <Text style={[styles.desc, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                No clock records found.
                            </Text>
                        ) : (
                            history.map((item) => (
                                <View key={item.id} style={styles.historyRow}>
                                    <Text style={[styles.historyDate, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                        {item.date || '--'}
                                    </Text>
                                    <Text style={[styles.historyMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                        In: {item.check_in_time || '--'}
                                        {item.check_in_distance_meters != null ? ` (${item.check_in_distance_meters}m)` : ''}
                                    </Text>
                                    <Text style={[styles.historyMeta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                        Out: {item.check_out_time || '--'}
                                        {item.check_out_distance_meters != null ? ` (${item.check_out_distance_meters}m)` : ''}
                                    </Text>
                                </View>
                            ))
                        )}
                    </Card>
                ) : null}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    content: { padding: SPACING.lg, paddingBottom: SPACING.xxl },
    section: { marginBottom: SPACING.md },
    sectionTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', marginBottom: SPACING.sm },
    desc: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.md },
    rosterList: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING.xs },
    rosterChip: {
        paddingHorizontal: SPACING.sm,
        paddingVertical: 6,
        borderRadius: 20,
        borderWidth: 1,
    },
    historyRow: {
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
        borderBottomColor: '#9993',
    },
    historyDate: { fontSize: FONT_SIZES.sm, fontWeight: '700' },
    historyMeta: { fontSize: FONT_SIZES.xs, marginTop: 2 },
});
