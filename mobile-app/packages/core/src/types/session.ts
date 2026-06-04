/** Session metadata persisted to AsyncStorage (the token itself lives in SecureStore). */
export interface PersistedSessionMeta {
  /** Epoch ms of server token expiry (from login `expires_at`), if known. */
  expiresAt: number | null;
  /** Epoch ms the session started. */
  startedAt: number;
  /** Epoch ms of the last successful activity (for idle timeout). */
  lastActivityAt: number;
  /** Whether the user opted into a longer idle window. */
  rememberMe: boolean;
}
