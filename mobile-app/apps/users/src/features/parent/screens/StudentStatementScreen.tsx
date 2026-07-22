import { useStudentDetail, useStudentStatement } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { formatKes } from '../utils/format';

type Nav = StackNavigationProp<ParentStackParamList>;

export const StudentStatementScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<RouteProp<ParentStackParamList, 'StudentStatement'>>();
  const { palette, spacing, typography, radius, colors } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });
  const statement = useStudentStatement(studentId);

  const data = statement.data as
    | {
        balance?: number | string;
        outstanding?: number | string;
        closing_balance?: number | string;
        transactions?: Array<{
          id: number;
          date: string;
          type: string;
          reference?: string;
          description?: string;
          debit?: number;
          credit?: number;
          balance?: number;
        }>;
      }
    | undefined;

  const balance =
    data?.balance ?? data?.outstanding ?? data?.closing_balance;

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title="Fee statement"
        subtitle={detail.data?.fullName ?? undefined}
        onBack={() => navigation.goBack()}
      />
      {statement.isLoading ? (
        <SkeletonListRows count={5} />
      ) : statement.isError ? (
        <EmptyState
          title="Could not load statement"
          message={statement.error instanceof Error ? statement.error.message : 'Try again later.'}
          icon="alert-circle-outline"
        />
      ) : (
        <>
          <View
            style={{
              backgroundColor: palette.surface,
              borderColor: palette.border,
              borderWidth: 1,
              borderRadius: radius.lg,
              padding: spacing.lg,
              marginBottom: spacing.md,
            }}
          >
            <Text style={{ color: palette.textPrimary, fontWeight: '700', fontSize: typography.headline.fontSize }}>
              Balance
            </Text>
            <Text style={{ color: colors.primary, fontSize: 28, fontWeight: '700', marginTop: spacing.sm }}>
              {formatKes(typeof balance === 'string' ? Number(balance) : balance)}
            </Text>
            <Button
              label="Pay with M-Pesa"
              style={{ marginTop: spacing.md }}
              onPress={() =>
                navigation.navigate('MpesaPrompt', {
                  studentId,
                  amount: typeof balance === 'number' && balance > 0 ? balance : undefined,
                })
              }
            />
          </View>

          {(data?.transactions ?? []).length > 0 ? (
            <>
              <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
                Transactions
              </Text>
              {(data?.transactions ?? []).map((tx) => (
                <View
                  key={tx.id}
                  style={{
                    backgroundColor: palette.surface,
                    borderColor: palette.border,
                    borderWidth: 1,
                    borderRadius: radius.md,
                    padding: spacing.md,
                    marginBottom: spacing.sm,
                  }}
                >
                  <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>
                    {tx.description || tx.reference || tx.type}
                  </Text>
                  <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                    {tx.date} · {tx.type}
                  </Text>
                  <Text style={{ color: palette.textPrimary, marginTop: spacing.xs }}>
                    {tx.debit ? `Dr ${formatKes(tx.debit)}` : null}
                    {tx.credit ? `Cr ${formatKes(tx.credit)}` : null}
                    {tx.balance != null ? ` · Bal ${formatKes(tx.balance)}` : null}
                  </Text>
                </View>
              ))}
            </>
          ) : (
            <Text style={{ color: palette.textMuted }}>No detailed transactions in this statement.</Text>
          )}
        </>
      )}
    </ScreenContainer>
  );
};
