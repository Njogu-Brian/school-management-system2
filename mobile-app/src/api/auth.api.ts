import { apiClient } from './client';
import {
    LoginCredentials,
    LoginResponse,
    ResetPasswordByEmailRequest,
    ResetPasswordByOTPRequest,
    VerifyOTPRequest,
    ResetPasswordRequest,
    User,
} from '@types/auth.types';
import { ApiResponse } from '@types/api.types';

export const authApi = {
    // Login
    async login(credentials: LoginCredentials): Promise<ApiResponse<LoginResponse>> {
        return apiClient.post<LoginResponse>('/login', credentials);
    },

    // Logout
    async logout(): Promise<ApiResponse<void>> {
        return apiClient.post<void>('/logout');
    },

    // Get current user
    async getProfile(): Promise<ApiResponse<User>> {
        return apiClient.get<User>('/user');
    },

    // Reset password by email
    async resetPasswordEmail(data: ResetPasswordByEmailRequest): Promise<ApiResponse<{ message: string }>> {
        return apiClient.post('/password/email', data);
    },

    // Reset password by OTP
    async resetPasswordOTP(data: ResetPasswordByOTPRequest): Promise<ApiResponse<{ message: string }>> {
        return apiClient.post('/password/otp', data);
    },

    // Verify OTP
    async verifyOTP(data: VerifyOTPRequest): Promise<ApiResponse<{ token: string }>> {
        return apiClient.post('/password/verify-otp', data);
    },

    // Reset password
    async resetPassword(data: ResetPasswordRequest): Promise<ApiResponse<{ message: string }>> {
        return apiClient.post('/password/reset', data);
    },
};
