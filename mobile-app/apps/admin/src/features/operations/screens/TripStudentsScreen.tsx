import {
  useInfiniteStudentList,
  useTransportRoute,
  useTripMutations,
  type RouteStudentRecord,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import {
  ActivityIndicator,
  FlatList,
  Modal,
  Pressable,
  RefreshControl,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<OperationsStackParamList, 'TripStudents'>;
type AssignMode = 'permanent' | 'short_term';
type Leg = 'morning' | 'evening' | 'both';

export const TripStudentsScreen: React.FC<Props> = ({ route, navigation }) => {
  const { tripId, tripName } = route.params;
  const { colors, palette, spacing, typography, radius } = useTheme();
  const query = useTransportRoute(tripId);
  const { assignRouteStudent } = useTripMutations();

  const [assignOpen, setAssignOpen] = useState(false);
  const [studentSearch, setStudentSearch] = useState('');
  const [selectedStudentId, setSelectedStudentId] = useState<number | null>(null);
  const [selectedStudentName, setSelectedStudentName] = useState('');
  const [mode, setMode] = useState<AssignMode>('permanent');
  const [leg, setLeg] = useState<Leg>('both');
  const [startDate, setStartDate] = useState(new Date().toISOString().slice(0, 10));
  const [endDate, setEndDate] = useState('');
  const [reason, setReason] = useState('');

  const studentsQuery = useInfiniteStudentList(
    {
      search: studentSearch.trim() || undefined,
      classroomId: null,
      streamId: null,
      status: 'active',
      gender: 'all',
      perPage: 20,
    },
    { enabled: assignOpen },
  );
  const studentResults = useMemo(
    () => studentsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [studentsQuery.data],
  );

  const students: RouteStudentRecord[] = query.data?.students ?? [];

  const submitAssign = async () => {
    if (!selectedStudentId) {
      showError('Missing student', 'Search and select a student to assign.');
      return;
    }
    if (mode === 'short_term' && (!startDate || !endDate)) {
      showError('Missing dates', 'Short-term assignment needs start and end dates.');
      return;
    }
    try {
      await assignRouteStudent.mutateAsync({
        routeId: tripId,
        student_id: selectedStudentId,
        mode,
        leg,
        start_date: mode === 'short_term' ? startDate : undefined,
        end_date: mode === 'short_term' ? endDate : undefined,
        reason: mode === 'short_term' ? reason.trim() || undefined : undefined,
      });
      showSuccess(
        'Assigned',
        mode === 'permanent'
          ? `${selectedStudentName} permanently assigned to this route.`
          : `${selectedStudentName} short-term transfer saved.`,
      );
      setAssignOpen(false);
      setSelectedStudentId(null);
      setSelectedStudentName('');
      setReason('');
      void query.refetch();
    } catch (e) {
      showError('Assign failed', (e as Error).message);
    }
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ padding: spacing.md, paddingBottom: 0 }}>
        <AcademicScreenHeader
          title="Route students"
          subtitle={tripName ?? `Trip #${tripId}`}
          onBack={() => navigation.goBack()}
        />
        <Button
          label="Assign student"
          onPress={() => setAssignOpen(true)}
          style={{ alignSelf: 'flex-start', marginBottom: spacing.sm }}
        />
      </View>

      {query.isLoading ? (
        <ActivityIndicator color={palette.primary} style={{ marginTop: spacing.lg }} />
      ) : query.isError ? (
        <EmptyState
          title="Could not load students"
          message={(query.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      ) : (
        <FlatList
          data={students}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md, paddingTop: spacing.sm, paddingBottom: spacing.xl }}
          refreshControl={
            <RefreshControl
              refreshing={query.isRefetching}
              onRefresh={() => void query.refetch()}
              colors={[palette.primary]}
              tintColor={palette.primary}
            />
          }
          ListEmptyComponent={
            <EmptyState
              title="No students on this route"
              message="Assign a student permanently or for a short-term transfer."
              icon="people-outline"
              actionLabel="Assign student"
              onAction={() => setAssignOpen(true)}
            />
          }
          renderItem={({ item }) => (
            <View
              style={[
                styles.card,
                {
                  borderColor: palette.borderSubtle,
                  backgroundColor: palette.surfaceRaised,
                  borderRadius: radius.card,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                },
              ]}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.body.fontSize }}>
                {item.full_name}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                {[item.admission_number, item.class_name].filter(Boolean).join(' · ') || '—'}
              </Text>
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: spacing.xs }}>
                {item.is_special
                  ? `Short-term${item.special_end_date ? ` · until ${item.special_end_date}` : ''}`
                  : item.leg
                    ? `Permanent · ${item.leg}`
                    : 'Permanent'}
              </Text>
            </View>
          )}
        />
      )}

      <Modal visible={assignOpen} animationType="slide" onRequestClose={() => setAssignOpen(false)}>
        <ScreenContainer scroll={false} style={{ flex: 1 }}>
          <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
            <AcademicScreenHeader title="Assign student" subtitle="Permanent or short-term" onBack={() => setAssignOpen(false)} />

            <Text style={[styles.label, { color: palette.textSecondary, marginTop: 0 }]}>Mode</Text>
            <FilterChipRow>
              <FilterChip label="Permanent" active={mode === 'permanent'} onPress={() => setMode('permanent')} />
              <FilterChip label="Short-term" active={mode === 'short_term'} onPress={() => setMode('short_term')} />
            </FilterChipRow>

            <Text style={[styles.label, { color: palette.textSecondary }]}>Leg</Text>
            <FilterChipRow>
              {(['both', 'morning', 'evening'] as Leg[]).map((l) => (
                <FilterChip key={l} label={l} active={leg === l} onPress={() => setLeg(l)} />
              ))}
            </FilterChipRow>

            {mode === 'short_term' ? (
              <>
                <TextField label="Start date (YYYY-MM-DD)" value={startDate} onChangeText={setStartDate} />
                <TextField label="End date (YYYY-MM-DD)" value={endDate} onChangeText={setEndDate} />
                <TextField label="Reason (optional)" value={reason} onChangeText={setReason} />
              </>
            ) : null}

            <TextField
              label="Search student"
              value={studentSearch}
              onChangeText={setStudentSearch}
              placeholder="Name or admission #"
            />
            {selectedStudentId ? (
              <Text style={{ color: colors.primary, fontWeight: '600', marginBottom: spacing.sm }}>
                Selected: {selectedStudentName}
              </Text>
            ) : null}

            {studentsQuery.isLoading ? (
              <ActivityIndicator color={colors.primary} />
            ) : (
              studentResults.slice(0, 20).map((s) => (
                <Pressable
                  key={s.id}
                  onPress={() => {
                    setSelectedStudentId(s.id);
                    setSelectedStudentName(s.fullName);
                  }}
                  style={[
                    styles.pickRow,
                    {
                      borderColor: selectedStudentId === s.id ? colors.primary : palette.borderSubtle,
                      backgroundColor: palette.surfaceRaised,
                      borderRadius: radius.control,
                      padding: spacing.sm,
                      marginBottom: spacing.xs,
                    },
                  ]}
                >
                  <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
                  <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                    {s.admissionNumber}
                  </Text>
                </Pressable>
              ))
            )}

            <Button
              label={assignRouteStudent.isPending ? 'Saving…' : 'Save assignment'}
              onPress={() => void submitAssign()}
              disabled={assignRouteStudent.isPending}
              style={{ marginTop: spacing.md }}
            />
          </ScrollView>
        </ScreenContainer>
      </Modal>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  card: { borderWidth: StyleSheet.hairlineWidth },
  label: {
    fontSize: 12,
    fontWeight: '600',
    marginBottom: 6,
    marginTop: 12,
    textTransform: 'uppercase',
    letterSpacing: 0.4,
  },
  pickRow: { borderWidth: StyleSheet.hairlineWidth },
});
