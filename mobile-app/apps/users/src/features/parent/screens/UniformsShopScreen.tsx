import { useCreatePosOrder, usePosUniforms, type PosUniform } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { Alert, Text, TextInput, TouchableOpacity, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Route = RouteProp<ParentStackParamList, 'UniformsShop'>;

export const UniformsShopScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<Route>();
  const { palette, spacing } = useTheme();
  const studentId = route.params.studentId;
  const uniforms = usePosUniforms({ studentId });
  const createOrder = useCreatePosOrder();

  const [picked, setPicked] = useState<Record<number, { variantId?: number; qty: number }>>({});
  const [txnCode, setTxnCode] = useState('');
  const [busy, setBusy] = useState(false);

  const selectedLines = useMemo(() => {
    const rows: Array<{ product: PosUniform; variantId?: number; qty: number; unit: number }> = [];
    for (const product of uniforms.data ?? []) {
      const sel = picked[product.id];
      if (!sel || sel.qty <= 0) continue;
      const variant = product.variants.find((v) => v.id === sel.variantId);
      rows.push({
        product,
        variantId: sel.variantId,
        qty: sel.qty,
        unit: variant?.price ?? product.base_price,
      });
    }
    return rows;
  }, [picked, uniforms.data]);

  const total = selectedLines.reduce((s, r) => s + r.qty * r.unit, 0);

  const place = async () => {
    if (selectedLines.length === 0) {
      Alert.alert('Select uniforms', 'Pick at least one uniform item.');
      return;
    }
    setBusy(true);
    try {
      const order = await createOrder.mutateAsync({
        studentId,
        items: selectedLines.map((row) => ({
          product_id: row.product.id,
          quantity: row.qty,
          variant_id: row.variantId ?? null,
        })),
        transaction_code: txnCode.trim() || undefined,
        payment_method: 'brian_paybill',
      });
      Alert.alert(
        order.payment_status === 'paid' ? 'Order confirmed' : 'Order created',
        order.payment_status === 'paid'
          ? 'Payment received. Uniforms will be availed in school.'
          : 'Pay via the school paybill details shown, then confirm with the transaction code from Orders if needed.',
      );
      navigation.goBack();
    } catch (e) {
      Alert.alert('Could not place order', e instanceof Error ? e.message : 'Try again.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="School uniforms" onBack={() => navigation.goBack()} />

      {uniforms.isLoading ? (
        <SkeletonListRows count={4} />
      ) : uniforms.isError ? (
        <EmptyState
          title="Could not load uniforms"
          message={uniforms.error instanceof Error ? uniforms.error.message : 'Try again.'}
          actionLabel="Retry"
          onAction={() => void uniforms.refetch()}
        />
      ) : (uniforms.data?.length ?? 0) === 0 ? (
        <EmptyState title="No uniforms listed" message="Uniforms will appear here when stocked." icon="shirt-outline" />
      ) : (
        <>
          {(uniforms.data ?? []).map((product) => {
            const sel = picked[product.id] ?? { qty: 0 };
            return (
              <SurfaceCard key={product.id} style={{ marginBottom: spacing.sm }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>{product.name}</Text>
                <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
                  From KES {product.base_price.toLocaleString()} · Stock {product.total_stock}
                </Text>
                {product.variants.length > 0 ? (
                  <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: 8, marginTop: 8 }}>
                    {product.variants.map((v) => {
                      const active = sel.variantId === v.id;
                      return (
                        <TouchableOpacity
                          key={v.id}
                          onPress={() =>
                            setPicked((prev) => ({
                              ...prev,
                              [product.id]: { variantId: v.id, qty: Math.max(1, prev[product.id]?.qty || 1) },
                            }))
                          }
                          style={{
                            paddingHorizontal: 10,
                            paddingVertical: 6,
                            borderRadius: 8,
                            borderWidth: 1,
                            borderColor: active ? palette.primary : palette.border,
                            backgroundColor: active ? palette.primary + '22' : 'transparent',
                          }}
                        >
                          <Text style={{ color: palette.textPrimary }}>
                            {v.variant_type}: {v.value}
                          </Text>
                        </TouchableOpacity>
                      );
                    })}
                  </View>
                ) : null}
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 10 }}>
                  <Text style={{ color: palette.textSecondary }}>Qty</Text>
                  <TextInput
                    keyboardType="number-pad"
                    value={String(sel.qty || 0)}
                    onChangeText={(t) =>
                      setPicked((prev) => ({
                        ...prev,
                        [product.id]: {
                          variantId: prev[product.id]?.variantId,
                          qty: Math.max(0, parseInt(t || '0', 10) || 0),
                        },
                      }))
                    }
                    style={{
                      borderWidth: 1,
                      borderColor: palette.border,
                      borderRadius: 8,
                      paddingHorizontal: 10,
                      paddingVertical: 4,
                      minWidth: 48,
                      color: palette.textPrimary,
                    }}
                  />
                </View>
              </SurfaceCard>
            );
          })}

          <SurfaceCard style={{ marginBottom: spacing.md }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>
              Total KES {total.toLocaleString()}
            </Text>
            <TextInput
              placeholder="Optional: paste M-Pesa code now"
              placeholderTextColor={palette.textSecondary}
              autoCapitalize="characters"
              value={txnCode}
              onChangeText={setTxnCode}
              style={{
                marginTop: spacing.sm,
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: 10,
                paddingHorizontal: 12,
                paddingVertical: 10,
                color: palette.textPrimary,
              }}
            />
          </SurfaceCard>

          <Button label={busy ? 'Placing…' : 'Place uniform order'} onPress={() => void place()} loading={busy} disabled={busy} />
        </>
      )}
    </ScreenContainer>
  );
};
