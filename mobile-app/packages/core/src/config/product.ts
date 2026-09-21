import type { BrandColorOverrides } from '../utils/mergePortalColors';

/**
 * Store / product identity for the combined Edulynk binary.
 * Tenant colours from GET /app-branding override these after a school code is resolved.
 * Royal Kings Admin/Users apps ignore this file.
 */
export const PRODUCT = {
  name: 'Edulynk',
  tagline: 'Your School. Connected. Simplified.',
  websiteUrl: 'https://edulynk.co.ke',
  websiteHost: 'edulynk.co.ke',
  salesEmail: 'sales@edulynk.co.ke',
  phone: '+254708225397',
  phoneDisplay: '+254 708 225 397',
  colors: {
    navy: '#071A3D',
    navyDeep: '#04122C',
    brand: '#1769FF',
    brandDark: '#0F4FD6',
    cyan: '#16C7C2',
    ink: '#14213D',
    mist: '#F6F9FC',
    muted: '#5B6B86',
  },
} as const;

/** Default theme overrides used on school-code / first launch before tenant branding loads. */
export const PRODUCT_COLOR_OVERRIDES: BrandColorOverrides = {
  primary: PRODUCT.colors.brand,
  primaryDark: PRODUCT.colors.brandDark,
  primaryLight: PRODUCT.colors.brand,
  secondary: PRODUCT.colors.cyan,
  info: PRODUCT.colors.brand,
  textMainLight: PRODUCT.colors.ink,
  textSubLight: PRODUCT.colors.muted,
  accentLight: PRODUCT.colors.mist,
  backgroundDark: PRODUCT.colors.navyDeep,
  surfaceDark: PRODUCT.colors.navy,
  accentDark: PRODUCT.colors.navyDeep,
};
