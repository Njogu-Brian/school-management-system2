import { useCan } from '@erp/core';
import { AccentIcon, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { navigateToDrawer, navigateToTab } from '../../../navigation/navigateWorkspace';

type Action = {
  id: string;
  label: string;
  icon: keyof typeof Ionicons.glyphMap;
  onPress: () => void;
  visible: boolean;
};

const ACTION_TONES = ['emerald', 'indigo', 'rose', 'amber', 'cyan', 'violet', 'teal', 'blue'] as const;

export const QuickActionFab: React.FC = () => {
  const { palette, spacing, typography, radius, elevation, opacity, zIndex } = useTheme();
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

  const fabStyle = useMemo(
    () => [
      styles.fab,
      elevation[5],
      {
        right: spacing.mdLg,
        /** Clear floating premium tab bar */
        bottom: spacing['5xl'] + spacing.xl,
        borderRadius: radius.full,
        zIndex: zIndex.fab,
        overflow: 'hidden' as const,
      },
    ],
    [elevation, radius.full, spacing, zIndex.fab],
  );

  if (actions.length === 0) return null;

  return (
    <>
      <Pressable
        onPress={() => setOpen(true)}
        style={fabStyle}
        accessibilityRole="button"
        accessibilityLabel="Quick actions"
      >
        <LinearGradient colors={[palette.primary, '#1a6bc4']} style={styles.fabFill}>
          <Ionicons name="add" size={28} color="#fff" />
        </LinearGradient>
      </Pressable>
      <Modal visible={open} transparent animationType="fade" onRequestClose={() => setOpen(false)}>
        <Pressable
          style={[styles.backdrop, { backgroundColor: `rgba(0,0,0,${opacity.scrim})` }]}
          onPress={() => setOpen(false)}
        >
          <View
            style={[
              styles.sheet,
              {
                backgroundColor: palette.surfaceRaised,
                padding: spacing.mdLg,
                borderTopLeftRadius: radius.sheet,
                borderTopRightRadius: radius.sheet,
              },
            ]}
          >
            <Text
              style={{
                fontWeight: typography.title.fontWeight,
                fontSize: typography.title.fontSize,
                marginBottom: spacing.md,
                color: palette.textMain,
              }}
            >
              Quick actions
            </Text>
            {actions.map((action, index) => (
              <Pressable
                key={action.id}
                onPress={() => {
                  setOpen(false);
                  action.onPress();
                }}
                style={[
                  styles.action,
                  {
                    borderColor: palette.borderSubtle,
                    paddingVertical: spacing.mdSm,
                    gap: spacing.md,
                  },
                ]}
              >
                <AccentIcon
                  name={action.icon}
                  tone={ACTION_TONES[index % ACTION_TONES.length]}
                  size={40}
                  iconSize={18}
                />
                <Text style={{ color: palette.textMain, fontSize: typography.body.fontSize, fontWeight: '600', flex: 1 }}>
                  {action.label}
                </Text>
                <Ionicons name="chevron-forward" size={16} color={palette.textMuted} />
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
    width: 58,
    height: 58,
  },
  fabFill: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  backdrop: { flex: 1, justifyContent: 'flex-end' },
  sheet: { maxHeight: '70%' },
  action: {
    flexDirection: 'row',
    alignItems: 'center',
    borderBottomWidth: StyleSheet.hairlineWidth,
    minHeight: 56,
  },
});
