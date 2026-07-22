import { useAuth, useCurrentUser } from '@erp/core';
import { Button, ScreenContainer, Soft3DIcon, useTheme } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { TeacherStackParamList } from '../../../navigation/teacher/teacherStackTypes';

type Nav = StackNavigationProp<TeacherStackParamList>;

const QUICK: Array<{
  label: string;
  icon:
    | 'checkbox-outline'
    | 'create-outline'
    | 'chatbubbles-outline'
    | 'time-outline'
    | 'calendar-outline'
    | 'megaphone-outline'
    | 'notifications-outline'
    | 'bus-outline';
  route: keyof TeacherStackParamList;
  tone: 'blue' | 'indigo' | 'emerald' | 'cyan' | 'amber' | 'rose';
}> = [
  { label: 'Mark attendance', icon: 'checkbox-outline', route: 'MarkAttendance', tone: 'emerald' },
  { label: 'Enter marks', icon: 'create-outline', route: 'MarksHub', tone: 'indigo' },
  { label: 'Student diary', icon: 'chatbubbles-outline', route: 'DiaryList', tone: 'blue' },
  { label: 'Clock in/out', icon: 'time-outline', route: 'StaffClock', tone: 'cyan' },
  { label: 'My leave', icon: 'calendar-outline', route: 'MyLeaveList', tone: 'amber' },
  { label: 'Transport', icon: 'bus-outline', route: 'TeacherTransportHub', tone: 'cyan' },
  { label: 'Announcements', icon: 'megaphone-outline', route: 'Announcements', tone: 'amber' },
  { label: 'Notifications', icon: 'notifications-outline', route: 'Notifications', tone: 'rose' },
];

export const TeacherHomeScreen: React.FC = () => {
  const user = useCurrentUser();
  const { logout } = useAuth();
  const { palette, spacing, typography, colors, radius } = useTheme();
  const navigation = useNavigation<Nav>();

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <Text style={[styles.greeting, { color: palette.textSecondary, fontSize: typography.caption.fontSize }]}>
        Welcome back
      </Text>
      <Text
        style={{
          color: palette.textPrimary,
          fontSize: typography.headline.fontSize,
          fontWeight: typography.headline.fontWeight,
          marginBottom: spacing.xs,
        }}
      >
        {user?.name ?? 'Teacher'}
      </Text>
      <Text style={{ color: palette.textMuted, marginBottom: spacing.lg }}>
        {user?.roleName ?? 'Teacher'} · Today’s capture & self-service
      </Text>

      <View style={styles.grid}>
        {QUICK.map((item) => (
          <Pressable
            key={item.route}
            onPress={() => navigation.navigate(item.route as never)}
            style={[
              styles.tile,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.lg,
                padding: spacing.md,
              },
            ]}
          >
            <Soft3DIcon name={item.icon} tone={item.tone} size={44} />
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '600',
                marginTop: spacing.sm,
                fontSize: typography.caption.fontSize,
              }}
            >
              {item.label}
            </Text>
          </Pressable>
        ))}
      </View>

      <Button
        label="Sign out"
        variant="ghost"
        onPress={logout}
        style={{ marginTop: spacing.xl, alignSelf: 'stretch' }}
      />
      <Text style={{ color: colors.primary, textAlign: 'center', marginTop: spacing.sm, opacity: 0.6 }}>
        Users App
      </Text>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  greeting: { textTransform: 'uppercase', letterSpacing: 0.6, marginBottom: 4 },
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  tile: { width: '47%', borderWidth: StyleSheet.hairlineWidth, minHeight: 110 },
});
