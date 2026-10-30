import {
  useConfirmPosPayment,
  useCreatePosOrder,
  usePosRequirementsCatalog,
  type PosCatalogItem,
} from '@erp/core';
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
import React, { useEffect, useMemo, useState } from 'react';
import {
  Alert,
  Image,
  Text,
  TextInput,
  TouchableOpacity,
  View,
} from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Route = RouteProp<ParentStackParamList, 'RequirementsShop'>;

type Selected = Record<number, { selected: boolean; qty: number; item: PosCatalogItem }>;

export const RequirementsShopScreen: React.FC = () => {
  const navigation = useNavigation<StackNavigationProp<ParentStackParamList>>();
  const route = useRoute<Route>();
  const { palette, spacing, typography } = useTheme();
  const studentId = route.params.studentId;
  const catalog = usePosRequirementsCatalog(studentId);
  const createOrder = useCreatePosOrder();
  const confirmPayment = useConfirmPosPayment();

  const [selected, setSelected] = useState<Selected>({});
  const [orderId, setOrderId] = useState<number | null>(null);
  const [txnCode, setTxnCode] = useState('');
  const [busy, setBusy] = useState(false);

  useEffect(() => {
    if (!catalog.data?.items) return;
    const next: Selected = {};
    for (const item of catalog.data.items) {
      next[item.template_id] = {
        selected: true,
        qty: Math.max(1, Math.round(item.default_quantity || 1)),
        item,
      };
    }
    setSelected(next);
  }, [catalog.data?.items]);

  const lines = useMemo(
    () => Object.values(selected).filter((row) => row.selected && row.qty > 0),
    [selected],
  );
  const total = useMemo(
    () => lines.reduce((sum, row) => sum + row.qty * row.item.unit_price, 0),
    [lines],
  );
  const payment = catalog.data?.payment;

  const toggleAll = (on: boolean) => {
    setSelected((prev) => {
      const next = { ...prev };
      for (const key of Object.keys(next)) {
        next[Number(key)] = { ...next[Number(key)], selected: on };
      }
      return next;
    });
  };

  const placeOrder = async (withCode?: string) => {
    if (lines.length === 0) {
      Alert.alert('Select items', 'Choose at least one item to buy.');
      return;
    }
    setBusy(true);
    try {
      const order = await createOrder.mutateAsync({
        studentId,
        items: lines.map((row) => ({
          product_id: row.item.product_id,
          quantity: row.qty,
          requirement_template_id: row.item.template_id,
        })),
        transaction_code: withCode || undefined,
        payment_method: 'brian_paybill',
      });
      setOrderId(order.id);
      if (order.payment_status === 'paid') {
        Alert.alert('Order confirmed', 'Payment received. Items will be availed in school.');
        navigation.goBack();
      } else {
        Alert.alert(
          'Order created',
          `Paybill ${payment?.paybill} Account ${payment?.account}. Paste the M-Pesa code below to confirm.`,
        );
      }
    } catch (e) {
      Alert.alert('Could not place order', e instanceof Error ? e.message : 'Try again.');
    } finally {
      setBusy(false);
    }
  };

  const confirm = async () => {
    if (!orderId) {
      await placeOrder(txnCode.trim() || undefined);
      return;
    }
    if (!txnCode.trim()) {
      Alert.alert('Transaction code', 'Paste the M-Pesa transaction code first.');
      return;
    }
    setBusy(true);
    try {
      await confirmPayment.mutateAsync({ orderId, transaction_code: txnCode.trim() });
      Alert.alert('Payment confirmed', 'Your order was received. Items will be availed in school.');
      navigation.goBack();
    } catch (e) {
      Alert.alert('Confirmation failed', e instanceof Error ? e.message : 'Try again.');
    } finally {
      setBusy(false);
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Buy requirements"
        subtitle={catalog.data?.student.name}
        onBack={() => navigation.goBack()}
      />

      {catalog.isLoading ? (
        <SkeletonListRows count={5} />
      ) : catalog.isError ? (
        <EmptyState
          title="Could not load shop"
          message={catalog.error instanceof Error ? catalog.error.message : 'Try again.'}
          actionLabel="Retry"
          onAction={() => void catalog.refetch()}
        />
      ) : (catalog.data?.items.length ?? 0) === 0 ? (
        <EmptyState
          title="Nothing available to buy"
          message="Shop items for this class have not been published yet."
          icon="cart-outline"
        />
      ) : (
        <>
          <SurfaceCard accent="info" style={{ marginBottom: spacing.md }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>Package total</Text>
            <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
              KES {total.toLocaleString()} · {lines.length} selected
            </Text>
            <View style={{ flexDirection: 'row', gap: 12, marginTop: spacing.sm }}>
              <TouchableOpacity onPress={() => toggleAll(true)}>
                <Text style={{ color: palette.primary, fontWeight: '700' }}>Select all</Text>
              </TouchableOpacity>
              <TouchableOpacity onPress={() => toggleAll(false)}>
                <Text style={{ color: palette.textSecondary, fontWeight: '700' }}>Clear</Text>
              </TouchableOpacity>
            </View>
          </SurfaceCard>

          {Object.values(selected).map((row) => (
            <TouchableOpacity
              key={row.item.template_id}
              onPress={() =>
                setSelected((prev) => ({
                  ...prev,
                  [row.item.template_id]: { ...row, selected: !row.selected },
                }))
              }
              style={{
                flexDirection: 'row',
                gap: 12,
                paddingVertical: 10,
                borderBottomWidth: 1,
                borderBottomColor: palette.border,
              }}
            >
              {row.item.image_url ? (
                <Image source={{ uri: row.item.image_url }} style={{ width: 48, height: 48, borderRadius: 8 }} />
              ) : (
                <View
                  style={{
                    width: 48,
                    height: 48,
                    borderRadius: 8,
                    backgroundColor: palette.surfaceMuted,
                  }}
                />
              )}
              <View style={{ flex: 1 }}>
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  {row.selected ? '✓ ' : ''}
                  {row.item.name}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.sizes.sm }}>
                  {row.item.brand || '—'} · KES {row.item.unit_price.toLocaleString()}
                </Text>
                <View style={{ flexDirection: 'row', alignItems: 'center', gap: 8, marginTop: 6 }}>
                  <Text style={{ color: palette.textSecondary }}>Qty</Text>
                  <TextInput
                    keyboardType="number-pad"
                    value={String(row.qty)}
                    onChangeText={(t) =>
                      setSelected((prev) => ({
                        ...prev,
                        [row.item.template_id]: {
                          ...row,
                          qty: Math.max(1, parseInt(t || '1', 10) || 1),
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
              </View>
            </TouchableOpacity>
          ))}

          {payment ? (
            <SurfaceCard style={{ marginTop: spacing.md, marginBottom: spacing.md }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>Pay via M-Pesa</Text>
              <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
                Paybill {payment.paybill} · Account {payment.account}
              </Text>
              <Text style={{ color: palette.textSecondary, marginTop: 4 }}>{payment.note}</Text>
              <TextInput
                placeholder="Paste M-Pesa transaction code"
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
          ) : null}

          <Button
            label={busy ? 'Working…' : orderId ? 'Confirm payment' : 'Place order'}
            onPress={() => void confirm()}
            loading={busy}
            disabled={busy || lines.length === 0}
          />
          {!orderId ? (
            <TouchableOpacity
              onPress={() => void placeOrder()}
              style={{ marginTop: spacing.sm, alignItems: 'center' }}
              disabled={busy}
            >
              <Text style={{ color: palette.textSecondary }}>
                Place order without code (confirm after paying)
              </Text>
            </TouchableOpacity>
          ) : null}
        </>
      )}
    </ScreenContainer>
  );
};
