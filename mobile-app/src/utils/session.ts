import AsyncStorage from '@react-native-async-storage/async-storage';

const SESSION_STARTED_KEY = '@school_erp_session_started_at';
const SESSION_LAST_ACTIVITY_KEY = '@school_erp_session_last_activity_at';

/** Absolute session lifetime (Sanctum token also expires server-side). */
export const SESSION_MAX_AGE_MS = 7 * 24 * 60 * 60 * 1000;

/** Idle timeout when "Remember me" is off. */
export const SESSION_IDLE_MS = 30 * 60 * 1000;

/** Idle timeout when "Remember me" is on (still capped by SESSION_MAX_AGE_MS). */
export const SESSION_IDLE_REMEMBER_MS = 7 * 24 * 60 * 60 * 1000;

export async function startSession(): Promise<void> {
    const now = Date.now();
    await Promise.all([
        AsyncStorage.setItem(SESSION_STARTED_KEY, String(now)),
        AsyncStorage.setItem(SESSION_LAST_ACTIVITY_KEY, String(now)),
    ]);
}

export async function touchSession(): Promise<void> {
    await AsyncStorage.setItem(SESSION_LAST_ACTIVITY_KEY, String(Date.now()));
}

export async function clearSessionTimestamps(): Promise<void> {
    await Promise.all([
        AsyncStorage.removeItem(SESSION_STARTED_KEY),
        AsyncStorage.removeItem(SESSION_LAST_ACTIVITY_KEY),
    ]);
}

export async function isSessionExpired(rememberMe: boolean): Promise<boolean> {
    const [startedRaw, lastRaw] = await Promise.all([
        AsyncStorage.getItem(SESSION_STARTED_KEY),
        AsyncStorage.getItem(SESSION_LAST_ACTIVITY_KEY),
    ]);

    if (!startedRaw || !lastRaw) {
        return true;
    }

    const started = Number(startedRaw);
    const lastActivity = Number(lastRaw);
    const now = Date.now();

    if (!Number.isFinite(started) || !Number.isFinite(lastActivity)) {
        return true;
    }

    if (now - started > SESSION_MAX_AGE_MS) {
        return true;
    }

    const idleLimit = rememberMe ? SESSION_IDLE_REMEMBER_MS : SESSION_IDLE_MS;
    return now - lastActivity > idleLimit;
}
