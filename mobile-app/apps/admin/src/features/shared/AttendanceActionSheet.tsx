import { ActionPickerSheet } from '@erp/ui';
import { useNavigation } from '@react-navigation/native';
import React from 'react';
import { navigateToDrawer, navigateToTab } from '../../navigation/navigateWorkspace';

/**
 * One Attendance entry point → report / mark present / mark absent.
 * Shared by Students, Academics, Dashboard, and the FAB.
 */
export const AttendanceActionSheet: React.FC<{
  visible: boolean;
  onClose: () => void;
}> = ({ visible, onClose }) => {
  const navigation = useNavigation();

  return (
    <ActionPickerSheet
      visible={visible}
      title="Attendance"
      onClose={onClose}
      actions={[
        {
          key: 'report',
          label: 'Attendance report',
          icon: 'attendance-report',
          onPress: () => navigateToTab(navigation, 'Students', 'AttendanceReport'),
        },
        {
          key: 'mark',
          label: 'Mark attendance',
          icon: 'mark-attendance',
          onPress: () => navigateToDrawer(navigation, 'Academics', 'MarkAttendance'),
        },
        {
          key: 'absent',
          label: 'Mark as absent',
          icon: 'mark-absent',
          onPress: () => navigateToDrawer(navigation, 'Academics', 'MarkAbsent'),
        },
      ]}
    />
  );
};
