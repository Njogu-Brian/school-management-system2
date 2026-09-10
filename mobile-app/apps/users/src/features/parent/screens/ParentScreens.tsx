import {
  buildParentHomeNavParams,
  formatRoleLabel,
  PARENT_HOME_CHILD_ACTIONS,
  PARENT_HOME_CORE_ACTIONS,
  timeOfDayGreeting,
  useAuth,
  useCurrentUser,
  useInfiniteStudentList,
  studentsApi,
  financeApi,
  useStudentStats,
  useUnreadNotificationCount,
  useStudentReportCards,
  type ParentHomeActionDef,
  type ReportCardListRecord,
} from '@erp/core';
import {
  Button,
  DashboardHero,
  DashboardSection,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ListRowCard,
  QuickAction,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  StatusBadge,
  SurfaceCard,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { ActivityIndicator, FlatList, Linking, Pressable, RefreshControl, Text, View } from 'react-native';
import { useQueryClient } from '@tanstack/react-query';
import { navigateToTab } from '../../../navigation/navigateToTab';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { showError, showSuccess, confirmAction } from '../../shared/utils/feedback';
import { AppModeSwitch } from '../../shared/components/AppModeSwitch';
import { useSelectedChild } from '../hooks/useSelectedChild';
import { formatKes, formatShortDate } from '../utils/format';

type Nav = StackNavigationProp<ParentStackParamList>;

function FamilyFeesCard({
  studentIds,
  onPressFees,
}: {
  studentIds: number[];
  onPressFees: () => void;
}) {
  const { palette, spacing, typography, colors } = useTheme();
  // Hooks can't be called in a loop — support up to 4 children on the home snapshot.
  const s0 = useStudentStats(studentIds[0] ?? 0, { enabled: (studentIds[0] ?? 0) > 0 });
  const s1 = useStudentStats(studentIds[1] ?? 0, { enabled: (studentIds[1] ?? 0) > 0 });
  const s2 = useStudentStats(studentIds[2] ?? 0, { enabled: (studentIds[2] ?? 0) > 0 });
  const s3 = useStudentStats(studentIds[3] ?? 0, { enabled: (studentIds[3] ?? 0) > 0 });

  const { due, upcoming, loading } = useMemo(() => {
    const rows = [s0, s1, s2, s3].slice(0, studentIds.length);
    let dueSum = 0;
    let upcomingSum = 0;
    let anyLoading = false;
    for (const row of rows) {
      if (row.isLoading) anyLoading = true;
      dueSum += Number(row.data?.fees_due ?? row.data?.fees_balance ?? 0);
      upcomingSum += Number(row.data?.fees_upcoming ?? 0);
    }
    return { due: dueSum, upcoming: upcomingSum, loading: anyLoading };
  }, [s0, s1, s2, s3, studentIds.length]);

  return (
    <SurfaceCard accent={due > 0 ? 'warning' : 'success'} onPress={onPressFees}>
      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
        School fees
      </Text>
      <View style={{ flexDirection: 'row', gap: spacing.md }}>
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Current due
          </Text>
          <Text style={{ color: colors.primary, fontSize: 22, fontWeight: '700', marginTop: 2 }}>
            {loading ? '…' : formatKes(due)}
          </Text>
        </View>
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Upcoming
          </Text>
          <Text style={{ color: palette.textPrimary, fontSize: 22, fontWeight: '700', marginTop: 2 }}>
            {loading ? '…' : formatKes(upcoming)}
          </Text>
        </View>
      </View>
      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: spacing.sm }}>
        Invoices · statements · wallet · payments
      </Text>
    </SurfaceCard>
  );
}

