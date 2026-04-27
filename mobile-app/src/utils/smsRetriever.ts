import { Platform } from 'react-native';

export type SmsRetrieverLike = {
    startSmsRetriever?: () => Promise<boolean>;
    addSmsListener?: (cb: (event: { message?: string }) => void) => { remove: () => void } | void;
    removeSmsListener?: () => void;
    requestPhoneNumber?: () => Promise<string>;
};

/**
 * `react-native-sms-retriever` is a native module and may be unavailable depending on:
 * - running in an environment without that native module (e.g. Expo Go)
 * - a build missing/failed autolinking
 * - New Architecture incompatibilities
 *
 * Importing it at the top-level can crash the app if its internal wrapper touches a null native module.
 * This helper safely/lazily loads it on Android only.
 */
export function getSmsRetriever(): SmsRetrieverLike | null {
    if (Platform.OS !== 'android') return null;
    try {
        // eslint-disable-next-line @typescript-eslint/no-var-requires
        const mod = require('react-native-sms-retriever');
        return (mod?.default ?? mod) as SmsRetrieverLike;
    } catch {
        return null;
    }
}

