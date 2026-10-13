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
  const [endDate, setEndDate] = useState<Date | null>(null);
  const dateStr = formatDateYmd(selectedDate);
  const endStr = endDate ? formatDateYmd(endDate) : null;
  const maxDate = useMemo(() => {
    const next = new Date();
    next.setDate(next.getDate() + 60);
    return next;
  }, []);
  const [searchInput, setSearchInput] = useState('');
  const [search, setSearch] = useState('');
  const [selected, setSelected] = useState<Record<number, true>>({});
  const [reasons, setReasons] = useState<Record<number, AttendanceReasonFields>>({});
  const [schoolDayOk, setSchoolDayOk] = useState<boolean | null>(null);
  const [schoolDayMessage, setSchoolDayMessage] = useState<string | null>(null);

  useEffect(() => {
    const t = setTimeout(() => setSearch(searchInput.trim()), 350);
    return () => clearTimeout(t);
  }, [searchInput]);

  useEffect(() => {
    void attendanceApi.getSchoolDay(dateStr).then((res) => {
      if (res.success && res.data) {
        if (!res.data.is_school_day && !endStr) {
          setSchoolDayOk(false);
          setSchoolDayMessage('This date is not a school day (weekend, holiday, or break).');
        } else {
          setSchoolDayOk(true);
          setSchoolDayMessage(endStr ? 'Non-school days between the dates are skipped.' : null);
        }
      } else {
        setSchoolDayOk(null);
        setSchoolDayMessage(null);
      }
    });
  }, [dateStr, endStr]);

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

  const submit = () => {
    if (selectedCount === 0) {
      showError('Select students', 'Tick at least one student to mark absent.');
      return;
    }
    if (endStr && endStr < dateStr) {
      showError('Invalid dates', 'End date must be on or after the start date.');
      return;
    }
    if (schoolDayOk === false) {
      showError('Not a school day', schoolDayMessage ?? 'Pick a valid school day.');
      return;
    }
    const lines = students
      .filter((s) => selected[s.id])
      .slice(0, 8)
      .map((s) => {
        const label = attendanceReasonLabel(reasons[s.id], reasonCodes);
        return label ? `${s.fullName} — ${label}` : s.fullName;
      });
    const extra = selectedCount > lines.length ? `\nand ${selectedCount - lines.length} more` : '';
    const range = endStr && endStr !== dateStr ? `${dateStr} to ${endStr}` : dateStr;
    confirmAction(
      'Mark as absent?',
      `Mark ${selectedCount} student${selectedCount === 1 ? '' : 's'} absent for ${range}?\n\n${lines.join('\n')}${extra}`,
      'Yes, mark absent',
      async () => {
        try {
          const data = await markAbsent.mutateAsync({
            date: dateStr,
            end_date: endStr,
            records: selectedIds.map((id) => ({
              student_id: id,
              reason_code_id: reasons[id]?.reason_code_id ?? null,
              reason: reasons[id]?.reason ?? null,
              excuse_notes: reasons[id]?.excuse_notes ?? null,
            })),
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
              subtitle="Each student gets their own reason"
              onBack={() => navigation.goBack()}
            />
            <DatePickerField
              label="Start date"
              value={selectedDate}
              onChange={(next) => {
                setSelectedDate(next);
                if (endDate && formatDateYmd(next) > formatDateYmd(endDate)) {
                  setEndDate(next);
                }
              }}
              maximumDate={maxDate}
            />
            {endDate ? (
              <View style={{ marginTop: spacing.sm }}>
                <DatePickerField
                  label="End date"
                  value={endDate}
                  onChange={setEndDate}
                  minimumDate={selectedDate}
                  maximumDate={maxDate}
                />
                <Button
                  label="Clear end date"
                  variant="ghost"
                  onPress={() => setEndDate(null)}
                  style={{ marginTop: spacing.xs }}
                />
              </View>
            ) : (
              <Button
                label="Add end date"
                variant="secondary"
                onPress={() => setEndDate(selectedDate)}
                style={{ marginTop: spacing.sm, marginBottom: spacing.sm }}
              />
            )}
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
              const reason = reasons[item.id];
              return (
                <View
                  style={[
                    styles.row,
                    {
                      borderColor: checked ? colors.primary : palette.border,
                      backgroundColor: palette.surfaceRaised,
                      alignItems: 'flex-start',
                    },
                  ]}
                >
                  <Pressable onPress={() => toggle(item.id)} style={styles.pickRow}>
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
                  {checked ? (
                    <View style={{ marginTop: spacing.sm }}>
                      {reasonCodes.length > 0 ? (
                        <FilterChipRow label="Reason">
                          {reasonCodes.map((code) => (
                            <FilterChip
                              key={code.id}
                              label={code.name}
                              active={reason?.reason_code_id === code.id}
                              onPress={() =>
                                setReasons((prev) => ({
                                  ...prev,
                                  [item.id]: {
                                    ...prev[item.id],
                                    reason_code_id: prev[item.id]?.reason_code_id === code.id ? null : code.id,
                                  },
                                }))
                              }
                            />
                          ))}
                        </FilterChipRow>
                      ) : null}
                      <TextField
                        label="Notes"
                        value={reason?.excuse_notes ?? ''}
                        onChangeText={(text) =>
                          setReasons((prev) => ({
                            ...prev,
                            [item.id]: {
                              ...prev[item.id],
                              reason: text,
                              excuse_notes: text,
                            },
                          }))
                        }
                        placeholder="This student's reason"
                      />
                    </View>
                  ) : null}
                </View>
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
    borderWidth: StyleSheet.hairlineWidth,
    borderRadius: 10,
    padding: 12,
    marginBottom: 8,
  },
  pickRow: {
    flexDirection: 'row',
    alignItems: 'center',
    gap: 12,
  },
});