function ChildResultsSnapshot({
  studentId,
  studentName,
  onOpen,
  onOpenAll,
}: {
  studentId: number;
  studentName: string;
  onOpen: (card: ReportCardListRecord) => void;
  onOpenAll: () => void;
}) {
  const { palette, spacing, typography } = useTheme();
  const reportCards = useStudentReportCards(studentId, { enabled: studentId > 0 });
  const published = useMemo(
    () => (reportCards.data ?? []).filter((c) => c.status === 'published').slice(0, 2),
    [reportCards.data],
  );

  if (reportCards.isLoading) {
    return <SkeletonListRows count={1} />;
  }

  if (published.length === 0) {
    return (
      <EmptyState
        title="No published results yet"
        message="When the school publishes report cards for this child, they will appear here."
        icon="school-outline"
        actionLabel="Open academics"
        onAction={onOpenAll}
      />
    );
  }

  return (
    <View style={{ marginBottom: spacing.sm }}>
      {published.map((card) => (
        <SurfaceCard
          key={card.id}
          onPress={() => onOpen(card)}
          accent={card.access_locked ? 'warning' : 'success'}
        >
          <View style={{ flexDirection: 'row', justifyContent: 'space-between', alignItems: 'center' }}>
            <View style={{ flex: 1, paddingRight: spacing.sm }}>
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                {studentName} · {card.class_name ?? 'Report card'}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                Term {card.term_name ?? `Term ${card.term_id}`}
                {card.generated_at || card.updated_at
                  ? ` · ${formatShortDate(card.generated_at ?? card.updated_at)}`
                  : ''}
              </Text>
            </View>
            <StatusBadge
              label={card.access_locked ? 'Fees due' : 'Published'}
              tone={card.access_locked ? 'warning' : 'success'}
            />
          </View>
        </SurfaceCard>
      ))}
      <Button label="All results" variant="secondary" onPress={onOpenAll} />
    </View>
  );
}

function runHomeAction(navigation: Nav, action: ParentHomeActionDef, studentId: number | null) {
  const params = buildParentHomeNavParams(action, studentId);
  if (params === null) {
    showError('Select a child', 'Choose a child before opening this section.');
    return;
  }
  navigateToTab(navigation, action.jump.tab, action.jump.screen, params, action.jump.tabHome);
}

