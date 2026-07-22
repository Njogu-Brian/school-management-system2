import { useStudentDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  Soft3DIcon,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Nav = StackNavigationProp<ParentStackParamList>;
type Route = RouteProp<ParentStackParamList, 'ChildHub'>;

const TILES: Array<{
  label: string;
  icon: keyof typeof import('@expo/vector-icons').Ionicons.glyphMap;
  tone: 'indigo' | 'emerald' | 'amber' | 'blue' | 'cyan' | 'rose' | 'violet';
  route:
    | 'ChildResults'
    | 'ChildAttendance'
    | 'ChildHomework'
    | 'StudentStatement'
    | 'Transport'
    | 'LiveBusTrack'
    | 'DiaryChat'
    | 'RaiseConcern';
}> = [
  { label: 'Results', icon: 'school-outline', tone: 'indigo', route: 'ChildResults' },
  { label: 'Attendance', icon: 'calendar-outline', tone: 'emerald', route: 'ChildAttendance' },
  { label: 'Homework', icon: 'book-outline', tone: 'amber', route: 'ChildHomework' },
  { label: 'Fees', icon: 'wallet-outline', tone: 'blue', route: 'StudentStatement' },
  { label: 'Transport', icon: 'bus-outline', tone: 'cyan', route: 'Transport' },
  { label: 'Track bus', icon: 'navigate-outline', tone: 'blue', route: 'LiveBusTrack' },
  { label: 'Diary', icon: 'chatbubbles-outline', tone: 'violet', route: 'DiaryChat' },
  { label: 'Raise concern', icon: 'alert-circle-outline', tone: 'rose', route: 'RaiseConcern' },
];

export const ChildHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const { palette, spacing, typography, radius } = useTheme();
  const studentId = route.params.studentId;
  const detail = useStudentDetail(studentId, { enabled: studentId > 0 });

  if (studentId <= 0) {
    return (
      <ScreenContainer contentContainerStyle={{ padding: spacing.md }}>
        <AcademicScreenHeader title="Child" onBack={() => navigation.goBack()} />
        <EmptyState title="Missing student" message="No child was selected." icon="alert-circle-outline" />
      </ScreenContainer>
    );
  }

  return (
    <ScreenContainer scroll contentContainerStyle={{ padding: spacing.md, paddingBottom: spacing.xl }}>
      <AcademicScreenHeader
        title={detail.data?.fullName ?? (detail.isLoading ? 'Loading…' : `Student #${studentId}`)}
        subtitle={[detail.data?.admissionNumber, detail.data?.className, detail.data?.streamName]
          .filter(Boolean)
          .join(' · ')}
        onBack={() => navigation.goBack()}
      />

      <View style={styles.grid}>
        {TILES.map((tile) => (
          <Pressable
            key={tile.route}
            onPress={() => {
              if (tile.route === 'RaiseConcern') {
                navigation.navigate('RaiseConcern', { studentId });
                return;
              }
              navigation.navigate(tile.route, { studentId });
            }}
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
            <Soft3DIcon name={tile.icon} tone={tile.tone} size={44} />
            <Text
              style={{
                color: palette.textPrimary,
                fontWeight: '600',
                marginTop: spacing.sm,
                fontSize: typography.caption.fontSize,
              }}
            >
              {tile.label}
            </Text>
          </Pressable>
        ))}
      </View>
    </ScreenContainer>
  );
};

const styles = StyleSheet.create({
  grid: { flexDirection: 'row', flexWrap: 'wrap', gap: 12 },
  tile: { width: '47%', borderWidth: StyleSheet.hairlineWidth, minHeight: 110 },
});
