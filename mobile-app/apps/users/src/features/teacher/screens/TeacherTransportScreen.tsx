import {
  useTeacherTransportActions,
  useTeacherTransportStudents,
  useTeacherTransportVehicles,
  type TeacherTransportLeg,
  type TeacherTransportStudent,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import {
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { showError, showSuccess } from '../../shared/utils/feedback';

const TransportLegLine: React.FC<{ label: 'Morning' | 'Evening'; leg?: TeacherTransportLeg | null }> = ({
  label,
  leg,
}) => {
  const { palette, typography, spacing } = useTheme();

  let detail: string;
  let iconName: React.ComponentProps<typeof Soft3DIcon>['name'] = 'bus-outline';
  let tone: React.ComponentProps<typeof Soft3DIcon>['tone'] = 'cyan';

  if (!leg) {
    detail = 'No assignment';
    iconName = 'help-circle-outline';
    tone = 'muted';
  } else if (leg.type === 'own_means') {
    detail = `Own means${leg.reason ? ` · ${leg.reason}` : ''}`;
    iconName = 'walk-outline';
    tone = 'amber';
  } else {
    detail =
      [
        leg.trip_name,
        leg.vehicle_registration,
        leg.departure_time ? `Dep ${leg.departure_time}` : null,
        leg.drop_off_point ? `Drop: ${leg.drop_off_point}` : null,
      ]
        .filter(Boolean)
        .join(' · ') || 'Assigned';
  }

  return (
    <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.xs, marginTop: 4 }}>
      <Soft3DIcon name={iconName} tone={tone} size={22} />
      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, flex: 1 }} numberOfLines={2}>
        <Text style={{ fontWeight: '700', color: palette.textSecondary }}>{label}: </Text>
        {detail}
      </Text>
    </View>
  );
};