export const ParentHomeScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { palette, spacing, typography, colors } = useTheme();
  const navigation = useNavigation<Nav>();
  const queryClient = useQueryClient();
  const unreadQuery = useUnreadNotificationCount();
  const childrenQuery = useInfiniteStudentList({
    search: '',
    classroomId: null,
    streamId: null,
    status: 'active',
    perPage: 40,
  });

  const children = useMemo(
    () => childrenQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [childrenQuery.data],
  );
  const childrenCount = childrenQuery.data?.pages[0]?.total ?? children.length;
  const { selectedId, selectedChild, selectChild, ready: childReady } = useSelectedChild(children);
  const feeStudentIds = useMemo(
    () => (selectedId ? [selectedId] : children.map((c) => c.id).slice(0, 4)),
    [selectedId, children],
  );
  const unread = unreadQuery.data ?? 0;
  const [manualRefreshing, setManualRefreshing] = useState(false);
  const refreshing = manualRefreshing || childrenQuery.isRefetching || unreadQuery.isRefetching;

  const onRefreshHome = async () => {
    setManualRefreshing(true);
    try {
      await Promise.all([
        childrenQuery.refetch(),
        unreadQuery.refetch(),
        queryClient.invalidateQueries({ queryKey: ['finance'] }),
        queryClient.invalidateQueries({ queryKey: ['attendance'] }),
        queryClient.invalidateQueries({ queryKey: ['parent-wallet'] }),
        queryClient.invalidateQueries({ queryKey: ['diaries'] }),
        queryClient.invalidateQueries({ queryKey: ['notifications'] }),
      ]);
    } finally {
      setManualRefreshing(false);
    }
  };

  const meta = useMemo(() => {
    const parts: string[] = [];
    if (childrenCount > 0) parts.push(`${childrenCount} ${childrenCount === 1 ? 'child' : 'children'}`);
    if (unread > 0) parts.push(`${unread} unread`);
    return parts.join(' · ') || undefined;
  }, [childrenCount, unread]);

  return (
    <ScreenContainer
      scroll
      edges={['bottom']}
      contentContainerStyle={{ padding: spacing.md }}
      scrollProps={{
        refreshControl: (
          <RefreshControl refreshing={refreshing} onRefresh={() => void onRefreshHome()} colors={[colors.primary]} />
        ),
      }}
    >
      {refreshing ? (
        <View style={{ alignItems: 'center', marginBottom: spacing.sm }}>
          <ActivityIndicator color={colors.primary} />
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
            Refreshing…
          </Text>
        </View>
      ) : null}

      <DashboardHero
        variant="people"
        greeting={timeOfDayGreeting()}
        userName={user?.name ?? 'Parent'}
        roleLabel={formatRoleLabel(user?.roleName ?? user?.role, 'Parent')}
        title="Home"
        subtitle="School life for your child — attendance, results, fees, and messages"
        meta={meta}
      />

      <View style={{ marginBottom: spacing.md }}>
        <AppModeSwitch />
      </View>

      {childrenQuery.isLoading || !childReady ? (
        <SkeletonListRows count={4} />
      ) : childrenQuery.isError ? (
        <EmptyState
          title="Could not load children"
          message={childrenQuery.error instanceof Error ? childrenQuery.error.message : 'Please try again.'}
          icon="alert-circle-outline"
          actionLabel="Retry"
          onAction={() => void childrenQuery.refetch()}
        />
      ) : children.length === 0 ? (
        <EmptyState
          title="No children linked"
          message="Children linked to your parent account will appear here."
          icon="people-outline"
        />
      ) : (
        <>
          <DashboardSection title={children.length > 1 ? 'Selected child' : 'Your child'}>
            {children.length > 1 ? (
              <FilterChipRow>
                {children.map((child) => (
                  <FilterChip
                    key={child.id}
                    label={child.fullName}
                    active={child.id === selectedId}
                    onPress={() => void selectChild(child.id)}
                  />
                ))}
              </FilterChipRow>
            ) : null}
            {selectedChild ? (
              <SurfaceCard
                accent="brand"
                onPress={() =>
                  navigateToTab(
                    navigation,
                    'ParentChildrenTab',
                    'ChildHub',
                    { studentId: selectedChild.id },
                    'ChildrenList',
                  )
                }
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{selectedChild.fullName}</Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  {[selectedChild.admissionNumber, selectedChild.className].filter(Boolean).join(' · ') ||
                    'Tap for child hub'}
                </Text>
              </SurfaceCard>
            ) : null}
          </DashboardSection>

          {unread > 0 ? (
            <DashboardSection title="Alerts">
              <SurfaceCard
                accent="warning"
                onPress={() =>
                  navigateToTab(navigation, 'ParentHomeTab', 'Notifications', undefined, 'ParentHome')
                }
              >
                <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                  {unread} unread notification{unread === 1 ? '' : 's'}
                </Text>
                <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                  Tap to open notifications
                </Text>
              </SurfaceCard>
            </DashboardSection>
          ) : null}

          <DashboardSection title="School fees">
            <FamilyFeesCard
              studentIds={feeStudentIds}
              onPressFees={() => navigateToTab(navigation, 'ParentFeesTab', 'FeesHome')}
            />
          </DashboardSection>

          <DashboardSection title="Results">
            {selectedId && selectedChild ? (
              <ChildResultsSnapshot
                studentId={selectedId}
                studentName={selectedChild.fullName}
                onOpen={(card) =>
                  navigateToTab(navigation, 'ParentAcademicTab', 'ReportCardDetail', {
                    studentId: selectedId,
                    reportCardId: card.id,
                  })
                }
                onOpenAll={() =>
                  navigateToTab(
                    navigation,
                    'ParentAcademicTab',
                    'ChildResults',
                    { studentId: selectedId },
                    'AcademicHome',
                  )
                }
              />
            ) : (
              <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
                Select a child to see results.
              </Text>
            )}
          </DashboardSection>

          <DashboardSection title="School life">
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
              {PARENT_HOME_CORE_ACTIONS.map((action) => (
                <QuickAction
                  key={action.id}
                  label={
                    action.id === 'notifications' && unread > 0
                      ? `Notifications (${unread})`
                      : action.label
                  }
                  icon={action.icon}
                  onPress={() => runHomeAction(navigation, action, selectedId)}
                />
              ))}
            </View>
          </DashboardSection>

          <DashboardSection title="Child & account">
            <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
              {PARENT_HOME_CHILD_ACTIONS.map((action) => (
                <QuickAction
                  key={action.id}
                  label={action.label}
                  icon={action.icon}
                  onPress={() => runHomeAction(navigation, action, selectedId)}
                />
              ))}
            </View>
          </DashboardSection>
        </>
      )}

      <Button
        label="Sign out"
        variant="ghost"
        onPress={() =>
          confirmAction('Sign out', 'Sign out of the Users app on this device?', 'Sign out', () => void logout(), true)
        }
        style={{ marginTop: spacing.md, marginBottom: spacing.sm, borderColor: colors.error, borderWidth: 1 }}
      />
    </ScreenContainer>
  );
};

