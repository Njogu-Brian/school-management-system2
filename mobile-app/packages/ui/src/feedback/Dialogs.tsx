import { Ionicons } from '@expo/vector-icons';
import React from 'react';
import {
  ActivityIndicator,
  Modal,
  Pressable,
  StyleSheet,
  Text,
  View,
} from 'react-native';
import { useTheme } from '../theme/ThemeContext';
import { Button } from '../primitives/Button';

function useDialogSurface() {
  const theme = useTheme();
  const { palette, isDark, elevation } = theme;
  // Dialogs must read clearly above the scrim — never reuse the dimmed page surface.
  const backgroundColor = isDark ? '#2B3444' : '#FFFFFF';
  return {
    ...theme,
    dialogBg: backgroundColor,
    dialogElevation: elevation[4] ?? elevation[3],
    textMain: isDark ? '#F3F6FB' : palette.textMain,
    textSub: isDark ? '#C5CEDC' : palette.textSub,
    border: isDark ? 'rgba(255,255,255,0.14)' : palette.borderSubtle,
  };
}

export interface AlertDialogProps {
  visible: boolean;
  title: string;
  message?: string;
  confirmLabel?: string;
  onConfirm: () => void;
  onRequestClose?: () => void;
  testID?: string;
}

/** Branded single-action alert dialog (V3). */
export const AlertDialog: React.FC<AlertDialogProps> = ({
  visible,
  title,
  message,
  confirmLabel = 'OK',
  onConfirm,
  onRequestClose,
  testID,
}) => {
  const { spacing, typography, radius, opacity, dialogBg, dialogElevation, textMain, textSub, border } =
    useDialogSurface();

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onRequestClose ?? onConfirm}
      statusBarTranslucent
    >
      <View style={styles.root}>
        <Pressable
          style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${Math.min(opacity.scrim, 0.5)})` }]}
          onPress={onRequestClose ?? onConfirm}
          accessibilityRole="button"
          accessibilityLabel="Dismiss dialog"
        />
        <View style={styles.center} pointerEvents="box-none">
          <View
            testID={testID}
            style={[
              styles.card,
              dialogElevation,
              {
                backgroundColor: dialogBg,
                borderRadius: radius.dialog,
                padding: spacing.lg,
                borderColor: border,
              },
            ]}
          >
            <Text
              style={{
                color: textMain,
                fontSize: typography.title.fontSize,
                fontWeight: typography.title.fontWeight,
                marginBottom: message ? spacing.sm : spacing.md,
              }}
            >
              {title}
            </Text>
            {message ? (
              <Text
                style={{
                  color: textSub,
                  fontSize: typography.body.fontSize,
                  lineHeight: typography.body.lineHeight,
                  marginBottom: spacing.lg,
                }}
              >
                {message}
              </Text>
            ) : null}
            <Button label={confirmLabel} onPress={onConfirm} />
          </View>
        </View>
      </View>
    </Modal>
  );
};

export interface ConfirmDialogProps {
  visible: boolean;
  title: string;
  message?: string;
  confirmLabel?: string;
  cancelLabel?: string;
  destructive?: boolean;
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
  testID?: string;
}

/** Branded confirm dialog with cancel + confirm (V3). */
export const ConfirmDialog: React.FC<ConfirmDialogProps> = ({
  visible,
  title,
  message,
  confirmLabel = 'Confirm',
  cancelLabel = 'Cancel',
  destructive = false,
  loading = false,
  onConfirm,
  onCancel,
  testID,
}) => {
  const { spacing, typography, radius, opacity, dialogBg, dialogElevation, textMain, textSub, border } =
    useDialogSurface();

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onCancel}
      statusBarTranslucent
    >
      <View style={styles.root}>
        <Pressable
          style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${Math.min(opacity.scrim, 0.5)})` }]}
          onPress={loading ? undefined : onCancel}
          accessibilityRole="button"
          accessibilityLabel="Cancel"
        />
        <View style={styles.center} pointerEvents="box-none">
          <View
            testID={testID}
            style={[
              styles.card,
              dialogElevation,
              {
                backgroundColor: dialogBg,
                borderRadius: radius.dialog,
                padding: spacing.lg,
                borderColor: border,
              },
            ]}
          >
            <Text
              style={{
                color: textMain,
                fontSize: typography.title.fontSize,
                fontWeight: typography.title.fontWeight,
                marginBottom: message ? spacing.sm : spacing.md,
              }}
            >
              {title}
            </Text>
            {message ? (
              <Text
                style={{
                  color: textSub,
                  fontSize: typography.body.fontSize,
                  lineHeight: typography.body.lineHeight,
                  marginBottom: spacing.lg,
                }}
              >
                {message}
              </Text>
            ) : null}
            <View style={{ gap: spacing.sm }}>
              <Button
                label={confirmLabel}
                onPress={onConfirm}
                loading={loading}
                variant={destructive ? 'destructive' : 'primary'}
              />
              <Button label={cancelLabel} onPress={onCancel} variant="ghost" disabled={loading} />
            </View>
          </View>
        </View>
      </View>
    </Modal>
  );
};

