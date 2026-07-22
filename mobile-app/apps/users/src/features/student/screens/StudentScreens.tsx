import { apiClient, useAuth, useCurrentUser, useStudentReportCards } from '@erp/core';
import {
  AcademicScreenHeader,
  Button,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import { useQuery } from '@tanstack/react-query';
import React, { useMemo } from 'react';
import { FlatList, Pressable, Text, View } from 'react-native';

export const StudentHomeScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { palette, spacing, typography, radius } = useTheme();
  const navigation = useNavigation();
  const studentId = user?.studentId ?? 0;

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>Student</Text>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.headline.fontSize,
          fontWeight: '700',
          marginBottom: spacing.md,
        }}
      >
        {user?.name ?? 'Student'}
      </Text>

      {studentId > 0 ? (
        <View style={{ gap: spacing.sm }}>
          {(
            [
              { title: 'Homework', route: 'StudentHomeworkMain', icon: 'document-text-outline' as const },
              { title: 'Results', route: 'StudentResultsMain', icon: 'ribbon-outline' as const },
              { title: 'Announcements', route: 'Announcements', icon: 'megaphone-outline' as const },
            ] as const
          ).map((item) => (
            <Pressable
              key={item.route}
              onPress={() => (navigation as { navigate: (n: string) => void }).navigate(item.route)}
              style={{
                flexDirection: 'row',
                alignItems: 'center',
                gap: spacing.md,
                backgroundColor: palette.surface,
                borderWidth: 1,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
              }}
            >
              <Soft3DIcon name={item.icon} tone="indigo" size={40} />
              <Text style={{ color: palette.textPrimary, fontWeight: '600', flex: 1 }}>{item.title}</Text>
            </Pressable>
          ))}
        </View>
      ) : (
        <EmptyState
          title="Student profile not linked"
          message="Ask the school to link your login to a student record (student_id on /user). Tabs stay available."
          icon="school-outline"
        />
      )}
      <Button label="Sign out" variant="ghost" onPress={logout} style={{ marginTop: spacing.lg }} />
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
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
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
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
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
