import React, { useState, useEffect, useCallback } from 'react';
import {
    View,
    Text,
    StyleSheet,
    SafeAreaView,
    ScrollView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { useTheme } from '@contexts/ThemeContext';
import { Card } from '@components/common/Card';
import { LoadingState } from '@components/common/EmptyState';
import { transportApi } from '@api/transport.api';
import { Route, DropPoint } from 'types/transport.types';
import { SPACING, FONT_SIZES } from '@constants/theme';
import { RADIUS } from '@constants/designTokens';
import { layoutStyles } from '@styles/common';
import Icon from 'react-native-vector-icons/MaterialIcons';

interface Props {
    navigation: { goBack: () => void };
    route: { params?: { routeId: number } };
}

export const RouteDetailScreen: React.FC<Props> = ({ navigation, route }) => {
    const routeId = route.params?.routeId;
    const { isDark, colors } = useTheme();

    const [data, setData] = useState<Route | null>(null);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const load = useCallback(async () => {
        if (!routeId) {
            return;
        }
        try {
            setLoading(true);
            const res = await transportApi.getRoute(routeId);
            if (res.success && res.data) {
                setData(res.data);
            } else {
                setData(null);
            }
        } catch (e: any) {
            Alert.alert('Transport', e?.message || 'Could not load route');
            setData(null);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [routeId]);

    useEffect(() => {
        load();
    }, [load]);

    const onRefresh = () => {
        setRefreshing(true);
        load();
    };

    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;
    const textMain = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const border = isDark ? colors.borderDark : colors.borderLight;
    const surfaceMuted = isDark ? colors.accentDark : colors.accentLight;

    if (!routeId) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
                <Text style={{ color: textMain, padding: SPACING.lg }}>Missing route.</Text>
                <TouchableOpacity onPress={() => navigation.goBack()} style={{ padding: SPACING.md }}>
                    <Text style={{ color: colors.primary }}>Go back</Text>
                </TouchableOpacity>
            </SafeAreaView>
        );
    }

    if (loading && !refreshing) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
                <LoadingState message="Loading route…" />
            </SafeAreaView>
        );
    }

    if (!data) {
        return (
            <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
                <View style={styles.header}>
                    <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12}>
                        <Icon name="arrow-back" size={24} color={textMain} />
                    </TouchableOpacity>
                    <Text style={[styles.title, { color: textMain }]}>Route</Text>
                    <View style={{ width: 24 }} />
                </View>
                <Text style={[styles.empty, { color: textSub }]}>Route not found.</Text>
            </SafeAreaView>
        );
    }

    const stops: DropPoint[] = data.drop_points ?? [];

    return (
        <SafeAreaView style={[layoutStyles.flex1, styles.container, { backgroundColor: bg }]}>
            <View style={[styles.header, { borderBottomColor: border }]}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12}>
                    <Icon name="arrow-back" size={24} color={textMain} />
                </TouchableOpacity>
                <Text style={[styles.title, { color: textMain }]} numberOfLines={1}>
                    {data.name}
                </Text>
                <View style={{ width: 24 }} />
            </View>

            <ScrollView
                contentContainerStyle={styles.scroll}
                refreshControl={
                    <RefreshControl refreshing={refreshing} onRefresh={onRefresh} colors={[colors.primary]} />
                }
            >
                {data.description ? (
                    <Text style={[styles.desc, { color: textSub }]}>{data.description}</Text>
                ) : null}

                <View style={[styles.mapPlaceholder, { borderColor: border, backgroundColor: surfaceMuted }]}>
                    <Icon name="map" size={36} color={colors.primary} />
                    <Text style={[styles.mapLabel, { color: textSub }]}>Route overview</Text>
                    <Text style={[styles.mapHint, { color: textSub }]}>
                        Stop order below matches the live run sheet. Map integration optional.
                    </Text>
                </View>

                <Card style={styles.card}>
                    <View style={styles.row}>
                        <Icon name="directions-bus" size={22} color={colors.primary} />
                        <View style={styles.rowText}>
                            <Text style={[styles.label, { color: textSub }]}>Vehicle</Text>
                            <Text style={[styles.value, { color: textMain }]}>
                                {data.vehicle_registration || '—'}
                            </Text>
                        </View>
                    </View>
                    <View style={[styles.row, { marginTop: SPACING.md }]}>
                        <Icon name="person" size={22} color={colors.primary} />
                        <View style={styles.rowText}>
                            <Text style={[styles.label, { color: textSub }]}>Driver</Text>
                            <Text style={[styles.value, { color: textMain }]}>{data.driver_name || '—'}</Text>
                        </View>
                    </View>
                </Card>

                <Text style={[styles.sectionTitle, { color: textMain }]}>Stops</Text>
                {stops.length === 0 ? (
                    <Text style={{ color: textSub }}>No stops listed for this trip.</Text>
                ) : (
                    stops.map((s) => (
                        <Card key={s.id} style={styles.stopCard}>
                            <View style={styles.stopRow}>
                                <View style={[styles.badge, { backgroundColor: colors.primary + '22' }]}>
                                    <Text style={[styles.badgeText, { color: colors.primary }]}>{s.sequence}</Text>
                                </View>
                                <View style={{ flex: 1 }}>
                                    <Text style={[styles.stopName, { color: textMain }]}>{s.name}</Text>
                                    {s.pickup_time ? (
                                        <Text style={[styles.stopMeta, { color: textSub }]}>Est. {s.pickup_time}</Text>
                                    ) : null}
                                </View>
                            </View>
                        </Card>
                    ))
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    container: { flex: 1 },
    header: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    title: { flex: 1, fontSize: FONT_SIZES.lg, fontWeight: '700', textAlign: 'center', marginHorizontal: SPACING.sm },
    scroll: { padding: SPACING.lg, paddingBottom: SPACING.xxl },
    mapPlaceholder: {
        borderWidth: 1,
        borderRadius: RADIUS.card,
        padding: SPACING.lg,
        alignItems: 'center',
        marginBottom: SPACING.lg,
    },
    mapLabel: { fontSize: FONT_SIZES.md, fontWeight: '700', marginTop: SPACING.sm },
    mapHint: { fontSize: FONT_SIZES.xs, textAlign: 'center', marginTop: SPACING.xs, lineHeight: 18 },
    desc: { fontSize: FONT_SIZES.sm, marginBottom: SPACING.lg, lineHeight: 20 },
    card: { borderRadius: RADIUS.card, padding: SPACING.lg, marginBottom: SPACING.lg },
    row: { flexDirection: 'row', alignItems: 'flex-start', gap: SPACING.md },
    rowText: { flex: 1 },
    label: { fontSize: FONT_SIZES.xs, textTransform: 'uppercase', letterSpacing: 0.5 },
    value: { fontSize: FONT_SIZES.md, fontWeight: '600', marginTop: 2 },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.md },
    stopCard: { marginBottom: SPACING.sm, borderRadius: RADIUS.card, padding: SPACING.md },
    stopRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md },
    badge: {
        width: 32,
        height: 32,
        borderRadius: 16,
        alignItems: 'center',
        justifyContent: 'center',
    },
    badgeText: { fontSize: FONT_SIZES.sm, fontWeight: '800' },
    stopName: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    stopMeta: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    empty: { padding: SPACING.lg, textAlign: 'center' },
});
