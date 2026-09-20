import { apiClient } from './client';
import type { ApiResponse, ApiUser } from '../types';

export interface ParentIdentityChild {
  id: number;
  admission_number?: string | null;
  first_name: string;
  last_name: string;
  dob: string;
  needs_name: boolean;
  needs_dob: boolean;
  required: boolean;
}

export interface ParentIdentityGate {
  required: boolean;
  slot: 'father' | 'mother' | 'guardian' | null;
  slot_label: string | null;
  parent: { name: string; phone: string };
  children: ParentIdentityChild[];
  missing: {
    parent_name: boolean;
    parent_phone: boolean;
    child_details: boolean;
  };
}

export interface ParentIdentityUpdatePayload {
  name: string;
  phone: string;
  children?: Array<{
    id: number;
    first_name: string;
    last_name: string;
    dob: string;
  }>;
}

export const parentIdentityGateApi = {
  get(): Promise<ApiResponse<ParentIdentityGate>> {
    return apiClient.get<ParentIdentityGate>('/parent/identity-gate');
  },

  update(payload: ParentIdentityUpdatePayload): Promise<
    ApiResponse<{ gate: ParentIdentityGate; user: ApiUser }>
  > {
    return apiClient.put('/parent/identity-gate', payload);
  },
};
