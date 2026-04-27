import { COLORS } from '@constants/theme';
import type { PortalBrandColors } from 'types/branding.types';

/** Apply portal Settings → Branding hex colors onto the app palette. */
export function mergePortalColors(portal: PortalBrandColors | undefined): typeof COLORS {
    if (!portal) {
        return { ...COLORS };
    }
    const next = { ...COLORS };
    if (portal.primary) {
        next.primary = portal.primary;
    }
    if (portal.primary_light) {
        next.primaryLight = portal.primary_light;
    }
    if (portal.primary_dark) {
        next.primaryDark = portal.primary_dark;
    }
    if (portal.secondary) {
        next.secondary = portal.secondary;
    }
    if (portal.success) {
        next.success = portal.success;
    }
    if (portal.warning) {
        next.warning = portal.warning;
    }
    if (portal.error) {
        next.error = portal.error;
    }
    if (portal.info) {
        next.info = portal.info;
    } else if (portal.secondary) {
        next.info = portal.secondary;
    }
    if (portal.surface_light) {
        next.surfaceLight = portal.surface_light;
    }
    if (portal.border_light) {
        next.borderLight = portal.border_light;
    }
    if (portal.text_main_light) {
        next.textMainLight = portal.text_main_light;
    }
    if (portal.text_sub_light) {
        next.textSubLight = portal.text_sub_light;
    }
    if (portal.accent_light) {
        next.accentLight = portal.accent_light;
    }
    if (portal.background_dark) {
        next.backgroundDark = portal.background_dark;
    }
    if (portal.surface_dark) {
        next.surfaceDark = portal.surface_dark;
    }
    if (portal.border_dark) {
        next.borderDark = portal.border_dark;
    }
    if (portal.text_main_dark) {
        next.textMainDark = portal.text_main_dark;
    }
    if (portal.text_sub_dark) {
        next.textSubDark = portal.text_sub_dark;
    }
    if (portal.accent_dark) {
        next.accentDark = portal.accent_dark;
    }
    return next;
}
