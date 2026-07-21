import { ActionSheetIOS, Linking, Platform } from 'react-native';
import { confirmAction, showError } from '../features/shared/utils/feedback';

function normalizePhone(raw: string): string {
  const digits = raw.replace(/\D/g, '');
  if (digits.startsWith('254')) return digits;
  if (digits.startsWith('0')) return `254${digits.slice(1)}`;
  if (digits.length === 9) return `254${digits}`;
  return digits;
}

export async function openPhoneActions(phone: string, label?: string): Promise<void> {
  const trimmed = phone.trim();
  if (!trimmed) return;

  const tel = trimmed.replace(/\s/g, '');
  const wa = normalizePhone(trimmed);

  const call = () => void Linking.openURL(`tel:${tel}`);

  if (Platform.OS === 'ios') {
    const sms = () => void Linking.openURL(`sms:${tel}`);
    const whatsapp = () => void Linking.openURL(`https://wa.me/${wa}`);
    ActionSheetIOS.showActionSheetWithOptions(
      {
        title: label ?? trimmed,
        options: ['Call', 'Text (SMS)', 'WhatsApp', 'Cancel'],
        cancelButtonIndex: 3,
      },
      (index) => {
        if (index === 0) void call();
        if (index === 1) void sms();
        if (index === 2) void whatsapp();
      },
    );
    return;
  }

  // Android: branded confirm for primary Call path
  confirmAction(label ?? 'Contact', trimmed, 'Call', call);
}

export async function openEmail(email: string): Promise<void> {
  const trimmed = email.trim();
  if (!trimmed) return;
  const url = `mailto:${encodeURIComponent(trimmed)}`;
  const can = await Linking.canOpenURL(url);
  if (!can) {
    showError('Email', 'No email app is available on this device.');
    return;
  }
  await Linking.openURL(url);
}
