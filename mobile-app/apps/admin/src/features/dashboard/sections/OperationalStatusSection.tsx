import { useCan } from '@erp/core';
import { DashboardSection } from '@erp/ui';
import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import { StyleSheet, Text, View } from 'react-native';
import { useTheme } from '@erp/ui';
import { OPERATIONAL_STATUS_PLACEHOLDERS } from '../data/placeholders';

const STATUS_COLOR = {
  ok: 'success',
  warning: 'warning',
  error: 'error',
} as const;

export const OperationalStatusSection: React.FC = () => {
  const canView = useCan('dashboard.view');
  const { palette, colors, fontSizes, spacing, radius } = useTheme();

  if (!canView) {
    return null;
  }

  return (
    <DashboardSection title="Operational status" subtitle="Platform & integrations">
      {OPERATIONAL_STATUS_PLACEHOLDERS.map((item) => {
        const tone = STATUS_COLOR[item.status];
        const accent =
          tone === 'success' ? colors.success : tone === 'warning' ? colors.warning : colors.error;

        return (
          <View
            key={item.id}
            style={[
              styles.row,
              {
                backgroundColor: palette.surface,
                borderColor: palette.border,
                borderRadius: radius.md,
                padding: spacing.md,
                marginBottom: spacing.sm,
              },
            ]}
          >
            <Ionicons
              name={
                item.status === 'ok'
                  ? 'checkmark-circle'
                  : item.status === 'warning'
                    ? 'warning'
                    : 'close-circle'
              }
              size={20}
              color={accent}
            />
            <View style={styles.text}>
              <Text style={[styles.label, { color: palette.textPrimary, fontSize: fontSizes.sm }]}>
                {item.label}
              </Text>
              <Text style={{ color: palette.textSecondary, fontSize: fontSizes.xs }}>
                {item.detail}
              </Text>
            </View>
          </View>
        );
      })}
    </DashboardSection>
  );
};

const styles = StyleSheet.create({
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
  text: { marginLeft: 10, flex: 1 },
  label: { fontWeight: '600' },
});
