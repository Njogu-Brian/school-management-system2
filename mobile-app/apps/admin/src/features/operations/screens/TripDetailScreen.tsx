import {
  useInfiniteStudentList,
  useTransportRoute,
  useTripMutations,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  ConfirmDialog,
  EmptyState,
  FinanceFieldSection,
  ScreenContainer,
  SearchBar,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  Modal,
  Platform,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'TripDetail'>;

export const TripDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { tripId, tripName } = route.params;
  const { palette, spacing, typography, radius, colors } = useTheme();
  const query = useTransportRoute(tripId);
  const { remove, assignRouteStudent } = useTripMutations();
  const [deleteVisible, setDeleteVisible] = useState(false);

  const [assignOpen, setAssignOpen] = useState(false);
  const [studentSearch, setStudentSearch] = useState('');
  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);
  const [selectedStudentName, setSelectedStudentName] = useState('');
  const [leg, setLeg] = useState<'morning' | 'evening' | 'both'>('both');
  const [transferMode, setTransferMode] = useState<'permanent' | 'short_term'>('permanent');
  const [startDate, setStartDate] = useState(() => new Date().toISOString().slice(0, 10));
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');

  const studentsQuery = useInfiniteStudentList(
    { search: studentSearch },
    { enabled: assignOpen },
  );
  const studentOptions = useMemo(
    () => studentsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [studentsQuery.data],
  );

  const rows = useMemo(() => {
    const trip = query.data;
    if (!trip) return [];
    return [
      { label: 'Route', value: trip.name },
      { label: 'Vehicle', value: trip.vehicle_registration ?? '—' },
      { label: 'Driver', value: trip.driver_name ?? '—' },
      { label: 'Status', value: trip.status ?? '—' },
      { label: 'Students', value: String(trip.students_count ?? trip.students?.length ?? 0) },
      { label: 'Description', value: trip.description ?? '—' },
    ];
  }, [query.data]);

  const stopRows = useMemo(
    () =>
      (query.data?.drop_points ?? []).map((stop, i) => ({
        label: `Stop ${i + 1}`,
        value: [stop.name, stop.pickup_time].filter(Boolean).join(' · ') || '—',
      })),
    [query.data],
  );

  const students = query.data?.students ?? [];

  const submitDelete = () => {
    setDeleteVisible(false);
    void remove
      .mutateAsync(tripId)
      .then(() => navigation.goBack())
      .catch((e) => showError('Failed', (e as Error).message));
  };

  const resetAssign = () => {
    setAssignOpen(false);
    setSelectedStudentId(null);
    setSelectedStudentName('');
    setStudentSearch('');
    setLeg('both');
    setTransferMode('permanent');
    setEndDate('');
    setReason('');
  };

  const saveAssign = async () => {
    if (!selectedStudentId) {
      showError('Select a student', 'Choose a student to assign to this trip.');
      return;
    }
    if (transferMode === 'short_term' && !endDate.trim()) {
      showError('End date required', 'Short-term transfers need a start and end date.');
      return;
    }
    try {
      await assignRouteStudent.mutateAsync({
        routeId: tripId,
        student_id: selectedStudentId,
        mode: transferMode,
        leg,
        start_date: transferMode === 'short_term' ? startDate : undefined,
        end_date: transferMode === 'short_term' ? endDate : undefined,
        reason:
          transferMode === 'short_term' ? reason.trim() || 'Short-term transfer' : undefined,
      });
      showSuccess(
        'Assigned',
        transferMode === 'permanent'
          ? 'Permanent transport assignment saved.'
          : 'Short-term transport assignment activated.',
      );
      resetAssign();
      void query.refetch();
    } catch (e) {
      showError('Failed', (e as Error).message);
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title={tripName ?? 'Trip detail'} subtitle={`Trip #${tripId}`} onBack={() => navigation.goBack()} />
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginBottom: spacing.md }}>
          <Button label="Edit" onPress={() => navigation.navigate('TripForm', { tripId })} />
          <Button
            label="Students"
            variant="secondary"
            onPress={() =>
              navigation.navigate('TripStudents', {
                tripId,
                tripName: tripName ?? query.data?.name,
              })
            }
          />
          <Button label="Assign student" variant="secondary" onPress={() => setAssignOpen(true)} />
          <Button label="Delete" variant="secondary" onPress={() => setDeleteVisible(true)} />
        </View>
        {query.isLoading ? (
          <ActivityIndicator color={palette.primary} />
        ) : query.isError ? (
          <EmptyState
            title="Could not load trip"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : (
          <>
            <FinanceFieldSection title="Route" rows={rows} />
            {stopRows.length > 0 ? <FinanceFieldSection title="Stops" rows={stopRows} /> : null}

            <Text
              style={{
                color: palette.textPrimary,
                fontSize: typography.titleSmall.fontSize,
                fontWeight: typography.titleSmall.fontWeight,
                marginTop: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              Students on route
            </Text>
            {students.length === 0 ? (
              <EmptyState
                title="No students"
                message="No permanent or short-term assignments for this trip."
                icon="people-outline"
              />
            ) : (
              students.map((s) => (
                <View
                  key={s.id}
                  style={{
                    borderWidth: StyleSheet.hairlineWidth,
                    borderColor: palette.border,
                    borderRadius: radius.card,
                    padding: spacing.sm,
                    marginBottom: spacing.xs,
                    backgroundColor: palette.surfaceRaised,
                  }}
                >
                  <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{s.full_name}</Text>
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                    {[
                      s.admission_number,
                      s.class_name,
                      s.leg ? `${s.leg} leg` : null,
                      s.is_special ? `Short-term${s.special_end_date ? ` → ${s.special_end_date}` : ''}` : 'Permanent',
                    ]
                      .filter(Boolean)
                      .join(' · ')}
                  </Text>
                </View>
              ))
            )}
          </>
        )}
      </ScrollView>

      <ConfirmDialog
        visible={deleteVisible}
        title="Delete trip"
        message="Remove this trip?"
        confirmLabel="Delete"
        cancelLabel="Cancel"
        destructive
        loading={remove.isPending}
        onConfirm={submitDelete}
        onCancel={() => setDeleteVisible(false)}
      />

      <Modal visible={assignOpen} transparent animationType="slide" onRequestClose={resetAssign}>
        <View style={styles.modalBackdrop}>
          <View style={[styles.modalCard, { backgroundColor: palette.surface }]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.title.fontSize }}>
              Assign / transfer student
            </Text>
            <Text style={{ color: palette.textSecondary, marginVertical: spacing.sm }}>
              {selectedStudentName || 'Pick a student, then choose permanent or short-term.'}
            </Text>

            <View style={styles.modeRow}>
              {([
                { key: 'permanent' as const, label: 'Permanent' },
                { key: 'short_term' as const, label: 'Short-term' },
              ]).map((m) => (
                <Pressable
                  key={m.key}
                  onPress={() => setTransferMode(m.key)}
                  style={[
                    styles.modeBtn,
                    {
                      borderColor: colors.primary,
                      backgroundColor: transferMode === m.key ? colors.primary : 'transparent',
                    },
                  ]}
                >
                  <Text style={{ color: transferMode === m.key ? colors.white : colors.primary, fontWeight: '600' }}>
                    {m.label}
                  </Text>
                </Pressable>
              ))}
            </View>

            {transferMode === 'permanent' ? (
              <View style={styles.modeRow}>
                {(['morning', 'evening', 'both'] as const).map((l) => (
                  <Pressable
                    key={l}
                    onPress={() => setLeg(l)}
                    style={[
                      styles.modeBtn,
                      {
                        borderColor: palette.border,
                        backgroundColor: leg === l ? `${colors.primary}20` : 'transparent',
                      },
                    ]}
                  >
                    <Text style={{ color: palette.textPrimary, fontWeight: '600', textTransform: 'capitalize' }}>{l}</Text>
                  </Pressable>
                ))}
              </View>
            ) : (
              <>
                <TextField label="Start date" value={startDate} onChangeText={setStartDate} placeholder="YYYY-MM-DD" />
                <TextField label="End date *" value={endDate} onChangeText={setEndDate} placeholder="YYYY-MM-DD" />
                <TextField label="Reason" value={reason} onChangeText={setReason} />
              </>
            )}

            <SearchBar
              value={studentSearch}
              onChangeText={setStudentSearch}
              placeholder="Search students"
            />
            <ScrollView style={{ maxHeight: 200, marginTop: spacing.sm }}>
              {studentOptions.map((s) => (
                <Pressable
                  key={s.id}
                  onPress={() => {
                    setSelectedStudentId(s.id);
                    setSelectedStudentName(s.fullName);
                  }}
                  style={[
                    styles.option,
                    {
                      borderColor: palette.border,
                      backgroundColor: selectedStudentId === s.id ? `${colors.primary}20` : 'transparent',
                    },
                  ]}
                >
                  <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                    {s.admissionNumber}
                  </Text>
                </Pressable>
              ))}
            </ScrollView>

            <View style={styles.modalActions}>
              <Button label="Cancel" variant="secondary" onPress={resetAssign} />
              <Button
                label="Save"
                onPress={() => void saveAssign()}
                loading={assignRouteStudent.isPending}
              />
            </View>
          </View>
        </View>
      </Modal>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  modalBackdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.5)', justifyContent: 'flex-end' },
  modalCard: {
    padding: 16,
    borderTopLeftRadius: 20,
    borderTopRightRadius: 20,
    paddingBottom: Platform.OS === 'ios' ? 28 : 16,
    maxHeight: '90%',
  },
  modalActions: { flexDirection: 'row', justifyContent: 'flex-end', gap: 8, marginTop: 12 },
  modeRow: { flexDirection: 'row', gap: 8, marginBottom: 12 },
  modeBtn: { flex: 1, paddingVertical: 8, borderRadius: 8, alignItems: 'center', borderWidth: 1 },
  option: { padding: 10, borderWidth: 1, borderRadius: 8, marginBottom: 8 },
});
