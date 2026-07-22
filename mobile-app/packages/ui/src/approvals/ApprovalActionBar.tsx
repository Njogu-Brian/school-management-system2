import React from 'react';
import { ActivityIndicator, Pressable, StyleSheet, Text, View } from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { useFloatingTabBarClearance } from '../layout/PremiumTabBar';

export interface ApprovalActionBarProps {
  canAct: boolean;
  isSubmitting?: boolean;
  onApprove: () => void;
  onReject: () => void;
  onEscalate?: () => void;
  showEscalate?: boolean;
  /** When false, only Reject is shown (e.g. admissions enroll elsewhere). */
  showApprove?: boolean;
}

/** Approx height of the action row (used by detail screens for scroll padding). */
export const APPROVAL_ACTION_BAR_HEIGHT = 64;

export const ApprovalActionBar: React.FC<ApprovalActionBarProps> = ({
  canAct,
  isSubmitting,
  onApprove,
  onReject,
  onEscalate,
  showEscalate = false,
  showApprove = true,
}) => {
  const { palette, colors, spacing, typography, radius, shadows } = useTheme();
  const tabClearance = useFloatingTabBarClearance();

  if (!canAct) {
    return null;
  }

  return (
    <View
      style={[
        styles.bar,
        {
          backgroundColor: palette.surface,
          borderTopColor: palette.border,
          paddingHorizontal: spacing.md,
          paddingTop: spacing.sm,
          paddingBottom: spacing.sm,
          bottom: tabClearance,
        },
        shadows.md,
      ]}
    >
      {showEscalate && onEscalate ? (
        <Pressable
          onPress={onEscalate}
          disabled={isSubmitting}
          style={[
            styles.secondaryBtn,
            { borderColor: palette.border, borderRadius: radius.md, marginBottom: spacing.xs },
          ]}
        >
          <Text
            style={{
              color: palette.textSecondary,
              fontWeight: '600',
              fontSize: typography.label.fontSize,
            }}
          >
            Escalate
          </Text>
        </Pressable>
      ) : null}

      <View style={[styles.row, { gap: spacing.sm }]}>
        <Pressable
          onPress={onReject}
          disabled={isSubmitting}
          style={[
            styles.btn,
            styles.flex,
            { borderColor: colors.error, borderRadius: radius.md },
          ]}
        >
          {isSubmitting ? (
            <ActivityIndicator color={colors.error} />
          ) : (
            <Text
              style={{
                color: colors.error,
                fontWeight: '700',
                fontSize: typography.label.fontSize,
              }}
            >
              Reject
            </Text>
          )}
        </Pressable>
        {showApprove ? (
          <Pressable
            onPress={onApprove}
            disabled={isSubmitting}
            style={[
              styles.btn,
              styles.flex,
              { backgroundColor: colors.primary, borderRadius: radius.md },
            ]}
          >
            {isSubmitting ? (
              <ActivityIndicator color={palette.textOnPrimary} />
            ) : (
              <Text
                style={{
                  color: palette.textOnPrimary,
                  fontWeight: '700',
                  fontSize: typography.label.fontSize,
                }}
              >
                Approve
              </Text>
            )}
          </Pressable>
        ) : null}
      </View>
    </View>
  );
};

const styles = StyleSheet.create({
  bar: {
    borderTopWidth: StyleSheet.hairlineWidth,
    position: 'absolute',
    left: 0,
    right: 0,
  },
  row: { flexDirection: 'row' },
  flex: { flex: 1 },
  btn: {
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 44,
    borderWidth: 1,
  },
  secondaryBtn: {
    alignItems: 'center',
    justifyContent: 'center',
    minHeight: 40,
    borderWidth: 1,
  },
});
