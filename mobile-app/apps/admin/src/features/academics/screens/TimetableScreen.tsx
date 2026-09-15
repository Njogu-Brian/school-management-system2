import {
  useClassTimetable,
  useClassrooms,
  useCurrentUser,
  useMyTimetable,
} from '@erp/core';
import {
  AcademicScreenHeader,
  ColoredTimetableGrid,
  EmptyState,
  FilterChip,
  FilterChipRow,
  ScreenContainer,
  SkeletonListRows,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Pressable, RefreshControl, ScrollView, Text, View } from 'react-native';

type Tab = 'lessons' | 'classes';

export const TimetableScreen: React.FC = () => {
  const navigation = useNavigation();
  const user = useCurrentUser();
  const staffId = user?.staffId ?? user?.teacherId ?? 0;
  const [tab, setTab] = useState<Tab>(staffId > 0 ? 'lessons' : 'classes');

  const lessonsQuery = useMyTimetable({ enabled: staffId > 0 && tab === 'lessons' });
  const classroomsQuery = useClassrooms();
  const classrooms = classroomsQuery.data ?? [];
  const [classroomId, setClassroomId] = useState<number | null>(null);
  const activeClassId = classroomId ?? classrooms[0]?.id ?? 0;
  const classQuery = useClassTimetable(activeClassId, { enabled: tab === 'classes' && activeClassId > 0 });

  const activeQuery = tab === 'lessons' ? lessonsQuery : classQuery;
  const slots = activeQuery.data?.slots ?? [];

  const tabs = useMemo(() => {
    const items: Tab[] = [];
    if (staffId > 0) items.push('lessons');
    items.push('classes');
    return items;
  }, [staffId]);

  return (
    <ScreenContainer scroll={false} style={{ flex: 1 }}>
      <ScrollView
        contentContainerStyle={{ padding: 16, paddingBottom: 40 }}
        refreshControl={
          <RefreshControl
            refreshing={activeQuery.isRefetching}
            onRefresh={() => void activeQuery.refetch()}
          />
        }
      >
        <AcademicScreenHeader
          title="Timetable"
          subtitle="Saved school timetable for this term"
          onBack={navigation.canGoBack() ? () => navigation.goBack() : undefined}
        />

        {tabs.length > 1 ? (
          <View style={{ flexDirection: 'row', gap: 8, marginBottom: 12 }}>
            {tabs.map((key) => {
              const active = tab === key;
              return (
                <Pressable
                  key={key}
                  onPress={() => setTab(key)}
                  style={{
                    flex: 1,
                    paddingVertical: 10,
                    borderRadius: 10,
                    borderWidth: 1,
                    borderColor: active ? '#004A99' : '#d8e0ea',
                    alignItems: 'center',
                    backgroundColor: active ? '#e8f1fb' : '#fff',
                  }}
                >
                  <Text style={{ fontWeight: '700', color: active ? '#004A99' : '#5b6b7c' }}>
                    {key === 'lessons' ? 'My lessons' : 'Class grid'}
                  </Text>
                </Pressable>
              );
            })}
          </View>
        ) : null}

        {tab === 'classes' && classrooms.length > 0 ? (
          <FilterChipRow label="Class">
            {classrooms.map((c) => (
              <FilterChip
                key={c.id}
                label={c.name}
                active={activeClassId === c.id}
                onPress={() => setClassroomId(c.id)}
              />
            ))}
          </FilterChipRow>
        ) : null}

        {tab === 'classes' && classrooms.length === 0 && !classroomsQuery.isLoading ? (
          <EmptyState title="No classes" message="No classrooms are available." icon="school-outline" />
        ) : activeQuery.isLoading ? (
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
