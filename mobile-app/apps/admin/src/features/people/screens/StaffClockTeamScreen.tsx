import { useStaffClockRoster, useStaffMemberClockHistory } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ScrollView, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'StaffClockTeam'>;

export const StaffClockTeamScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing, typography } = useTheme();
  const rosterQuery = useStaffClockRoster();
  const [selectedStaffId, setSelectedStaffId] = useState<number | null>(null);
  const historyQuery = useStaffMemberClockHistory(selectedStaffId ?? 0, {
    enabled: selectedStaffId != null && selectedStaffId > 0,
  });

  useEffect(() => {
    if (rosterQuery.data?.length && !selectedStaffId) {
      setSelectedStaffId(rosterQuery.data[0].id);
    }
  }, [rosterQuery.data, selectedStaffId]);

  const selectedName =
    historyQuery.data?.staff?.full_name ??
    rosterQuery.data?.find((m) => m.id === selectedStaffId)?.full_name ??
    'Clock history';

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="Team clock history"
          subtitle="Last 90 days per staff member"
          onBack={() => navigation.goBack()}
        />

        {rosterQuery.isLoading ? (
          <SkeletonListRows variant="compact" count={5} />
        ) : (rosterQuery.data?.length ?? 0) === 0 ? (
          <EmptyState
            title="No staff available"
            message="No staff available for your access level."
            icon="people-outline"
          />
        ) : (
          <>
            <FilterChipRow label="Select a staff member">
              {(rosterQuery.data ?? []).map((member) => (
                <FilterChip
                  key={member.id}
                  label={member.full_name}
                  active={selectedStaffId === member.id}
                  onPress={() => setSelectedStaffId(member.id)}
                />
              ))}
            </FilterChipRow>

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
        )}
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderBottomWidth: StyleSheet.hairlineWidth },
});
