/**
 * Resolved school from the control-plane registry (DB-per-school SaaS).
 */
export interface ResolvedSchoolBranding {
  logoUrl?: string | null;
  primaryColor?: string | null;
}

export interface ResolvedSchool {
  code: string;
  name: string;
  slug: string;
  /** Tenant ERP API base, e.g. https://erp.example.com/api */
  apiBaseUrl: string;
  status: string;
  branding: ResolvedSchoolBranding;
}

/**
 * @deprecated Prefer ResolvedSchool for SaaS flows. Kept for backlog E1 stubs.
 */
export interface School {
  id: number;
  name: string;
  slug?: string;
  logoUrl?: string | null;
}
