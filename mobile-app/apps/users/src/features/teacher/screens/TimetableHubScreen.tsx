import {
  useClassTimetable,
  useClassrooms,
  useCurrentUser,
  useMyTimetable,
  UserRole,
} from '@erp/core';
import {
  AcademicScreenHeader,
  ColoredTimetableGrid,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, Text, View } from 'react-native';

type Tab = 'lessons' | 'classes';

export const TimetableHubScreen: React.FC = () => {
  const navigation = useNavigation();
  const user = useCurrentUser();
  const { palette, spacing, typography, radius } = useTheme();
  const [tab, setTab] = useState<Tab>('lessons');
  const homeroomIds = user?.classTeacherClassroomIds ?? [];
  const isSenior =
    user?.role === UserRole.SENIOR_TEACHER || user?.role === UserRole.SUPERVISOR;
  const showClassTab = isSenior || homeroomIds.length > 0;

  const lessonsQuery = useMyTimetable();
  const classroomsQuery = useClassrooms();
  const classOptions = useMemo(() => {
    const all = classroomsQuery.data ?? [];
    if (isSenior) return all;
    return all.filter((c) => homeroomIds.includes(c.id));
  }, [classroomsQuery.data, homeroomIds, isSenior]);
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const activeClassId = classroomId ?? classOptions[0]?.id ?? 0;
  const classQuery = useClassTimetable(activeClassId, { enabled: tab === 'classes' && activeClassId > 0 });

  const activeQuery = tab === 'lessons' ? lessonsQuery : classQuery;
  const slots = activeQuery.data?.slots ?? [];

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}
        refreshControl={
          <RefreshControl
            refreshing={activeQuery.isRefetching}
            onRefresh={() => void activeQuery.refetch()}
            tintColor={palette.primary}
          />
        }
      >
        <AcademicScreenHeader
          title="Timetable"
          subtitle="Saved school timetable for this term"
          onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
        />

        {showClassTab ? (
          <View style={{ flexDirection: 'row', gap: spacing.sm, marginBottom: spacing.md }}>
            {(['lessons', 'classes'] as const).map((key) => {
              const active = tab === key;
              return (
                <Pressable
                  key={key}
                  onPress={() => setTab(key)}
                  style={{
                    flex: 1,
                    paddingVertical: spacing.sm,
                    borderRadius: radius.md,
                    borderWidth: 1,
                    borderColor: active ? palette.primary : palette.border,
                    backgroundColor: active ? palette.surfaceRaised : palette.surface,
                    alignItems: 'center',
                  }}
                >
                  <Text style={{ color: active ? palette.primary : palette.textSecondary, fontWeight: '700' }}>
                    {key === 'lessons' ? 'My lessons' : 'My classes'}
                  </Text>
                </Pressable>
              );
            })}
          </View>
        ) : null}

        {tab === 'classes' && classOptions.length > 1 ? (
          <FilterChipRow label="Class">
            {classOptions.map((c) => (
              <FilterChip
                key={c.id}
                label={c.name}
                active={activeClassId === c.id}
                onPress={() => setClassroomId(c.id)}
              />
            ))}
          </FilterChipRow>
        ) : null}

        {activeQuery.isLoading ? (
          <SkeletonListRows count={8} />
        ) : activeQuery.isError ? (
          <EmptyState
            title="Could not load timetable"
            message={(activeQuery.error as Error).message}
            icon="alert-circle-outline"
            actionLabel="Retry"
            onAction={() => void activeQuery.refetch()}
          />
        ) : (
          <ColoredTimetableGrid
            slots={slots}
            showClassroom={tab === 'lessons'}
            showTeacher={tab === 'classes'}
          />
        )}
      </ScrollView>
    </ScreenContainer>
  );
};
