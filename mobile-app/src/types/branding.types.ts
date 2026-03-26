/** Portal Settings → Branding (finance_* keys), merged into app theme. */
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
}

/** Response from GET /api/app-branding (portal Settings → General & Branding). */
export interface AppBranding {
    school_name: string;
    logo_url: string | null;
    login_background_url?: string | null;
    colors?: PortalBrandColors;
}
