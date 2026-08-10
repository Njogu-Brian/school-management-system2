import {
  timeOfDayGreeting,
  useCurrentUser,
  useInfiniteStudentList,
  studentsApi,
  financeApi,
  useStudentStats,
  useUnreadNotificationCount,
  useStudentReportCards,
  type ReportCardListRecord,
} from '@erp/core';
import {
  Button,
  DashboardHero,
  DashboardSection,
  EmptyState,
  QuickAction,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  StatusBadge,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo, useState } from 'react';
import { FlatList, Linking, Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';
import { showError, showSuccess } from '../../shared/utils/feedback';
import { formatKes, formatShortDate } from '../utils/format';

type Nav = StackNavigationProp<ParentStackParamList>;

const QUICK_ACTIONS: Array<{
  label: string;
  icon: 'people-outline' | 'cash-outline' | 'wallet-outline' | 'chatbubbles-outline' | 'school-outline' | 'megaphone-outline' | 'notifications-outline' | 'alert-circle-outline';
  route: keyof ParentStackParamList;
}> = [
  { label: 'Children', icon: 'people-outline', route: 'ChildrenList' },
  { label: 'Fees', icon: 'cash-outline', route: 'FeesHome' },
  { label: 'Wallets', icon: 'wallet-outline', route: 'WalletHome' },
  { label: 'Diary', icon: 'chatbubbles-outline', route: 'DiaryList' },
  { label: 'Academic', icon: 'school-outline', route: 'AcademicHome' },
];

const SCHOOL_ACTIONS: Array<{
  label: string;
  icon: 'people-outline' | 'wallet-outline' | 'chatbubbles-outline' | 'megaphone-outline' | 'notifications-outline' | 'alert-circle-outline';
  route: keyof ParentStackParamList;
}> = [
  { label: 'Announcements', icon: 'megaphone-outline', route: 'Announcements' },
  { label: 'Notifications', icon: 'notifications-outline', route: 'Notifications' },
  { label: 'Raise concern', icon: 'alert-circle-outline', route: 'ConcernsList' },
];

function FamilyFeesCard({
  studentIds,
  onPressFees,
}: {
  studentIds: number[];
  onPressFees: () => void;
}) {
  const { palette, spacing, typography, radius, colors } = useTheme();
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
    <View
      style={{
        backgroundColor: palette.surface,
        borderColor: palette.border,
        borderWidth: 1,
        borderRadius: radius.lg,
        padding: spacing.md,
        marginBottom: spacing.sm,
      }}
    >
      <Text style={{ color: palette.textPrimary, fontWeight: '700', marginBottom: spacing.sm }}>
        School fees
      </Text>
      <View style={{ flexDirection: 'row', gap: spacing.md }}>
        <Pressable
          onPress={onPressFees}
          accessibilityRole="button"
          accessibilityLabel="View or pay current due fees"
          style={{ flex: 1 }}
        >
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Current due
          </Text>
          <Text style={{ color: colors.primary, fontSize: 22, fontWeight: '700', marginTop: 2 }}>
            {loading ? '…' : formatKes(due)}
          </Text>
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
            Tap to view / pay
          </Text>
        </Pressable>
        <Pressable
          onPress={onPressFees}
          accessibilityRole="button"
          accessibilityLabel="View upcoming fees"
          style={{ flex: 1 }}
        >
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Upcoming
          </Text>
          <Text style={{ color: palette.textPrimary, fontSize: 22, fontWeight: '700', marginTop: 2 }}>
            {loading ? '…' : formatKes(upcoming)}
          </Text>
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginTop: 4 }}>
            Tap to view
          </Text>
        </Pressable>
      </View>
    </View>
  );
}

function ChildResultsSnapshot({
  studentId,
  studentName,
  onOpen,
}: {
  studentId: number;
  studentName: string;
  onOpen: (card: ReportCardListRecord) => void;
}) {
  const { palette, spacing, typography, radius } = useTheme();
  const reportCards = useStudentReportCards(studentId, { enabled: studentId > 0 });
  const published = useMemo(
    () => (reportCards.data ?? []).filter((c) => c.status === 'published').slice(0, 2),
    [reportCards.data],
  );

  if (reportCards.isLoading || published.length === 0) return null;

  return (
    <View style={{ marginBottom: spacing.sm }}>
      {published.map((card) => (
        <Pressable
          key={card.id}
          onPress={() => onOpen(card)}
          style={{
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: radius.lg,
            padding: spacing.md,
            marginBottom: spacing.sm,
          }}
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
        </Pressable>
      ))}
    </View>
  );
}

