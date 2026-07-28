import { useReportCardDetail, useStudentDetail } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import React, { useState } from 'react';
import { ActivityIndicator, Linking, Pressable, Text, View } from 'react-native';
import { WebView } from 'react-native-webview';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

export const ParentReportCardDetailScreen: React.FC = () => {
  const navigation = useNavigation();
  const route = useRoute<RouteProp<ParentStackParamList, 'ReportCardDetail'>>();
  const { reportCardId, studentId } = route.params;
  const { colors, palette, spacing, typography } = useTheme();
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const detailQuery = useReportCardDetail(reportCardId);
  const [webLoading, setWebLoading] = useState(true);

  const card = detailQuery.data;
  const studentName = detail.data?.fullName ?? card?.student_name;
  const viewUrl = card?.view_url ?? null;

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

  if (!viewUrl) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Report form" subtitle={studentName} onBack={() => navigation.goBack()} />
        <EmptyState
          title="Report form unavailable"
          message="This report does not have a published form link yet. Ask the school to republish the report card."
          icon="school-outline"
        />
      </ScreenContainer>
    );
  }

  return (
    <View style={{ flex: 1, backgroundColor: palette.background }}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.sm }}>
        <AcademicScreenHeader title="Report form" subtitle={studentName} onBack={() => navigation.goBack()} />
        <Pressable
          onPress={() => void Linking.openURL(card?.pdf_url || viewUrl)}
          style={{ marginBottom: spacing.sm }}
        >
          <Text style={{ color: colors.primary, fontWeight: '600', fontSize: typography.caption.fontSize }}>
            {card?.pdf_url ? 'Open PDF' : 'Open in browser'}
          </Text>
        </Pressable>
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
};
