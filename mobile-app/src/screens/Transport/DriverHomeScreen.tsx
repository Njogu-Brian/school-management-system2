import React, { useCallback, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    TouchableOpacity,
    RefreshControl,
    Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import { useFocusEffect } from '@react-navigation/native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { format } from 'date-fns';
import { useTheme } from '@contexts/ThemeContext';
import { useAuth } from '@contexts/AuthContext';
import { transportApi } from '@api/transport.api';
import { Trip } from 'types/transport.types';
import { Button } from '@components/common/Button';
import { EmptyState } from '@components/common/EmptyState';
import { ListLoadingSkeleton } from '@components/common/ListLoadingSkeleton';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';

interface Props {
    navigation: any;
}

/**
 * Driver home (Stitch: active trip journey entry). Lists today's trips for the logged-in driver.
 */
export const DriverHomeScreen: React.FC<Props> = ({ navigation }) => {
    const { isDark, colors } = useTheme();
    const { user } = useAuth();
    const [trips, setTrips] = useState<Trip[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);

    const driverId = user?.staff_id ?? user?.id;
    const today = format(new Date(), 'yyyy-MM-dd');

    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;
    const text = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const surface = isDark ? colors.surfaceDark : colors.surfaceLight;
    const border = isDark ? colors.borderDark : colors.borderLight;

    const load = useCallback(async () => {
        if (!driverId) {
            setTrips([]);
            setLoading(false);
            setRefreshing(false);
            return;
        }
        try {
            setLoading(true);
            const res = await transportApi.getTrips({
                driver_id: driverId,
                date_from: today,
                date_to: today,
                per_page: 50,
            });
            if (res.success && res.data?.data) {
                setTrips(res.data.data);
            } else {
                setTrips([]);
            }
        } catch (e: any) {
            Alert.alert('Transport', e?.message || 'Could not load trips');
            setTrips([]);
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [driverId, today]);

    useFocusEffect(
        useCallback(() => {
            void load();
        }, [load])
    );

    const active = trips.find((t) => t.status === 'in_progress');
    const upcoming = trips.filter((t) => t.status === 'scheduled');

    return (
        <SafeAreaView style={[styles.safe, { backgroundColor: bg }]} edges={['top']}>
            <View style={[styles.header, { borderBottomColor: border }]}>
                <Text style={[styles.title, { color: text }]}>Today&apos;s routes</Text>
                <Text style={[styles.date, { color: textSub }]}>{format(new Date(), 'EEE, MMM d')}</Text>
            </View>

            {loading && !refreshing ? (
                <View style={{ padding: SPACING.lg }}>
                    <ListLoadingSkeleton layout="default" />
                </View>
            ) : (
                <ScrollView
                    contentContainerStyle={styles.scroll}
                    refreshControl={
                        <RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); void load(); }} colors={[colors.primary]} />
                    }
                >
                    {active && (
                        <TouchableOpacity
                            activeOpacity={0.9}
                            onPress={() => navigation.navigate('ActiveTrip', { tripId: active.id, tripDate: active.date })}
                            style={[styles.heroCard, { backgroundColor: colors.primary, borderColor: colors.primaryDark }]}
                        >
                            <View style={styles.heroRow}>
                                <Icon name="navigation" size={28} color="#fff" />
                                <View style={{ flex: 1 }}>
                                    <Text style={styles.heroLabel}>Trip in progress</Text>
                                    <Text style={styles.heroTitle} numberOfLines={1}>
                                        {active.route_name || `Route #${active.route_id}`}
                                    </Text>
                                    <Text style={styles.heroMeta}>
                                        {active.type === 'pickup' ? 'Morning pickup' : 'Drop-off'} · Tap to manage stops
                                    </Text>
                                </View>
                                <Icon name="chevron-right" size={28} color="#fff" />
                            </View>
                        </TouchableOpacity>
                    )}

                    {!active && upcoming.length === 0 && trips.length === 0 && (
                        <EmptyState
                            accent="info"
                            icon="directions-bus"
                            title="No routes today"
                            message="There are no trips scheduled for you today. Pull to refresh or check assigned routes."
                            action={
                                <Button
                                    title="Browse routes"
                                    variant="outline"
                                    onPress={() => {
                                        const parent = navigation.getParent?.();
                                        if (parent) parent.navigate('DriverRoutesTab', { screen: 'RoutesList' });
                                    }}
                                />
                            }
                        />
                    )}

                    {!active && upcoming.length > 0 && (
                        <View style={styles.section}>
                            <Text style={[styles.sectionTitle, { color: text }]}>Up next</Text>
                            {upcoming.map((t) => (
                                <TouchableOpacity
                                    key={t.id}
                                    style={[styles.tripRow, { backgroundColor: surface, borderColor: border }]}
                                    onPress={() => navigation.navigate('ActiveTrip', { tripId: t.id })}
                                >
                                    <View style={[styles.dot, { backgroundColor: colors.accentLight }]}>
                                        <Icon name="schedule" size={20} color={colors.primary} />
                                    </View>
                                    <View style={{ flex: 1 }}>
                                        <Text style={[styles.tripName, { color: text }]}>{t.route_name || `Route #${t.route_id}`}</Text>
                                        <Text style={[styles.tripMeta, { color: textSub }]}>
                                            {t.type === 'pickup' ? 'Pickup' : 'Drop-off'}
                                            {t.start_time ? ` · ${t.start_time}` : ''}
                                        </Text>
                                    </View>
                                    <Icon name="chevron-right" size={22} color={textSub} />
                                </TouchableOpacity>
                            ))}
                        </View>
                    )}

                    {trips.some((t) => t.status === 'completed') && (
                        <View style={styles.section}>
                            <Text style={[styles.sectionTitle, { color: text }]}>Completed</Text>
                            {trips
                                .filter((t) => t.status === 'completed')
                                .map((t) => (
                                    <View key={`${t.id}-${t.date}`} style={[styles.doneRow, { borderColor: border }]}>
                                        <Icon name="check-circle" size={22} color={colors.success} />
                                        <Text style={[styles.doneText, { color: textSub }]} numberOfLines={1}>
                                            {t.route_name || `Route #${t.route_id}`}
                                        </Text>
                                    </View>
                                ))}
                        </View>
                    )}
                </ScrollView>
            )}
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    safe: { flex: 1 },
    header: {
        paddingHorizontal: SPACING.lg,
        paddingVertical: SPACING.md,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    title: { fontSize: FONT_SIZES.xxl, fontWeight: '800', letterSpacing: -0.5 },
    date: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    scroll: { padding: SPACING.lg, paddingBottom: SPACING.xxl },
    heroCard: {
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.lg,
        marginBottom: SPACING.xl,
        borderWidth: 1,
    },
    heroRow: { flexDirection: 'row', alignItems: 'center', gap: SPACING.md },
    heroLabel: { color: 'rgba(255,255,255,0.85)', fontSize: FONT_SIZES.xs, fontWeight: '600', textTransform: 'uppercase' },
    heroTitle: { color: '#fff', fontSize: FONT_SIZES.lg, fontWeight: '800', marginTop: 4 },
    heroMeta: { color: 'rgba(255,255,255,0.9)', fontSize: FONT_SIZES.sm, marginTop: 4 },
    section: { marginBottom: SPACING.lg },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.md },
    tripRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        padding: SPACING.md,
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        marginBottom: SPACING.sm,
    },
    dot: {
        width: 44,
        height: 44,
        borderRadius: 22,
        alignItems: 'center',
        justifyContent: 'center',
    },
    tripName: { fontSize: FONT_SIZES.md, fontWeight: '700' },
    tripMeta: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    doneRow: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.sm,
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    doneText: { flex: 1, fontSize: FONT_SIZES.sm },
});