export interface LoadingDialogProps {
  visible: boolean;
  message?: string;
  testID?: string;
}

/** Blocking branded loading dialog (V3). */
export const LoadingDialog: React.FC<LoadingDialogProps> = ({
  visible,
  message = 'Please wait…',
  testID,
}) => {
  const { spacing, typography, radius, opacity, dialogBg, border, palette, textMain } = useDialogSurface();

  return (
    <Modal visible={visible} transparent animationType="fade" statusBarTranslucent>
      <View style={[styles.root, styles.centerFill]}>
        <View
          style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${Math.min(opacity.scrim, 0.5)})` }]}
          pointerEvents="none"
        />
        <View
          testID={testID}
          style={[
            styles.loadingCard,
            {
              backgroundColor: dialogBg,
              borderRadius: radius.card,
              padding: spacing.lg,
              borderColor: border,
            },
          ]}
        >
          <ActivityIndicator size="large" color={palette.primary} />
          <Text
            style={{
              marginTop: spacing.md,
              color: textMain,
              fontSize: typography.body.fontSize,
              textAlign: 'center',
            }}
          >
            {message}
          </Text>
        </View>
      </View>
    </Modal>
  );
};

export interface SuccessDialogProps {
  visible: boolean;
  title: string;
  message?: string;
  confirmLabel?: string;
  onConfirm: () => void;
  testID?: string;
}

/** Branded success dialog (V3). */
export const SuccessDialog: React.FC<SuccessDialogProps> = ({
  visible,
  title,
  message,
  confirmLabel = 'Done',
  onConfirm,
  testID,
}) => {
  const {
    spacing,
    typography,
    radius,
    opacity,
    dialogBg,
    dialogElevation,
    textMain,
    textSub,
    border,
    semantic,
  } = useDialogSurface();

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onConfirm} statusBarTranslucent>
      <View style={styles.root}>
        <Pressable
          style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${Math.min(opacity.scrim, 0.5)})` }]}
          onPress={onConfirm}
        />
        <View style={styles.center} pointerEvents="box-none">
          <View
            testID={testID}
            style={[
              styles.card,
              dialogElevation,
              {
                backgroundColor: dialogBg,
                borderRadius: radius.dialog,
                padding: spacing.lg,
                borderColor: border,
                alignItems: 'center',
              },
            ]}
          >
            <View
              style={{
                width: 64,
                height: 64,
                borderRadius: 32,
                backgroundColor: semantic.success.bg,
                alignItems: 'center',
                justifyContent: 'center',
                marginBottom: spacing.md,
              }}
            >
              <Ionicons name="checkmark-circle" size={36} color={semantic.success.fg} />
            </View>
            <Text
              style={{
                color: textMain,
                fontSize: typography.title.fontSize,
                fontWeight: typography.title.fontWeight,
                textAlign: 'center',
                marginBottom: message ? spacing.sm : spacing.md,
              }}
            >
              {title}
            </Text>
            {message ? (
              <Text
                style={{
                  color: textSub,
                  fontSize: typography.body.fontSize,
                  textAlign: 'center',
                  marginBottom: spacing.lg,
                }}
              >
                {message}
              </Text>
            ) : null}
            <Button label={confirmLabel} onPress={onConfirm} />
          </View>
        </View>
      </View>
    </Modal>
  );
};

const styles = StyleSheet.create({
  root: {
    flex: 1,
  },
  scrim: {
    ...StyleSheet.absoluteFillObject,
  },
  center: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
    // Keep the card above the scrim so it stays bright and tappable.
    zIndex: 2,
    elevation: 8,
  },
  centerFill: {
    alignItems: 'center',
    justifyContent: 'center',
  },
  card: {
    width: '100%',
    maxWidth: 400,
    borderWidth: StyleSheet.hairlineWidth,
    zIndex: 3,
  },
  loadingCard: {
    minWidth: 180,
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
    zIndex: 3,
  },
});
