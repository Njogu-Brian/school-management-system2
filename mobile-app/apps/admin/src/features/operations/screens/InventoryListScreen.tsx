import { useInventoryItems } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'InventoryList'>;

export const InventoryListScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useInventoryItems();

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader title="Inventory" subtitle="GET /inventory/items" onBack={() => navigation.goBack()} />
        }
        renderItem={({ item }) => (
          <View style={[styles.row, { borderColor: palette.border, padding: spacing.sm, marginBottom: spacing.xs }]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>{item.name}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {item.quantity} {item.unit ?? ''} · {item.is_low_stock ? 'Low stock' : 'In stock'}
            </Text>
          </View>
        )}
        ListEmptyComponent={
          query.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No inventory items found.</Text>
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