export const ParentHomeScreen: React.FC = () => {
  const user = useCurrentUser();
  const { palette, spacing, typography } = useTheme();
  const navigation = useNavigation<Nav>();
  const tabClearance = useFloatingTabBarClearance();
  const unreadQuery = useUnreadNotificationCount();
  const childrenQuery = useInfiniteStudentList({
    search: '',
    classroomId: null,
    streamId: null,
    status: 'active',
    perPage: 8,
  });

  const children = useMemo(
    () => childrenQuery.data?.pages.flatMap((p) => p.items) ?? [],
    [childrenQuery.data],
  );
  const childrenCount = childrenQuery.data?.pages[0]?.total ?? children.length;
  const studentIds = useMemo(() => children.map((c) => c.id).slice(0, 4), [children]);

  const meta = useMemo(() => {
    const parts: string[] = [];
    if (childrenCount > 0) parts.push(`${childrenCount} ${childrenCount === 1 ? 'child' : 'children'}`);
    const unread = unreadQuery.data ?? 0;
    if (unread > 0) parts.push(`${unread} unread`);
    return parts.join(' · ') || undefined;
  }, [childrenCount, unreadQuery.data]);

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}>
      <DashboardHero
        variant="people"
        greeting={timeOfDayGreeting()}
        userName={user?.name ?? 'Parent'}
        title="Parent portal"
        subtitle="Track fees, attendance, and school updates for your children"
        meta={meta}
      />

      <DashboardSection title="Fees snapshot">
        {childrenQuery.isLoading ? (
          <SkeletonListRows count={1} />
        ) : studentIds.length === 0 ? (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
            Link a child to see due and upcoming fees.
          </Text>
        ) : (
          <FamilyFeesCard studentIds={studentIds} onPressFees={() => navigation.navigate('FeesHome')} />
        )}
      </DashboardSection>

      <DashboardSection title="Current results">
        {childrenQuery.isLoading ? (
          <SkeletonListRows count={2} />
        ) : children.length === 0 ? (
          <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize }}>
            Published report cards will appear here.
          </Text>
        ) : (
          <>
            {children.slice(0, 4).map((child) => (
              <ChildResultsSnapshot
                key={child.id}
                studentId={child.id}
                studentName={child.fullName}
                onOpen={(card) =>
                  navigation.navigate('ReportCardDetail', {
                    studentId: child.id,
                    reportCardId: card.id,
                  })
                }
              />
            ))}
            <Text style={{ color: palette.textMuted, fontSize: typography.caption.fontSize, marginBottom: spacing.sm }}>
              Tap a published report card to view it, or open Academic for the full list.
            </Text>
            <Button
              label="View all results"
              variant="secondary"
              onPress={() => navigation.navigate('AcademicHome')}
            />
          </>
        )}
      </DashboardSection>

      <DashboardSection title="Children & fees">
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          {QUICK_ACTIONS.map((item) => (
            <QuickAction
              key={item.route}
              label={item.label}
              icon={item.icon}
              onPress={() => navigation.navigate(item.route as never)}
            />
          ))}
        </View>
      </DashboardSection>

      <DashboardSection title="School">
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          {SCHOOL_ACTIONS.map((item) => (
            <QuickAction
              key={item.route}
              label={item.label}
              icon={item.icon}
              onPress={() => navigation.navigate(item.route as never)}
            />
          ))}
        </View>
      </DashboardSection>
    </ScreenContainer>
  );
};

export const ParentChildrenScreen: React.FC = () => {
  const { palette, spacing, typography, radius } = useTheme();
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
            <Pressable
              onPress={() => navigation.navigate('ChildHub', { studentId: item.id })}
              style={{
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderWidth: 1,
                borderRadius: radius.md,
                padding: spacing.md,
                marginBottom: spacing.sm,
              }}
            >
              <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{item.fullName}</Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 2 }}>
                {[item.admissionNumber, item.className].filter(Boolean).join(' · ')}
              </Text>
            </Pressable>
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
  const { palette, spacing, typography, radius, colors } = useTheme();
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
    <View
      style={{
        backgroundColor: palette.surface,
        borderColor: palette.border,
        borderWidth: 1,
        borderRadius: radius.lg,
        padding: spacing.md,
        marginBottom: spacing.md,
      }}
    >
      <Pressable onPress={() => navigation.navigate('ChildHub', { studentId })}>
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
          label="Statement"
          variant="secondary"
          onPress={() => navigation.navigate('StudentStatement', { studentId })}
        />
        <Button label="Pay link" variant="secondary" loading={loadingLink} onPress={() => void openPayLink()} />
        <Button
          label="M-Pesa"
          onPress={() =>
            navigation.navigate('MpesaPrompt', {
              studentId,
              amount: typeof balanceDue === 'number' && balanceDue > 0 ? balanceDue : undefined,
            })
          }
        />
        <Button
          label={showInvoices ? 'Hide invoices' : 'Invoices'}
          variant="ghost"
          loading={loadingInvoices}
          onPress={() => void loadInvoices()}
        />
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
    </View>
  );
}

export const ParentFeesScreen: React.FC = () => {
  const { palette, spacing, typography } = useTheme();
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
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <Pressable
        onPress={() => navigation.navigate('WalletHome')}
        style={{
          flexDirection: 'row',
          alignItems: 'center',
          gap: spacing.md,
          marginBottom: spacing.md,
          padding: spacing.md,
          borderRadius: 16,
          borderWidth: 1,
          borderColor: palette.border,
          backgroundColor: palette.surface,
        }}
      >
        <Soft3DIcon name="wallet-outline" glyph="wallet" tone="emerald" size={48} />
        <View style={{ flex: 1 }}>
          <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>Wallets</Text>
          <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
            Family balance, top up & saving plans
          </Text>
        </View>
      </Pressable>
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
