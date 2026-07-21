import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import {
  Modal,
  Pressable,
  ScrollView,
  StyleSheet,
  Text,
  View,
  useWindowDimensions,
} from 'react-native';
import { useSafeAreaInsets } from 'react-native-safe-area-context';
import { useTheme } from '../theme/ThemeContext';

export interface FilterBottomSheetProps {
  visible: boolean;
  title?: string;
  onClose: () => void;
  onApply: () => void;
  onClear: () => void;
  children: React.ReactNode;
}

export const FilterBottomSheet: React.FC<FilterBottomSheetProps> = ({
  visible,
  title = 'Filters',
  onClose,
  onApply,
  onClear,
  children,
}) => {
  const { palette, colors, spacing, typography, radius, opacity } = useTheme();
  const insets = useSafeAreaInsets();
  const { height } = useWindowDimensions();
  const maxSheetHeight = height * 0.82;

  return (
    <Modal visible={visible} transparent animationType="slide" onRequestClose={onClose}>
      <View style={styles.overlay}>
        <Pressable
          style={[styles.backdrop, { backgroundColor: `rgba(0,0,0,${opacity.scrim})` }]}
          onPress={onClose}
          accessibilityLabel="Close filters"
        />
        <View
          style={[
            styles.sheet,
            elevationStyle,
            {
              backgroundColor: palette.surfaceRaised,
              borderTopLeftRadius: radius.sheet,
              borderTopRightRadius: radius.sheet,
              paddingBottom: insets.bottom + spacing.md,
              maxHeight: maxSheetHeight,
            },
          ]}
        >
          <View style={[styles.handle, { backgroundColor: palette.border }]} />
          <View style={[styles.header, { paddingHorizontal: spacing.md, marginBottom: spacing.sm }]}>
            <Text
              style={{
                flex: 1,
                fontSize: typography.title.fontSize,
                fontWeight: typography.title.fontWeight,
                color: palette.textPrimary,
              }}
            >
              {title}
            </Text>
            <Pressable onPress={onClose} hitSlop={8} accessibilityLabel="Close">
              <Ionicons name="close" size={22} color={palette.textSecondary} />
            </Pressable>
          </View>

          <ScrollView
            style={{ flexGrow: 0 }}
            contentContainerStyle={{ paddingHorizontal: spacing.md, paddingBottom: spacing.sm }}
            keyboardShouldPersistTaps="handled"
            showsVerticalScrollIndicator={false}
          >
            {children}
          </ScrollView>

          <View
            style={[
              styles.actions,
              {
                paddingHorizontal: spacing.md,
                paddingTop: spacing.sm,
                gap: spacing.sm,
                borderTopColor: palette.borderSubtle,
              },
            ]}
          >
            <Pressable
              onPress={onClear}
              accessibilityRole="button"
              style={[
                styles.actionBtn,
                {
                  borderColor: palette.border,
                  borderRadius: radius.md,
                  paddingVertical: spacing.sm,
                },
              ]}
            >
              <Text style={{ color: palette.textSecondary, fontWeight: '600', textAlign: 'center' }}>
                Clear
              </Text>
            </Pressable>
            <Pressable
              onPress={onApply}
              accessibilityRole="button"
              style={[
                styles.actionBtn,
                {
                  backgroundColor: colors.primary,
                  borderRadius: radius.md,
                  paddingVertical: spacing.sm,
                },
              ]}
            >
              <Text style={{ color: colors.white, fontWeight: '700', textAlign: 'center' }}>Apply</Text>
            </Pressable>
          </View>
        </View>
      </View>
    </Modal>
  );
};

const elevationStyle = {
  shadowColor: '#000',
  shadowOffset: { width: 0, height: -4 },
  shadowOpacity: 0.12,
  shadowRadius: 12,
  elevation: 16,
};

const styles = StyleSheet.create({
  overlay: { flex: 1, justifyContent: 'flex-end' },
  backdrop: { ...StyleSheet.absoluteFillObject },
  sheet: { width: '100%' },
  handle: {
    width: 36,
    height: 4,
    borderRadius: 2,
    alignSelf: 'center',
    marginTop: 8,
    marginBottom: 8,
  },
  header: { flexDirection: 'row', alignItems: 'center' },
  actions: { flexDirection: 'row', borderTopWidth: StyleSheet.hairlineWidth },
  actionBtn: { flex: 1, borderWidth: StyleSheet.hairlineWidth },
});
