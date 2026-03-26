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
    Linking,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { Card } from '@components/common/Card';
import { Avatar } from '@components/common/Avatar';
import { StatusBadge } from '@components/common/StatusBadge';
import { Input } from '@components/common/Input';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { hrApi } from '@api/hr.api';
import { Staff } from '@types/hr.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { WEB_BASE_URL } from '@utils/env';
import { canManageStaff, canViewPayrollRecords } from '@utils/staffHrAccess';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface StaffDirectoryScreenProps {
    navigation: any;
}

export const StaffDirectoryScreen: React.FC<StaffDirectoryScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const manage = canManageStaff(user);
    const showPayroll = canViewPayrollRecords(user);
    const portal = (WEB_BASE_URL || '').replace(/\/$/, '');

    const [staff, setStaff] = useState<Staff[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

    const fetchStaff = useCallback(
        async (pageNum: number = 1, search?: string) => {
            try {
                if (pageNum === 1) {
                    setLoading(true);
                }

                const response = await hrApi.getStaff({
                    search: search || searchQuery,
                    page: pageNum,
                    per_page: 20,
                });

                if (response.success && response.data) {
                    if (pageNum === 1) {
                        setStaff(response.data.data);
                    } else {
                        setStaff((prev) => [...prev, ...response.data.data]);
                    }

                    setHasMore(response.data.current_page < response.data.last_page);
                    setPage(pageNum);
                }
            } catch (error: any) {
                Alert.alert('Error', error.message || 'Failed to load staff');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [searchQuery]
    );

    useEffect(() => {
        fetchStaff(1);
    }, []);

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchQuery !== undefined) {
                fetchStaff(1, searchQuery);
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [searchQuery]);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchStaff(1);
    };

    const handleLoadMore = () => {
        if (!loading && hasMore) {
            fetchStaff(page + 1);
        }
    };

    const handleStaffPress = (staffMember: Staff) => {
        navigation.navigate('StaffDetail', { staffId: staffMember.id });
    };

    const renderStaffCard = ({ item }: { item: Staff }) => (
        <Card onPress={() => handleStaffPress(item)}>
            <View style={styles.staffCard}>
                <Avatar name={item.full_name} imageUrl={item.avatar} size={50} />

                <View style={styles.staffInfo}>
                    <Text style={[styles.staffName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {item.full_name}
                    </Text>
                    <View style={styles.row}>
                        <Icon name="badge" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.employeeNumber, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.employee_number}
                        </Text>
                    </View>
                    <View style={styles.row}>
                        <Icon name="work" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.designation, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.designation || item.role}
                        </Text>
                    </View>
                    {item.department && (
                        <View style={styles.row}>
                            <Icon name="business" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.department, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {item.department}
                            </Text>
                        </View>
                    )}
                </View>

                <View style={styles.statusContainer}>
                    <StatusBadge status={item.status} />
                    <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                </View>
            </View>
        </Card>
    );

    if (loading && page === 1) {
        return (
            <SafeAreaView
                style={[
                    layoutStyles.flex1,
                    styles.container,
                    { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
                ]}
            >
                <LoadingState message="Loading staff..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[
                layoutStyles.flex1,
                styles.container,
                { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
            ]}
        >
            {/* Header */}
            <View style={styles.header}>
                <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Staff Directory
                </Text>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 4 }}>
                    {showPayroll ? (
                        <TouchableOpacity
                            style={styles.addButton}
                            onPress={() => navigation.navigate('PayrollRecords')}
                            accessibilityLabel="Payroll records"
                        >
                            <Icon name="payments" size={24} color={colors.primary} />
                        </TouchableOpacity>
                    ) : null}
                    {manage && portal ? (
                        <>
                            <TouchableOpacity
                                style={styles.addButton}
                                onPress={() => Linking.openURL(`${portal}/staff/create`)}
                                accessibilityLabel="Add staff in portal"
                            >
                                <Icon name="person-add" size={24} color={colors.primary} />
                            </TouchableOpacity>
                            <TouchableOpacity
                                style={styles.addButton}
                                onPress={() => Linking.openURL(`${portal}/staff/upload`)}
                                accessibilityLabel="Bulk upload staff"
                            >
                                <Icon name="upload-file" size={24} color={colors.primary} />
                            </TouchableOpacity>
                        </>
                    ) : null}
                </View>
            </View>

            {/* Search Bar */}
            <View style={styles.searchContainer}>
                <Input
                    placeholder="Search staff..."
                    value={searchQuery}
                    onChangeText={setSearchQuery}
                    icon="search"
                    containerStyle={styles.searchInput}
                />
            </View>

            {/* Staff List */}
            <FlatList
                data={staff}
                renderItem={renderStaffCard}
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
                        icon="people"
                        title="No Staff Found"
                        message="No staff members match your search criteria"
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
    searchContainer: {
        paddingHorizontal: SPACING.xl,
        marginBottom: SPACING.md,
    },
    searchInput: {
        marginBottom: 0,
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    staffCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    staffInfo: {
        flex: 1,
        gap: 4,
    },
    staffName: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    row: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    employeeNumber: {
        fontSize: FONT_SIZES.xs,
    },
    designation: {
        fontSize: FONT_SIZES.xs,
    },
    department: {
        fontSize: FONT_SIZES.xs,
    },
    statusContainer: {
        alignItems: 'flex-end',
        gap: SPACING.xs,
    },
});
