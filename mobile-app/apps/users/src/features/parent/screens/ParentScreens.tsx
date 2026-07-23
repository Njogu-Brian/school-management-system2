import { useCurrentUser, useInfiniteStudentList, studentsApi, financeApi, useStudentStats, useUnreadNotificationCount } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  DashboardHero,
  DashboardSection,
  EmptyState,
  QuickAction,
  ScreenContainer,
  SkeletonListRows,
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
  icon: 'people-outline' | 'wallet-outline' | 'chatbubbles-outline' | 'megaphone-outline' | 'notifications-outline' | 'alert-circle-outline';
  route: keyof ParentStackParamList;
}> = [
  { label: 'Children', icon: 'people-outline', route: 'ChildrenList' },
  { label: 'Fees', icon: 'wallet-outline', route: 'FeesHome' },
  { label: 'Diary', icon: 'chatbubbles-outline', route: 'DiaryList' },
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

export const ParentHomeScreen: React.FC = () => {
  const user = useCurrentUser();
  const { spacing } = useTheme();
  const navigation = useNavigation<Nav>();
  const tabClearance = useFloatingTabBarClearance();
  const unreadQuery = useUnreadNotificationCount();
  const childrenQuery = useInfiniteStudentList({
    search: '',
    classroomId: null,
    streamId: null,
    status: 'active',
    perPage: 1,
  });

  const childrenCount = childrenQuery.data?.pages[0]?.total ?? 0;
  const meta = useMemo(() => {
    const parts: string[] = [];
    if (childrenCount > 0) parts.push(`${childrenCount} ${childrenCount === 1 ? 'child' : 'children'}`);
    const unread = unreadQuery.data ?? 0;
    if (unread > 0) parts.push(`${unread} unread`);
    return parts.join(' · ') || undefined;
  }, [childrenCount, unreadQuery.data]);

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}>
      <DashboardHero
        variant="people"
        greeting="Welcome back"
        userName={user?.name ?? 'Parent'}
        title="Parent portal"
        subtitle="Track fees, attendance, and school updates for your children"
        meta={meta}
      />

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
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader title="My children" />
      </View>
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

  const balance = stats.data?.fees_balance;

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

      <Text style={{ color: palette.textSecondary, marginTop: spacing.md, fontSize: typography.caption.fontSize }}>
        Outstanding balance
      </Text>
      <Text style={{ color: colors.primary, fontSize: 22, fontWeight: '700', marginTop: 2 }}>
        {stats.isLoading ? '…' : formatKes(balance)}
      </Text>

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
              amount: typeof balance === 'number' && balance > 0 ? balance : undefined,
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
            <View
              key={inv.id}
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
            </View>
          ))
        )
      ) : null}
    </View>
  );
}

export const ParentFeesScreen: React.FC = () => {
  const { spacing } = useTheme();
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
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader title="Fees" subtitle="Balances, statements, Pay link & M-Pesa" />
      {listQuery.isLoading ? (
        <SkeletonListRows count={3} />
      ) : students.length === 0 ? (
        <EmptyState title="No children" message="Link children to manage fees." icon="wallet-outline" />
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