export const ParentChildrenScreen: React.FC = () => {
  const { spacing } = useTheme();
  const navigation = useNavigation<Nav>();
  const listQuery = useInfiniteStudentList({
    search: '',
    classroomId: null,
    streamId: null,
    status: 'active',
    perPage: 40,
  });
  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['bottom']}>
      {listQuery.isLoading ? (
        <SkeletonListRows count={4} />
      ) : students.length === 0 ? (
        <EmptyState
          title="No children linked"
          message="Children linked to your parent account will appear here."
          icon="people-outline"
        />
      ) : (
        <FlatList
          data={students}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md }}
          renderItem={({ item }) => (
            <ListRowCard
              title={item.fullName}
              subtitle={[item.admissionNumber, item.className].filter(Boolean).join(' · ')}
              icon="person-outline"
              glyph="person"
              accent="brand"
              onPress={() => navigation.navigate('ChildHub', { studentId: item.id })}
            />
          )}
        />
      )}
    </ScreenContainer>
  );
};

function ChildFeeCard({
  studentId,
  name,
  admissionNumber,
  className,
}: {
  studentId: number;
  name: string;
  admissionNumber?: string | null;
  className?: string | null;
}) {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, colors } = useTheme();
  const stats = useStudentStats(studentId);
  const [loadingLink, setLoadingLink] = useState(false);
  const [loadingInvoices, setLoadingInvoices] = useState(false);
  const [invoices, setInvoices] = useState<
    Array<{ id: number; invoice_number: string; balance: number; status: string; due_date?: string | null }>
  >([]);
  const [showInvoices, setShowInvoices] = useState(false);

  const balanceDue = stats.data?.fees_due ?? stats.data?.fees_balance ?? 0;
  const balanceUpcoming = stats.data?.fees_upcoming ?? 0;

  const openPayLink = async () => {
    setLoadingLink(true);
    try {
      const res = await studentsApi.getPaymentLink(studentId);
      if (!res.success || !res.data) throw new Error(res.message || 'Could not create payment link.');
      const url = res.data.short_url || res.data.url;
      if (!url) throw new Error('No payment URL returned.');
      await Linking.openURL(url);
      showSuccess('Payment link opened');
    } catch (err) {
      showError('Pay link failed', err instanceof Error ? err.message : 'Could not open payment link.');
    } finally {
      setLoadingLink(false);
    }
  };

  const loadInvoices = async () => {
    if (showInvoices) {
      setShowInvoices(false);
      return;
    }
    setLoadingInvoices(true);
    try {
      const res = await financeApi.listInvoices({ student_id: studentId, per_page: 20 });
      if (!res.success || !res.data) throw new Error(res.message || 'Failed to load invoices.');
      setInvoices(res.data.data ?? []);
      setShowInvoices(true);
    } catch (err) {
      showError('Invoices failed', err instanceof Error ? err.message : 'Could not load invoices.');
    } finally {
      setLoadingInvoices(false);
    }
  };

  return (
    <SurfaceCard accent={Number(balanceDue) > 0 ? 'warning' : 'success'}>
      <Pressable
        onPress={() => navigateToTab(navigation, 'ParentChildrenTab', 'ChildHub', { studentId })}
      >
        <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{name}</Text>
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
          {[admissionNumber, className].filter(Boolean).join(' · ')}
        </Text>
      </Pressable>

      <Pressable
        onPress={() =>
          navigation.navigate('MpesaPrompt', {
            studentId,
            amount: typeof balanceDue === 'number' && balanceDue > 0 ? balanceDue : undefined,
          })
        }
        accessibilityRole="button"
        accessibilityLabel="View or pay current due"
        style={{ marginTop: spacing.md }}
      >
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
          Current due
        </Text>
        <Text style={{ color: colors.primary, fontSize: 22, fontWeight: '700', marginTop: 2 }}>
          {stats.isLoading ? '…' : formatKes(balanceDue)}
        </Text>
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
          Tap to view / pay
        </Text>
      </Pressable>
      <Pressable
        onPress={() => navigation.navigate('StudentStatement', { studentId })}
        accessibilityRole="button"
        accessibilityLabel="View upcoming fees on statement"
        style={{ marginTop: spacing.sm }}
      >
        <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
          Upcoming
        </Text>
        <Text style={{ color: palette.textPrimary, fontSize: 18, fontWeight: '700', marginTop: 2 }}>
          {stats.isLoading ? '…' : formatKes(balanceUpcoming)}
        </Text>
        <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
          Tap to view statement
        </Text>
      </Pressable>

      <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.md }}>
        <Button
          label="Pay M-Pesa"
          onPress={() =>
            navigation.navigate('MpesaPrompt', {
              studentId,
              amount: typeof balanceDue === 'number' && balanceDue > 0 ? balanceDue : undefined,
            })
          }
        />
        <Button
          label="Statement"
          variant="secondary"
          onPress={() => navigation.navigate('StudentStatement', { studentId })}
        />
        <Button
          label={showInvoices ? 'Hide invoices' : 'Invoices'}
          variant="ghost"
          loading={loadingInvoices}
          onPress={() => void loadInvoices()}
        />
        <Button label="Browser link" variant="ghost" loading={loadingLink} onPress={() => void openPayLink()} />
      </View>

      {showInvoices ? (
        invoices.length === 0 ? (
          <Text style={{ color: palette.textMuted, marginTop: spacing.sm }}>No invoices found.</Text>
        ) : (
          invoices.map((inv) => (
            <Pressable
              key={inv.id}
              onPress={() =>
                navigation.navigate('InvoiceDetail', {
                  studentId,
                  invoiceId: inv.id,
                })
              }
              style={{
                marginTop: spacing.sm,
                paddingTop: spacing.sm,
                borderTopWidth: 1,
                borderTopColor: palette.border,
              }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{inv.invoice_number}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                {formatKes(inv.balance)} · {inv.status}
                {inv.due_date ? ` · Due ${formatShortDate(inv.due_date)}` : ''}
              </Text>
              <Text style={{ color: palette.textMuted, marginTop: 2, fontSize: typography.caption.fontSize }}>
                Tap to open invoice
              </Text>
            </Pressable>
          ))
        )
      ) : null}
    </SurfaceCard>
  );
}

