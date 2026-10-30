import { usePosOrders, useUpdatePosOrderStatus } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { Alert, RefreshControl, Text } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';
import { OpsListCard } from '../components/OpsListCard';

type Props = StackScreenProps<OperationsStackParamList, 'PosOrders'>;

export const PosOrdersScreen: React.FC<Props> = ({ navigation }) => {
  const { palette, spacing } = useTheme();
  const [paymentStatus, setPaymentStatus] = useState<string | undefined>('paid');
  const query = usePosOrders({ payment_status: paymentStatus });
  const updateStatus = useUpdatePosOrderStatus();

  return (
    <ScreenContainer
      scroll
      contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
      refreshControl={<RefreshControl refreshing={query.isFetching} onRefresh={() => void query.refetch()} />}
    >
      <AcademicScreenHeader
        title="POS orders received"
        subtitle="Requirements & uniform shop orders"
        onBack={() => navigation.goBack()}
      />

      <FilterChipRow label="Payment">
        <FilterChip label="Paid" active={paymentStatus === 'paid'} onPress={() => setPaymentStatus('paid')} />
        <FilterChip
          label="Pending"
          active={paymentStatus === 'pending'}
          onPress={() => setPaymentStatus('pending')}
        />
        <FilterChip label="All" active={!paymentStatus} onPress={() => setPaymentStatus(undefined)} />
      </FilterChipRow>

      <Text
        style={{ color: palette.primary, fontWeight: '700', marginVertical: spacing.sm }}
        onPress={() => navigation.navigate('PosPlaceOrder', {})}
      >
        + Place order for a child
      </Text>

      {query.isLoading ? (
        <SkeletonListRows count={5} />
      ) : query.isError ? (
        <EmptyState
          title="Could not load orders"
          message={query.error instanceof Error ? query.error.message : 'Try again.'}
          actionLabel="Retry"
          onAction={() => void query.refetch()}
        />
      ) : (query.data?.length ?? 0) === 0 ? (
        <EmptyState title="No orders" message="New POS orders will appear here." icon="cart-outline" />
      ) : (
        (query.data ?? []).map((order) => (
          <OpsListCard
            key={order.id}
            title={order.order_number}
            lines={[
              order.student?.name ?? 'Student',
              order.student?.classroom,
              `${order.order_type} · KES ${order.total_amount.toLocaleString()}`,
              order.payment_reference ? `Txn ${order.payment_reference}` : null,
            ]}
            badge={{
              label: order.payment_status,
              tone: order.payment_status === 'paid' ? 'success' : 'warning',
            }}
            onPress={() => {
              Alert.alert(order.order_number, 'Update fulfilment status', [
                { text: 'Cancel', style: 'cancel' },
                {
                  text: 'Mark processing',
                  onPress: () =>
                    void updateStatus.mutateAsync({ orderId: order.id, status: 'processing' }),
                },
                {
                  text: 'Mark completed',
                  onPress: () =>
                    void updateStatus.mutateAsync({ orderId: order.id, status: 'completed' }),
                },
              ]);
            }}
          />
        ))
      )}
    </ScreenContainer>
  );
};
