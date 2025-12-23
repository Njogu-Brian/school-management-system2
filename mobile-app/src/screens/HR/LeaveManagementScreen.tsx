import React, { useState, useEffect } from 'react';
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
import { Button } from '@components/common/Button';
import { StatusBadge } from '@components/common/StatusBadge';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { hrApi } from '@api/hr.api';
import { LeaveApplication } from '../types/hr.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface LeaveManagementScreenProps {
    navigation: any;
}

export const LeaveManagementScreen: React.FC<LeaveManagementScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();

    const [leaveApplications, setLeaveApplications] = useState<LeaveApplication[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [filter, setFilter] = useState<'all' | 'pending' | 'approved' | 'rejected'>('all');

    const isAdmin = user?.role === 'admin' || user?.role === 'super_admin';

    useEffect(() => {
        loadLeaveApplications();
    }, [filter]);

    const loadLeaveApplications = async () => {
        try {
            setLoading(true);
            const filters: any = { per_page: 50 };

            if (filter !== 'all') {
                filters.status = filter;
            }

            if (!isAdmin) {
                filters.staff_id = user?.id;
            }

            const response = await hrApi.getLeaveApplications(filters);
            if (response.success && response.data) {
                setLeaveApplications(response.data.data);
            }
        } catch (error: any) {
            Alert.alert('Error', 'Failed to load leave applications');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    };

    const handleRefresh = () => {
        setRefreshing(true);
        loadLeaveApplications();
    };

    const handleApprove = async (id: number) => {
        try {
            await hrApi.approveLeave(id);
            Alert.alert('Success', 'Leave approved');
            loadLeaveApplications();
        } catch (error: any) {
            Alert.alert('Error', 'Failed to approve leave');
        }
    };

    const handleReject = async (id: number) => {
        try {
            await hrApi.rejectLeave(id);
            Alert.alert('Success', 'Leave rejected');
            loadLeaveApplications();
        } catch (error: any) {
            Alert.alert('Error', 'Failed to reject leave');
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved': return colors.success;
            case 'rejected': return colors.error;
            case 'pending': return '#f59e0b';
            default: return isDark ? colors.textSubDark : colors.textSubLight;
        }
    };

    const renderLeaveApplication = ({ item }: { item: LeaveApplication }) => (
        <Card>
            <View style={styles.leaveCard}>
                <View style={styles.leaveInfo}>
                    <View style={styles.headerRow}>
                        <Text style={[styles.staffName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {item.staff_name}
                        </Text>
                        <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) + '20' }]}>
                            <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
                                {formatters.capitalize(item.status)}
                            </Text>
                        </View>
                    </View>

                    <Text style={[styles.leaveType, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {item.leave_type_name}
                    </Text>

                    <View style={styles.dateRow}>
                        <Icon name="event" size={16} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.dateText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {formatters.formatDate(item.start_date)} - {formatters.formatDate(item.end_date)}
                        </Text>
                    </View>

                    <Text style={[styles.duration, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {item.days_count} day{item.days_count !== 1 ? 's' : ''}
                    </Text>

                    {item.reason && (
                        <Text style={[styles.reason, { color: isDark ? colors.textSubDark : colors.textSubLight }]} numberOfLines={2}>
                            {item.reason}
                        </Text>
                    )}

                    {isAdmin && item.status === 'pending' && (
                        <View style={styles.actionsRow}>
                            <Button
                                title="Approve"
                                onPress={() => handleApprove(item.id)}
                                variant="primary"
                                size="small"
                                style={styles.actionButton}
                            />
                            <Button
                                title="Reject"
                                onPress={() => handleReject(item.id)}
                                variant="outline"
                                size="small"
                                style={styles.actionButton}
                            />
                        </View>
                    )}
                </View>
            </View>
        </Card>
    );

    if (loading) {
        return (
            <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
                <LoadingState message="Loading leave applications..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Leave Management
                </Text>
                {!isAdmin && (
                    <TouchableOpacity onPress={() => navigation.navigate('ApplyLeave')}>
                        <Icon name="add" size={24} color={colors.primary} />
                    </TouchableOpacity>
                )}
            </View>

            <View style={styles.filterTabs}>
                {['all', 'pending', 'approved', 'rejected'].map((tab) => (
                    <TouchableOpacity
                        key={tab}
                        style={[styles.filterTab, filter === tab && { borderBottomColor: colors.primary, borderBottomWidth: 2 }]}
                        onPress={() => setFilter(tab as any)}
                    >
                        <Text style={[styles.filterText, { color: filter === tab ? colors.primary : isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {formatters.capitalize(tab)}
                        </Text>
                    </TouchableOpacity>
                ))}
            </View>

            <FlatList
                data={leaveApplications}
                renderItem={renderLeaveApplication}
                keyExtractor={(item) => item.id.toString()}
                contentContainerStyle={styles.listContent}
                refreshControl={<RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} tintColor={colors.primary} />}
                ListEmptyComponent={<EmptyState icon="event-busy" title="No Leave Applications" message="No leave applications found" />}
            />
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center', paddingHorizontal: SPACING.xl, paddingVertical: SPACING.md },
    title: { fontSize: FONT_SIZES.xxl, fontWeight: 'bold' },
    filterTabs: { flexDirection: 'row', paddingHorizontal: SPACING.xl, marginBottom: SPACING.md },
    filterTab: { flex: 1, paddingVertical: SPACING.sm, alignItems: 'center' },
    filterText: { fontSize: FONT_SIZES.sm, fontWeight: '600' },
    listContent: { paddingHorizontal: SPACING.xl, paddingBottom: SPACING.xl },
    leaveCard: { gap: SPACING.sm },
    leaveInfo: { gap: 6 },
    headerRow: { flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' },
    staffName: { fontSize: FONT_SIZES.md, fontWeight: 'bold', flex: 1 },
    statusBadge: { paddingHorizontal: SPACING.sm, paddingVertical: 4, borderRadius: 12 },
    statusText: { fontSize: FONT_SIZES.xs, fontWeight: '600' },
    leaveType: { fontSize: FONT_SIZES.sm, fontStyle: 'italic' },
    dateRow: { flexDirection: 'row', alignItems: 'center', gap: 4 },
    dateText: { fontSize: FONT_SIZES.xs },
    duration: { fontSize: FONT_SIZES.xs, fontWeight: '600' },
    reason: { fontSize: FONT_SIZES.sm, lineHeight: 18, marginTop: 4 },
    actionsRow: { flexDirection: 'row', gap: SPACING.sm, marginTop: SPACING.sm },
    actionButton: { flex: 1 },
});
