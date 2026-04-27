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
    Modal,
    TextInput,
    KeyboardAvoidingView,
    Platform,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { Button } from '@components/common/Button';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { hrApi } from '@api/hr.api';
import type { LeaveApplication } from 'types/hr.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { canAccessLeaveManagement, canApproveLeaveRequests } from '@utils/staffHrAccess';
import { layoutStyles } from '@styles/common';
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
    const [rejectVisible, setRejectVisible] = useState(false);
    const [rejectTargetId, setRejectTargetId] = useState<number | null>(null);
    const [rejectReason, setRejectReason] = useState('');

    const canApprove = canApproveLeaveRequests(user);
    const showApply = canAccessLeaveManagement(user);

    useEffect(() => {
        loadLeaveApplications();
    }, [filter]);

    const loadLeaveApplications = async () => {
        try {
            setLoading(true);
            const filters: Record<string, string | number> = { per_page: 50 };
            if (filter !== 'all') {
                filters.status = filter;
            }
            // Scoping is enforced server-side; do not send user.id as staff_id (breaks HR views).

            const response = await hrApi.getLeaveApplications(filters);
            if (response.success && response.data) {
                setLeaveApplications(response.data.data);
            }
        } catch {
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
            const res = await hrApi.approveLeave(id);
            if (res.success) {
                Alert.alert('Success', 'Leave approved');
                loadLeaveApplications();
            } else {
                Alert.alert('Error', (res as { message?: string }).message || 'Failed to approve');
            }
        } catch (e: any) {
            const msg = e?.response?.data?.message || e?.message || 'Failed to approve leave';
            Alert.alert('Error', typeof msg === 'string' ? msg : 'Failed to approve leave');
        }
    };

    const openReject = (id: number) => {
        setRejectTargetId(id);
        setRejectReason('');
        setRejectVisible(true);
    };

    const confirmReject = async () => {
        if (rejectTargetId == null) return;
        const trimmed = rejectReason.trim();
        if (!trimmed) {
            Alert.alert('Validation', 'Enter a rejection reason.');
            return;
        }
        try {
            const res = await hrApi.rejectLeave(rejectTargetId, trimmed);
            if (res.success) {
                setRejectVisible(false);
                setRejectTargetId(null);
                Alert.alert('Success', 'Leave rejected');
                loadLeaveApplications();
            } else {
                Alert.alert('Error', (res as { message?: string }).message || 'Failed to reject');
            }
        } catch (e: any) {
            const msg = e?.response?.data?.message || e?.message || 'Failed to reject leave';
            Alert.alert('Error', typeof msg === 'string' ? msg : 'Failed to reject leave');
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved':
                return colors.success;
            case 'rejected':
                return colors.error;
            case 'pending':
                return colors.warning;
            default:
                return isDark ? colors.textSubDark : colors.textSubLight;
        }
    };

    const days = (item: LeaveApplication) => item.days_count ?? item.days;
    const leaveLabel = (item: LeaveApplication) => item.leave_type_name ?? item.leave_type ?? 'Leave';

    const renderLeaveApplication = ({ item }: { item: LeaveApplication }) => (
        <Card>
            <View style={styles.leaveCard}>
                <View style={styles.leaveInfo}>
                    <View style={styles.headerRow}>
                        <Text style={[styles.staffName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                            {item.staff_name ?? '—'}
                        </Text>
                        <View style={[styles.statusBadge, { backgroundColor: getStatusColor(item.status) + '20' }]}>
                            <Text style={[styles.statusText, { color: getStatusColor(item.status) }]}>
                                {formatters.capitalize(item.status)}
                            </Text>
                        </View>
                    </View>

                    <Text style={[styles.leaveType, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {leaveLabel(item)}
                    </Text>

                    <View style={styles.dateRow}>
                        <Icon name="event" size={16} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.dateText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {formatters.formatDate(item.start_date)} - {formatters.formatDate(item.end_date)}
                        </Text>
                    </View>

                    <Text style={[styles.duration, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                        {days(item)} day{days(item) !== 1 ? 's' : ''}
                    </Text>

                    {item.reason ? (
                        <Text
                            style={[styles.reason, { color: isDark ? colors.textSubDark : colors.textSubLight }]}
                            numberOfLines={2}
                        >
                            {item.reason}
                        </Text>
                    ) : null}

                    {canApprove && item.status === 'pending' && (
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
                                onPress={() => openReject(item.id)}
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
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading leave applications..." />
            </SafeAreaView>
        );
    }

    const mainText = isDark ? colors.textMainDark : colors.textMainLight;
    const subText = isDark ? colors.textSubDark : colors.textSubLight;
    const border = isDark ? colors.borderDark : colors.borderLight;
    const showBack = navigation.canGoBack?.() ?? false;

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}>
            <View style={styles.header}>
                {showBack ? (
                    <TouchableOpacity onPress={() => navigation.goBack()} style={styles.backBtn}>
                        <Icon name="arrow-back" size={24} color={mainText} />
                    </TouchableOpacity>
                ) : (
                    <View style={styles.backBtnPlaceholder} />
                )}
                <Text style={[styles.title, { color: mainText, flex: 1 }]}>Leave & apply</Text>
                {showApply ? (
                    <TouchableOpacity onPress={() => navigation.navigate('ApplyLeave')} hitSlop={{ top: 8, bottom: 8, left: 8, right: 8 }}>
                        <Icon name="add" size={26} color={colors.primary} />
                    </TouchableOpacity>
                ) : (
                    <View style={{ width: 26 }} />
                )}
            </View>

            <View style={styles.filterTabs}>
                {(['all', 'pending', 'approved', 'rejected'] as const).map((tab) => (
                    <TouchableOpacity
                        key={tab}
                        style={[styles.filterTab, filter === tab && { borderBottomColor: colors.primary, borderBottomWidth: 2 }]}
                        onPress={() => setFilter(tab)}
                    >
                        <Text
                            style={[
                                styles.filterText,
                                { color: filter === tab ? colors.primary : subText },
                            ]}
                        >
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
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={handleRefresh} colors={[colors.primary]} tintColor={colors.primary} />
                }
                ListEmptyComponent={<EmptyState icon="event-busy" title="No leave requests" message="Nothing to show for this filter." />}
            />

            <Modal visible={rejectVisible} transparent animationType="fade" onRequestClose={() => setRejectVisible(false)}>
                <KeyboardAvoidingView
                    behavior={Platform.OS === 'ios' ? 'padding' : undefined}
                    style={[styles.modalOverlay, { backgroundColor: 'rgba(0,0,0,0.5)' }]}
                >
                    <View style={[styles.modalCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight, borderColor: border }]}>
                        <Text style={[styles.modalTitle, { color: mainText }]}>Reject leave</Text>
                        <Text style={[styles.modalHint, { color: subText }]}>Reason is required and visible on the record.</Text>
                        <TextInput
                            style={[styles.rejectInput, { color: mainText, borderColor: border }]}
                            value={rejectReason}
                            onChangeText={setRejectReason}
                            placeholder="Reason for rejection"
                            placeholderTextColor={subText}
                            multiline
                        />
                        <View style={styles.modalActions}>
                            <Button title="Cancel" variant="outline" onPress={() => setRejectVisible(false)} style={{ flex: 1 }} />
                            <Button title="Reject" variant="primary" onPress={confirmReject} style={{ flex: 1 }} />
                        </View>
                    </View>
                </KeyboardAvoidingView>
            </Modal>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.md,
    },
    backBtn: { marginRight: SPACING.sm },
    backBtnPlaceholder: { width: 24, marginRight: SPACING.sm },
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
    modalOverlay: { flex: 1, justifyContent: 'center', padding: SPACING.xl },
    modalCard: {
        borderRadius: 12,
        borderWidth: 1,
        padding: SPACING.lg,
        marginTop: 'auto',
        marginBottom: 'auto',
    },
    modalTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700', marginBottom: SPACING.xs },
    modalHint: { fontSize: FONT_SIZES.xs, marginBottom: SPACING.md },
    rejectInput: {
        borderWidth: 1,
        borderRadius: 8,
        minHeight: 88,
        padding: SPACING.md,
        textAlignVertical: 'top',
        marginBottom: SPACING.md,
    },
    modalActions: { flexDirection: 'row', gap: SPACING.sm },
});
