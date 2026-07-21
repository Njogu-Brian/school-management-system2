import { useDriverTrip } from '@erp/core';
import { AcademicScreenHeader, EmptyState, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, ScrollView, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'DriverTripDetail'>;

export const DriverTripDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { tripId, tripName } = route.params;
  const { palette, spacing, typography, radius } = useTheme();
  const query = useDriverTrip(tripId);
  const students = query.data?.students ?? [];

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title={tripName ?? `Trip #${tripId}`}
          subtitle="Assigned students"
          onBack={() => navigation.goBack()}
        />
        {query.isLoading ? (
          <ActivityIndicator color={palette.primary} />
        ) : query.isError ? (
          <EmptyState
            title="Could not load trip"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : students.length === 0 ? (
          <EmptyState title="No students" message="No students are assigned to this trip." icon="people-outline" />
        ) : (
          students.map((s) => (
            <View
              key={s.id}
              style={{
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: radius.card,
                padding: spacing.sm,
                marginBottom: spacing.xs,
                backgroundColor: palette.surfaceRaised,
              }}
            >
              <Text
                style={{
                  color: palette.textPrimary,
                  fontSize: typography.titleSmall.fontSize,
                  fontWeight: typography.titleSmall.fontWeight,
                }}
              >
                {s.full_name}
              </Text>
              <Text
                style={{
                  color: palette.textSecondary,
                  fontSize: typography.caption.fontSize,
                  marginTop: spacing.xs,
                }}
              >
                {[s.admission_number, s.drop_point, s.fee_status].filter(Boolean).join(' · ')}
              </Text>
            </View>
          ))
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
