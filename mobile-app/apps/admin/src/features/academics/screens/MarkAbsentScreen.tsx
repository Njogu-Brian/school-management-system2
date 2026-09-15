import {
  attendanceApi,
  useAttendanceReasonCodes,
  useInfiniteStudentList,
  useMarkStudentsAbsent,
  type AttendanceReasonFields,
} from '@erp/core';
import {
  AcademicScreenHeader,
  attendanceReasonLabel,
  Button,
  DatePickerField,
  DockedActionLayout,
  EmptyState,
  FilterChip,
  FilterChipRow,
  FooterDock,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  TextField,
  useTheme,
} from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import type { AcademicsStackParamList } from '../../../navigation/academicsStackTypes';
import { confirmAction, showError, showSuccess } from '../../shared/utils/feedback';

type Props = StackScreenProps<AcademicsStackParamList, 'MarkAbsent'>;

function formatDateYmd(d: Date): string {
  const y = d.getFullYear();
  const m = String(d.getMonth() + 1).padStart(2, '0');
  const day = String(d.getDate()).padStart(2, '0');
  return `${y}-${m}-${day}`;
}

export const MarkAbsentScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, typography } = useTheme();
  const markAbsent = useMarkStudentsAbsent();
  const reasonCodesQuery = useAttendanceReasonCodes();
  const reasonCodes = reasonCodesQuery.data ?? [];

  const [selectedDate, setSelectedDate] = useState(() => new Date());
  const dateStr = formatDateYmd(selectedDate);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [selected, setSelected] = useState<Record<number, true>>({});
  const [reasonCodeId, setReasonCodeId] = useState<number | null>(null);
  const [notes, setNotes] = useState('');
  const [schoolDayOk, setSchoolDayOk] = useState<boolean | null>(null);
  const [schoolDayMessage, setSchoolDayMessage] = useState<string | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setSearch(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  useEffect(() => {
    void attendanceApi.getSchoolDay(dateStr).then((res) => {
      if (res.success && res.data) {
        if (res.data.is_future) {
          setSchoolDayOk(false);
          setSchoolDayMessage('Cannot mark attendance for a future date.');
        } else if (!res.data.is_school_day) {
          setSchoolDayOk(false);
          setSchoolDayMessage('This date is not a school day (weekend, holiday, or break).');
        } else {
          setSchoolDayOk(true);
          setSchoolDayMessage(null);
        }
      } else {
        setSchoolDayOk(null);
        setSchoolDayMessage(null);
      }
    });
  }, [dateStr]);

  const listQuery = useInfiniteStudentList({ search: search || undefined, perPage: 40 });
  const students = useMemo(
    () => listQuery.data?.pages.flatMap((page) => page.items) ?? [],
    [listQuery.data],
  );
  const selectedIds = useMemo(
    () => Object.keys(selected).map((id) => Number(id)).filter((id) => selected[id]),
    [selected],
  );
  const selectedCount = selectedIds.length;

  const toggle = (id: number) => {
    setSelected((prev) => {
      const next = { ...prev };
      if (next[id]) delete next[id];
      else next[id] = true;
      return next;
    });
  };

  const reason: AttendanceReasonFields = {
    reason_code_id: reasonCodeId,
    reason: notes.trim() || null,
    excuse_notes: notes.trim() || null,
  };
  const reasonText = attendanceReasonLabel(reason, reasonCodes);

  const submit = () => {
    if (selectedCount === 0) {
      showError('Select students', 'Tick at least one student to mark absent.');
      return;
    }
    if (schoolDayOk === false) {
      showError('Not a school day', schoolDayMessage ?? 'Pick a valid school day.');
      return;
    }
    const names = students
      .filter((s) => selected[s.id])
      .map((s) => s.fullName)
      .slice(0, 8);
    const extra = selectedCount > names.length ? ` and ${selectedCount - names.length} more` : '';
    confirmAction(
      'Mark as absent?',
      `Mark ${selectedCount} student${selectedCount === 1 ? '' : 's'} absent for ${dateStr}?\n\n${names.join('\n')}${extra}${reasonText ? `\n\nReason: ${reasonText}` : ''}`,
      'Yes, mark absent',
      async () => {
        try {
          const data = await markAbsent.mutateAsync({
            date: dateStr,
            student_ids: selectedIds,
            ...reason,
          });
          setSelected({});
          showSuccess('Marked absent', data?.message ?? `${selectedCount} students marked absent.`);
        } catch (err) {
          showError('Could not submit', (err as Error).message);
        }
      },
      true,
    );
  };

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} clearFloatingTabBar={false}>
      <DockedActionLayout
        headerMaxFraction={0.58}
        header={
          <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
            <AcademicScreenHeader
              title="Mark as absent"
              subtitle="Search, tick students, then confirm"
              onBack={() => navigation.goBack()}
            />
            <DatePickerField value={selectedDate} onChange={setSelectedDate} maximumDate={new Date()} />
            {schoolDayMessage ? (
              <View style={[styles.warnBanner, { backgroundColor: `${colors.warning}18`, borderColor: colors.warning }]}>
                <Text style={{ color: colors.warning, fontSize: typography.body.fontSize }}>{schoolDayMessage}</Text>
              </View>
            ) : null}
            <SearchBar
              value={searchInput}
              onChangeText={setSearchInput}
              placeholder="Search name or admission no."
            />
            {reasonCodes.length > 0 ? (
              <FilterChipRow label="Reason">
                {reasonCodes.map((code) => (
                  <FilterChip
                    key={code.id}
                    label={code.name}
                    active={reasonCodeId === code.id}
                    onPress={() => setReasonCodeId(reasonCodeId === code.id ? null : code.id)}
                  />
                ))}
              </FilterChipRow>
            ) : null}
            <TextField
              label="Notes"
              value={notes}
              onChangeText={setNotes}
              placeholder="Optional reason for parents"
              containerStyle={{ marginBottom: spacing.sm }}
            />
            <Text
              style={{
                color: palette.textMuted,
                fontSize: typography.caption.fontSize,
                marginBottom: spacing.sm,
              }}
            >
              {selectedCount} selected
            </Text>
          </View>
        }
        footer={
          <FooterDock>
            <Button
              label={selectedCount > 0 ? `Mark ${selectedCount} absent` : 'Mark as absent'}
              onPress={submit}
              disabled={selectedCount === 0 || schoolDayOk === false || markAbsent.isPending}
              loading={markAbsent.isPending}
            />
          </FooterDock>
        }
      >
        {listQuery.isLoading ? (
          <View style={{ paddingHorizontal: spacing.md }}>
            <SkeletonListRows variant="avatar" count={6} />
          </View>
        ) : (
          <FlatList
            data={students}
            keyExtractor={(item) => String(item.id)}
            keyboardShouldPersistTaps="handled"
            onEndReached={() => {
              if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
                void listQuery.fetchNextPage();
              }
            }}
            onEndReachedThreshold={0.4}
            contentContainerStyle={{
              paddingHorizontal: spacing.md,
              paddingBottom: spacing.sm,
              flexGrow: 1,
            }}
            renderItem={({ item }) => {
              const checked = !!selected[item.id];
              return (
                <Pressable
                  onPress={() => toggle(item.id)}
                  style={[
                    styles.row,
                    {
                      borderColor: checked ? colors.primary : palette.border,
                      backgroundColor: palette.surfaceRaised,
                    },
                  ]}
                >
                  <Ionicons
                    name={checked ? 'checkbox' : 'square-outline'}
                    size={24}
                    color={checked ? colors.primary : palette.textMuted}
                  />
                  <View style={{ flex: 1, minWidth: 0 }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '600' }} numberOfLines={1}>
                      {item.fullName}
                    </Text>
                    <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                      {item.admissionNumber}
                      {item.className ? ` · ${item.className}` : ''}
                      {item.streamName ? ` ${item.streamName}` : ''}
                    </Text>
                  </View>
                </Pressable>
              );
            }}
            ListEmptyComponent={
              <EmptyState
                title={search ? 'No matches' : 'Search students'}
                message={
                  search
                    ? 'Try a different name or admission number.'
                    : 'Type a name or admission number, then tick who is absent.'
                }
                icon="search-outline"
              />
            }
          />
        )}
      </DockedActionLayout>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  warnBanner: {
    borderWidth: 1,
    borderRadius: 8,
    padding: 10,
    marginBottom: 8,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
  },
});
