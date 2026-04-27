import React, { useCallback, useEffect, useState } from 'react';
import {
    View,
    Text,
    StyleSheet,
    ScrollView,
    TouchableOpacity,
    Alert,
} from 'react-native';
import { SafeAreaView } from 'react-native-safe-area-context';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { format } from 'date-fns';
import { useTheme } from '@contexts/ThemeContext';
import { transportApi } from '@api/transport.api';
import { Trip, Route as SchoolRoute, DropPoint } from 'types/transport.types';
import { ListLoadingSkeleton } from '@components/common/ListLoadingSkeleton';
import { SPACING, FONT_SIZES, BORDER_RADIUS } from '@constants/theme';

interface TripStudentRow {
    id: number;
    full_name: string;
    admission_number?: string | null;
    class_name?: string | null;
    stream_name?: string | null;
}

interface Props {
    navigation: any;
    route: { params: { tripId: number; tripDate?: string } };
}

/**
 * Driver trip detail: roster for a calendar date + route stops (no server-side "trip run" yet).
 */
export const DriverActiveTripScreen: React.FC<Props> = ({ navigation, route }) => {
    const tripId = route.params?.tripId;
    const tripDate = route.params?.tripDate ?? format(new Date(), 'yyyy-MM-dd');
    const { isDark, colors } = useTheme();
    const [trip, setTrip] = useState<Trip | null>(null);
    const [students, setStudents] = useState<TripStudentRow[]>([]);
    const [routeDetail, setRouteDetail] = useState<SchoolRoute | null>(null);
    const [loading, setLoading] = useState(true);

    const bg = isDark ? colors.backgroundDark : colors.backgroundLight;
    const text = isDark ? colors.textMainDark : colors.textMainLight;
    const textSub = isDark ? colors.textSubDark : colors.textSubLight;
    const surface = isDark ? colors.surfaceDark : colors.surfaceLight;
    const border = isDark ? colors.borderDark : colors.borderLight;

    const load = useCallback(async () => {
        if (!tripId) return;
        try {
            setLoading(true);
            const tRes = await transportApi.getTrip(tripId, { date: tripDate });
            if (!tRes.success || !tRes.data?.trip) {
                setTrip(null);
                setStudents([]);
                setRouteDetail(null);
                return;
            }
            setTrip(tRes.data.trip);
            setStudents(tRes.data.students ?? []);
            const rRes = await transportApi.getRoute(tRes.data.trip.route_id);
            if (rRes.success && rRes.data) {
                setRouteDetail(rRes.data);
            } else {
                setRouteDetail(null);
            }
        } catch (e: any) {
            Alert.alert('Trip', e?.message || 'Failed to load trip');
            setTrip(null);
            setStudents([]);
        } finally {
            setLoading(false);
        }
    }, [tripId, tripDate]);

    useEffect(() => {
        void load();
    }, [load]);

    if (!tripId) {
        return (
            <SafeAreaView style={[styles.safe, { backgroundColor: bg }]}>
                <Text style={{ color: text, padding: SPACING.lg }}>Missing trip.</Text>
            </SafeAreaView>
        );
    }

    if (loading || !trip) {
        return (
            <SafeAreaView style={[styles.safe, { backgroundColor: bg }]}>
                <View style={styles.topRow}>
                    <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12} style={styles.backBtn}>
                        <Icon name="close" size={26} color={textSub} />
                    </TouchableOpacity>
                </View>
                <View style={{ padding: SPACING.lg }}>
                    <ListLoadingSkeleton layout="marks" />
                </View>
            </SafeAreaView>
        );
    }

    const stops: DropPoint[] = [...(routeDetail?.drop_points ?? [])].sort((a, b) => a.sequence - b.sequence);

    return (
        <SafeAreaView style={[styles.safe, { backgroundColor: bg }]} edges={['top']}>
            <View style={[styles.topBar, { backgroundColor: surface, borderBottomColor: border }]}>
                <TouchableOpacity onPress={() => navigation.goBack()} hitSlop={12}>
                    <Icon name="arrow-back" size={24} color={textSub} />
                </TouchableOpacity>
                <Text style={[styles.topTitle, { color: text }]} numberOfLines={1}>
                    Trip detail
                </Text>
                <TouchableOpacity
                    onPress={() => trip && navigation.navigate('RouteDetail', { routeId: trip.route_id })}
                    hitSlop={12}
                >
                    <Icon name="map" size={24} color={colors.primary} />
                </TouchableOpacity>
            </View>

            <ScrollView contentContainerStyle={styles.scroll} keyboardShouldPersistTaps="handled">
                <View style={[styles.summary, { backgroundColor: surface, borderColor: border }]}>
                    <Text style={[styles.routeTitle, { color: text }]}>{trip.route_name || `Route #${trip.route_id}`}</Text>
                    <Text style={[styles.dateLine, { color: textSub }]}>
                        {format(new Date(tripDate + 'T12:00:00'), 'EEE, MMM d')}
                    </Text>
                    <View style={styles.statusRow}>
                        <View style={[styles.pill, { backgroundColor: colors.accentLight }]}>
                            <Text style={[styles.pillText, { color: colors.primary }]}>
                                {trip.type === 'pickup' ? 'Pickup' : 'Drop-off'}
                            </Text>
                        </View>
                        <View style={[styles.pill, { backgroundColor: `${colors.textSubLight}18` }]}>
                            <Text style={[styles.pillText, { color: textSub }]}>Roster</Text>
                        </View>
                    </View>
                    {!!trip.vehicle_registration && (
                        <Text style={[styles.meta, { color: textSub }]}>Vehicle {trip.vehicle_registration}</Text>
                    )}
                    {trip.students_on_route != null ? (
                        <Text style={[styles.meta, { color: textSub }]}>
                            {trip.students_on_route} student{trip.students_on_route === 1 ? '' : 's'} on this run
                        </Text>
                    ) : null}
                </View>

                <Text style={[styles.sectionTitle, { color: text }]}>Students ({students.length})</Text>
                {students.length === 0 ? (
                    <Text style={{ color: textSub }}>No students assigned for this date.</Text>
                ) : (
                    students.map((s) => (
                        <View key={s.id} style={[styles.studentCard, { backgroundColor: surface, borderColor: border }]}>
                            <Icon name="person" size={22} color={colors.primary} />
                            <View style={{ flex: 1 }}>
                                <Text style={[styles.studentName, { color: text }]}>{s.full_name}</Text>
                                <Text style={[styles.studentMeta, { color: textSub }]} numberOfLines={2}>
                                    {[s.admission_number, s.class_name, s.stream_name].filter(Boolean).join(' · ')}
                                </Text>
                            </View>
                        </View>
                    ))
                )}

                <View style={[styles.mapPlaceholder, { borderColor: border, backgroundColor: isDark ? colors.accentDark : colors.accentLight }]}>
                    <Icon name="map" size={40} color={colors.primary} />
                    <Text style={[styles.mapText, { color: textSub }]}>Route map</Text>
                    <Text style={[styles.mapHint, { color: textSub }]}>
                        Live map can be integrated with your provider (Google Maps, Mapbox).
                    </Text>
                </View>

                <Text style={[styles.sectionTitle, { color: text }]}>Stops ({stops.length})</Text>
                {stops.length === 0 ? (
                    <Text style={{ color: textSub }}>No stops on file for this route.</Text>
                ) : (
                    stops.map((s) => (
                        <View key={s.id} style={[styles.stopCard, { backgroundColor: surface, borderColor: border }]}>
                            <View style={[styles.seq, { backgroundColor: colors.primary }]}>
                                <Text style={styles.seqText}>{s.sequence}</Text>
                            </View>
                            <View style={{ flex: 1 }}>
                                <Text style={[styles.stopName, { color: text }]}>{s.name}</Text>
                                {s.location ? (
                                    <Text style={[styles.stopLoc, { color: textSub }]} numberOfLines={2}>
                                        {s.location}
                                    </Text>
                                ) : null}
                                {s.students_count != null ? (
                                    <Text style={[styles.stopCount, { color: textSub }]}>{s.students_count} students</Text>
                                ) : null}
                            </View>
                        </View>
                    ))
                )}
            </ScrollView>
        </SafeAreaView>
    );
};

