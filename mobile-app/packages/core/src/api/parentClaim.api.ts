import { apiClient } from './client';
import type { ApiLoginData, ApiResponse } from '../types';

export type ClaimChannel = 'phone' | 'email';

export interface ClaimOtpVerifyData {
  claim_token: string;
  expires_in: number;
}

export interface ClaimChild {
  id: number;
  first_name_masked: string;
  class_name: string | null;
  admission_number: string | null;
}

export interface ClaimAdmissionData {
  children: ClaimChild[];
  parent_info_id: number;
}

/**
 * First-time parent account claim (public, no auth).
 * Backend: App\Http\Controllers\Api\ApiParentClaimController.
 */
export const parentClaimApi = {
  /** `POST /parent-claim/otp/request` — send OTP to phone or email. */
  requestOtp(channel: ClaimChannel, identifier: string): Promise<ApiResponse<null>> {
    return apiClient.post<null>('/parent-claim/otp/request', {
      channel,
      identifier: identifier.trim(),
    });
  },

  /** `POST /parent-claim/otp/verify` → `{ claim_token }`. */
  verifyOtp(
    channel: ClaimChannel,
    identifier: string,
    code: string,
  ): Promise<ApiResponse<ClaimOtpVerifyData>> {
    return apiClient.post<ClaimOtpVerifyData>('/parent-claim/otp/verify', {
      channel,
      identifier: identifier.trim(),
      code: code.trim(),
    });
  },

  /** `POST /parent-claim/verify-admission` → `{ children, parent_info_id }`. */
  verifyAdmission(
    claimToken: string,
    admissionNumber: string,
  ): Promise<ApiResponse<ClaimAdmissionData>> {
    return apiClient.post<ClaimAdmissionData>('/parent-claim/verify-admission', {
      claim_token: claimToken,
      admission_number: admissionNumber.trim(),
    });
  },

  /** `POST /parent-claim/complete` → `{ token, user, expires_at }` (like login). */
  complete(payload: {
    claimToken: string;
    name: string;
    password: string;
    passwordConfirmation: string;
    email?: string;
  }): Promise<ApiResponse<ApiLoginData>> {
    return apiClient.post<ApiLoginData>('/parent-claim/complete', {
      claim_token: payload.claimToken,
      name: payload.name.trim(),
      password: payload.password,
      password_confirmation: payload.passwordConfirmation,
      email: payload.email?.trim() || undefined,
    });
  },
};
