import { useCan, useStaffDetail, type StaffDetail, type StaffSummary } from '@erp/core';
import { ScreenContainer, StaffEmploymentBadge, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import {
  ActivityIndicator,
  Image,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import type { PeopleStackParamList } from '../../../navigation/peopleStackTypes';

type Props = StackScreenProps<PeopleStackParamList, 'StaffDetail'>;

function summaryAsDetail(summary: StaffSummary): StaffDetail {
  return {
    ...summary,
    idNumber: null,
    personalEmail: null,
    maritalStatus: null,
    residentialAddress: null,
    emergencyContact: { name: null, relationship: null, phone: null },
    hireDate: null,
    terminationDate: null,
    employmentType: null,
    contractStartDate: null,
    contractEndDate: null,
    dateOfBirth: null,
    departmentId: null,
    staffCategoryId: null,
    jobTitleId: null,
    supervisorId: null,
    supervisorName: null,
    maxLessonsPerWeek: null,
  };
}

function FieldRow({ label, value }: { label: string; value: string | null | undefined }) {
  const { palette, fontSizes, spacing } = useTheme();
  return (
    <View style={{ marginBottom: spacing.md }}>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginBottom: 4 }}>
        {label}
      </Text>
      <Text style={{ color: palette.textPrimary, fontSize: fontSizes.md }}>{value || '—'}</Text>
    </View>
  );
}

export const StaffDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { staffId, summary } = route.params;
  const canView = useCan(['people.view', 'staff.view']);
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  const detailQuery = useStaffDetail(staffId, { enabled: canView });
  const staff = detailQuery.data ?? (summary ? summaryAsDetail(summary) : undefined);

  const orgLine = useMemo(() => {
    if (!staff) return '';
    return [staff.departmentName, staff.jobTitle].filter(Boolean).join(' · ');
  }, [staff]);

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>
          You need people.view permission to view staff profiles.
        </Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer style={{ flex: 1 }}>
      <View style={[styles.topBar, { paddingHorizontal: spacing.md, paddingTop: spacing.sm }]}>
        <Pressable
          onPress={() => navigation.goBack()}
          accessibilityRole="button"
          style={({ pressed }) => [{ opacity: pressed ? 0.7 : 1, padding: spacing.xs }]}
        >
          <Ionicons name="arrow-back" size={24} color={palette.textPrimary} />
        </Pressable>
        <Text style={{ flex: 1, fontSize: fontSizes.lg, fontWeight: '700', color: palette.textPrimary }}>
          Staff profile
        </Text>
      </View>

      {detailQuery.isLoading && !staff ? (
        <ActivityIndicator style={{ marginTop: spacing.xl }} />
      ) : detailQuery.isError && !staff ? (
        <View style={styles.denied}>
          <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>
            {(detailQuery.error as Error)?.message ?? 'Failed to load profile.'}
          </Text>
        </View>
      ) : staff ? (
        <ScrollView
          contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
          showsVerticalScrollIndicator={false}
        >
          <View
            style={[
              styles.headerCard,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
              },
              shadows.sm,
            ]}
          >
            <View style={styles.headerRow}>
              {staff.avatarUrl ? (
                <Image source={{ uri: staff.avatarUrl }} style={styles.avatar} />
              ) : (
                <View
                  style={[
                    styles.avatar,
                    styles.avatarPlaceholder,
                    { backgroundColor: palette.accent },
                  ]}
                >
                  <Ionicons name="person" size={32} color={colors.primary} />
                </View>
              )}
              <View style={{ flex: 1 }}>
                <Text style={{ fontSize: fontSizes.lg, fontWeight: '700', color: palette.textPrimary }}>
                  {staff.fullName}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
                  {staff.employeeNumber}
                </Text>
                {orgLine ? (
                  <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 4 }}>
                    {orgLine}
                  </Text>
                ) : null}
                <View style={{ marginTop: spacing.sm }}>
                  <StaffEmploymentBadge status={staff.employmentStatus} />
                </View>
              </View>
            </View>
          </View>

          <Text
            style={{
              marginTop: spacing.lg,
              marginBottom: spacing.sm,
              fontSize: fontSizes.sm,
              fontWeight: '700',
              color: palette.textSecondary,
              textTransform: 'uppercase',
            }}
          >
            Overview
          </Text>
          <View
            style={{
              backgroundColor: palette.surface,
              borderRadius: radius.lg,
              borderWidth: StyleSheet.hairlineWidth,
              borderColor: palette.border,
              padding: spacing.md,
            }}
          >
            <FieldRow label="System role" value={staff.systemRole} />
            <FieldRow label="Category" value={staff.staffCategory} />
            <FieldRow label="Work email" value={staff.email} />
            <FieldRow label="Phone" value={staff.phone} />
            <FieldRow label="Gender" value={staff.gender} />
            <FieldRow label="Supervisor" value={staff.supervisorName} />
            <FieldRow label="Hire date" value={staff.hireDate} />
            <FieldRow label="Employment type" value={staff.employmentType} />
          </View>

          <Text
            style={{
              marginTop: spacing.lg,
              color: palette.textSecondary,
              fontSize: fontSizes.xs,
              textAlign: 'center',
            }}
          >
            Full Staff 360 tabs (leave, payroll, attendance) ship in a later sprint.
          </Text>
        </ScrollView>
      ) : null}
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  topBar: { flexDirection: 'row', alignItems: 'center', gap: 8 },
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  headerCard: { borderWidth: StyleSheet.hairlineWidth },
  headerRow: { flexDirection: 'row', alignItems: 'center', gap: 12 },
  avatar: { width: 72, height: 72, borderRadius: 36 },
  avatarPlaceholder: { alignItems: 'center', justifyContent: 'center' },
});
