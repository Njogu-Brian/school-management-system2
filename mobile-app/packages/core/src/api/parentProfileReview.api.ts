import { apiClient } from './client';
import type { ApiResponse, ApiUser } from '../types';
import type { KemisLearnerValues } from '../types/kemis';

export interface ProfileReviewParent {
  id: number;
  father_name: string | null;
  father_first_name: string | null;
  father_middle_name: string | null;
  father_last_name: string | null;
  father_id_type: string | null;
  father_id_number: string | null;
  father_country_of_residence: string | null;
  father_phone: string | null;
  father_whatsapp: string | null;
  father_email: string | null;
  mother_name: string | null;
  mother_first_name: string | null;
  mother_middle_name: string | null;
  mother_last_name: string | null;
  mother_id_type: string | null;
  mother_id_number: string | null;
  mother_country_of_residence: string | null;
  mother_phone: string | null;
  mother_whatsapp: string | null;
  mother_email: string | null;
  guardian_name: string | null;
  guardian_first_name: string | null;
  guardian_middle_name: string | null;
  guardian_last_name: string | null;
  guardian_id_type: string | null;
  guardian_id_number: string | null;
  guardian_country_of_residence: string | null;
  guardian_phone: string | null;
  guardian_whatsapp: string | null;
  guardian_relationship: string | null;
  guardian_email: string | null;
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
  nationality: string | null;
  county_of_birth: string | null;
  sub_county_of_birth: string | null;
  location_of_birth: string | null;
  birth_certificate_entry_no: string | null;
  medical_condition: string | null;
  religion: string | null;
  learner_interests: string[] | null;
  orphan_status: string | null;
  has_special_needs: boolean;
  disability_type: string | null;
}

export interface ProfileReviewData {
  review_required: boolean;
  parent: ProfileReviewParent;
  students: ProfileReviewStudent[];
  documents?: Array<{
    id: number;
    title: string | null;
    category: string | null;
    file_name: string | null;
    download_path: string;
  }>;
}

export interface ProfileReviewUpdatePayload {
  residential_area?: string | null;
  preferred_hospital?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  father_first_name?: string | null;
  father_middle_name?: string | null;
  father_last_name?: string | null;
  father_id_type?: string | null;
  father_id_number?: string | null;
  father_country_of_residence?: string | null;
  father_phone?: string | null;
  father_whatsapp?: string | null;
  father_email?: string | null;
  mother_first_name?: string | null;
  mother_middle_name?: string | null;
  mother_last_name?: string | null;
  mother_id_type?: string | null;
  mother_id_number?: string | null;
  mother_country_of_residence?: string | null;
  mother_phone?: string | null;
  mother_whatsapp?: string | null;
  mother_email?: string | null;
  guardian_first_name?: string | null;
  guardian_middle_name?: string | null;
  guardian_last_name?: string | null;
  guardian_id_type?: string | null;
  guardian_id_number?: string | null;
  guardian_country_of_residence?: string | null;
  guardian_phone?: string | null;
  guardian_whatsapp?: string | null;
  guardian_email?: string | null;
  guardian_relationship?: string | null;
  marital_status?: string | null;
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
  } & Partial<KemisLearnerValues>>;
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
