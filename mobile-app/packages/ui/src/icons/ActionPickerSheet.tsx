import React from 'react';
import { Modal, Pressable, StyleSheet, Text, View } from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../theme/ThemeContext';
import { AppIcon } from './AppIcon';
import { Soft3DIcon } from '../primitives/AccentIcon';
import type { AppIconName } from './names';

export interface ActionPickerItem {
  key: string;
  label: string;
  icon: AppIconName | string;
  onPress: () => void;
}

export interface ActionPickerSheetProps {
  visible: boolean;
  title: string;
  actions: ActionPickerItem[];
  onClose: () => void;
}

/** Lightweight action list used for grouped shortcuts (e.g. Attendance). */
export const ActionPickerSheet: React.FC<ActionPickerSheetProps> = ({
  visible,
  title,
  actions,
  onClose,
}) => {
  const { palette, spacing, typography, radius, opacity } = useTheme();
  const insets = useSafeAreaInsets();

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Pressable
          style={[styles.backdrop, { backgroundColor: `rgba(0,0,0,${opacity.scrim})` }]}
          onPress={onClose}
          accessibilityLabel={`Close ${title}`}
        />
        <View
          style={[
            styles.sheet,
            {
              backgroundColor: palette.surfaceRaised,
              borderTopLeftRadius: radius.sheet,
              borderTopRightRadius: radius.sheet,
              paddingBottom: insets.bottom + spacing.md,
            },
          ]}
        >
          <View style={[styles.handle, { backgroundColor: palette.border }]} />
          <Text
            style={{
              fontSize: typography.title.fontSize,
              fontWeight: typography.title.fontWeight,
              color: palette.textPrimary,
              paddingHorizontal: spacing.lg,
              marginBottom: spacing.sm,
            }}
          >
            {title}
          </Text>
          {actions.map((action) => (
            <Pressable
              key={action.key}
              accessibilityRole="button"
              accessibilityLabel={action.label}
              onPress={() => {
                onClose();
                action.onPress();
              }}
              style={({ pressed }) => [
                styles.row,
                {
                  paddingHorizontal: spacing.lg,
                  paddingVertical: spacing.md,
                  opacity: pressed ? 0.7 : 1,
                  borderBottomColor: palette.borderSubtle,
                },
              ]}
            >
              <View
                style={[
                  styles.iconWell,
                  { backgroundColor: '#F8FAFC', borderRadius: radius.md },
                ]}
              >
                <Soft3DIcon name={action.icon} size={36} />
              </View>
              <Text
                style={{
                  flex: 1,
                  marginLeft: spacing.md,
                  color: palette.textPrimary,
                  fontSize: typography.body.fontSize,
                  fontWeight: '600',
                }}
              >
                {action.label}
              </Text>
              <AppIcon name="navigation" size={16} color={palette.textMuted} />
            </Pressable>
          ))}
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  overlay: { flex: 1, justifyContent: 'flex-end' },
  backdrop: { ...StyleSheet.absoluteFill },
  sheet: { width: '100%' },
  handle: {
    width: 36,
    height: 4,
    borderRadius: 2,
    alignSelf: 'center',
    marginTop: 8,
    marginBottom: 12,
  },
  row: {
    flexDirection: 'row',
    alignItems: 'center',
    borderBottomWidth: StyleSheet.hairlineWidth,
  },
  iconWell: {
    width: 40,
    height: 40,
    alignItems: 'center',
    justifyContent: 'center',
  },
});
