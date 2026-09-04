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
import { navigateToTab } from '../../../navigation/navigateToTab';
import type { ParentStackParamList } from '../../../navigation/parent/parentStackTypes';

type Nav = StackNavigationProp<ParentStackParamList>;
type Route = RouteProp<ParentStackParamList, 'ChildHub'>;

type HubTile = {
  label: string;
  icon: keyof typeof import('@expo/vector-icons').Ionicons.glyphMap;
  tone: 'indigo' | 'emerald' | 'amber' | 'blue' | 'cyan' | 'rose' | 'violet' | 'teal';
  route:
    | 'ChildResults'
    | 'ChildAttendance'
    | 'ChildHomework'
    | 'StudentStatement'
    | 'Transport'
    | 'DiaryChat'
    | 'RaiseConcern'
    | 'ChildProfile'
    | 'CoCurricularChild'
    | 'ChildRequirements';
  /** When set, switch bottom tab so the bar highlight matches the destination area. */
  tabJump?: { tab: string; screen: string; tabHome?: string };
};

const TILES: HubTile[] = [
  {
    label: 'Profile',
    icon: 'person-outline',
    tone: 'teal',
    route: 'ChildProfile',
  },
  {
    label: 'Results',
    icon: 'school-outline',
    tone: 'indigo',
    route: 'ChildResults',
    tabJump: { tab: 'ParentAcademicTab', screen: 'ChildResults', tabHome: 'AcademicHome' },
  },
  {
    label: 'Attendance',
    icon: 'calendar-outline',
    tone: 'emerald',
    route: 'ChildAttendance',
    tabJump: { tab: 'ParentAcademicTab', screen: 'ChildAttendance', tabHome: 'AcademicHome' },
  },
  {
    label: 'Homework',
    icon: 'book-outline',
    tone: 'amber',
    route: 'ChildHomework',
    tabJump: { tab: 'ParentAcademicTab', screen: 'ChildHomework', tabHome: 'AcademicHome' },
  },
  {
    label: 'Fees',
    icon: 'cash-outline',
    tone: 'blue',
    route: 'StudentStatement',
    tabJump: { tab: 'ParentFeesTab', screen: 'StudentStatement', tabHome: 'FeesHome' },
  },
  {
    label: 'Transport',
    icon: 'bus-outline',
    tone: 'cyan',
    route: 'Transport',
  },
  {
    label: 'Diary',
    icon: 'chatbubbles-outline',
    tone: 'violet',
    route: 'DiaryChat',
    tabJump: { tab: 'ParentMoreTab', screen: 'DiaryChat', tabHome: 'MoreMenu' },
  },
  {
    label: 'Co-curricular',
    icon: 'sparkles-outline',
    tone: 'amber',
    route: 'CoCurricularChild',
  },
  {
    label: 'Requirements',
    icon: 'clipboard-outline',
    tone: 'indigo',
    route: 'ChildRequirements',
  },
  {
    label: 'Raise concern',
    icon: 'alert-circle-outline',
    tone: 'rose',
    route: 'RaiseConcern',
    tabJump: { tab: 'ParentMoreTab', screen: 'RaiseConcern', tabHome: 'MoreMenu' },
  },
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
            key={tile.route}
            onPress={() => {
              const params = { studentId };
              if (tile.tabJump) {
                navigateToTab(
                  navigation,
                  tile.tabJump.tab,
                  tile.tabJump.screen,
                  params,
                  tile.tabJump.tabHome,
                );
                return;
              }
              navigation.navigate(tile.route, params);
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
