import { apiClient } from './client';
import type { ApiResponse, ApiUser } from '../types';

export interface ProfileReviewParent {
  id: number;
  father_name: string | null;
  father_id_number: string | null;
  father_phone: string | null;
  father_email: string | null;
  mother_name: string | null;
  mother_id_number: string | null;
  mother_phone: string | null;
  mother_email: string | null;
  guardian_name: string | null;
  guardian_phone: string | null;
  guardian_relationship: string | null;
  marital_status: string | null;
}

export interface ProfileReviewStudent {
  id: number;
  admission_number: string | null;
  first_name: string | null;
  middle_name: string | null;
  last_name: string | null;
  gender: string | null;
  dob: string | null;
  class_name: string | null;
  has_allergies: boolean;
  allergies_notes: string | null;
  is_fully_immunized: boolean;
  residential_area: string | null;
  preferred_hospital: string | null;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
}

export interface ProfileReviewData {
  review_required: boolean;
  parent: ProfileReviewParent;
  students: ProfileReviewStudent[];
}

export interface ProfileReviewUpdatePayload {
  residential_area?: string | null;
  father_name?: string | null;
  father_id_number?: string | null;
  father_phone?: string | null;
  father_email?: string | null;
  mother_name?: string | null;
  mother_id_number?: string | null;
  mother_phone?: string | null;
  mother_email?: string | null;
  guardian_name?: string | null;
  guardian_phone?: string | null;
  guardian_relationship?: string | null;
  marital_status?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  preferred_hospital?: string | null;
  students?: Array<{
    id: number;
    first_name: string;
    middle_name?: string | null;
    last_name: string;
    gender?: string | null;
    dob?: string | null;
    has_allergies?: boolean;
    allergies_notes?: string | null;
    is_fully_immunized?: boolean;
  }>;
}

/**
 * Post-claim parent profile review (auth:sanctum, data only — no file uploads).
 * Backend: App\Http\Controllers\Api\ApiParentProfileReviewController.
 */
export const parentProfileReviewApi = {
  /** `GET /parent/profile-review`. */
  get(): Promise<ApiResponse<ProfileReviewData>> {
    return apiClient.get<ProfileReviewData>('/parent/profile-review');
  },

  /** `PUT /parent/profile-review`. */
  update(payload: ProfileReviewUpdatePayload): Promise<ApiResponse<null>> {
    return apiClient.put<null>('/parent/profile-review', payload);
  },

  /** `POST /parent/profile-review/complete` → refreshed user. */
  complete(): Promise<ApiResponse<ApiUser>> {
    return apiClient.post<ApiUser>('/parent/profile-review/complete');
  },
};
