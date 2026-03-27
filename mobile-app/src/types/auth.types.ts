import { UserRole, Permission } from '@constants/roles';

// Auth types
export interface LoginCredentials {
    identifier: string;
    password: string;
    remember?: boolean;
}

export interface LoginResponse {
    token: string;
    user: User;
}

export interface User {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    permissions: Permission[];
    avatar?: string;
    phone?: string;
    // Role-specific data
    teacher_id?: number;
    student_id?: number;
    parent_id?: number;
    staff_id?: number;
}

export interface ResetPasswordByEmailRequest {
    identifier: string;
}

export interface ResetPasswordByOTPRequest {
    identifier: string;
}

export interface VerifyOTPRequest {
    identifier: string;
    code: string;
}

export interface ResetPasswordRequest {
    identifier: string;
    token: string;
    password: string;
    password_confirmation: string;
}

export interface AuthState {
    isAuthenticated: boolean;
    user: User | null;
    token: string | null;
    loading: boolean;
    error: string | null;
}
