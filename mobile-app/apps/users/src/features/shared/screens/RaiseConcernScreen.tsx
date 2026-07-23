import {
  useConcernStaffOptions,
  useCreateConcern,
  useCurrentUser,
  useInfiniteStudentList,
  type ConcernCategory,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  TextField,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useEffect, useMemo, useState } from 'react';
import { Pressable, Text, View } from 'react-native';
import type { ConcernsSharedParamList } from '../types/concerns';
import { showError, showSuccess } from '../utils/feedback';

const CATEGORIES: Array<{ id: ConcernCategory; label: string }> = [
  { id: 'academic', label: 'Academic' },
  { id: 'teacher', label: 'Teacher' },
  { id: 'financial', label: 'Financial' },
  { id: 'transport', label: 'Transport' },
  { id: 'meals', label: 'Meals' },
  { id: 'administration', label: 'Admin' },
];

type SelectedStudent = { id: number; fullName: string; meta?: string };
type SelectedStaff = { id: number; fullName: string };

/**
 * Shared Raise Concern — search + multi-select students, then tag staff and submit.
 * Creates one concern per selected student (same category / description / staff).
 */
export const RaiseConcernScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ConcernsSharedParamList, 'RaiseConcern'>>();
  const { colors, palette, spacing, typography, radius } = useTheme();
  const create = useCreateConcern();
  const user = useCurrentUser();

  const selfStudentId = user?.studentId ?? null;
  const lockedStudent = selfStudentId != null || route.params?.studentId != null;

  const [studentSearchDraft, setStudentSearchDraft] = useState('');
  const [studentSearch, setStudentSearch] = useState('');
  const [staffSearchDraft, setStaffSearchDraft] = useState('');
  const [staffSearch, setStaffSearch] = useState('');
  const [selectedStudents, setSelectedStudents] = useState<SelectedStudent[]>([]);
  const [selectedStaff, setSelectedStaff] = useState<SelectedStaff[]>([]);
  const [category, setCategory] = useState<ConcernCategory>('academic');
  const [description, setDescription] = useState('');

  const studentsQuery = useInfiniteStudentList(
    {
      search: studentSearch.trim() || undefined,
      classroomId: null,
      streamId: null,
      status: 'active',
      perPage: 20,
    },
    { enabled: !lockedStudent && studentSearch.trim().length >= 1 },
  );

  const staffQuery = useConcernStaffOptions(staffSearch, {
    enabled: staffSearch.trim().length >= 2,
  });

  const studentHits = useMemo(
    () => studentsQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [studentsQuery.data],
  );
  const staffHits = staffQuery.data ?? [];

  useEffect(() => {
    const presetId = route.params?.studentId ?? selfStudentId;
    if (presetId == null) return;
    setSelectedStudents((prev) => {
      if (prev.some((s) => s.id === presetId)) return prev;
      return [
        {
          id: presetId,
          fullName: selfStudentId === presetId ? 'Myself' : `Student #${presetId}`,
        },
      ];
    });
  }, [route.params?.studentId, selfStudentId]);

  const toggleStudent = (item: SelectedStudent) => {
    if (lockedStudent) return;
    setSelectedStudents((prev) =>
      prev.some((s) => s.id === item.id) ? prev.filter((s) => s.id !== item.id) : [...prev, item],
    );
  };

  const toggleStaff = (item: SelectedStaff) => {
    setSelectedStaff((prev) =>
      prev.some((s) => s.id === item.id) ? prev.filter((s) => s.id !== item.id) : [...prev, item],
    );
  };

  const submit = async () => {
    if (selectedStudents.length === 0) {
      showError('Select students', 'Search and select at least one student.');
      return;
    }
    if (selectedStaff.length === 0) {
      showError('Tag staff', 'Search and select at least one staff member to notify.');
      return;
    }
    if (!description.trim()) {
      showError('Description required', 'Please describe the concern.');
      return;
    }
    try {
      await create.mutateAsync({
        student_ids: selectedStudents.map((s) => s.id),
        category,
        description: description.trim(),
        staff_ids: selectedStaff.map((s) => s.id),
      });
      const n = selectedStudents.length;
      showSuccess(
        n === 1 ? 'Concern submitted' : `${n} concerns submitted`,
        'Tagged staff will be notified.',
      );
      navigation.goBack();
    } catch (err) {
      showError('Submit failed', err instanceof Error ? err.message : 'Could not create concern.');
    }
  };

  return (
    <ScreenContainer
      scroll
      edges={['top', 'bottom']}
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
    >
      <AcademicScreenHeader
        title="Raise concern"
        subtitle="Search students, tag staff, then submit"
        onBack={() => navigation.goBack()}
      />

      {!lockedStudent ? (
        <View style={{ marginBottom: spacing.md }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>
            Students
          </Text>
          <TextField
            label="Search student"
            value={studentSearchDraft}
            onChangeText={setStudentSearchDraft}
            placeholder="Name or admission #"
            returnKeyType="search"
            onSubmitEditing={() => setStudentSearch(studentSearchDraft.trim())}
          />
          <Button
            label="Search students"
            variant="secondary"
            onPress={() => setStudentSearch(studentSearchDraft.trim())}
            style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}
          />
          {studentSearch.trim().length === 0 ? (
            <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
              Use search to find students — the full list is not shown.
            </Text>
          ) : studentsQuery.isLoading ? (
            <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>Searching…</Text>
          ) : studentHits.length === 0 ? (
            <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>No matches.</Text>
          ) : (
            studentHits.slice(0, 20).map((s) => {
              const active = selectedStudents.some((x) => x.id === s.id);
              return (
                <Pressable
                  key={s.id}
                  onPress={() =>
                    toggleStudent({
                      id: s.id,
                      fullName: s.fullName,
                      meta: [s.admissionNumber, s.className].filter(Boolean).join(' · '),
                    })
                  }
                  style={{
                    backgroundColor: active ? `${colors.primary}14` : palette.surface,
                    borderColor: active ? colors.primary : palette.border,
                    borderWidth: 1,
                    borderRadius: radius.md,
                    padding: spacing.md,
                    marginTop: spacing.sm,
                  }}
                >
                  <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                    {[s.admissionNumber, s.className].filter(Boolean).join(' · ')}
                    {active ? ' · Selected' : ''}
                  </Text>
                </Pressable>
              );
            })
          )}
        </View>
      ) : null}

      {selectedStudents.length > 0 ? (
        <FilterChipRow label={`Selected (${selectedStudents.length})`}>
          {selectedStudents.map((s) => (
            <FilterChip
              key={s.id}
              label={s.fullName}
              active
              onPress={() => {
                if (!lockedStudent) toggleStudent(s);
              }}
            />
          ))}
        </FilterChipRow>
      ) : null}

      <FilterChipRow label="Category">
        {CATEGORIES.map((c) => (
          <FilterChip
            key={c.id}
            label={c.label}
            active={category === c.id}
            onPress={() => setCategory(c.id)}
          />
        ))}
      </FilterChipRow>

      <TextField
        label="Description"
        value={description}
        onChangeText={setDescription}
        placeholder="Describe what happened and how we can help"
        multiline
      />

      <View style={{ marginTop: spacing.md, marginBottom: spacing.sm }}>
        <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.xs }}>
          Tag staff
        </Text>
        <TextField
          label="Search staff"
          value={staffSearchDraft}
          onChangeText={setStaffSearchDraft}
          placeholder="Name or employee # (min 2 characters)"
          returnKeyType="search"
          onSubmitEditing={() => setStaffSearch(staffSearchDraft.trim())}
        />
        <Button
          label="Search staff"
          variant="secondary"
          onPress={() => setStaffSearch(staffSearchDraft.trim())}
          style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}
        />
        {staffSearch.trim().length < 2 ? (
          <Text style={{ color: palette.textMuted, marginTop: spacing.sm, fontSize: typography.caption.fontSize }}>
            Search and select who should be notified / follow up.
          </Text>
        ) : staffQuery.isLoading ? (
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>Searching…</Text>
        ) : staffHits.length === 0 ? (
          <Text style={{ color: palette.textSecondary, marginTop: spacing.sm }}>No matches.</Text>
        ) : (
          staffHits.map((s) => {
            const active = selectedStaff.some((x) => x.id === s.id);
            return (
              <Pressable
                key={s.id}
                onPress={() => toggleStaff({ id: s.id, fullName: s.fullName })}
                style={{
                  backgroundColor: active ? `${colors.primary}14` : palette.surface,
                  borderColor: active ? colors.primary : palette.border,
                  borderWidth: 1,
                  borderRadius: radius.md,
                  padding: spacing.md,
                  marginTop: spacing.sm,
                }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.fullName}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                  {[s.employeeNumber, s.jobTitle].filter(Boolean).join(' · ')}
                  {active ? ' · Selected' : ''}
                </Text>
              </Pressable>
            );
          })
        )}
      </View>

      {selectedStaff.length > 0 ? (
        <FilterChipRow label={`Tagged staff (${selectedStaff.length})`}>
          {selectedStaff.map((s) => (
            <FilterChip key={s.id} label={s.fullName} active onPress={() => toggleStaff(s)} />
          ))}
        </FilterChipRow>
      ) : null}

      <Button
        label={
          selectedStudents.length > 1
            ? `Submit ${selectedStudents.length} concerns`
            : 'Submit concern'
        }
        loading={create.isPending}
        onPress={() => void submit()}
        style={{ marginTop: spacing.md }}
      />
    </ScreenContainer>
  );
};