export const ParentFeesScreen: React.FC = () => {
  const { palette, spacing, typography, radius, colors } = useTheme();
  const navigation = useNavigation<Nav>();
  const listQuery = useInfiniteStudentList({
    search: '',
    classroomId: null,
    streamId: null,
    status: 'active',
    perPage: 40,
  });
  const students = useMemo(
    () => listQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [listQuery.data],
  );
  const studentIds = useMemo(() => students.map((s) => s.id), [students]);

  // Up to 4 children for family totals (same pattern as home snapshot).
  const s0 = useStudentStats(studentIds[0] ?? 0, { enabled: (studentIds[0] ?? 0) > 0 });
  const s1 = useStudentStats(studentIds[1] ?? 0, { enabled: (studentIds[1] ?? 0) > 0 });
  const s2 = useStudentStats(studentIds[2] ?? 0, { enabled: (studentIds[2] ?? 0) > 0 });
  const s3 = useStudentStats(studentIds[3] ?? 0, { enabled: (studentIds[3] ?? 0) > 0 });

  const { totalDue, totalUpcoming, loadingTotals } = useMemo(() => {
    const rows = [s0, s1, s2, s3].slice(0, studentIds.length);
    let due = 0;
    let upcoming = 0;
    let loading = false;
    for (const r of rows) {
      if (r.isLoading) loading = true;
      due += Number(r.data?.fees_due ?? r.data?.fees_balance ?? 0);
      upcoming += Number(r.data?.fees_upcoming ?? 0);
    }
    return { totalDue: due, totalUpcoming: upcoming, loadingTotals: loading };
  }, [s0, s1, s2, s3, studentIds.length]);

  const primaryPayStudent = useMemo(() => {
    const stats = [s0, s1, s2, s3];
    let bestIdx = 0;
    let bestDue = -1;
    studentIds.forEach((_, i) => {
      const due = Number(stats[i]?.data?.fees_due ?? stats[i]?.data?.fees_balance ?? 0);
      if (due > bestDue) {
        bestDue = due;
        bestIdx = i;
      }
    });
    return students[bestIdx] ?? students[0];
  }, [students, studentIds, s0, s1, s2, s3]);

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md }}>
      <View
        style={{
          backgroundColor: colors.primary,
          borderRadius: radius.lg,
          padding: spacing.lg,
          marginBottom: spacing.md,
        }}
      >
        <Text style={{ color: 'rgba(255,255,255,0.85)', fontWeight: '600', fontSize: typography.caption.fontSize }}>
          School fees · family balance due
        </Text>
        <Text style={{ color: '#fff', fontSize: 32, fontWeight: '800', marginTop: 4 }}>
          {loadingTotals ? '…' : formatKes(totalDue)}
        </Text>
        <Text style={{ color: 'rgba(255,255,255,0.75)', marginTop: spacing.sm }}>
          Upcoming {loadingTotals ? '…' : formatKes(totalUpcoming)}
        </Text>
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm, marginTop: spacing.md }}>
          <Button
            label="Pay with M-Pesa"
            onPress={() => {
              if (!primaryPayStudent) return;
              navigation.navigate('MpesaPrompt', {
                studentId: primaryPayStudent.id,
                amount: totalDue > 0 ? totalDue : undefined,
              });
            }}
          />
          <Button
            label="Wallets"
            variant="secondary"
            onPress={() => navigation.navigate('WalletHome')}
          />
        </View>
      </View>

      <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md }}>
        <Pressable
          onPress={() => navigation.navigate('WalletHome')}
          style={{
            flex: 1,
            padding: spacing.md,
            borderRadius: radius.md,
            borderWidth: 1,
            borderColor: palette.border,
            backgroundColor: palette.surface,
          }}
        >
          <Soft3DIcon name="wallet-outline" glyph="wallet" tone="emerald" size={40} />
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.sm }}>Wallet</Text>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Balance, top up & pay
          </Text>
        </Pressable>
        <Pressable
          onPress={() => {
            if (primaryPayStudent) {
              navigation.navigate('StudentStatement', { studentId: primaryPayStudent.id });
            }
          }}
          style={{
            flex: 1,
            padding: spacing.md,
            borderRadius: radius.md,
            borderWidth: 1,
            borderColor: palette.border,
            backgroundColor: palette.surface,
          }}
        >
          <Soft3DIcon name="receipt-outline" glyph="receipt" tone="cyan" size={40} />
          <Text style={{ color: palette.textPrimary, fontWeight: '700', marginTop: spacing.sm }}>Statements</Text>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Ledger per child
          </Text>
        </Pressable>
      </View>

      <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.md }}>
        School fees includes invoices, statements, payments, and wallet for your linked children. Amounts come
        from each child fee record — not a combined invented balance.
      </Text>

      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm, fontSize: typography.title.fontSize }}>
        By child · invoices & payments
      </Text>

      {listQuery.isLoading ? (
        <SkeletonListRows count={3} />
      ) : students.length === 0 ? (
        <EmptyState title="No children" message="Link children to manage fees." icon="cash-outline" />
      ) : (
        students.map((item) => (
          <ChildFeeCard
            key={item.id}
            studentId={item.id}
            name={item.fullName}
            admissionNumber={item.admissionNumber}
            className={item.className}
          />
        ))
      )}
    </ScreenContainer>
  );
};
