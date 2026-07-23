import { apiClient, useCurrentUser, useStudentReportCards, useUnreadNotificationCount } from '@erp/core';
import {
  AcademicScreenHeader,
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
import { useQuery } from '@tanstack/react-query';
import React, { useMemo } from 'react';
import { FlatList, Text, View } from 'react-native';

type CrossTabNav = {
  navigate: (name: string, params?: object) => void;
  getParent: () => CrossTabNav | undefined;
};

/** Jumps to a sibling tab's own stack (Home lives in its own nested stack). */
function navigateToTab(navigation: CrossTabNav, tab: string, screen?: string): void {
  const parent = navigation.getParent?.() ?? navigation;
  parent.navigate(tab, screen ? { screen } : undefined);
}

const ACADEMICS_ACTIONS = [
  { label: 'Homework', icon: 'document-text-outline' as const, tab: 'StudentHomeworkTab' },
  { label: 'Results', icon: 'ribbon-outline' as const, tab: 'StudentResultsTab' },
];

export const StudentHomeScreen: React.FC = () => {
  const user = useCurrentUser();
  const { spacing } = useTheme();
  const navigation = useNavigation();
  const tabClearance = useFloatingTabBarClearance();
  const studentId = user?.studentId ?? 0;
  const unreadQuery = useUnreadNotificationCount();

  const meta = (unreadQuery.data ?? 0) > 0 ? `${unreadQuery.data} unread notifications` : undefined;

  return (
    <ScreenContainer scroll edges={['bottom']} contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}>
      <DashboardHero
        variant="academics"
        greeting="Welcome back"
        userName={user?.name ?? 'Student'}
        title="Student portal"
        subtitle="Homework, results, and school updates"
        meta={meta}
      />

      {studentId > 0 ? (
        <DashboardSection title="Academics">
          <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
            {ACADEMICS_ACTIONS.map((item) => (
              <QuickAction
                key={item.tab}
                label={item.label}
                icon={item.icon}
                onPress={() => navigateToTab(navigation as unknown as CrossTabNav, item.tab)}
              />
            ))}
          </View>
        </DashboardSection>
      ) : (
        <EmptyState
          title="Student profile not linked"
          message="Ask the school to link your login to a student record (student_id on /user). Tabs stay available."
          icon="school-outline"
        />
      )}

      <DashboardSection title="School">
        <View style={{ flexDirection: 'row', flexWrap: 'wrap', gap: spacing.sm }}>
          <QuickAction
            label="Announcements"
            icon="megaphone-outline"
            onPress={() => navigation.navigate('Announcements' as never)}
          />
          <QuickAction
            label="Notifications"
            icon="notifications-outline"
            onPress={() => navigation.navigate('Notifications' as never)}
          />
          <QuickAction
            label="Raise concern"
            icon="alert-circle-outline"
            onPress={() => navigation.navigate('RaiseConcern' as never)}
          />
        </View>
      </DashboardSection>
    </ScreenContainer>
  );
};

export const StudentHomeworkScreen: React.FC = () => {
  const user = useCurrentUser();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = user?.studentId ?? 0;

  const homeworkQuery = useQuery({
    queryKey: ['student-homework', studentId],
    queryFn: async () => {
      const res = await apiClient.get<{ data?: Array<Record<string, unknown>> } | Array<Record<string, unknown>>>(
        '/assignments',
        { per_page: 50, student_id: studentId },
      );
      if (!res.success) throw new Error(res.message || 'Failed to load homework.');
      const raw = res.data as { data?: Array<Record<string, unknown>> } | Array<Record<string, unknown>>;
      return Array.isArray(raw) ? raw : raw?.data ?? [];
    },
    enabled: studentId > 0,
    staleTime: 60_000,
  });

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['bottom']}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader title="Homework" />
      </View>
      {studentId <= 0 ? (
        <EmptyState
          title="Not linked"
          message="Homework needs a linked student profile."
          icon="document-text-outline"
        />
      ) : homeworkQuery.isLoading ? (
        <SkeletonListRows count={6} />
      ) : (homeworkQuery.data ?? []).length === 0 ? (
        <EmptyState title="No homework" message="Assigned work will appear here." icon="document-text-outline" />
      ) : (
        <FlatList
          data={homeworkQuery.data ?? []}
          keyExtractor={(item, i) => String(item.id ?? i)}
          contentContainerStyle={{ padding: spacing.md }}
          renderItem={({ item }) => (
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
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                {String(item.title ?? item.name ?? 'Assignment')}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                {[item.subject_name, item.due_date, item.status].filter(Boolean).map(String).join(' · ')}
              </Text>
            </View>
          )}
        />
      )}
    </ScreenContainer>
  );
};

export const StudentResultsScreen: React.FC = () => {
  const user = useCurrentUser();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = user?.studentId ?? 0;
  const cards = useStudentReportCards(studentId, { enabled: studentId > 0 });

  const items = useMemo(() => cards.data ?? [], [cards.data]);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }} edges={['bottom']}>
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader title="Results" />
      </View>
      {studentId <= 0 ? (
        <EmptyState title="Not linked" message="Results need a linked student profile." icon="ribbon-outline" />
      ) : cards.isLoading ? (
        <SkeletonListRows count={5} />
      ) : items.length === 0 ? (
        <EmptyState title="No results yet" message="Published report cards will appear here." icon="ribbon-outline" />
      ) : (
        <FlatList
          data={items}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md }}
          renderItem={({ item }) => (
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
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>
                {(item as { term_name?: string; title?: string }).term_name ??
                  (item as { title?: string }).title ??
                  `Report #${item.id}`}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize, marginTop: 4 }}>
                Status: {String((item as { status?: string }).status ?? '—')}
              </Text>
            </View>
          )}
        />
      )}
    </ScreenContainer>
  );
};
