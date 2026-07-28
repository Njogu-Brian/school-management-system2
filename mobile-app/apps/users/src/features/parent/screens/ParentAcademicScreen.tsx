import { useInfiniteStudentList } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  SkeletonListRows,
  Soft3DIcon,
  useFloatingTabBarClearance,
  useTheme,
} from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useMemo } from 'react';
import { FlatList, Pressable, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Nav = StackNavigationProp<ParentStackParamList>;

const ACTIONS: Array<{
  label: string;
  subtitle: string;
  icon: 'school-outline' | 'calendar-outline' | 'book-outline';
  tone: 'indigo' | 'emerald' | 'amber';
  route: 'ChildResults' | 'ChildAttendance' | 'ChildHomework';
}> = [
  { label: 'Results', subtitle: 'Report forms & grades', icon: 'school-outline', tone: 'indigo', route: 'ChildResults' },
  { label: 'Attendance', subtitle: 'Present / absent days', icon: 'calendar-outline', tone: 'emerald', route: 'ChildAttendance' },
  { label: 'Homework', subtitle: 'Assignments & tasks', icon: 'book-outline', tone: 'amber', route: 'ChildHomework' },
];

export const ParentAcademicScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const { palette, spacing, typography, radius } = useTheme();
  const tabClearance = useFloatingTabBarClearance();
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
      <View style={{ paddingHorizontal: spacing.md, paddingTop: spacing.md }}>
        <AcademicScreenHeader title="Academic" subtitle="Results, attendance & homework" />
      </View>
      {listQuery.isLoading ? (
        <SkeletonListRows count={3} />
      ) : students.length === 0 ? (
        <EmptyState
          title="No children linked"
          message="Link children to view academic reports and homework."
          icon="school-outline"
        />
      ) : (
        <FlatList
          data={students}
          keyExtractor={(item) => String(item.id)}
          contentContainerStyle={{ padding: spacing.md, paddingBottom: tabClearance }}
          renderItem={({ item }) => (
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
              <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.fullName}</Text>
              <Text
                style={{
                  color: palette.textSecondary,
                  fontSize: typography.caption.fontSize,
                  marginTop: 2,
                  marginBottom: spacing.sm,
                }}
              >
                {[item.admissionNumber, item.className].filter(Boolean).join(' · ')}
              </Text>
              <View style={{ gap: spacing.sm }}>
                {ACTIONS.map((action) => (
                  <Pressable
                    key={action.route}
                    onPress={() => navigation.navigate(action.route, { studentId: item.id })}
                    style={{
                      flexDirection: 'row',
                      alignItems: 'center',
                      gap: spacing.md,
                      paddingVertical: spacing.xs,
                    }}
                  >
                    <Soft3DIcon name={action.icon} tone={action.tone} size={40} />
                    <View style={{ flex: 1 }}>
                      <Text style={{ color: palette.textPrimary, fontWeight: '600' }}>{action.label}</Text>
                      <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
                        {action.subtitle}
                      </Text>
                    </View>
                  </Pressable>
                ))}
              </View>
            </View>
          )}
        />
      )}
    </ScreenContainer>
  );
};
