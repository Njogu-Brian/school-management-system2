import React, { useCallback, useEffect, useMemo, useState } from 'react';
import {
    ActivityIndicator,
    Alert,
    FlatList,
    Modal,
    Platform,
    RefreshControl,
    ScrollView,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    View,
} from 'react-native';
import Icon from 'react-native-vector-icons/MaterialIcons';
import { useTheme } from '@contexts/ThemeContext';
import { SPACING, FONT_SIZES } from '@constants/theme';
import {
    teacherTransportApi,
    TeacherTransportStudent,
    TeacherTransportVehicle,
    TeacherTransportTrip,
} from '@api/teacherTransport.api';

export const TeacherTransportListScreen: React.FC = () => {
    const { isDark, colors } = useTheme();

    const [students, setStudents] = useState<TeacherTransportStudent[]>([]);
    const [loading, setLoading] = useState(true);
    const [refreshing, setRefreshing] = useState(false);
    const [error, setError] = useState<string | null>(null);
    const [date] = useState<string>(new Date().toISOString().slice(0, 10));

    const [pickupFor, setPickupFor] = useState<TeacherTransportStudent | null>(null);
    const [pickupName, setPickupName] = useState('Parent');
    const [pickupNotes, setPickupNotes] = useState('');
    const [submitting, setSubmitting] = useState(false);

    const [reassignFor, setReassignFor] = useState<TeacherTransportStudent | null>(null);
    const [reassignMode, setReassignMode] = useState<'vehicle' | 'trip'>('vehicle');
    const [vehicles, setVehicles] = useState<TeacherTransportVehicle[]>([]);
    const [trips, setTrips] = useState<TeacherTransportTrip[]>([]);
    const [selectedVehicle, setSelectedVehicle] = useState<number | null>(null);
    const [selectedTrip, setSelectedTrip] = useState<number | null>(null);
    const [reassignReason, setReassignReason] = useState('');

    const load = useCallback(async (silent = false) => {
        try {
            if (!silent) setLoading(true);
            setError(null);
            const res = await teacherTransportApi.getStudents({ date });
            if (res.success && res.data) {
                setStudents(res.data.students ?? []);
            } else {
                setError(res.message || 'Unable to load transport list.');
            }
        } catch (e: any) {
            setError(e?.message || 'Unable to load transport list.');
        } finally {
            setLoading(false);
            setRefreshing(false);
        }
    }, [date]);

    useEffect(() => { load(); }, [load]);

    const loadVehiclesIfNeeded = async () => {
        if (vehicles.length > 0 || trips.length > 0) return;
        const res = await teacherTransportApi.getVehiclesAndTrips();
        if (res.success && res.data) {
            setVehicles(res.data.vehicles ?? []);
            setTrips(res.data.trips ?? []);
        }
    };

    const confirmPickup = async () => {
        if (!pickupFor) return;
        try {
            setSubmitting(true);
            const res = await teacherTransportApi.markCollectedByParent({
                student_id: pickupFor.id,
                date,
                direction: 'evening',
                picked_up_by: pickupName.trim() || 'Parent',
                notes: pickupNotes.trim() || undefined,
            });
            if (res.success) {
                setPickupFor(null);
                setPickupNotes('');
                await load(true);
            } else {
                Alert.alert('Error', res.message || 'Could not record pickup.');
            }
        } catch (e: any) {
            Alert.alert('Error', e?.message || 'Could not record pickup.');
        } finally {
            setSubmitting(false);
        }
    };

    const undoPickup = async (student: TeacherTransportStudent) => {
        if (!student.pickup) return;
        try {
            await teacherTransportApi.cancelPickup(student.pickup.id);
            await load(true);
        } catch (e: any) {
            Alert.alert('Error', e?.message || 'Could not undo pickup.');
        }
    };

    const openReassign = async (student: TeacherTransportStudent) => {
        setReassignFor(student);
        setReassignMode('vehicle');
        setSelectedVehicle(null);
        setSelectedTrip(null);
        setReassignReason('');
        await loadVehiclesIfNeeded();
    };

    const saveReassign = async () => {
        if (!reassignFor) return;
        if (reassignMode === 'vehicle' && !selectedVehicle) {
            Alert.alert('Select a vehicle', 'Please choose a vehicle for the temporary change.');
            return;
        }
        if (reassignMode === 'trip' && !selectedTrip) {
            Alert.alert('Select a trip', 'Please choose a trip for the temporary change.');
            return;
        }
        try {
            setSubmitting(true);
            const res = await teacherTransportApi.temporaryReassign({
                student_id: reassignFor.id,
                start_date: date,
                end_date: date,
                mode: reassignMode,
                vehicle_id: reassignMode === 'vehicle' ? selectedVehicle! : undefined,
                trip_id: reassignMode === 'trip' ? selectedTrip! : undefined,
                reason: reassignReason.trim() || undefined,
            });
            if (res.success) {
                setReassignFor(null);
                await load(true);
            } else {
                Alert.alert('Error', res.message || 'Could not save change.');
            }
        } catch (e: any) {
            Alert.alert('Error', e?.message || 'Could not save change.');
        } finally {
            setSubmitting(false);
        }
    };

    const renderItem = ({ item }: { item: TeacherTransportStudent }) => {
        const pickupActive = !!item.pickup;
        return (
            <View style={[styles.card, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight, borderColor: isDark ? colors.borderDark : colors.borderLight }]}>
                <View style={styles.row}>
                    <Icon name="directions-bus" size={22} color={colors.primary} />
                    <View style={{ flex: 1, marginLeft: SPACING.sm }}>
                        <Text style={[styles.name, { color: isDark ? colors.textDark : colors.textLight }]}>{item.full_name}</Text>
                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>
                            {item.admission_number}{item.class_name ? ` • ${item.class_name}` : ''}{item.stream_name ? ` / ${item.stream_name}` : ''}
                        </Text>
                    </View>
                </View>

                <View style={styles.legBox}>
                    <Text style={[styles.legLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Morning</Text>
                    <Text style={[styles.legValue, { color: isDark ? colors.textDark : colors.textLight }]}>
                        {formatLeg(item.morning)}
                    </Text>
                </View>
                <View style={styles.legBox}>
                    <Text style={[styles.legLabel, { color: isDark ? colors.textSubDark : colors.textSubLight }]}>Evening</Text>
                    <Text style={[styles.legValue, { color: isDark ? colors.textDark : colors.textLight }]}>
                        {pickupActive ? 'Picked by parent — evening trip skipped' : formatLeg(item.evening)}
                    </Text>
                </View>

                <View style={styles.actionRow}>
                    {pickupActive ? (
                        <TouchableOpacity style={[styles.btn, { borderColor: colors.primary }]} onPress={() => undoPickup(item)}>
                            <Icon name="undo" size={16} color={colors.primary} />
                            <Text style={[styles.btnText, { color: colors.primary }]}>Undo pickup</Text>
                        </TouchableOpacity>
                    ) : (
                        <TouchableOpacity style={[styles.btn, { backgroundColor: colors.primary }]} onPress={() => setPickupFor(item)}>
                            <Icon name="person" size={16} color="#fff" />
                            <Text style={[styles.btnText, { color: '#fff' }]}>Collected by parent</Text>
                        </TouchableOpacity>
                    )}
                    <TouchableOpacity style={[styles.btn, { borderColor: isDark ? colors.borderDark : colors.borderLight }]} onPress={() => openReassign(item)}>
                        <Icon name="swap-horiz" size={16} color={isDark ? colors.textDark : colors.textLight} />
                        <Text style={[styles.btnText, { color: isDark ? colors.textDark : colors.textLight }]}>Change vehicle/trip</Text>
                    </TouchableOpacity>
                </View>
            </View>
        );
    };

    return (
        <View style={{ flex: 1, backgroundColor: isDark ? colors.backgroundDark : colors.backgroundLight }}>
            {loading ? (
                <View style={styles.center}><ActivityIndicator color={colors.primary} /></View>
            ) : error ? (
                <View style={styles.center}>
                    <Icon name="error-outline" size={36} color={colors.primary} />
                    <Text style={{ color: isDark ? colors.textDark : colors.textLight, marginTop: 8 }}>{error}</Text>
                    <TouchableOpacity onPress={() => load()} style={[styles.retryBtn, { backgroundColor: colors.primary }]}>
                        <Text style={{ color: '#fff', fontWeight: '600' }}>Retry</Text>
                    </TouchableOpacity>
                </View>
            ) : (
                <FlatList
                    data={students}
                    keyExtractor={(i) => String(i.id)}
                    renderItem={renderItem}
                    contentContainerStyle={{ padding: SPACING.md }}
                    refreshControl={<RefreshControl refreshing={refreshing} onRefresh={() => { setRefreshing(true); load(true); }} tintColor={colors.primary} />}
                    ListEmptyComponent={(
                        <View style={styles.center}>
                            <Icon name="directions-bus" size={40} color={isDark ? colors.textSubDark : colors.textSubLight} />
                            <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight, marginTop: 8 }}>No transport data for today.</Text>
                        </View>
                    )}
                />
            )}

            <Modal visible={!!pickupFor} transparent animationType="slide" onRequestClose={() => setPickupFor(null)}>
                <View style={styles.modalBackdrop}>
                    <View style={[styles.modalCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight }]}>
                        <Text style={[styles.modalTitle, { color: isDark ? colors.textDark : colors.textLight }]}>Mark as picked up by parent</Text>
                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight, marginBottom: SPACING.md }]}>
                            {pickupFor?.full_name} — evening trip will be skipped for today.
                        </Text>
                        <Text style={[styles.label, { color: isDark ? colors.textDark : colors.textLight }]}>Picked up by</Text>
                        <TextInput
                            value={pickupName}
                            onChangeText={setPickupName}
                            style={[styles.input, { color: isDark ? colors.textDark : colors.textLight, borderColor: isDark ? colors.borderDark : colors.borderLight }]}
                        />
                        <Text style={[styles.label, { color: isDark ? colors.textDark : colors.textLight }]}>Notes</Text>
                        <TextInput
                            value={pickupNotes}
                            onChangeText={setPickupNotes}
                            multiline
                            style={[styles.input, { color: isDark ? colors.textDark : colors.textLight, borderColor: isDark ? colors.borderDark : colors.borderLight, minHeight: 60 }]}
                        />
                        <View style={styles.modalActions}>
                            <TouchableOpacity style={[styles.cancelBtn, { borderColor: isDark ? colors.borderDark : colors.borderLight }]} onPress={() => setPickupFor(null)} disabled={submitting}>
                                <Text style={{ color: isDark ? colors.textDark : colors.textLight }}>Cancel</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={[styles.saveBtn, { backgroundColor: colors.primary, opacity: submitting ? 0.7 : 1 }]} onPress={confirmPickup} disabled={submitting}>
                                <Text style={{ color: '#fff', fontWeight: '600' }}>{submitting ? 'Saving…' : 'Save'}</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                </View>
            </Modal>

            <Modal visible={!!reassignFor} transparent animationType="slide" onRequestClose={() => setReassignFor(null)}>
                <View style={styles.modalBackdrop}>
                    <View style={[styles.modalCard, { backgroundColor: isDark ? colors.surfaceDark : colors.surfaceLight, maxHeight: '80%' }]}>
                        <Text style={[styles.modalTitle, { color: isDark ? colors.textDark : colors.textLight }]}>Temporary vehicle / trip change</Text>
                        <Text style={[styles.meta, { color: isDark ? colors.textSubDark : colors.textSubLight, marginBottom: SPACING.md }]}>
                            {reassignFor?.full_name} — applies to today ({date}).
                        </Text>

                        <View style={styles.modeRow}>
                            {(['vehicle', 'trip'] as const).map((m) => (
                                <TouchableOpacity
                                    key={m}
                                    style={[styles.modeBtn, {
                                        backgroundColor: reassignMode === m ? colors.primary : 'transparent',
                                        borderColor: colors.primary,
                                    }]}
                                    onPress={() => setReassignMode(m)}
                                >
                                    <Text style={{ color: reassignMode === m ? '#fff' : colors.primary, fontWeight: '600' }}>
                                        {m === 'vehicle' ? 'Vehicle' : 'Trip'}
                                    </Text>
                                </TouchableOpacity>
                            ))}
                        </View>

                        <ScrollView style={{ maxHeight: 220 }}>
                            {reassignMode === 'vehicle'
                                ? vehicles.map((v) => (
                                    <TouchableOpacity
                                        key={v.id}
                                        style={[styles.option, { borderColor: isDark ? colors.borderDark : colors.borderLight, backgroundColor: selectedVehicle === v.id ? colors.primary + '20' : 'transparent' }]}
                                        onPress={() => setSelectedVehicle(v.id)}
                                    >
                                        <Text style={{ color: isDark ? colors.textDark : colors.textLight, fontWeight: '600' }}>{v.vehicle_number}</Text>
                                        <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight, fontSize: FONT_SIZES.sm }}>{v.driver_name || 'No driver'} • Capacity {v.capacity ?? '—'}</Text>
                                    </TouchableOpacity>
                                ))
                                : trips.map((t) => (
                                    <TouchableOpacity
                                        key={t.id}
                                        style={[styles.option, { borderColor: isDark ? colors.borderDark : colors.borderLight, backgroundColor: selectedTrip === t.id ? colors.primary + '20' : 'transparent' }]}
                                        onPress={() => setSelectedTrip(t.id)}
                                    >
                                        <Text style={{ color: isDark ? colors.textDark : colors.textLight, fontWeight: '600' }}>{t.name || `Trip #${t.id}`}</Text>
                                        <Text style={{ color: isDark ? colors.textSubDark : colors.textSubLight, fontSize: FONT_SIZES.sm }}>
                                            {t.direction ?? ''} • {t.departure_time ?? ''} • {t.vehicle?.vehicle_number ?? '—'}
                                        </Text>
                                    </TouchableOpacity>
                                ))}
                        </ScrollView>

                        <Text style={[styles.label, { color: isDark ? colors.textDark : colors.textLight }]}>Reason (optional)</Text>
                        <TextInput
                            value={reassignReason}
                            onChangeText={setReassignReason}
                            style={[styles.input, { color: isDark ? colors.textDark : colors.textLight, borderColor: isDark ? colors.borderDark : colors.borderLight }]}
                        />
                        <View style={styles.modalActions}>
                            <TouchableOpacity style={[styles.cancelBtn, { borderColor: isDark ? colors.borderDark : colors.borderLight }]} onPress={() => setReassignFor(null)} disabled={submitting}>
                                <Text style={{ color: isDark ? colors.textDark : colors.textLight }}>Cancel</Text>
                            </TouchableOpacity>
                            <TouchableOpacity style={[styles.saveBtn, { backgroundColor: colors.primary, opacity: submitting ? 0.7 : 1 }]} onPress={saveReassign} disabled={submitting}>
                                <Text style={{ color: '#fff', fontWeight: '600' }}>{submitting ? 'Saving…' : 'Apply today'}</Text>
                            </TouchableOpacity>
                        </View>
                    </View>
                </View>
            </Modal>
        </View>
    );
};

