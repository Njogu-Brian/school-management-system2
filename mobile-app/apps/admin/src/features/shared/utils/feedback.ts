import { Alert } from 'react-native';

export function showSuccess(title: string, message?: string, onDismiss?: () => void): void {
  Alert.alert(title, message, [{ text: 'OK', onPress: onDismiss }]);
}

export function showError(title: string, message?: string): void {
  Alert.alert(title, message ?? 'Something went wrong. Please try again.');
}

export function confirmAction(
  title: string,
  message: string,
  confirmLabel: string,
  onConfirm: () => void | Promise<void>,
  destructive = false,
): void {
  Alert.alert(title, message, [
    { text: 'Cancel', style: 'cancel' },
    {
      text: confirmLabel,
      style: destructive ? 'destructive' : 'default',
      onPress: () => void Promise.resolve(onConfirm()).catch((err: Error) => showError('Action failed', err.message)),
    },
  ]);
}
