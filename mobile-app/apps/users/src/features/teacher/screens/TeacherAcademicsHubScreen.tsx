import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import { AcademicScreenHeader, ScreenContainer, Soft3DIcon, useTheme } from '@erp/ui';
import React from 'react';
import { Pressable, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

const ITEMS: Array<{
  title: string;
  subtitle: string;
  route: keyof TeacherStackParamList;
  icon:
    | 'create-outline'
    | 'document-text-outline'
    | 'grid-outline'
    | 'book-outline'
    | 'chatbubbles-outline'
    | 'clipboard-outline';
}> = [
  { title: 'Homework', subtitle: 'Assign and track class homework', route: 'AssignmentsHub', icon: 'book-outline' },
  { title: 'Marks entry', subtitle: 'Enter exam marks for your subjects', route: 'MarksHub', icon: 'create-outline' },
  { title: 'Lesson plans', subtitle: 'Create and submit lesson plans', route: 'LessonPlansHub', icon: 'document-text-outline' },
  { title: 'Student diary', subtitle: 'Message parents about students', route: 'DiaryList', icon: 'chatbubbles-outline' },
  { title: 'Timetable', subtitle: 'View your teaching timetable', route: 'TimetableHub', icon: 'grid-outline' },
  { title: 'Requirements', subtitle: 'Collect class requirements', route: 'RequirementsHub', icon: 'clipboard-outline' },
];

export const TeacherAcademicsHubScreen: React.FC = () => {
  const { palette, spacing, typography, radius } = useTheme();
  const navigation = useNavigation<Nav>();

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md }}>
      <AcademicScreenHeader title="Academics" subtitle="Subjects you teach — marks, plans, and class work" />
      {ITEMS.map((item) => (
        <Pressable
          key={item.route}
          onPress={() => navigation.navigate(item.route as never)}
          style={{
            flexDirection: 'row',
            alignItems: 'center',
            gap: spacing.md,
            backgroundColor: palette.surface,
            borderColor: palette.border,
            borderWidth: 1,
            borderRadius: radius.lg,
            padding: spacing.md,
            marginBottom: spacing.sm,
          }}
        >
          <Soft3DIcon name={item.icon} tone="indigo" size={44} />
          <View style={{ flex: 1 }}>
            <Text style={{ color: palette.textPrimary, fontWeight: '700' }}>{item.title}</Text>
            <Text style={{ color: palette.textSecondary, fontSize: typography.caption.fontSize }}>
              {item.subtitle}
            </Text>
          </View>
        </Pressable>
      ))}
    </ScreenContainer>
  );
};
