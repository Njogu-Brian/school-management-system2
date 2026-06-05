import { useTransportRoutes } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'TripsList'>;

export const TripsListScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useTransportRoutes();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data?.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <View>
            <AcademicScreenHeader title="Transport trips" subtitle="Routes and trip templates" onBack={() => navigation.goBack()} />
            <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md }}>
              <Button label="Add trip" onPress={() => navigation.navigate('TripForm', {})} />
              <Button label="Vehicles" variant="secondary" onPress={() => navigation.navigate('VehiclesList')} />
            </View>
          </View>
        }
        renderItem={({ item }) => (
          <Pressable
            onPress={() => navigation.navigate('TripDetail', { tripId: item.id, tripName: item.name })}
            style={[styles.row, { borderColor: palette.border, padding: spacing.sm, marginBottom: spacing.xs }]}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>{item.name}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {[item.vehicle_registration, item.driver_name].filter(Boolean).join(' · ') || 'No vehicle assigned'}
            </Text>
          </Pressable>
        )}
        ListEmptyComponent={
          query.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No transport trips found.</Text>
          )
        }
        refreshing={query.isRefetching}
        onRefresh={() => void query.refetch()}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8 },
});
