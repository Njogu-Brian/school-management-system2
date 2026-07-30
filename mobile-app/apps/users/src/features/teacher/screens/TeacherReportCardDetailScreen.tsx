import { useReportCardDetail, useStudentDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useState } from 'react';
import { ActivityIndicator, Linking, Pressable, Text, View } from 'react-native';
import { WebView } from 'react-native-webview';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Mode = 'choice' | 'view';

/** Teacher view of a report form — no parent fee lock. */
export const TeacherReportCardDetailScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<TeacherStackParamList>>();
  const route = useRoute<RouteProp<TeacherStackParamList, 'TeacherReportCardDetail'>>();
  const { reportCardId, studentId } = route.params;
  const { colors, palette, spacing, typography, radius } = useTheme();
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const detailQuery = useReportCardDetail(reportCardId);
  const [mode, setMode] = useState<Mode>('choice');
  const [webLoading, setWebLoading] = useState(true);

  const card = detailQuery.data;
  const studentName = detail.data?.fullName ?? card?.student_name;
  const viewUrl = card?.view_url ?? null;
  const pdfUrl = card?.pdf_url ?? null;

  const downloadPdf = async () => {
    if (!pdfUrl) return;
    await Linking.openURL(pdfUrl);
  };

  if (detailQuery.isLoading) {
    return (
      <ScreenContainer contentContainerStyle={{ flex: 1, justifyContent: 'center', alignItems: 'center' }}>
        <ActivityIndicator color={colors.primary} />
      </ScreenContainer>
    );
  }

  if (detailQuery.isError) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Report form" onBack={() => navigation.goBack()} />
        <EmptyState
          title="Could not load report"
          message={(detailQuery.error as Error).message}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void detailQuery.refetch()}
        />
      </ScreenContainer>
    );
  }

  if (mode === 'view' && viewUrl) {
    return (
      <View style={{ flex: 1, backgroundColor: palette.background }}>
        <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.sm }}>
          <AcademicScreenHeader
            title="Report form"
            subtitle={studentName}
            onBack={() => {
              setMode('choice');
              setWebLoading(true);
            }}
          />
          {pdfUrl ? (
            <Pressable onPress={() => void downloadPdf()} style={{ marginBottom: spacing.sm }}>
              <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
                Download PDF
              </Text>
            </Pressable>
          ) : null}
        </View>
        <View style={{ flex: 1 }}>
          {webLoading ? (
            <View style={{ position: 'absolute', top: 24, left: 0, right: 0, zIndex: 1, alignItems: 'center' }}>
              <ActivityIndicator color={colors.primary} />
            </View>
          ) : null}
          <WebView
            source={{ uri: viewUrl }}
            onLoadEnd={() => setWebLoading(false)}
            startInLoadingState
            style={{ flex: 1, backgroundColor: 'transparent' }}
            allowsBackForwardNavigationGestures
            setSupportMultipleWindows={false}
          />
        </View>
      </View>
    );
  }

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Report form" subtitle={studentName} onBack={() => navigation.goBack()} />
      <Text style={{ color: palette.textSecondary, marginBottom: spacing.md }}>
        {[card?.class_name, card?.display_term_label].filter(Boolean).join(' · ') ||
          'Open the report form to confirm marks are accurate.'}
      </Text>

      <Pressable
        onPress={() => void downloadPdf()}
        disabled={!pdfUrl}
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          gap: spacing.md,
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderWidth: 1,
          borderRadius: radius.lg,
          padding: spacing.md,
          marginBottom: spacing.sm,
          opacity: pdfUrl ? 1 : 0.5,
        }}
      >
        <Soft3DIcon name="download-outline" tone="emerald" size={48} />
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>Download PDF</Text>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Save or share the official report form as a PDF
          </Text>
        </View>
      </Pressable>

      <Pressable
        onPress={() => {
          if (!viewUrl) return;
          setMode('view');
        }}
        disabled={!viewUrl}
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          gap: spacing.md,
          backgroundColor: palette.surface,
          borderColor: palette.border,
          borderWidth: 1,
          borderRadius: radius.lg,
          padding: spacing.md,
          opacity: viewUrl ? 1 : 0.5,
        }}
      >
        <Soft3DIcon name="document-text-outline" tone="indigo" size={48} />
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>View report form</Text>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Open the full report in the app to verify marks
          </Text>
        </View>
      </Pressable>

      {!pdfUrl && !viewUrl ? (
        <EmptyState
          title="Report form unavailable"
          message="This report does not have a form link yet. Generate or republish the report card on the ERP."
          icon="school-outline"
        />
      ) : null}
    </ScreenContainer>
  );
};
