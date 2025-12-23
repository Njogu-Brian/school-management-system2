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
import { StatusBadge } from '@components/common/StatusBadge';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { communicationApi } from '@api/communication.api';
import { Announcement } from '../types/communication.types';
import { formatters } from '@utils/formatters';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface AnnouncementsScreenProps {
    navigation: any;
}

export const AnnouncementsScreen: React.FC<AnnouncementsScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [announcements, setAnnouncements] = useState<Announcement[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [page, setPage] = useState(1);
    const [hasMore, setHasMore] = useState(true);

    const fetchAnnouncements = useCallback(
        async (pageNum: number = 1) => {
            try {
                if (pageNum === 1) {
                    setLoading(true);
                }

                const response = await communicationApi.getAnnouncements({
                    status: 'published',
                    page: pageNum,
                    per_page: 20,
                });

                if (response.success && response.data) {
                    if (pageNum === 1) {
                        setAnnouncements(response.data.data);
                    } else {
                        setAnnouncements((prev) => [...prev, ...response.data.data]);
                    }

                    setHasMore(response.data.current_page < response.data.last_page);
                    setPage(pageNum);
                }
            } catch (error: any) {
                Alert.alert('Error', error.message || 'Failed to load announcements');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        []
    );

    useEffect(() => {
        fetchAnnouncements(1);
    }, []);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchAnnouncements(1);
    };

    const handleLoadMore = () => {
        if (!loading && hasMore) {
            fetchAnnouncements(page + 1);
        }
    };

    const getTypeIcon = (type: string) => {
        switch (type) {
            case 'urgent':
                return 'error';
            case 'event':
                return 'event';
            case 'academic':
                return 'school';
            case 'holiday':
                return 'celebration';
            default:
                return 'campaign';
        }
    };

    const getTypeColor = (type: string) => {
        switch (type) {
            case 'urgent':
                return colors.error;
            case 'event':
                return colors.primary;
            case 'academic':
                return colors.success;
            default:
                return isDark ? colors.textMainDark : colors.textMainLight;
        }
    };

    const renderAnnouncementCard = ({ item }: { item: Announcement }) => (
        <Card onPress={() => navigation.navigate('AnnouncementDetail', { announcementId: item.id })}>
            <View style={styles.announcementCard}>
                <View style={styles.header}>
                    <View style={styles.typeContainer}>
                        <Icon name={getTypeIcon(item.type)} size={24} color={getTypeColor(item.type)} />
                        <View style={styles.titleContainer}>
                            <Text style={[styles.title, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                                {item.title}
                            </Text>
                            {item.priority === 'high' && (
                                <View style={styles.priorityBadge}>
                                    <Text style={styles.priorityText}>HIGH PRIORITY</Text>
                                </View>
                            )}
                        </View>
                    </View>
                </View>

                <Text
                    style={[styles.content, { color: isDark ? colors.textSubDark : colors.textSubLight }]}
                    numberOfLines={3}
                >
                    {item.content}
                </Text>

                <View style={styles.footer}>
                    <View style={styles.metaInfo}>
                        <Icon name="person" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.metaText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.published_by_name}
                        </Text>
                    </View>
                    <View style={styles.metaInfo}>
                        <Icon name="access-time" size={14} color={isDark ? colors.textSubDark : colors.textSubLight} />
                        <Text style={[styles.metaText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {formatters.getRelativeTime(item.publish_date)}
                        </Text>
                    </View>
                </View>
            </View>
        </Card>
    );

    if (loading && page === 1) {
        return (
            <SafeAreaView
                style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
            >
                <LoadingState message="Loading announcements..." />
            </SafeAreaView>
        );
    }

    return (
        <SafeAreaView
            style={[styles.container, { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }]}
        >
            {/* Header */}
            <View style={styles.headerContainer}>
                <Text style={[styles.headerTitle, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                    Announcements
                </Text>
            </View>

            {/* Announcements List */}
            <FlatList
                data={announcements}
                renderItem={renderAnnouncementCard}
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
                        icon="campaign"
                        title="No Announcements"
                        message="There are no announcements at the moment"
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
    headerContainer: {
        paddingHorizontal: SPACING.xl,
        paddingVertical: SPACING.md,
    },
    headerTitle: {
        fontSize: FONT_SIZES.xxl,
        fontWeight: 'bold',
    },
    listContent: {
        paddingHorizontal: SPACING.xl,
        paddingBottom: SPACING.xl,
    },
    announcementCard: {
        gap: SPACING.sm,
    },
    header: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        alignItems: 'flex-start',
    },
    typeContainer: {
        flexDirection: 'row',
        gap: SPACING.sm,
        flex: 1,
    },
    titleContainer: {
        flex: 1,
        gap: 4,
    },
    title: {
        fontSize: FONT_SIZES.md,
        fontWeight: 'bold',
    },
    priorityBadge: {
        backgroundColor: '#ff4444',
        paddingHorizontal: SPACING.xs,
        paddingVertical: 2,
        borderRadius: 4,
        alignSelf: 'flex-start',
    },
    priorityText: {
        color: '#fff',
        fontSize: 10,
        fontWeight: 'bold',
    },
    content: {
        fontSize: FONT_SIZES.sm,
        lineHeight: 20,
    },
    footer: {
        flexDirection: 'row',
        justifyContent: 'space-between',
        marginTop: SPACING.xs,
    },
    metaInfo: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    metaText: {
        fontSize: FONT_SIZES.xs,
    },
});