const styles = StyleSheet.create({
    safe: { flex: 1 },
    topRow: { padding: SPACING.md },
    backBtn: { alignSelf: 'flex-end' },
    topBar: {
        flexDirection: 'row',
        alignItems: 'center',
        justifyContent: 'space-between',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderBottomWidth: StyleSheet.hairlineWidth,
    },
    topTitle: { flex: 1, textAlign: 'center', fontSize: FONT_SIZES.lg, fontWeight: '700', marginHorizontal: SPACING.sm },
    scroll: { padding: SPACING.lg, paddingBottom: SPACING.xxl * 2 },
    summary: {
        borderRadius: BORDER_RADIUS.lg,
        borderWidth: 1,
        padding: SPACING.lg,
        marginBottom: SPACING.lg,
    },
    routeTitle: { fontSize: FONT_SIZES.xl, fontWeight: '800' },
    dateLine: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    statusRow: { flexDirection: 'row', flexWrap: 'wrap', gap: SPACING.sm, marginTop: SPACING.md },
    pill: { paddingHorizontal: SPACING.md, paddingVertical: 6, borderRadius: BORDER_RADIUS.full },
    pillText: { fontSize: FONT_SIZES.xs, fontWeight: '700', textTransform: 'capitalize' },
    meta: { marginTop: SPACING.sm, fontSize: FONT_SIZES.sm },
    sectionTitle: { fontSize: FONT_SIZES.md, fontWeight: '700', marginBottom: SPACING.md },
    studentCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.md,
        marginBottom: SPACING.sm,
    },
    studentName: { fontSize: FONT_SIZES.md, fontWeight: '700' },
    studentMeta: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    mapPlaceholder: {
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.xl,
        alignItems: 'center',
        marginVertical: SPACING.lg,
    },
    mapText: { fontSize: FONT_SIZES.md, fontWeight: '700', marginTop: SPACING.sm },
    mapHint: { fontSize: FONT_SIZES.xs, textAlign: 'center', marginTop: SPACING.xs },
    stopCard: {
        flexDirection: 'row',
        alignItems: 'center',
        gap: SPACING.md,
        borderWidth: 1,
        borderRadius: BORDER_RADIUS.lg,
        padding: SPACING.md,
        marginBottom: SPACING.sm,
    },
    seq: {
        width: 32,
        height: 32,
        borderRadius: 16,
        alignItems: 'center',
        justifyContent: 'center',
    },
    seqText: { color: '#fff', fontWeight: '800', fontSize: FONT_SIZES.sm },
    stopName: { fontSize: FONT_SIZES.md, fontWeight: '700' },
    stopLoc: { fontSize: FONT_SIZES.sm, marginTop: 4 },
    stopCount: { fontSize: FONT_SIZES.xs, marginTop: 4 },
});
