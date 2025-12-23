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
import { communicationApi } from '@api/communication.api';
import { Notification } from '../types/communication.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface NotificationsScreenProps {
    navigation: any;
}

export const NotificationsScreen: React.FC<NotificationsScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [notifications, setNotifications] = useState<Notification[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const fetchNotifications = useCallback(async () => {
        try {
            setLoading(true);

            const response = await communicationApi.getNotifications({
                per_page: 50,
            });

            if (response.success && response.data) {
                setNotifications(response.data.data);
            }
        } catch (error: any) {
            Alert.alert('Error', error.message || 'Failed to load notifications');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, []);

    useEffect(() => {
        fetchNotifications();
    }, []);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchNotifications();
    };

    const handleMarkAsRead = async (id: number) => {
        try {
            await communicationApi.markAsRead(id);
            setNotifications((prev) =>
                prev.map((notif) => (notif.id === id ? { ...notif, is_read: true } : notif))
            );
        } catch (error: any) {
            Alert.alert('Error', 'Failed to mark as read');
        }
    };

    const handleMarkAllAsRead = async () => {
        try {
            await communicationApi.markAllAsRead();
            setNotifications((prev) => prev.map((notif) => ({ ...notif, is_read: true })));
        } catch (error: any) {
            Alert.alert('Error', 'Failed to mark all as read');
        }
    };

    const getTypeIcon = (category: string) => {
        switch (category) {
            case 'announcement':
                return 'campaign';
            case 'fee':
                return 'payment';
            case 'attendance':
                return 'event';
            case 'exam':
                return 'school';
            default:
                return 'notifications';
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'error':
                return colors.error;
            case 'warning':
                return '#ff9800';
            case 'success':
                return colors.success;
            default:
                return colors.primary;
        }
    };

    const renderNotificationCard = ({ item }: { item: Notification }) => (
        <TouchableOpacity onPress={() => !item.is_read && handleMarkAsRead(item.id)}>
            <Card style={[styles.notificationCard, !item.is_read && styles.unreadCard]}>
                <View style={styles.notificationContent}>
                    <View style={[styles.iconContainer, { backgroundColor: getTypeColor(item.type) + '20' }]}>
                        <Icon name={getTypeIcon(item.category)} size={24} color={getTypeColor(item.type)} />
                    </View>

                    <View style={styles.textContainer}>
                        <View style={styles.headerRow}>
                            <Text
                                style={[
                                    styles.notificationTitle,
                                    {
                                        color: isDark ? colors.textMainDark : colors.textMainLight,
                                        fontWeight: item.is_read ? '500' : 'bold',
                                    },
                                ]}
                            >
                                {item.title}
                            </Text>
                            {!item.is_read && <View style={styles.unreadDot} />}
                        </View>

                        <Text
                            style={[styles.notificationBody, { color: isDark ? colors.textSubDark : colors.textSubLight }]}
                            numberOfLines={2}
                        >
                            {item.body}
                        </Text>

                        <Text style={[styles.timestamp, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {formatters.getRelativeTime(item.created_at)}
                        </Text>
                    </View>
                </View>
            </Card>
        </TouchableOpacity>
    );

    if (loading) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading notifications..." />
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
                    Notifications
                </Text>
                {notifications.some((n) => !n.is_read) && (
                    <TouchableOpacity onPress={handleMarkAllAsRead}>
                        <Text style={[styles.markAllButton, { color: colors.primary }]}>Mark all read</Text>
                    </TouchableOpacity>
                )}
            </View>

            {/* Notifications List */}
            <FlatList
                data={notifications}
                renderItem={renderNotificationCard}
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
                        icon="notifications"
                        title="No Notifications"
                        message="You're all caught up! No notifications at the moment."
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
    markAllButton: {
        fontSize: FONT_SIZES.sm,
        fontWeight: '600',
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    notificationCard: {
        marginBottom: SPACING.sm,
    },
    unreadCard: {
        borderLeftWidth: 3,
        borderLeftColor: '#2196F3',
    },
    notificationContent: {
        flexDirection: 'row',
        gap: SPACING.md,
    },
    iconContainer: {
        width: 48,
        height: 48,
        borderRadius: 24,
        alignItems: 'center',
        justifyContent: 'center',
    },
    textContainer: {
        flex: 1,
        gap: 4,
    },
    headerRow: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'center',
    },
    notificationTitle: {
        fontSize: FONT_SIZES.md,
        flex: 1,
    },
    unreadDot: {
        width: 8,
        height: 8,
        borderRadius: 4,
        backgroundColor: '#2196F3',
        marginLeft: SPACING.xs,
    },
    notificationBody: {
        fontSize: FONT_SIZES.sm,
        lineHeight: 18,
    },
    timestamp: {
        fontSize: FONT_SIZES.xs,
    },
});
