import {
  type TeacherTransportStudent,
  type TeacherTransportTrip,
  type TeacherTransportVehicle,
  useTeacherTransportActions,
  useTeacherTransportStudents,
  useTeacherTransportVehicles,
} from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, TextField, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Alert,
  FlatList,
  Modal,
  Platform,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'TeacherTransport'>;

function formatLeg(leg: TeacherTransportStudent['morning']): string {
  if (!leg) return 'No transport assigned';
  if (leg.type === 'own_means') return `Own means — ${leg.reason ?? 'not using school transport'}`;
  const parts = [leg.trip_name, leg.vehicle_registration, leg.departure_time ? `at ${leg.departure_time}` : null, leg.drop_off_point ? `→ ${leg.drop_off_point}` : null].filter(Boolean);
  return parts.length ? parts.join(' · ') : 'Assigned';
}

function FeeBadge({ status, balance }: { status?: 'cleared' | 'pending'; balance?: number | null }) {
  const { colors, fontSizes } = useTheme();
  if (!status) return null;
  const pending = status === 'pending';
  return (
    <Text style={{ color: pending ? colors.warning : colors.success, fontSize: fontSizes.xs, fontWeight: '700', marginTop: 4 }}>
      Fees: {pending ? `Pending${balance != null ? ` (${balance})` : ''}` : 'Cleared'}
    </Text>
  );
}

