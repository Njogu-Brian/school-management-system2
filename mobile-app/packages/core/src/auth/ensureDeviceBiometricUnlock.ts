import { Platform } from 'react-native';
import { accountApi } from '../api/account.api';
import {
  generateBiometricUnlockPair,
  getBiometricAuthBundle,
  saveBiometricAuthBundle,
  setBiometricEnabled,
} from '../storage/biometricStorage';

/** Register (or refresh) this phone's biometric unlock secret on the account. */
export async function ensureDeviceBiometricUnlock(opts: {
  userId?: number;
  identifier?: string;
}): Promise<void> {
  const existing = await getBiometricAuthBundle();
  let selector = existing?.selector;
  let secret = existing?.secret;
  if (!selector || !secret) {
    const pair = await generateBiometricUnlockPair();
    selector = pair.selector;
    secret = pair.secret;
  }

  const res = await accountApi.registerBiometricUnlock({
    selector,
    secret,
    device_name: Platform.OS === 'ios' ? 'iPhone' : 'Android',
  });
  if (!res.success) {
    throw new Error(res.message || 'Could not enable biometric unlock on this device.');
  }

  await setBiometricEnabled(true);
  await saveBiometricAuthBundle({
    selector,
    secret,
    userId: opts.userId ?? existing?.userId,
    identifier: opts.identifier ?? existing?.identifier,
  });
}