export const TeacherTransportScreen: React.FC = () => {
  const navigation = useNavigation();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const date = useMemo(() => new Date().toISOString().slice(0, 10), []);
  const [search, setSearch] = useState('');
  const rosterQuery = useTeacherTransportStudents({ date, search: search.trim() || undefined });
  const vehiclesQuery = useTeacherTransportVehicles();
  const { markPickup, cancelPickup, reassign } = useTeacherTransportActions();

  const [pickupFor, setPickupFor] = useState<TeacherTransportStudent | null>(null);
  const [pickupName, setPickupName] = useState('Parent');
  const [pickupNotes, setPickupNotes] = useState('');

  const [reassignFor, setReassignFor] = useState<TeacherTransportStudent | null>(null);
  const [reassignMode, setReassignMode] = useState<'vehicle' | 'trip'>('trip');
  const [reassignTargetId, setReassignTargetId] = useState<number | null>(null);
  const [reassignStartDate, setReassignStartDate] = useState(date);
  const [reassignEndDate, setReassignEndDate] = useState('');
  const [reassignReason, setReassignReason] = useState('');

  const students = rosterQuery.data?.students ?? [];

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
      showSuccess('Recorded', 'Parent pickup saved.');
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Could not record pickup.');
    }
  };

  const undoPickup = async (student: TeacherTransportStudent) => {
    if (!student.pickup) return;
    try {
      await cancelPickup.mutateAsync(student.pickup.id);
      showSuccess('Cancelled', 'Pickup record removed.');
    } catch (err) {
      showError('Error', err instanceof Error ? err.message : 'Could not cancel pickup.');
    }
  };

  const openReassign = (student: TeacherTransportStudent) => {
    setReassignFor(student);
    setReassignMode('trip');
    setReassignTargetId(null);
    setReassignStartDate(date);
    setReassignEndDate('');
    setReassignReason('');
  };

  const confirmReassign = async () => {
    if (!reassignFor) return;
    if (!reassignTargetId) {
      showError('Select an option', `Choose a ${reassignMode} to assign.`);
      return;
    }
    if (!reassignReason.trim()) {
      showError('Reason required', 'Tell the office why transport is being reassigned.');
      return;
    }
    try {
      await reassign.mutateAsync({
        student_id: reassignFor.id,
        start_date: reassignStartDate.trim() || date,
        end_date: reassignEndDate.trim() || undefined,
        mode: reassignMode,
        vehicle_id: reassignMode === 'vehicle' ? reassignTargetId : undefined,
        trip_id: reassignMode === 'trip' ? reassignTargetId : undefined,
        reason: reassignReason.trim(),
      });
      showSuccess('Reassigned', 'Temporary transport change saved.');
      setReassignFor(null);
    } catch (err) {
      showError('Reassign failed', err instanceof Error ? err.message : 'Could not reassign transport.');
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={students}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl, flexGrow: 1 }}
        ListHeaderComponent={
          <View style={{ marginBottom: spacing.sm }}>
            <AcademicScreenHeader
              title="Transport pickup"
              subtitle={`Roster for ${date}`}
              onBack={() => navigation.goBack()}
            />
            <TextField
              label="Search"
              value={search}
              onChangeText={setSearch}
              placeholder="Name or admission #"
            />
          </View>
        }
        renderItem={({ item }) => (
          <View
            style={[
              styles.card,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
                marginBottom: spacing.sm,
              },
            ]}
          >
            <View style={{ flexDirection: 'row', alignItems: 'center', gap: spacing.sm }}>
              <Soft3DIcon name="bus-outline" tone="cyan" size={40} />
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.full_name}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[item.admission_number, item.class_name, item.stream_name].filter(Boolean).join(' · ')}
                </Text>
              </View>
            </View>
            <TransportLegLine label="Morning" leg={item.morning} />
            <TransportLegLine label="Evening" leg={item.evening} />
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginTop: spacing.sm }}>
              {item.pickup ? (
                <Button
                  label="Undo pickup"
                  variant="ghost"
                  onPress={() => void undoPickup(item)}
                  loading={cancelPickup.isPending}
                  style={{ flex: 1 }}
                />
              ) : (
                <Button
                  label="Mark parent pickup"
                  onPress={() => {
                    setPickupFor(item);
                    setPickupName('Parent');
                    setPickupNotes('');
                  }}
                  style={{ flex: 1 }}
                />
              )}
              <Button
                label="Reassign transport"
                variant="ghost"
                onPress={() => openReassign(item)}
                style={{ flex: 1 }}
              />
            </View>
            {item.pickup ? (
              <Text style={{ color: colors.success, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
                Picked up by {item.pickup.picked_up_by ?? 'parent'}
              </Text>
            ) : null}
          </View>
        )}
        refreshControl={
          <RefreshControl
            refreshing={rosterQuery.isRefetching}
            onRefresh={() => void rosterQuery.refetch()}
            colors={[colors.primary]}
          />
        }
        ListEmptyComponent={
          rosterQuery.isLoading ? (
            <SkeletonListRows variant="compact" count={5} />
          ) : rosterQuery.isError ? (
            <EmptyState
              title="Could not load roster"
              message={(rosterQuery.error as Error)?.message ?? 'Something went wrong.'}
              icon="alert-circle-outline"
              actionLabel="Retry"
              onAction={() => void rosterQuery.refetch()}
            />
          ) : (
            <EmptyState
              title="No students"
              message="No transport roster for today."
              icon="bus-outline"
            />
          )
        }
      />

      <Modal visible={!!pickupFor} transparent animationType="fade" onRequestClose={() => setPickupFor(null)}>
        <Pressable style={styles.modalBackdrop} onPress={() => setPickupFor(null)}>
          <Pressable
            style={[
              styles.modalCard,
              { backgroundColor: palette.surface, borderRadius: radius.lg, padding: spacing.md },
            ]}
            onPress={(e) => e.stopPropagation()}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
              Record pickup · {pickupFor?.full_name}
            </Text>
            <TextField label="Picked up by" value={pickupName} onChangeText={setPickupName} />
            <TextField label="Notes" value={pickupNotes} onChangeText={setPickupNotes} />
            <Button label="Confirm" onPress={() => void confirmPickup()} loading={markPickup.isPending} />
            <Button label="Cancel" variant="ghost" onPress={() => setPickupFor(null)} style={{ marginTop: spacing.xs }} />
          </Pressable>
        </Pressable>
      </Modal>

      <Modal visible={!!reassignFor} transparent animationType="fade" onRequestClose={() => setReassignFor(null)}>
        <Pressable style={styles.modalBackdrop} onPress={() => setReassignFor(null)}>
          <Pressable
            style={[
              styles.modalCard,
              { backgroundColor: palette.surface, borderRadius: radius.lg, padding: spacing.md, maxHeight: '85%' },
            ]}
            onPress={(e) => e.stopPropagation()}
          >
            <ScrollView>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
                Reassign transport · {reassignFor?.full_name}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
                Temporary changes need school approval before they take effect.
              </Text>

              <FilterChipRow label="Mode">
                {(['trip', 'vehicle'] as const).map((m) => (
                  <FilterChip
                    key={m}
                    label={m === 'trip' ? 'Trip' : 'Vehicle'}
                    active={reassignMode === m}
                    onPress={() => {
                      setReassignMode(m);
                      setReassignTargetId(null);
                    }}
                  />
                ))}
              </FilterChipRow>

              {vehiclesQuery.isLoading ? (
                <SkeletonListRows variant="compact" count={2} />
              ) : (
                <FilterChipRow label={reassignMode === 'trip' ? 'Trip' : 'Vehicle'}>
                  {reassignMode === 'trip'
                    ? (vehiclesQuery.data?.trips ?? []).map((t) => (
                        <FilterChip
                          key={t.id}
                          label={t.name ?? `Trip #${t.id}`}
                          active={reassignTargetId === t.id}
                          onPress={() => setReassignTargetId(t.id)}
                        />
                      ))
                    : (vehiclesQuery.data?.vehicles ?? []).map((v) => (
                        <FilterChip
                          key={v.id}
                          label={v.vehicle_number}
                          active={reassignTargetId === v.id}
                          onPress={() => setReassignTargetId(v.id)}
                        />
                      ))}
                </FilterChipRow>
              )}

              <TextField label="Start date (YYYY-MM-DD)" value={reassignStartDate} onChangeText={setReassignStartDate} />
              <TextField
                label="End date (optional, YYYY-MM-DD)"
                value={reassignEndDate}
                onChangeText={setReassignEndDate}
                placeholder="Leave blank for a single day"
              />
              <TextField label="Reason" value={reassignReason} onChangeText={setReassignReason} multiline />

              <Button label="Submit reassignment" onPress={() => void confirmReassign()} loading={reassign.isPending} style={{ marginTop: spacing.sm }} />
              <Button
                label="Cancel"
                variant="ghost"
                onPress={() => setReassignFor(null)}
                style={{ marginTop: spacing.xs }}
              />
            </ScrollView>
          </Pressable>
        </Pressable>
      </Modal>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  modalBackdrop: {
    flex: 1,
    backgroundColor: 'rgba(0,0,0,0.45)',
    justifyContent: 'center',
    padding: 24,
  },
  modalCard: { borderWidth: StyleSheet.hairlineWidth },
});
