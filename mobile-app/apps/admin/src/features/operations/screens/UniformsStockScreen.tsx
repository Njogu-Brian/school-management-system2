import { usePosUniforms, useUpdateUniformStock } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { Alert, RefreshControl, Text, TextInput, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'UniformsStock'>;

export const UniformsStockScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing } = useTheme();
  const query = usePosUniforms();
  const updateStock = useUpdateUniformStock();
  const [drafts, setDrafts] = useState<Record<string, string>>({});

  const saveVariant = async (productId: number, variantId: number) => {
    const key = `${productId}:${variantId}`;
    const qty = Math.max(0, parseInt(drafts[key] || '0', 10) || 0);
    try {
      await updateStock.mutateAsync({
        productId,
        variants: [{ id: variantId, stock_quantity: qty }],
      });
      Alert.alert('Saved', 'Stock updated.');
    } catch (e) {
      Alert.alert('Failed', e instanceof Error ? e.message : 'Try again.');
    }
  };

  const saveProduct = async (productId: number) => {
    const qty = Math.max(0, parseInt(drafts[`p:${productId}`] || '0', 10) || 0);
    try {
      await updateStock.mutateAsync({ productId, stock_quantity: qty });
      Alert.alert('Saved', 'Stock updated.');
    } catch (e) {
      Alert.alert('Failed', e instanceof Error ? e.message : 'Try again.');
    }
  };

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
      refreshControl={<RefreshControl refreshing={query.isFetching} onRefresh={() => void query.refetch()} />}
    >
      <AcademicScreenHeader
        title="Uniforms stock"
        subtitle="Sizes & colours"
        onBack={() => navigation.goBack()}
      />

      {query.isLoading ? (
        <SkeletonListRows count={4} />
      ) : query.isError ? (
        <EmptyState
          title="Could not load uniforms"
          message={query.error instanceof Error ? query.error.message : 'Try again.'}
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      ) : (query.data?.length ?? 0) === 0 ? (
        <EmptyState
          title="No uniforms"
          message="Create uniforms in the web POS module (name, optional size/color, buy & sell price)."
          icon="shirt-outline"
        />
      ) : (
        (query.data ?? []).map((product) => (
          <SurfaceCard key={product.id} style={{ marginBottom: spacing.sm }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>{product.name}</Text>
            <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
              Sell KES {product.base_price.toLocaleString()}
              {product.cost_price != null ? ` · Buy KES ${product.cost_price.toLocaleString()}` : ''}
            </Text>

            {product.variants.length === 0 ? (
              <View style={{ marginTop: 8 }}>
                <Text style={{ color: palette.textSecondary }}>Product stock</Text>
                <TextInput
                  keyboardType="number-pad"
                  defaultValue={String(product.stock_quantity)}
                  onChangeText={(t) => setDrafts((d) => ({ ...d, [`p:${product.id}`]: t }))}
                  onEndEditing={() => void saveProduct(product.id)}
                  style={{
                    marginTop: 4,
                    borderWidth: 1,
                    borderColor: palette.border,
                    borderRadius: 8,
                    paddingHorizontal: 10,
                    paddingVertical: 6,
                    color: palette.textPrimary,
                  }}
                />
              </View>
            ) : (
              product.variants.map((v) => {
                const key = `${product.id}:${v.id}`;
                return (
                  <View key={v.id} style={{ marginTop: 10 }}>
                    <Text style={{ color: palette.textPrimary }}>
                      {v.variant_type}: {v.value} · KES {v.price.toLocaleString()}
                    </Text>
                    <TextInput
                      keyboardType="number-pad"
                      defaultValue={String(v.stock_quantity)}
                      onChangeText={(t) => setDrafts((d) => ({ ...d, [key]: t }))}
                      onEndEditing={() => void saveVariant(product.id, v.id)}
                      style={{
                        marginTop: 4,
                        borderWidth: 1,
                        borderColor: palette.border,
                        borderRadius: 8,
                        paddingHorizontal: 10,
                        paddingVertical: 6,
                        color: palette.textPrimary,
                      }}
                    />
                  </View>
                );
              })
            )}
          </SurfaceCard>
        ))
      )}
    </ScreenContainer>
  );
};
