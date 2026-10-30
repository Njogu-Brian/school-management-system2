import {
  useCreatePosOrder,
  usePosRequirementsCatalog,
  usePosUniforms,
  useStudentFinanceSearch,
} from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SearchBar,
  SkeletonListRows,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useEffect, useMemo, useState } from 'react';
import { Alert, Text, TextInput, TouchableOpacity, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'PosPlaceOrder'>;

export const PosPlaceOrderScreen: React.FC<Props> = ({ navigation, route }) => {
  const { palette, spacing } = useTheme();
  const [search, setSearch] = useState('');
  const [studentId, setStudentId] = useState<number | null>(route.params?.studentId ?? null);
  const [studentName, setStudentName] = useState(route.params?.studentName ?? '');
  const [mode, setMode] = useState<'requirements' | 'uniforms'>('requirements');
  const [txnCode, setTxnCode] = useState('');
  const [selectedTemplates, setSelectedTemplates] = useState<Record<number, number>>({});
  const [uniformQty, setUniformQty] = useState<Record<number, { qty: number; variantId?: number }>>({});

  const students = useStudentFinanceSearch(search, { enabled: search.trim().length >= 2 && !studentId });
  const catalog = usePosRequirementsCatalog(studentId ?? 0, { enabled: !!studentId && mode === 'requirements' });
  const uniforms = usePosUniforms({ enabled: !!studentId && mode === 'uniforms' });
  const createOrder = useCreatePosOrder();

  useEffect(() => {
    if (!catalog.data?.items) return;
    const next: Record<number, number> = {};
    for (const item of catalog.data.items) {
      next[item.template_id] = Math.max(1, Math.round(item.default_quantity || 1));
    }
    setSelectedTemplates(next);
  }, [catalog.data?.items]);

  const reqTotal = useMemo(() => {
    if (!catalog.data?.items) return 0;
    return catalog.data.items.reduce((sum, item) => {
      const qty = selectedTemplates[item.template_id];
      if (!qty) return sum;
      return sum + qty * item.unit_price;
    }, 0);
  }, [catalog.data?.items, selectedTemplates]);

  const placeRequirements = async () => {
    if (!studentId || !catalog.data) return;
    const items = catalog.data.items
      .filter((item) => (selectedTemplates[item.template_id] ?? 0) > 0)
      .map((item) => ({
        product_id: item.product_id,
        quantity: selectedTemplates[item.template_id],
        requirement_template_id: item.template_id,
      }));
    if (items.length === 0) {
      Alert.alert('Select items', 'Choose at least one requirement item.');
      return;
    }
    try {
      const order = await createOrder.mutateAsync({
        studentId,
        items,
        transaction_code: txnCode.trim() || undefined,
        payment_method: 'brian_paybill',
        asAdmin: true,
      });
      Alert.alert('Order placed', `${order.order_number} · ${order.payment_status}`);
      navigation.navigate('PosOrders');
    } catch (e) {
      Alert.alert('Failed', e instanceof Error ? e.message : 'Try again.');
    }
  };

  const placeUniforms = async () => {
    if (!studentId) return;
    const items = (uniforms.data ?? [])
      .map((u) => {
        const sel = uniformQty[u.id];
        if (!sel || sel.qty <= 0) return null;
        return {
          product_id: u.id,
          quantity: sel.qty,
          variant_id: sel.variantId ?? null,
        };
      })
      .filter(Boolean) as Array<{ product_id: number; quantity: number; variant_id?: number | null }>;
    if (items.length === 0) {
      Alert.alert('Select uniforms', 'Choose at least one uniform.');
      return;
    }
    try {
      const order = await createOrder.mutateAsync({
        studentId,
        items,
        transaction_code: txnCode.trim() || undefined,
        payment_method: 'brian_paybill',
        asAdmin: true,
      });
      Alert.alert('Order placed', `${order.order_number} · ${order.payment_status}`);
      navigation.navigate('PosOrders');
    } catch (e) {
      Alert.alert('Failed', e instanceof Error ? e.message : 'Try again.');
    }
  };

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Place POS order" subtitle="Buy on behalf of a parent" onBack={() => navigation.goBack()} />

      {!studentId ? (
        <>
          <SearchBar value={search} onChangeText={setSearch} placeholder="Search student…" />
          {students.isLoading ? <SkeletonListRows count={3} /> : null}
          {(students.data ?? []).map((s) => (
            <TouchableOpacity
              key={s.id}
              onPress={() => {
                setStudentId(s.id);
                setStudentName(s.full_name || `Student #${s.id}`);
              }}
              style={{ paddingVertical: 12, borderBottomWidth: 1, borderBottomColor: palette.border }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{s.full_name}</Text>
              <Text style={{ color: palette.textSecondary }}>{s.admission_number}</Text>
            </TouchableOpacity>
          ))}
        </>
      ) : (
        <>
          <SurfaceCard style={{ marginBottom: spacing.md }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>{studentName}</Text>
            <TouchableOpacity onPress={() => setStudentId(null)}>
              <Text style={{ color: palette.primary, marginTop: 6 }}>Change student</Text>
            </TouchableOpacity>
          </SurfaceCard>

          <View style={{ flexDirection: 'row', gap: 12, marginBottom: spacing.md }}>
            <Button
              label="Requirements"
              variant={mode === 'requirements' ? 'primary' : 'secondary'}
              onPress={() => setMode('requirements')}
              fullWidth={false}
            />
            <Button
              label="Uniforms"
              variant={mode === 'uniforms' ? 'primary' : 'secondary'}
              onPress={() => setMode('uniforms')}
              fullWidth={false}
            />
          </View>

          <TextInput
            placeholder="M-Pesa transaction code (optional)"
            placeholderTextColor={palette.textSecondary}
            autoCapitalize="characters"
            value={txnCode}
            onChangeText={setTxnCode}
            style={{
              borderWidth: 1,
              borderColor: palette.border,
              borderRadius: 10,
              paddingHorizontal: 12,
              paddingVertical: 10,
              color: palette.textPrimary,
              marginBottom: spacing.md,
            }}
          />

          {mode === 'requirements' ? (
            catalog.isLoading ? (
              <SkeletonListRows count={4} />
            ) : catalog.isError ? (
              <EmptyState title="Catalog unavailable" message="Import Term 1 2027 requirements first." />
            ) : (
              <>
                <Text style={{ color: palette.textSecondary, marginBottom: spacing.sm }}>
                  Package KES {reqTotal.toLocaleString()}
                </Text>
                {(catalog.data?.items ?? []).map((item) => (
                  <TouchableOpacity
                    key={item.template_id}
                    onPress={() =>
                      setSelectedTemplates((prev) => {
                        const next = { ...prev };
                        if (next[item.template_id]) delete next[item.template_id];
                        else next[item.template_id] = Math.max(1, Math.round(item.default_quantity || 1));
                        return next;
                      })
                    }
                    style={{ paddingVertical: 10, borderBottomWidth: 1, borderBottomColor: palette.border }}
                  >
                    <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                      {selectedTemplates[item.template_id] ? '✓ ' : ''}
                      {item.name}
                    </Text>
                    <Text style={{ color: palette.textSecondary }}>
                      Qty {selectedTemplates[item.template_id] || item.default_quantity} · KES{' '}
                      {item.unit_price.toLocaleString()}
                    </Text>
                  </TouchableOpacity>
                ))}
                <Button
                  label="Place requirements order"
                  onPress={() => void placeRequirements()}
                  loading={createOrder.isPending}
                  style={{ marginTop: spacing.md }}
                />
              </>
            )
          ) : uniforms.isLoading ? (
            <SkeletonListRows count={4} />
          ) : (
            <>
              {(uniforms.data ?? []).map((u) => (
                <SurfaceCard key={u.id} style={{ marginBottom: spacing.sm }}>
                  <Text style={{ color: palette.textPrimary, fontWeight: '800' }}>{u.name}</Text>
                  <Text style={{ color: palette.textSecondary }}>KES {u.base_price.toLocaleString()}</Text>
                  <TextInput
                    keyboardType="number-pad"
                    placeholder="Qty"
                    placeholderTextColor={palette.textSecondary}
                    value={String(uniformQty[u.id]?.qty || 0)}
                    onChangeText={(t) =>
                      setUniformQty((prev) => ({
                        ...prev,
                        [u.id]: {
                          qty: Math.max(0, parseInt(t || '0', 10) || 0),
                          variantId: prev[u.id]?.variantId ?? u.variants[0]?.id,
                        },
                      }))
                    }
                    style={{
                      marginTop: 8,
                      borderWidth: 1,
                      borderColor: palette.border,
                      borderRadius: 8,
                      paddingHorizontal: 10,
                      paddingVertical: 6,
                      color: palette.textPrimary,
                    }}
                  />
                </SurfaceCard>
              ))}
              <Button
                label="Place uniform order"
                onPress={() => void placeUniforms()}
                loading={createOrder.isPending}
              />
            </>
          )}
        </>
      )}
    </ScreenContainer>
  );
};
