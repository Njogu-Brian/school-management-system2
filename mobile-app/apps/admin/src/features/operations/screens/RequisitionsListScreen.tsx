import { useRequisitions } from '@erp/core';
import { AcademicScreenHeader, ScreenContainer, useTheme } from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React from 'react';
import { ActivityIndicator, FlatList, StyleSheet, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'RequisitionsList'>;

export const RequisitionsListScreen: React.FC<Props> = ({ navigation }) => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const query = useRequisitions({ status: 'pending' });

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <FlatList
        data={query.data ?? []}
        keyExtractor={(item) => String(item.id)}
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        ListHeaderComponent={
          <AcademicScreenHeader
            title="Requisitions"
            subtitle="GET /requisitions · pending"
            onBack={() => navigation.goBack()}
          />
        }
        renderItem={({ item }) => (
          <View style={[styles.row, { borderColor: palette.border, padding: spacing.sm, marginBottom: spacing.xs }]}>
            <Text style={{ color: palette.textPrimary, fontWeight: '600', fontSize: fontSizes.sm }}>
              {item.requisition_number}
            </Text>
            <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs, marginTop: 2 }}>
              {[item.requested_by, item.type, item.status].filter(Boolean).join(' · ')}
            </Text>
          </View>
        )}
        ListEmptyComponent={
          query.isLoading ? (
            <ActivityIndicator color={colors.primary} />
          ) : (
            <Text style={{ color: palette.textSecondary, textAlign: 'center' }}>No pending requisitions.</Text>
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
