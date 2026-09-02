import { useCan, useReceiptsReport } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  TextField,
  useTheme,
} from '@erp/ui';
import type { StackScreenProps } from '@react-navigation/stack';
import React, { useState } from 'react';
import { ScrollView, Text, View } from 'react-native';
import type { OperationsStackParamList } from '../../../navigation/operationsStackTypes';

type Props = StackScreenProps<OperationsStackParamList, 'InventoryReceipts'>;

export const InventoryReceiptsScreen: React.FC<Props> = ({ navigation }) => {
  const canView = useCan('operations.view');
  const { palette, spacing, typography, radius } = useTheme();
  const [fromInput, setFromInput] = useState('');
  const [toInput, setToInput] = useState('');
  const [from, setFrom] = useState<string | undefined>();
  const [to, setTo] = useState<string | undefined>();
  const query = useReceiptsReport(
    { from, to },
    { enabled: canView },
  );
  const data = query.data;

  if (!canView) {
    return (
      <ScreenContainer>
        <EmptyState title="Access denied" message="You need operations access to open this report." icon="lock-closed-outline" />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
        <AcademicScreenHeader
          title="What we received"
          subtitle={data ? `${data.from} – ${data.to}` : 'Stock received in this period'}
          onBack={() => navigation.goBack()}
        />
        <TextField
          label="From (YYYY-MM-DD)"
          value={fromInput}
          onChangeText={setFromInput}
          placeholder="Leave blank for term start"
          autoCapitalize="none"
        />
        <TextField
          label="To (YYYY-MM-DD)"
          value={toInput}
          onChangeText={setToInput}
          placeholder="Leave blank for today"
          autoCapitalize="none"
        />
        <Button
          label="Show totals"
          onPress={() => {
            setFrom(fromInput.trim() || undefined);
            setTo(toInput.trim() || undefined);
          }}
          style={{ marginBottom: spacing.md }}
        />
        {query.isLoading ? <SkeletonListRows variant="card" /> : null}
        {query.isError ? (
          <EmptyState
            title="Could not load receipts"
            message={(query.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void query.refetch()}
          />
        ) : null}
        {data && data.rows.length === 0 ? (
          <EmptyState title="Nothing received" message="No stock came in for this period." icon="cube-outline" />
        ) : null}
        {data?.rows.map((row) => (
          <View
            key={row.item_id}
            style={{
              backgroundColor: palette.surface,
              borderRadius: radius.md,
              padding: spacing.md,
              marginBottom: spacing.sm,
              borderWidth: 1,
              borderColor: palette.border,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{row.name}</Text>
            <Text style={{ color: palette.textSecondary, marginTop: 4 }}>
              Received {row.total_received} {row.unit}
              {row.from_learners ? ` · ${row.from_learners} from learners` : ''}
              {row.other_receipts ? ` · ${row.other_receipts} other` : ''}
            </Text>
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 2 }}>
              Current stock: {row.current_stock} {row.unit}
            </Text>
          </View>
        ))}
      </ScrollView>
    </ScreenContainer>
  );
};
