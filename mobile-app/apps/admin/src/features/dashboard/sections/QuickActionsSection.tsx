import { useRbac } from '@erp/core';
import { DashboardSection, QuickAction } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import type { StackNavigationProp } from '@react-navigation/stack';
import React, { useCallback, useMemo, useState } from 'react';
import { StyleSheet, View } from 'react-native';
import { useTheme } from '@erp/ui';
import type { DashboardStackParamList } from '../../../navigation/dashboardStackTypes';
import { navigateToDrawer, navigateToTab } from '../../../navigation/navigateWorkspace';
import { AttendanceActionSheet } from '../../shared/AttendanceActionSheet';
import { QUICK_ACTION_PLACEHOLDERS } from '../data/placeholders';

export const QuickActionsSection: React.FC = () => {
  const { can } = useRbac();
  const { spacing } = useTheme();
  const navigation = useNavigation<StackNavigationProp<DashboardStackParamList>>();
  const [attendanceOpen, setAttendanceOpen] = useState(false);

  const actions = useMemo(
    () =>
      QUICK_ACTION_PLACEHOLDERS.filter((a) => can(a.permissions)),
    [can],
  );

  const onActionPress = useCallback(
    (actionId: string) => {
      switch (actionId) {
        case 'qa_students':
          navigateToTab(navigation, 'Students', 'StudentRegistry');
          break;
        case 'qa_admissions':
          navigateToDrawer(navigation, 'Admissions', 'AdmissionsWorkspace');
          break;
        case 'qa_attendance':
          setAttendanceOpen(true);
          break;
        case 'qa_clock':
          navigateToTab(navigation, 'People', 'StaffClock');
          break;
        case 'qa_staff_calendar':
          navigateToTab(navigation, 'People', 'StaffAttendanceCalendar');
          break;
        case 'qa_concerns':
          navigateToDrawer(navigation, 'Operations', 'ConcernCreate');
          break;
        default:
          break;
      }
    },
    [navigation],
  );

  if (actions.length === 0) {
    return null;
  }

  return (
    <DashboardSection title="Quick actions" subtitle="Jump to frequent tasks">
      <View style={[styles.row, { gap: spacing.sm }]}>
        {actions.map((action) => (
          <QuickAction
            key={action.id}
            label={action.label}
            icon={action.icon}
            onPress={() => onActionPress(action.id)}
          />
        ))}
      </View>
      <AttendanceActionSheet visible={attendanceOpen} onClose={() => setAttendanceOpen(false)} />
    </DashboardSection>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    flexWrap: 'wrap',
  },
});
