import axios from 'axios';
import { CONTROL_PLANE_BASE_URL, API_TIMEOUT_MS } from '../config/env';
import type { ResolvedSchool } from '../types';

type ResolveApiPayload = {
  code: string;
  name: string;
  slug: string;
  api_base_url: string;
  status: string;
  branding?: {
    logo_url?: string | null;
    primary_color?: string | null;
  };
  message?: string;
};

function mapResolved(raw: ResolveApiPayload): ResolvedSchool {
  return {
    code: raw.code,
    name: raw.name,
    slug: raw.slug,
    apiBaseUrl: raw.api_base_url.replace(/\/$/, ''),
    status: raw.status,
    branding: {
      logoUrl: raw.branding?.logo_url ?? null,
      primaryColor: raw.branding?.primary_color ?? null,
    },
  };
}

/**
 * Resolve a school code via the control plane (not the selected tenant API).
 */
export async function resolveSchoolCode(code: string): Promise<ResolvedSchool> {
  const trimmed = code.trim();
  if (trimmed.length < 3) {
    throw { message: 'Enter a valid school code.', status: 422 };
  }

  try {
    const res = await axios.get<ResolveApiPayload>(`${CONTROL_PLANE_BASE_URL.replace(/\/$/, '')}/schools/resolve`, {
      params: { code: trimmed },
      timeout: Number.isFinite(API_TIMEOUT_MS) ? API_TIMEOUT_MS : 30000,
      headers: {
        Accept: 'application/json',
        'Content-Type': 'application/json',
      },
    });
    return mapResolved(res.data);
  } catch (err: unknown) {
    const ax = err as {
      response?: { status?: number; data?: { message?: string; status?: string } };
      message?: string;
    };
    const message =
      ax.response?.data?.message ||
      ax.message ||
      'Could not look up that school code. Check your connection and try again.';
    throw {
      message,
      status: ax.response?.status,
    };
  }
}

export const schoolsApi = {
  resolve: resolveSchoolCode,
};