function formatLeg(leg: any): string {
    if (!leg) return 'No transport assigned';
    if (leg.type === 'own_means') return `Own means — ${leg.reason || 'not using school transport'}`;
    const parts = [] as string[];
    if (leg.trip_name) parts.push(leg.trip_name);
    if (leg.vehicle_registration) parts.push(leg.vehicle_registration);
    if (leg.departure_time) parts.push(`at ${leg.departure_time}`);
    if (leg.drop_off_point) parts.push(`→ ${leg.drop_off_point}`);
    return parts.length ? parts.join(' • ') : 'Assigned';
}

const styles = StyleSheet.create({
    center: { flex: 1, alignItems: 'center', justifyContent: 'center', padding: SPACING.xl },
    retryBtn: { marginTop: SPACING.md, paddingHorizontal: SPACING.lg, paddingVertical: SPACING.sm, borderRadius: 8 },
    card: { padding: SPACING.md, borderRadius: 12, borderWidth: 1, marginBottom: SPACING.md },
    row: { flexDirection: 'row', alignItems: 'center' },
    name: { fontSize: FONT_SIZES.md, fontWeight: '600' },
    meta: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    legBox: { marginTop: SPACING.sm },
    legLabel: { fontSize: FONT_SIZES.xs, textTransform: 'uppercase', fontWeight: '700' },
    legValue: { fontSize: FONT_SIZES.sm, marginTop: 2 },
    actionRow: { flexDirection: 'row', marginTop: SPACING.md, gap: SPACING.sm },
    btn: {
        flexDirection: 'row',
        alignItems: 'center',
        paddingHorizontal: SPACING.md,
        paddingVertical: SPACING.sm,
        borderRadius: 8,
        borderWidth: 1,
        borderColor: 'transparent',
    },
    btnText: { marginLeft: 6, fontSize: FONT_SIZES.sm, fontWeight: '600' },
    modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
    modalCard: { padding: SPACING.lg, borderTopLeftRadius: 20, borderTopRightRadius: 20 },
    modalTitle: { fontSize: FONT_SIZES.lg, fontWeight: '700' },
    label: { fontSize: FONT_SIZES.sm, marginTop: SPACING.sm, marginBottom: 4, fontWeight: '600' },
    input: { borderWidth: 1, borderRadius: 8, paddingHorizontal: SPACING.sm, paddingVertical: Platform.OS === 'ios' ? 10 : 6 },
    modalActions: { flexDirection: 'row', justifyContent: 'flex-end', marginTop: SPACING.lg },
    cancelBtn: { paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm, borderRadius: 8, borderWidth: 1, marginRight: SPACING.sm },
    saveBtn: { paddingHorizontal: SPACING.md, paddingVertical: SPACING.sm, borderRadius: 8 },
    modeRow: { flexDirection: 'row', marginBottom: SPACING.md, gap: SPACING.sm },
    modeBtn: { flex: 1, paddingVertical: SPACING.sm, borderRadius: 8, alignItems: 'center', borderWidth: 1 },
    option: { padding: SPACING.sm, borderWidth: 1, borderRadius: 8, marginBottom: SPACING.sm },
});
