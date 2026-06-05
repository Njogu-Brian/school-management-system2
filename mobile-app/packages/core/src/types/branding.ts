/** Portal Settings → Branding colors merged into app theme. */
export interface PortalBrandColors {
  primary?: string;
  primary_dark?: string;
  primary_light?: string;
  secondary?: string;
  success?: string;
  warning?: string;
  error?: string;
  info?: string;
  surface_light?: string;
  border_light?: string;
  text_main_light?: string;
  text_sub_light?: string;
  accent_light?: string;
  background_dark?: string;
  surface_dark?: string;
  border_dark?: string;
  text_main_dark?: string;
  text_sub_dark?: string;
  accent_dark?: string;
}

/** Response from GET /api/app-branding (public). */
export interface AppBranding {
  school_name: string;
  logo_url: string | null;
  login_background_url?: string | null;
  colors?: PortalBrandColors;
  android_apk_download_url?: string | null;
}
