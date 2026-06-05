import { useDriverTrips } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'DriverTrips'>;

export const DriverTripsScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useDriverTrips();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader title="Driver trips" subtitle="GET /driver/trips" onBack={() => navigation.goBack()} />
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('DriverTripDetail', { tripId: item.id, tripName: item.name ?? undefined })}
            style={[styles.row, { borderColor: palette.border, padding: spacing.sm, marginBottom: spacing.xs }]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.name ?? `Trip #${item.id}`}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
              {[item.direction, item.departure_time, item.vehicle_registration].filter(Boolean).join(' · ')}
            </Text>
          </Pressable>
        )}
        ListEmptyComponent={query.isLoading ? <ActivityIndicator color={colors.primary} /> : <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No trips assigned today.</Text>}
        refreshing={query.isRefetching}
        onRefresh={() => void query.refetch()}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({ row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8 } });
