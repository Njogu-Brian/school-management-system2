import { useDriverTrip } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, ScrollView, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'DriverTripDetail'>;

export const DriverTripDetailScreen: React.FC<Props> = ({ route, navigation }) => {
  const { tripId, tripName } = route.params;
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useDriverTrip(tripId);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader title={tripName ?? `Trip #${tripId}`} subtitle="GET /driver/trips/{id}" onBack={() => navigation.goBack()} />
        {query.isLoading ? (
          <ActivityIndicator color={colors.primary} />
        ) : (
          (query.data?.students ?? []).map((s) => (
            <View key={s.id} style={{ borderWidth: 1, borderColor: palette.border, borderRadius: 8, padding: spacing.sm, marginBottom: spacing.xs }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{s.full_name}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
                {[s.admission_number, s.drop_point, s.fee_status].filter(Boolean).join(' · ')}
              </Text>
            </View>
          ))
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
