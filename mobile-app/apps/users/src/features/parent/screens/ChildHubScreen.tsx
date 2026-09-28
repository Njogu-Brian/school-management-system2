import { useStudentDetail } from '@erp/core';
import {
  AcademicScreenHeader,
  EmptyState,
  ScreenContainer,
  Soft3DIcon,
  type Student360TabId,
  useTheme,
} from '@erp/ui';
import { useNavigation, useRoute, type RouteProp } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React from 'react';
import { Pressable, StyleSheet, Text, View } from 'react-native';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Nav = StackNavigationProp<ParentStackParamList>;
type Route = RouteProp<ParentStackParamList, 'ChildHub'>;

type HubTile = {
  label: string;
  icon: keyof typeof import('@expo/vector-icons').Ionicons.glyphMap;
  tone: 'indigo' | 'emerald' | 'amber' | 'blue' | 'cyan' | 'rose' | 'violet' | 'teal';
  /** Open student 360 on a specific tab (preserves hub under the stack for back). */
  profileTab?: Student360TabId;
  route?:
    | 'ChildHomework'
    | 'DiaryChat'
    | 'RaiseConcern'
    | 'ChildProfile'
    | 'CoCurricularChild'
    | 'ChildResults';
};

/**
 * Profile sections open StudentDetail tabs so Back returns to this hub
 * (scroll position preserved). Extra actions stay as dedicated screens.
 */
const TILES: HubTile[] = [
  { label: 'Overview', icon: 'person-outline', tone: 'teal', profileTab: 'overview' },
  { label: 'Attendance', icon: 'calendar-outline', tone: 'emerald', profileTab: 'attendance' },
  { label: 'Fees', icon: 'cash-outline', tone: 'blue', profileTab: 'fees' },
  { label: 'Academic', icon: 'school-outline', tone: 'indigo', profileTab: 'academics' },
  { label: 'Family', icon: 'people-outline', tone: 'violet', profileTab: 'family' },
  { label: 'Transport', icon: 'bus-outline', tone: 'cyan', profileTab: 'transport' },
  { label: 'Requirements', icon: 'clipboard-outline', tone: 'indigo', profileTab: 'requirements' },
  { label: 'Documents', icon: 'document-text-outline', tone: 'amber', profileTab: 'documents' },
  { label: 'Health', icon: 'medkit-outline', tone: 'rose', profileTab: 'health' },
  { label: 'Results', icon: 'ribbon-outline', tone: 'indigo', route: 'ChildResults' },
  { label: 'Homework', icon: 'book-outline', tone: 'amber', route: 'ChildHomework' },
  { label: 'Diary', icon: 'chatbubbles-outline', tone: 'violet', route: 'DiaryChat' },
  { label: 'Co-curricular', icon: 'sparkles-outline', tone: 'amber', route: 'CoCurricularChild' },
  { label: 'Edit profile', icon: 'create-outline', tone: 'teal', route: 'ChildProfile' },
  { label: 'Raise concern', icon: 'alert-circle-outline', tone: 'rose', route: 'RaiseConcern' },
];

export const ChildHubScreen: React.FC = () => {
  const navigation = useNavigation<Nav>();
  const route = useRoute<Route>();
  const { palette, spacing, typography, radius, elevation } = useTheme();
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
            key={tile.label}
            onPress={() => {
              if (tile.profileTab) {
                navigation.navigate('StudentDetail', { studentId, tab: tile.profileTab });
                return;
              }
              if (tile.route) {
                navigation.navigate(tile.route, { studentId });
              }
            }}
            style={[
              styles.tile,
              elevation[2],
              {
                backgroundColor: palette.surfaceRaised,
                borderColor: palette.borderSubtle,
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
