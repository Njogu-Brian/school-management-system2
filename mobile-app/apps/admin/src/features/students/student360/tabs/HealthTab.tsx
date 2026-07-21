import type { StudentDetail } from '@erp/core';
import { useMedicalRecords, useUploadMedicalCertificate } from '@erp/core';
import { Button, EmptyState, FinanceFieldSection, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import * as DocumentPicker from 'expo-document-picker';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, Linking, Pressable, StyleSheet, Text, View } from 'react-native';
import type { StudentsStackParamList } from '../../../../navigation/studentsStackTypes';
import { showError, showSuccess } from '../../../shared/utils/feedback';

export interface HealthTabProps {
  student: StudentDetail;
}

export const HealthTab: React.FC<HealthTabProps> = ({ student }) => {
  const { colors, palette, typography, spacing, radius } = useTheme();
  const navigation = useNavigation<StackNavigationProp<StudentsStackParamList>>();
  const medicalQuery = useMedicalRecords(student.id);
  const uploadMutation = useUploadMedicalCertificate();
  const [uploadingId, setUploadingId] = useState<number | null>(null);

  const profileRows = useMemo(
    () => [
      { label: 'Blood group', value: student.bloodGroup ?? '—' },
      { label: 'Preferred hospital', value: student.preferredHospital ?? '—' },
      {
        label: 'Allergies',
        value: student.hasAllergies
          ? student.allergiesNotes?.trim() || 'Yes (no notes)'
          : 'None reported',
      },
      {
        label: 'Immunization',
        value:
          student.isFullyImmunized == null
            ? '—'
            : student.isFullyImmunized
              ? 'Fully immunized'
              : 'Not fully immunized',
      },
      { label: 'Emergency contact', value: student.emergencyContact.name ?? '—' },
      { label: 'Emergency phone', value: student.emergencyContact.phone ?? '—' },
    ],
    [student],
  );

  const records = medicalQuery.data ?? [];

  const onAttachCertificate = async (recordId: number) => {
    try {
      const result = await DocumentPicker.getDocumentAsync({
        type: ['image/*', 'application/pdf'],
        copyToCacheDirectory: true,
        multiple: false,
      });
      if (result.canceled || !result.assets?.length) return;
      const asset = result.assets[0];
      setUploadingId(recordId);
      await uploadMutation.mutateAsync({
        studentId: student.id,
        recordId,
        file: {
          uri: asset.uri,
          name: asset.name ?? 'certificate',
          type: asset.mimeType ?? 'application/octet-stream',
        },
      });
      showSuccess('Uploaded', 'Certificate attached to the record.');
    } catch (err) {
      showError('Upload failed', (err as Error).message);
    } finally {
      setUploadingId(null);
    }
  };

  return (
    <>
      <View style={{ marginBottom: spacing.md }}>
        <Button
          label="Log medical record"
          variant="secondary"
          onPress={() =>
            navigation.navigate('MedicalRecordForm', {
              studentId: student.id,
              studentName: student.fullName,
            })
          }
        />
      </View>
      <FinanceFieldSection title="Health profile" rows={profileRows} />
      {medicalQuery.isLoading ? (
        <View style={{ paddingVertical: spacing.md, alignItems: 'center' }}>
          <ActivityIndicator color={colors.primary} />
        </View>
      ) : medicalQuery.isError ? (
        <EmptyState
          title="Could not load clinic records"
          message={(medicalQuery.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void medicalQuery.refetch()}
        />
      ) : records.length > 0 ? (
        <View style={{ marginTop: spacing.md }}>
          <Text
            style={{
              color: palette.textSub,
              fontSize: typography.overline.fontSize,
              fontWeight: '700',
              letterSpacing: typography.overline.letterSpacing,
              marginBottom: spacing.sm,
            }}
          >
            CLINIC RECORDS
          </Text>
          {records.map((record) => (
            <View
              key={record.id}
              style={[
                styles.recordCard,
                {
                  backgroundColor: palette.surfaceRaised,
                  borderColor: palette.borderSubtle,
                  borderRadius: radius.md,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                },
              ]}
            >
              <Text style={{ color: palette.textMain, fontWeight: '700' }} numberOfLines={2}>
                {record.title ?? record.record_type ?? 'Medical record'}
              </Text>
              <Text
                style={{
                  color: palette.textSub,
                  fontSize: typography.caption.fontSize,
                  marginTop: spacing.xs,
                }}
              >
                {[record.record_date, record.doctor_name, record.vaccination_name]
                  .filter(Boolean)
                  .join(' · ') || '—'}
              </Text>
              <View style={[styles.recordActions, { marginTop: spacing.sm, gap: spacing.md }]}>
                {record.certificate_url ? (
                  <Pressable onPress={() => void Linking.openURL(record.certificate_url!)}>
                    <Text
                      style={{
                        color: colors.primary,
                        fontWeight: '700',
                        fontSize: typography.caption.fontSize,
                      }}
                    >
                      View certificate
                    </Text>
                  </Pressable>
                ) : null}
                <Pressable
                  onPress={() => void onAttachCertificate(record.id)}
                  disabled={uploadingId === record.id}
                >
                  <Text
                    style={{
                      color: colors.primary,
                      fontWeight: '700',
                      fontSize: typography.caption.fontSize,
                    }}
                  >
                    {uploadingId === record.id
                      ? 'Uploading…'
                      : record.certificate_url
                        ? 'Replace certificate'
                        : 'Attach certificate'}
                  </Text>
                </Pressable>
              </View>
            </View>
          ))}
        </View>
      ) : (
        <EmptyState
          title="No clinic records"
          message="No clinic visit records on file for this student."
          icon="medkit-outline"
        />
      )}
    </>
  );
};

const styles = StyleSheet.create({
  recordCard: {
    borderWidth: StyleSheet.hairlineWidth,
  },
  recordActions: { flexDirection: 'row', flexWrap: 'wrap' },
});
