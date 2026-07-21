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
  const { palette, spacing, typography, radius, opacity, zIndex } = useTheme();

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onRequestClose ?? onConfirm}
      statusBarTranslucent
    >
      <Pressable
        style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${opacity.scrim})`, zIndex: zIndex.dialog }]}
        onPress={onRequestClose ?? onConfirm}
        accessibilityRole="button"
        accessibilityLabel="Dismiss dialog"
      />
      <View style={styles.center} pointerEvents="box-none">
        <View
          testID={testID}
          style={[
            styles.card,
            {
              backgroundColor: palette.surfaceRaised,
              borderRadius: radius.dialog,
              padding: spacing.lg,
              borderColor: palette.borderSubtle,
            },
          ]}
        >
          <Text
            style={{
              color: palette.textMain,
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
                color: palette.textSub,
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
  const { palette, spacing, typography, radius, opacity, zIndex } = useTheme();

  return (
    <Modal
      visible={visible}
      transparent
      animationType="fade"
      onRequestClose={onCancel}
      statusBarTranslucent
    >
      <Pressable
        style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${opacity.scrim})`, zIndex: zIndex.dialog }]}
        onPress={onCancel}
        accessibilityRole="button"
        accessibilityLabel="Cancel"
      />
      <View style={styles.center} pointerEvents="box-none">
        <View
          testID={testID}
          style={[
            styles.card,
            {
              backgroundColor: palette.surfaceRaised,
              borderRadius: radius.dialog,
              padding: spacing.lg,
              borderColor: palette.borderSubtle,
            },
          ]}
        >
          <Text
            style={{
              color: palette.textMain,
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
                color: palette.textSub,
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
  const { palette, spacing, typography, radius, opacity, zIndex } = useTheme();

  return (
    <Modal visible={visible} transparent animationType="fade" statusBarTranslucent>
      <View
        style={[
          styles.scrimFill,
          { backgroundColor: `rgba(0,0,0,${opacity.scrim})`, zIndex: zIndex.dialog },
        ]}
      >
        <View
          testID={testID}
          style={[
            styles.loadingCard,
            {
              backgroundColor: palette.surfaceRaised,
              borderRadius: radius.card,
              padding: spacing.lg,
              borderColor: palette.borderSubtle,
            },
          ]}
        >
          <ActivityIndicator size="large" color={palette.primary} />
          <Text
            style={{
              marginTop: spacing.md,
              color: palette.textMain,
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
  const { palette, spacing, typography, radius, opacity, zIndex, semantic } = useTheme();

  return (
    <Modal visible={visible} transparent animationType="fade" onRequestClose={onConfirm} statusBarTranslucent>
      <Pressable
        style={[styles.scrim, { backgroundColor: `rgba(0,0,0,${opacity.scrim})`, zIndex: zIndex.dialog }]}
        onPress={onConfirm}
      />
      <View style={styles.center} pointerEvents="box-none">
        <View
          testID={testID}
          style={[
            styles.card,
            {
              backgroundColor: palette.surfaceRaised,
              borderRadius: radius.dialog,
              padding: spacing.lg,
              borderColor: palette.borderSubtle,
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
              color: palette.textMain,
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
                color: palette.textSub,
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
    </Modal>
  );
};

const styles = StyleSheet.create({
  scrim: {
    ...StyleSheet.absoluteFillObject,
  },
  scrimFill: {
    flex: 1,
    alignItems: 'center',
    justifyContent: 'center',
  },
  center: {
    ...StyleSheet.absoluteFillObject,
    alignItems: 'center',
    justifyContent: 'center',
    paddingHorizontal: 24,
  },
  card: {
    width: '100%',
    maxWidth: 400,
    borderWidth: StyleSheet.hairlineWidth,
  },
  loadingCard: {
    minWidth: 180,
    alignItems: 'center',
    borderWidth: StyleSheet.hairlineWidth,
  },
});
