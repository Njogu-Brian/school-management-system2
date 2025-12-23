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
import { Input } from '@components/common/Input';
import { EmptyState, LoadingState } from '@components/common/EmptyState';
import { transportApi } from '@api/transport.api';
import { Route } from '../types/transport.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface RoutesListScreenProps {
    navigation: any;
}

export const RoutesListScreen: React.FC<RoutesListScreenProps> = ({ navigation }) => {
    const { isDark, colors } = useTheme();

    const [routes, setRoutes] = useState<Route[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [searchQuery, setSearchQuery] = useState('');

    const fetchRoutes = useCallback(
        async (search?: string) => {
            try {
                setLoading(true);

                const response = await transportApi.getRoutes({
                    search: search || searchQuery,
                    per_page: 100,
                });

                if (response.success && response.data) {
                    setRoutes(response.data.data);
                }
            } catch (error: any) {
                Alert.alert('Error', error.message || 'Failed to load routes');
            } finally {
                setLoading(false);
                setRefreshing(false);
            }
        },
        [searchQuery]
    );

    useEffect(() => {
        fetchRoutes();
    }, []);

    useEffect(() => {
        const timeoutId = setTimeout(() => {
            if (searchQuery !== undefined) {
                fetchRoutes(searchQuery);
            }
        }, 500);

        return () => clearTimeout(timeoutId);
    }, [searchQuery]);

    const handleRefresh = () => {
        setRefreshing(true);
        fetchRoutes();
    };

    const handleRoutePress = (route: Route) => {
        navigation.navigate('RouteDetail', { routeId: route.id });
    };

    const renderRouteCard = ({ item }: { item: Route }) => (
        <Card onPress={() => handleRoutePress(item)}>
            <View style={styles.routeCard}>
                <View style={styles.iconContainer}>
                    <Icon name="route" size={32} color={colors.primary} />
                </View>

                <View style={styles.routeInfo}>
                    <Text style={[styles.routeName, { color: isDark ? colors.textMainDark : colors.textMainLight }]}>
                        {item.name}
                    </Text>
                    {item.code && (
                        <Text style={[styles.routeCode, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            Code: {item.code}
                        </Text>
                    )}

                    <View style={styles.detailsRow}>
                        {item.vehicle_registration && (
                            <View style={styles.detail}>
                                <Icon name="directions-bus" size={16} color={isDark ? colors.textSubDark : colors.textSubLight} />
                                <Text style={[styles.detailText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {item.vehicle_registration}
                                </Text>
                            </View>
                        )}
                        {item.driver_name && (
                            <View style={styles.detail}>
                                <Icon name="person" size={16} color={isDark ? colors.textSubDark : colors.textSubLight} />
                                <Text style={[styles.detailText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                    {item.driver_name}
                                </Text>
                            </View>
                        )}
                    </View>

                    <View style={styles.detailsRow}>
                        <View style={styles.detail}>
                            <Icon name="location-on" size={16} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.detailText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {item.drop_points?.length || 0} stops
                            </Text>
                        </View>
                        <View style={styles.detail}>
                            <Icon name="school" size={16} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={[styles.detailText, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                                {item.students_count || 0} students
                            </Text>
                        </View>
                    </View>
                </View>

                <View style={styles.statusContainer}>
                    <StatusBadge status={item.status} />
                    <Icon name="chevron-right" size={24} color={isDark ? colors.textSubDark : colors.textSubLight} />
                </View>
            </View>
        </Card>
    );

    if (loading) {
        return (
            <SafeAreaView
                style={[
                    styles.container,
                    { backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight },
                ]}
            >
                <LoadingState message="Loading routes..." />
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
                    Transport Routes
                </Text>
                <TouchableOpacity
                    style={styles.addButton}
                    onPress={() => navigation.navigate('AddRoute')}
                >
                    <Icon name="add" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            {/* Search Bar */}
            <View style={styles.searchContainer}>
                <Input
                    placeholder="Search routes..."
                    value={searchQuery}
                    onChangeText={setSearchQuery}
                    icon="search"
                    containerStyle={styles.searchInput}
                />
            </View>

            {/* Routes List */}
            <FlatList
                data={routes}
                renderItem={renderRouteCard}
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
                        icon="route"
                        title="No Routes Found"
                        message="No transport routes match your search criteria"
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
    routeCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
    },
    iconContainer: {
        width: 56,
        height: 56,
        borderRadius: 28,
        backgroundColor: '#f0f0f0',
        alignItems: 'center',
        justifyContent: 'center',
    },
    routeInfo: {
        flex: 1,
        gap: 4,
    },
    routeName: {
        fontSize: FONT_SIZES.md,
        fontWeight: '600',
    },
    routeCode: {
        fontSize: FONT_SIZES.xs,
    },
    detailsRow: {
        flexDirection: 'row',
        gap: SPACING.md,
        marginTop: 2,
    },
    detail: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: 4,
    },
    detailText: {
        fontSize: FONT_SIZES.xs,
    },
    statusContainer: {
        alignItems: 'flex-end',
        gap: SPACING.xs,
    },
});
