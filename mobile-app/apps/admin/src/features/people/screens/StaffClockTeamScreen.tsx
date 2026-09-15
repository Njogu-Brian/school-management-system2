import { useStaffClockRoster, useStaffMemberClockHistory } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  StatusBadge,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'StaffClockTeam'>;

export const StaffClockTeamScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography, radius } = useTheme();
  const rosterQuery = useStaffClockRoster();
  const [search, setSearch] = useState('');
  const [selectedStaffId, setSelectedStaffId] = useState<number | null>(null);
  const historyQuery = useStaffMemberClockHistory(selectedStaffId ?? 0, {
    enabled: selectedStaffId != null && selectedStaffId > 0,
  });

  const roster = useMemo(() => {
    const q = search.trim().toLowerCase();
    const rows = rosterQuery.data ?? [];
    const filtered = q
      ? rows.filter((m) => m.full_name.toLowerCase().includes(q) || String(m.staff_id ?? '').toLowerCase().includes(q))
      : rows;
    return [...filtered].sort((a, b) => Number(Boolean(b.clocked_in)) - Number(Boolean(a.clocked_in)) || a.full_name.localeCompare(b.full_name));
  }, [rosterQuery.data, search]);

  const selectedName =
    historyQuery.data?.staff?.full_name ??
    rosterQuery.data?.find((m) => m.id === selectedStaffId)?.full_name ??
    'Clock history';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Team attendance"
          subtitle="Active staff only · today clock-in"
          onBack={() => navigation.goBack()}
        />

        <SearchBar value={search} onChangeText={setSearch} placeholder="Search staff…" />

        {rosterQuery.isLoading ? (
          <SkeletonListRows variant="compact" count={6} />
        ) : roster.length === 0 ? (
          <EmptyState
            title="No staff available"
            message="Archived or inactive staff are hidden from this roster."
            icon="people-outline"
          />
        ) : (
          <View style={{ marginTop: spacing.md }}>
            {roster.map((member) => {
              const active = selectedStaffId === member.id;
              return (
                <Pressable
                  key={member.id}
                  onPress={() => setSelectedStaffId(member.id)}
                  style={{
                    backgroundColor: active ? palette.surfaceMuted : palette.surfaceRaised,
                    borderColor: active ? palette.primary : palette.borderSubtle,
                    borderWidth: 1,
                    borderRadius: radius.md,
                    padding: spacing.md,
                    marginBottom: spacing.sm,
                    flexDirection: 'row',
                    alignItems: 'center',
                    gap: spacing.sm,
                  }}
                >
                  <View style={{ flex: 1 }}>
                    <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{member.full_name}</Text>
                    <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                      {member.clocked_in
                        ? `Clocked in ${member.check_in_time ?? ''}`.trim()
                        : 'Not clocked in'}
                    </Text>
                  </View>
                  <StatusBadge
                    label={member.clocked_in ? 'In' : 'Out'}
                    tone={member.clocked_in ? 'success' : 'warning'}
                  />
                </Pressable>
              );
            })}
          </View>
        )}

        {selectedStaffId ? (
          <>
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '700',
                fontSize: typography.titleSmall.fontSize,
                marginTop: spacing.lg,
                marginBottom: spacing.sm,
              }}
            >
              {selectedName}
            </Text>
            {historyQuery.isLoading ? (
              <SkeletonListRows variant="compact" count={4} />
            ) : (historyQuery.data?.history.length ?? 0) === 0 ? (
              <EmptyState
                title="No clock records"
                message="No clock records found for this staff member."
                icon="time-outline"
              />
            ) : (
              (historyQuery.data?.history ?? []).map((item) => (
                <View
                  key={item.id}
                  style={[styles.row, { borderBottomColor: palette.borderSubtle, paddingVertical: spacing.sm }]}
                >
                  <Text
                    style={{
                      color: palette.textPrimary,
                      fontWeight: '700',
                      fontSize: typography.caption.fontSize,
                    }}
                  >
                    {item.date ?? '—'}
                  </Text>
                  <Text
                    style={{
                      color: palette.textSecondary,
                      fontSize: typography.overline.fontSize,
                      marginTop: 2,
                    }}
                  >
                    In: {item.check_in_time ?? '—'}
                    {item.check_in_distance_meters != null ? ` (${item.check_in_distance_meters}m)` : ''}
                  </Text>
                  <Text
                    style={{
                      color: palette.textSecondary,
                      fontSize: typography.overline.fontSize,
                    }}
                  >
                    Out: {item.check_out_time ?? '—'}
                    {item.check_out_distance_meters != null
                      ? ` (${item.check_out_distance_meters}m)`
                      : ''}
                  </Text>
                </View>
              ))
            )}
          </>
        ) : null}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderBottomWidth: StyleSheet.hairlineWidth },
});