export const TeacherTransportScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const date = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const query = useTeacherTransportStudents({ date });
  const vehiclesQuery = useTeacherTransportVehicles({ enabled: false });
  const { markPickup, cancelPickup, reassign } = useTeacherTransportActions();

  const [pickupFor, setPickupFor] = useState<TeacherTransportStudent | null>(null);
  const [pickupName, setPickupName] = useState('Parent');
  const [pickupNotes, setPickupNotes] = useState('');

  const [reassignFor, setReassignFor] = useState<TeacherTransportStudent | null>(null);
  const [reassignMode, setReassignMode] = useState<'vehicle' | 'trip'>('vehicle');
  const [vehicles, setVehicles] = useState<TeacherTransportVehicle[]>([]);
  const [trips, setTrips] = useState<TeacherTransportTrip[]>([]);
  const [selectedVehicle, setSelectedVehicle] = useState<number | null>(null);
  const [selectedTrip, setSelectedTrip] = useState<number | null>(null);
  const [reassignReason, setReassignReason] = useState('');

  const tripsForSelectedVehicle = selectedVehicle
    ? trips.filter((t) => (t.vehicle?.id ?? null) === selectedVehicle)
    : [];

  const loadVehiclesIfNeeded = async () => {
    if (vehicles.length > 0) return;
    const res = await vehiclesQuery.refetch();
    if (res.data) {
      setVehicles(res.data.vehicles ?? []);
      setTrips(res.data.trips ?? []);
    }
  };

  const confirmPickup = async () => {
    if (!pickupFor) return;
    try {
      await markPickup.mutateAsync({
        student_id: pickupFor.id,
        date,
        direction: 'evening',
        picked_up_by: pickupName.trim() || 'Parent',
        notes: pickupNotes.trim() || undefined,
      });
      setPickupFor(null);
      setPickupNotes('');
      Alert.alert('Recorded', 'Parent pickup logged.');
    } catch (err) {
      Alert.alert('Error', (err as Error).message);
    }
  };

  const undoPickup = async (student: TeacherTransportStudent) => {
    if (!student.pickup) return;
    try {
      await cancelPickup.mutateAsync(student.pickup.id);
    } catch (err) {
      Alert.alert('Error', (err as Error).message);
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
    if (reassignMode === 'vehicle') {
      if (!selectedVehicle || !selectedTrip) {
        Alert.alert('Select vehicle and trip', 'Choose a vehicle, then a trip for that vehicle.');
        return;
      }
    } else if (!selectedTrip) {
      Alert.alert('Select a trip', 'Please choose a trip.');
      return;
    }
    try {
      await reassign.mutateAsync({
        student_id: reassignFor.id,
        start_date: date,
        end_date: date,
        mode: selectedTrip ? 'trip' : reassignMode,
        vehicle_id: selectedVehicle ?? undefined,
        trip_id: selectedTrip ?? undefined,
        reason: reassignReason.trim() || undefined,
      });
      setReassignFor(null);
      Alert.alert('Saved', 'Temporary transport change applied for today.');
    } catch (err) {
      Alert.alert('Error', (err as Error).message);
    }
  };

  const renderItem = ({ item }: { item: TeacherTransportStudent }) => {
    const pickupActive = !!item.pickup;
    const eveningOwn = item.evening?.type === 'own_means';
    const morningOwn = item.morning?.type === 'own_means';
    const bothOwn = morningOwn && eveningOwn;
    const busy = markPickup.isPending || cancelPickup.isPending || reassign.isPending;

    return (
      <View style={[styles.card, { borderColor: palette.border, backgroundColor: palette.surface }]}>
        <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.full_name}</Text>
        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
          {item.admission_number}
          {item.class_name ? ` · ${item.class_name}` : ''}
          {item.stream_name ? ` / ${item.stream_name}` : ''}
        </Text>
        <FeeBadge status={item.fee_status} balance={item.outstanding_balance} />

        <Text style={[styles.legLabel, { color: palette.textSecondary }]}>Morning</Text>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm }}>{formatLeg(item.morning)}</Text>
        <Text style={[styles.legLabel, { color: palette.textSecondary }]}>Evening</Text>
        <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm }}>
          {pickupActive ? 'Picked by parent — evening trip skipped' : formatLeg(item.evening)}
        </Text>

        <View style={styles.actionRow}>
          {bothOwn ? (
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>Own means — no actions today</Text>
          ) : eveningOwn ? null : pickupActive ? (
            <Button label="Undo pickup" variant="secondary" onPress={() => void undoPickup(item)} disabled={busy} />
          ) : (
            <Button label="Collected by parent" onPress={() => setPickupFor(item)} disabled={busy} />
          )}
          {!bothOwn ? (
            <Button label="Change vehicle/trip" variant="secondary" onPress={() => void openReassign(item)} disabled={busy} />
          ) : null}
        </View>
      </View>
    );
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data?.students ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl refreshing={query.isRefetching} onRefresh={() => void query.refetch()} tintColor={colors.primary} />
        }
        ListHeaderComponent={
          <AcademicScreenHeader title="Teacher transport" subtitle={`Today · ${date}`} onBack={() => navigation.goBack()} />
        }
        renderItem={renderItem}
        ListEmptyComponent={
          query.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : query.isError ? (
            <Pressable onPress={() => void query.refetch()}>
              <Text style={{ color: colors.error, textAlign: 'center' }}>{(query.error as Error).message}</Text>
            </Pressable>
          ) : (
            <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No transport data for today.</Text>
          )
        }
      />

      <Modal visible={!!pickupFor} transparent animationType="slide" onRequestClose={() => setPickupFor(null)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalCard, { backgroundColor: palette.surface }]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: fontSizes.lg }}>Parent pickup</Text>
            <Text style={{ color: palette.textSecondary, marginVertical: spacing.sm }}>
              {pickupFor?.full_name} — evening trip skipped for today.
            </Text>
            <TextField label="Picked up by" value={pickupName} onChangeText={setPickupName} />
            <TextField label="Notes" value={pickupNotes} onChangeText={setPickupNotes} multiline />
            <View style={styles.modalActions}>
              <Button label="Cancel" variant="secondary" onPress={() => setPickupFor(null)} />
              <Button label="Save" onPress={() => void confirmPickup()} loading={markPickup.isPending} />
            </View>
          </View>
        </View>
      </Modal>

      <Modal visible={!!reassignFor} transparent animationType="slide" onRequestClose={() => setReassignFor(null)}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalCard, { backgroundColor: palette.surface, maxHeight: '85%' }]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: fontSizes.lg }}>Temporary change</Text>
            <Text style={{ color: palette.textSecondary, marginVertical: spacing.sm }}>
              {reassignFor?.full_name} — applies to {date}
            </Text>
            <View style={styles.modeRow}>
              {(['vehicle', 'trip'] as const).map((m) => (
                <Pressable
                  key={m}
                  onPress={() => setReassignMode(m)}
                  style={[styles.modeBtn, { borderColor: colors.primary, backgroundColor: reassignMode === m ? colors.primary : 'transparent' }]}
                >
                  <Text style={{ color: reassignMode === m ? colors.white : colors.primary, fontWeight: '600' }}>
                    {m === 'vehicle' ? 'Vehicle' : 'Trip'}
                  </Text>
                </Pressable>
              ))}
            </View>
            <ScrollView style={{ maxHeight: 240 }}>
              {reassignMode === 'vehicle' ? (
                <>
                  {vehicles.map((v) => (
                    <Pressable
                      key={v.id}
                      onPress={() => { setSelectedVehicle(v.id); setSelectedTrip(null); }}
                      style={[styles.option, { borderColor: palette.border, backgroundColor: selectedVehicle === v.id ? `${colors.primary}20` : 'transparent' }]}
                    >
                      <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{v.vehicle_number}</Text>
                      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
                        {v.driver_name ?? 'No driver'} · Cap {v.capacity ?? '—'}
                      </Text>
                    </Pressable>
                  ))}
                  {selectedVehicle ? (
                    <>
                      <Text style={{ color: palette.textPrimary, fontWeight: '600', marginTop: spacing.sm }}>Trips for vehicle</Text>
                      {tripsForSelectedVehicle.length === 0 ? (
                        <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>No trips for this vehicle.</Text>
                      ) : (
                        tripsForSelectedVehicle.map((t) => (
                          <Pressable
                            key={t.id}
                            onPress={() => setSelectedTrip(t.id)}
                            style={[styles.option, { borderColor: palette.border, backgroundColor: selectedTrip === t.id ? `${colors.primary}20` : 'transparent' }]}
                          >
                            <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{t.name ?? `Trip #${t.id}`}</Text>
                            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
                              {[t.direction, t.departure_time].filter(Boolean).join(' · ')}
                            </Text>
                          </Pressable>
                        ))
                      )}
                    </>
                  ) : null}
                </>
              ) : (
                trips.map((t) => (
                  <Pressable
                    key={t.id}
                    onPress={() => setSelectedTrip(t.id)}
                    style={[styles.option, { borderColor: palette.border, backgroundColor: selectedTrip === t.id ? `${colors.primary}20` : 'transparent' }]}
                  >
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{t.name ?? `Trip #${t.id}`}</Text>
                    <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
                      {[t.direction, t.departure_time, t.vehicle?.vehicle_number].filter(Boolean).join(' · ')}
                    </Text>
                  </Pressable>
                ))
              )}
            </ScrollView>
            <TextField label="Reason (optional)" value={reassignReason} onChangeText={setReassignReason} />
            <View style={styles.modalActions}>
              <Button label="Cancel" variant="secondary" onPress={() => setReassignFor(null)} />
              <Button label="Apply today" onPress={() => void saveReassign()} loading={reassign.isPending} />
            </View>
          </View>
        </View>
      </Modal>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  card: { padding: 12, borderRadius: 12, borderWidth: StyleSheet.hairlineWidth, marginBottom: 12 },
  legLabel: { fontSize: 10, textTransform: 'uppercase', fontWeight: '700', marginTop: 8 },
  actionRow: { flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 12 },
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalCard: { padding: 16, borderTopLeftRadius: 20, borderTopRightRadius: 20, paddingBottom: Platform.OS === 'ios' ? 28 : 16 },
  modalActions: { flexDirection: 'row', justifyContent: 'flex-end', gap: 8, marginTop: 12 },
  modeRow: { flexDirection: 'row', gap: 8, marginBottom: 12 },
  modeBtn: { flex: 1, paddingVertical: 8, borderRadius: 8, alignItems: 'center', borderWidth: 1 },
  option: { padding: 10, borderWidth: 1, borderRadius: 8, marginBottom: 8 },
});
