/** SecureStore keys (sensitive). */
export const SECURE_KEYS = {
  TOKEN: 'admin_erp_token',
  /** Reserved for future refresh-token support (build plan §4 / batch-2 requirement). */
  REFRESH_TOKEN: 'admin_erp_refresh_token',
} as const;

/** AsyncStorage keys (non-sensitive cache + session metadata). */
export const ASYNC_KEYS = {
  USER: '@admin_erp_user',
  SESSION_META: '@admin_erp_session_meta',
  BIOMETRIC_ENABLED: '@admin_erp_biometric_enabled',
  BIOMETRIC_FAILURE_COUNT: '@admin_erp_biometric_failure_count',
  THEME_MODE: '@admin_erp_theme_mode',
} as const;

export const BIOMETRIC_SECURE_KEYS = {
  AUTH_BUNDLE: 'admin_erp_biometric_auth_bundle',
} as const;
