/**
 * Imperative feedback bridge for Admin App.
 * Prefer ConfirmDialog / useToast in new screens; this routes legacy helpers
 * through branded Toast + ConfirmDialog hosts when FeedbackProvider is mounted.
 */
import type { ToastTone } from '@erp/ui';

type ConfirmRequest = {
  title: string;
  message: string;
  confirmLabel: string;
  destructive: boolean;
  onConfirm: () => void | Promise<void>;
};

type ToastRequest = {
  title: string;
  message?: string;
  tone: ToastTone;
};

type FeedbackHandlers = {
  showToast: (req: ToastRequest) => void;
  showConfirm: (req: ConfirmRequest) => void;
};

let handlers: FeedbackHandlers | null = null;

export function registerFeedbackHandlers(next: FeedbackHandlers | null): void {
  handlers = next;
}

export function showSuccess(title: string, message?: string, onDismiss?: () => void): void {
  if (handlers) {
    handlers.showToast({ title, message, tone: 'success' });
    onDismiss?.();
    return;
  }
  // Fallback if provider not mounted
  const { Alert } = require('react-native') as typeof import('react-native');
  Alert.alert(title, message, [{ text: 'OK', onPress: onDismiss }]);
}

export function showError(title: string, message?: string): void {
  if (handlers) {
    handlers.showToast({ title, message: message ?? 'Something went wrong. Please try again.', tone: 'danger' });
    return;
  }
  const { Alert } = require('react-native') as typeof import('react-native');
  Alert.alert(title, message ?? 'Something went wrong. Please try again.');
}

export function confirmAction(
  title: string,
  message: string,
  confirmLabel: string,
  onConfirm: () => void | Promise<void>,
  destructive = false,
): void {
  if (handlers) {
    handlers.showConfirm({ title, message, confirmLabel, destructive, onConfirm });
    return;
  }
  const { Alert } = require('react-native') as typeof import('react-native');
  Alert.alert(title, message, [
    { text: 'Cancel', style: 'cancel' },
    {
      text: confirmLabel,
      style: destructive ? 'destructive' : 'default',
      onPress: () =>
        void Promise.resolve(onConfirm()).catch((err: Error) => showError('Action failed', err.message)),
    },
  ]);
}
