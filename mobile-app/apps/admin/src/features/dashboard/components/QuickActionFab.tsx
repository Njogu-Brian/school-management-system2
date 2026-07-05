import { useCan } from '@erp/core';
import { useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { useNavigation } from '@react-navigation/native';
import React, { useState } from 'react';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { navigateToDrawer, navigateToTab } from '../../../navigation/navigateWorkspace';

type Action = { id: string; label: string; icon: keyof typeof Ionicons.glyphMap; onPress: () => void; visible: boolean };

export const QuickActionFab: React.FC = () => {
  const { colors, palette, spacing, fontSizes } = useTheme();
  const navigation = useNavigation();
  const [open, setOpen] = useState(false);

  const canAdmissions = useCan('admissions.view');
  const canFinance = useCan('finance.view');
  const canComm = useCan('communication.view');
  const canOps = useCan('operations.view');
  const canApprovals = useCan(['approvals.view', 'dashboard.approvals.view']);
  const canPeople = useCan(['people.view', 'staff.view']);

  const allActions: Action[] = [
    {
      id: 'admit',
      label: 'Admissions workspace',
      icon: 'school-outline',
      visible: canAdmissions,
      onPress: () => navigateToDrawer(navigation, 'Admissions', 'AdmissionsWorkspace'),
    },
    {
      id: 'payment',
      label: 'Record payment',
      icon: 'cash-outline',
      visible: canFinance,
      onPress: () => navigateToTab(navigation, 'Finance', 'CollectionsList'),
    },
    {
      id: 'sms',
      label: 'Send SMS',
      icon: 'chatbubble-outline',
      visible: canComm,
      onPress: () => navigateToDrawer(navigation, 'Communication', 'SmsCompose'),
    },
    {
      id: 'announcement',
      label: 'Create announcement',
      icon: 'megaphone-outline',
      visible: canComm,
      onPress: () => navigateToDrawer(navigation, 'Communication', 'AnnouncementForm'),
    },
    {
      id: 'visitor',
      label: 'Visitor check-in',
      icon: 'person-add-outline',
      visible: canOps,
      onPress: () => navigateToDrawer(navigation, 'Operations', 'VisitorCheckIn'),
    },
    {
      id: 'requisition',
      label: 'Requisitions',
      icon: 'clipboard-outline',
      visible: canOps,
      onPress: () => navigateToDrawer(navigation, 'Operations', 'RequisitionsList'),
    },
    {
      id: 'staff_clock',
      label: 'Staff sign in/out',
      icon: 'time-outline',
      visible: canPeople,
      onPress: () => navigateToTab(navigation, 'People', 'StaffClock'),
    },
    {
      id: 'staff',
      label: 'Staff registry',
      icon: 'briefcase-outline',
      visible: canPeople,
      onPress: () => navigateToTab(navigation, 'People', 'StaffRegistry'),
    },
    {
      id: 'approvals',
      label: 'View approvals',
      icon: 'checkmark-done-outline',
      visible: canApprovals,
      onPress: () => navigateToDrawer(navigation, 'Approvals', 'ApprovalsHome'),
    },
  ];
  const actions = allActions.filter((a) => a.visible);

  if (actions.length === 0) return null;

  return (
    <>
      <Pressable
        onPress={() => setOpen(true)}
        style={[styles.fab, { backgroundColor: colors.primary }]}
        accessibilityRole="button"
        accessibilityLabel="Quick actions"
      >
        <Ionicons name="add" size={28} color="#fff" />
      </Pressable>
      <Modal visible={open} transparent animationType="fade" onRequestClose={() => setOpen(false)}>
        <Pressable style={styles.backdrop} onPress={() => setOpen(false)}>
          <View style={[styles.sheet, { backgroundColor: palette.surface }]}>
            <Text style={{ fontWeight: '700', fontSize: fontSizes.md, marginBottom: spacing.sm, color: palette.textPrimary }}>
              Quick actions
            </Text>
            {actions.map((action) => (
              <Pressable
                key={action.id}
                onPress={() => {
                  setOpen(false);
                  action.onPress();
                }}
                style={[styles.action, { borderColor: palette.border }]}
              >
                <Ionicons name={action.icon} size={20} color={colors.primary} />
                <Text style={{ marginLeft: 10, color: palette.textPrimary }}>{action.label}</Text>
              </Pressable>
            ))}
          </View>
        </Pressable>
      </Modal>
    </>
  );
};

const styles = StyleSheet.create({
  fab: {
    position: 'absolute',
    right: 20,
    bottom: 24,
    width: 56,
    height: 56,
    borderRadius: 28,
    alignItems: 'center',
    justifyContent: 'center',
    elevation: 4,
    zIndex: 100,
  },
  backdrop: { flex: 1, backgroundColor: 'rgba(0,0,0,0.4)', justifyContent: 'flex-end' },
  sheet: { padding: 20, borderTopLeftRadius: 16, borderTopRightRadius: 16, maxHeight: '70%' },
  action: { flexDirection: 'row', alignItems: 'center', paddingVertical: 12, borderBottomWidth: StyleSheet.hairlineWidth },
});
