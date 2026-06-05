import { useCan, useVehicleMutations, useVehicles } from '@erp/core';
import { AcademicScreenHeader, Button, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, Alert, FlatList, Pressable, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'VehiclesList'>;

export const VehiclesListScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useVehicles({ enabled: canView });
  const { remove } = useVehicleMutations();

  const onDelete = (id: number, label: string) => {
    Alert.alert('Delete vehicle', `Remove ${label}?`, [
      { text: 'Cancel', style: 'cancel' },
      {
        text: 'Delete',
        style: 'destructive',
        onPress: () =>
          void remove.mutateAsync(id).catch((e) => Alert.alert('Failed', (e as Error).message)),
      },
    ]);
  };

  if (!canView) {
    return (
      <ScreenContainer contentContainerStyle={styles.denied}>
        <Text style={{ color: palette.textSecondary }}>Access denied.</Text>
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <View>
            <AcademicScreenHeader title="Vehicles" subtitle="Fleet registry" onBack={() => navigation.goBack()} />
            <Button label="Add vehicle" onPress={() => navigation.navigate('VehicleForm', {})} style={{ marginBottom: spacing.md }} />
          </View>
        }
        renderItem={({ item }) => (
          <View style={[styles.row, { borderColor: palette.border, padding: spacing.sm, marginBottom: spacing.xs }]}>
            <Pressable onPress={() => navigation.navigate('VehicleForm', { vehicleId: item.id })} style={{ flex: 1 }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.vehicle_number}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
                {[item.driver_name, item.make, item.model, item.capacity ? `Cap ${item.capacity}` : null]
                  .filter(Boolean)
                  .join(' · ') || 'No details'}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
                {item.trips_count ?? 0} trip{(item.trips_count ?? 0) === 1 ? '' : 's'}
              </Text>
            </Pressable>
            <Pressable onPress={() => onDelete(item.id, item.vehicle_number)}>
              <Text style={{ color: colors.error, fontSize: fontSizes.xs }}>Delete</Text>
            </Pressable>
          </View>
        )}
        ListEmptyComponent={
          query.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No vehicles found.</Text>
          )
        }
        refreshing={query.isRefetching}
        onRefresh={() => void query.refetch()}
      />
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  denied: { flex: 1, justifyContent: 'center', padding: 24 },
  row: { borderWidth: StyleSheet.hairlineWidth, borderRadius: 8, flexDirection: 'row', alignItems: 'center' },
});
