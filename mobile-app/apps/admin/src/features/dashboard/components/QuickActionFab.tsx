import { useCan } from '@erp/core';
import { AccentIcon, useFloatingTabBarClearance, useTheme } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import { LinearGradient } from 'expo-linear-gradient';
import { useNavigation } from '@react-navigation/native';
import React, { useMemo, useState } from 'react';
import { Modal, Pressable, ScrollView, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
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
  const insets = useSafeAreaInsets();
  const tabClearance = useFloatingTabBarClearance(true);
  const [open, setOpen] = useState(false);

  const canAdmissions = useCan('admissions.view');
  const canFinance = useCan('finance.view');
  const canComm = useCan('communication.view');
  const canOps = useCan('operations.view');
  const canApprovals = useCan(['approvals.view', 'dashboard.approvals.view']);
  const canPeople = useCan(['people.view', 'staff.view']);
  const canAcademics = useCan(['academics.view', 'dashboard.view']);

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
      id: 'concern',
      label: 'Report concern',
      icon: 'alert-circle-outline',
      visible: canOps,
      onPress: () => navigateToDrawer(navigation, 'Operations', 'ConcernCreate'),
    },
    {
      id: 'requisition',
      label: 'Requisitions',
      icon: 'clipboard-outline',
      visible: canOps,
      onPress: () => navigateToDrawer(navigation, 'Operations', 'RequisitionsList'),
    },
    {
      id: 'attendance',
      label: 'Mark attendance',
      icon: 'clipboard-outline',
      visible: canAcademics,
      onPress: () => navigateToDrawer(navigation, 'Academics', 'MarkAttendance'),
    },
    {
      id: 'staff_clock',
      label: 'Staff attendance',
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
        bottom: tabClearance + spacing.sm,
        borderRadius: radius.full,
        zIndex: zIndex.fab,
        overflow: 'hidden' as const,
      },
    ],
    [elevation, radius.full, spacing, tabClearance, zIndex.fab],
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
        <View style={styles.overlay}>
          <Pressable
            style={[styles.backdrop, { backgroundColor: `rgba(0,0,0,${opacity.scrim})` }]}
            onPress={() => setOpen(false)}
            accessibilityLabel="Close quick actions"
          />
          <View
            style={[
              styles.sheet,
              {
                backgroundColor: palette.surfaceRaised,
                paddingTop: spacing.md,
                paddingHorizontal: spacing.md,
                paddingBottom: Math.max(insets.bottom, spacing.md),
                borderTopLeftRadius: radius.sheet,
                borderTopRightRadius: radius.sheet,
                maxHeight: '78%',
              },
            ]}
          >
            <View style={[styles.handle, { backgroundColor: palette.border }]} />
            <Text
              style={{
                fontWeight: typography.title.fontWeight,
                fontSize: typography.title.fontSize,
                marginBottom: spacing.sm,
                color: palette.textMain,
              }}
            >
              Quick actions
            </Text>
            <ScrollView
              keyboardShouldPersistTaps="handled"
              showsVerticalScrollIndicator={false}
              contentContainerStyle={{ paddingBottom: spacing.sm }}
            >
              {actions.map((action, index) => (
                <Pressable
                  key={action.id}
                  onPress={() => {
                    setOpen(false);
                    action.onPress();
                  }}
                  accessibilityRole="button"
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
                  <Text
                    style={{
                      color: palette.textMain,
                      fontSize: typography.body.fontSize,
                      fontWeight: '600',
                      flex: 1,
                    }}
                  >
                    {action.label}
                  </Text>
                  <Ionicons name="chevron-forward" size={16} color={palette.textMuted} />
                </Pressable>
              ))}
            </ScrollView>
          </View>
        </View>
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
  overlay: {
    flex: 1,
    justifyContent: 'flex-end',
  },
  backdrop: {
    ...StyleSheet.absoluteFillObject,
  },
  handle: {
    alignSelf: 'center',
    width: 36,
    height: 4,
    borderRadius: 2,
    marginBottom: 12,
  },
  sheet: {},
  action: {
    flexDirection: 'row',
    alignItems: 'center',
    borderBottomWidth: StyleSheet.hairlineWidth,
    minHeight: 56,
  },
});
