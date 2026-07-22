import { downloadAuthenticatedFile, payrollApi, useCurrentUser, usePayrollRecordsList } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useCallback, useMemo, useState } from 'react';
import { FlatList, Text, View } from 'react-native';
import { showError } from '../../shared/utils/feedback';

export const MyPayslipsScreen: React.FC = () => {
  const navigation = useNavigation();
  const user = useCurrentUser();
  const { palette, spacing, typography, radius } = useTheme();
  const staffId = user?.staffId ?? undefined;
  const listQuery = usePayrollRecordsList({
    staffId,
    enabled: staffId != null,
  });
  const [downloadingId, setDownloadingId] = useState<number | null>(null);

  const items = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  const downloadPayslip = useCallback(async (recordId: number, label: string) => {
    setDownloadingId(recordId);
    try {
      await downloadAuthenticatedFile(payrollApi.payslipDownloadPath(recordId), `payslip-${label}`);
    } catch (err) {
      showError('Download failed', err instanceof Error ? err.message : 'Could not download payslip.');
    } finally {
      setDownloadingId(null);
    }
  }, []);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader title="My payslips" onBack={() => navigation.goBack()} />
      </View>
      {staffId == null ? (
        <EmptyState
          title="No staff profile"
          message="Your account is not linked to a staff record, so payslips are unavailable."
          icon="wallet-outline"
        />
      ) : listQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : items.length === 0 ? (
        <EmptyState title="No payslips yet" message="Processed payroll for you will show here." icon="wallet-outline" />
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md }}
          renderItem={({ item }) => {
            const label =
              (item as { month?: string; period?: string }).month ??
              (item as { period?: string }).period ??
              `Payslip #${item.id}`;
            return (
              <View
                style={{
                  backgroundColor: palette.surface,
                  borderColor: palette.border,
                  borderWidth: 1,
                  borderRadius: radius.md,
                  padding: spacing.md,
                  marginBottom: spacing.sm,
                }}
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{label}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                  Net:{' '}
                  {String(
                    (item as { net_pay?: number | string; netPay?: number | string }).net_pay ??
                      (item as { netPay?: number | string }).netPay ??
                      '—',
                  )}
                </Text>
                <Button
                  label={downloadingId === item.id ? 'Downloading…' : 'Download payslip PDF'}
                  variant="secondary"
                  loading={downloadingId === item.id}
                  onPress={() => void downloadPayslip(item.id, String(label))}
                  style={{ marginTop: spacing.sm, alignSelf: 'flex-start' }}
                />
              </View>
            );
          }}
        />
      )}
    </ScreenContainer>
  );
};
