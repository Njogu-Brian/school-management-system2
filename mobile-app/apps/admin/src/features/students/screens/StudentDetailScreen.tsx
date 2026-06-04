import { useStudentDetail, type StudentDetail, type StudentSummary } from '@erp/core';
import {
  ScreenContainer,
  StudentStatusBadge,
  useTheme,
} from '@erp/ui';
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
import { Ionicons } from '@expo/vector-icons';
import type { StudentsStackParamList } from '../../../navigation/studentsStackTypes';

type Props = StackScreenProps<StudentsStackParamList, 'StudentDetail'>;

function summaryAsDetail(summary: StudentSummary): StudentDetail {
  return {
    ...summary,
    dateOfBirth: null,
    phone: null,
    email: null,
    admissionDate: null,
    enrollmentYear: null,
    address: null,
    category: null,
    nemisNumber: null,
    outstandingBalance: null,
  };
}

function DetailField({ label, value }: { label: string; value: string }) {
  const { palette, fontSizes, spacing } = useTheme();
  return (
    <View style={[styles.field, { borderBottomColor: palette.border, paddingVertical: spacing.sm }]}>
      <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, fontWeight: '600' }}>
        {label}
      </Text>
      <Text style={{ color: palette.textPrimary, fontSize: fontSizes.sm, marginTop: 2 }}>{value}</Text>
    </View>
  );
}

/**
 * Student profile shell — routing + header fields only (no 360 / Family / Fees / Academics tabs).
 */
export const StudentDetailScreen: React.FC<Props> = ({ route }) => {
  const { studentId, summary } = route.params;
  const { palette, colors, spacing, fontSizes, radius, shadows } = useTheme();

  const query = useStudentDetail(studentId);
  const student = query.data ?? (summary ? summaryAsDetail(summary) : undefined);

  const classLine = useMemo(
    () => [student?.className, student?.streamName].filter(Boolean).join(' · ') || '—',
    [student],
  );

  if (query.isLoading && !student) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (!student) {
    return (
      <ScreenContainer contentContainerStyle={styles.centered}>
        <Text style={{ color: colors.error }}>Student not found.</Text>
        {query.isError ? (
          <Pressable onPress={() => void query.refetch()} style={{ marginTop: spacing.sm }}>
            <Text style={{ color: colors.primary, fontWeight: '600' }}>Retry</Text>
          </Pressable>
        ) : null}
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer style={styles.flex}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <View
          style={[
            styles.hero,
            {
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderRadius: radius.lg,
              padding: spacing.md,
            },
            shadows.sm,
          ]}
        >
          {student.avatarUrl ? (
            <Image source={{ uri: student.avatarUrl }} style={styles.avatar} />
          ) : (
            <View style={[styles.avatar, styles.avatarPh, { backgroundColor: palette.accent }]}>
              <Ionicons name="person" size={40} color={colors.primary} />
            </View>
          )}
          <Text style={[styles.name, { color: palette.textPrimary, fontSize: fontSizes.xl }]}>
            {student.fullName}
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
            {student.admissionNumber}
          </Text>
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm, marginTop: 4 }}>
            {classLine}
          </Text>
          <View style={[styles.badges, { marginTop: spacing.sm, gap: spacing.xs }]}>
            <StudentStatusBadge kind="enrollment" enrollmentStatus={student.enrollmentStatus} />
            {student.feeStatus ? (
              <StudentStatusBadge kind="fee" feeStatus={student.feeStatus} />
            ) : null}
          </View>
        </View>

        <Text
          style={[
            styles.section,
            { color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: spacing.lg },
          ]}
        >
          Profile
        </Text>
        <DetailField label="Gender" value={student.gender} />
        <DetailField label="Date of birth" value={student.dateOfBirth ?? '—'} />
        <DetailField label="Admission date" value={student.admissionDate ?? '—'} />
        <DetailField label="Phone" value={student.phone ?? '—'} />
        <DetailField label="Email" value={student.email ?? '—'} />
        <DetailField label="Address" value={student.address ?? '—'} />
        <DetailField label="NEMIS" value={student.nemisNumber ?? '—'} />

        <View
          style={[
            styles.note,
            {
              backgroundColor: `${colors.primary}10`,
              borderColor: `${colors.primary}33`,
              borderRadius: radius.md,
              marginTop: spacing.lg,
              padding: spacing.md,
            },
          ]}
        >
          <Text style={{ color: palette.textSecondary, fontSize: fontSizes.sm }}>
            Student 360 tabs (Family, Fees, Academics) will be added in a later sprint.
          </Text>
        </View>
      </ScrollView>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  flex: { flex: 1 },
  centered: { flex: 1, justifyContent: 'center', alignItems: 'center' },
  hero: {
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
  avatar: { width: 80, height: 80, borderRadius: 40, marginBottom: 12 },
  avatarPh: { alignItems: 'center', justifyContent: 'center' },
  name: { fontWeight: '700', textAlign: 'center' },
  badges: { flexDirection: 'row', flexWrap: 'wrap', justifyContent: 'center' },
  section: { fontWeight: '700', letterSpacing: 0.4, textTransform: 'uppercase' },
  field: { borderBottomWidth: StyleSheet.hairlineWidth },
  note: { borderWidth: 1 },
});
