import { apiClient } from './client';
import type {
  ApiLoginData,
  ApiResponse,
  ApiUser,
  GoogleLoginRequest,
  LoginCredentials,
} from '../types';

/** Authentication endpoints (Laravel Sanctum). */
export const authApi = {
  /** `POST /login` → `{ token, user, expires_at }`. */
  login(credentials: LoginCredentials): Promise<ApiResponse<ApiLoginData>> {
    return apiClient.post<ApiLoginData>('/login', credentials);
  },

  /** `POST /login/otp/request` — send SMS OTP for passwordless sign-in. */
  requestLoginOtp(identifier: string): Promise<ApiResponse<null>> {
    return apiClient.post<null>('/login/otp/request', { identifier });
  },

  /** `POST /login/otp/verify` → `{ token, user, expires_at }`. */
  verifyLoginOtp(identifier: string, code: string): Promise<ApiResponse<ApiLoginData>> {
    return apiClient.post<ApiLoginData>('/login/otp/verify', { identifier, code });
  },

  /** `POST /login/google` — exchange Google ID token for a Sanctum session. */
  loginWithGoogle(data: GoogleLoginRequest): Promise<ApiResponse<ApiLoginData>> {
    return apiClient.post<ApiLoginData>('/login/google', data);
  },

  /** `POST /logout` — revokes the current access token server-side. */
  logout(): Promise<ApiResponse<null>> {
    return apiClient.post<null>('/logout');
  },

  /** `GET /user` — uses the token currently in SecureStore. */
  getProfile(): Promise<ApiResponse<ApiUser>> {
    return apiClient.get<ApiUser>('/user');
  },

  /** `GET /user` with an explicit bearer token (biometric unlock before session write). */
  getProfileWithToken(token: string): Promise<ApiResponse<ApiUser>> {
    return apiClient.getWithToken<ApiUser>('/user', token);
  },
};
