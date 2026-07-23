import {
  downloadAuthenticatedFile,
  formatFinanceAmount,
  payrollApi,
  useCurrentUser,
  usePayrollRecordsList,
} from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useCallback, useMemo, useState } from 'react';
import { FlatList, Pressable, RefreshControl, Text, View } from 'react-native';
import { showError } from '../../shared/utils/feedback';

type LooseNav = {
  goBack: () => void;
  navigate: (name: string, params?: object) => void;
};

export const MyPayslipsScreen: React.FC = () => {
  const navigation = useNavigation<LooseNav>();
  const user = useCurrentUser();
  const { colors, palette, spacing, typography, radius } = useTheme();
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
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['top', 'bottom']}>
      <View style={{ flex: 1, paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader
          title="My payslips"
          subtitle="Tap a period to view the full payslip"
          onBack={() => navigation.goBack()}
        />

        {staffId == null ? (
          <EmptyState
            title="No staff profile"
            message="Your account is not linked to a staff record, so payslips are unavailable."
            icon="wallet-outline"
          />
        ) : listQuery.isLoading ? (
          <SkeletonListRows count={6} />
        ) : listQuery.isError ? (
          <EmptyState
            title="Could not load payslips"
            message={listQuery.error instanceof Error ? listQuery.error.message : 'Try again later.'}
            icon="alert-circle-outline"
          />
        ) : (
          <FlatList
            style={{ flex: 1 }}
            data={items}
            keyExtractor={(item) => String(item.id)}
            contentContainerStyle={{
              paddingTop: spacing.sm,
              paddingBottom: spacing.xl,
              flexGrow: 1,
            }}
            refreshControl={
              <RefreshControl
                refreshing={listQuery.isFetching && !listQuery.isLoading}
                onRefresh={() => void listQuery.refetch()}
                colors={[colors.primary]}
                tintColor={colors.primary}
              />
            }
            onEndReached={() => {
              if (listQuery.hasNextPage && !listQuery.isFetchingNextPage) {
                void listQuery.fetchNextPage();
              }
            }}
            ListEmptyComponent={
              <EmptyState
                title="No payslips yet"
                message="Processed payroll for you will show here."
                icon="wallet-outline"
              />
            }
            renderItem={({ item }) => {
              const label = item.period_name ?? item.month ?? `Payslip #${item.id}`;
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
                  <Pressable
                    onPress={() => navigation.navigate('PayslipDetail', { recordId: item.id })}
                    accessibilityRole="button"
                    accessibilityLabel={`View payslip for ${label}`}
                  >
                    <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{label}</Text>
                    <Text
                      style={{
                        color: palette.textSecondary,
                        fontSize: typography.caption.fontSize,
                        marginTop: 4,
                        textTransform: 'capitalize',
                      }}
                    >
                      {formatFinanceAmount(item.net_salary)} · {item.status ?? '—'}
                    </Text>
                    <Text
                      style={{
                        color: colors.primary,
                        fontWeight: '600',
                        fontSize: typography.caption.fontSize,
                        marginTop: 8,
                      }}
                    >
                      View full details
                    </Text>
                  </Pressable>
                  <Pressable
                    onPress={() => void downloadPayslip(item.id, String(label))}
                    style={{ marginTop: 8 }}
                    accessibilityRole="button"
                  >
                    <Text
                      style={{
                        color: palette.textSecondary,
                        fontWeight: '600',
                        fontSize: typography.caption.fontSize,
                      }}
                    >
                      {downloadingId === item.id ? 'Downloading…' : 'Download payslip PDF'}
                    </Text>
                  </Pressable>
                </View>
              );
            }}
          />
        )}
      </View>
    </ScreenContainer>
  );
};
