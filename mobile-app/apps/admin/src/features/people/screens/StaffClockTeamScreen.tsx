import { useStaffClockRoster, useStaffMemberClockHistory } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useState } from 'react';
import { ActivityIndicator, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'StaffClockTeam'>;

export const StaffClockTeamScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
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
          <ActivityIndicator color={colors.primary} />
        ) : (rosterQuery.data?.length ?? 0) === 0 ? (
          <Text style={{ color: palette.textSecondary }}>No staff available for your access level.</Text>
        ) : (
          <>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginBottom: spacing.sm }}>
              Select a staff member
            </Text>
            <View style={styles.roster}>
              {(rosterQuery.data ?? []).map((member) => {
                const active = selectedStaffId === member.id;
                return (
                  <Pressable
                    key={member.id}
                    onPress={() => setSelectedStaffId(member.id)}
                    style={[
                      styles.chip,
                      {
                        backgroundColor: active ? colors.primary : 'transparent',
                        borderColor: active ? colors.primary : palette.border,
                      },
                    ]}
                  >
                    <Text style={{ color: active ? colors.white : palette.textPrimary, fontSize: fontSizes.sm }}>
                      {member.full_name}
                    </Text>
                  </Pressable>
                );
              })}
            </View>

            <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.lg, marginBottom: spacing.sm }}>
              {selectedName}
            </Text>

            {historyQuery.isLoading ? (
              <ActivityIndicator color={colors.primary} />
            ) : (historyQuery.data?.history.length ?? 0) === 0 ? (
              <Text style={{ color: palette.textSecondary }}>No clock records found.</Text>
            ) : (
              (historyQuery.data?.history ?? []).map((item) => (
                <View key={item.id} style={[styles.row, { borderBottomColor: palette.border }]}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: fontSizes.sm }}>
                    {item.date ?? '—'}
                  </Text>
                  <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                    In: {item.check_in_time ?? '—'}
                    {item.check_in_distance_meters != null ? ` (${item.check_in_distance_meters}m)` : ''}
                  </Text>
                  <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
                    Out: {item.check_out_time ?? '—'}
                    {item.check_out_distance_meters != null ? ` (${item.check_out_distance_meters}m)` : ''}
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
  roster: { flexDirection: 'row', flexWrap: 'wrap', gap: 8 },
  chip: { paddingHorizontal: 10, paddingVertical: 6, borderRadius: 20, borderWidth: 1 },
  row: { paddingVertical: 10, borderBottomWidth: StyleSheet.hairlineWidth },
});
