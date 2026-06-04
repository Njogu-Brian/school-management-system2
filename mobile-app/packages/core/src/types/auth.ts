import type { User } from './user';

export interface LoginCredentials {
  /** Email or phone number (backend `identifier`). */
  identifier: string;
  password: string;
  /** Keep the session alive longer (idle policy) when true. */
  remember?: boolean;
}

export interface LoginResult {
  token: string;
  user: User;
  /** ISO8601 server token expiry, when provided. */
  expiresAt: string | null;
}

export type AuthStatus = 'initializing' | 'authenticated' | 'unauthenticated';

/** Which app a session is allowed into (drives the app-mismatch guard, build plan §5.1). */
export type AppTarget = 'admin' | 'staff';

/** Google account identity decoded from the ID token (stored in Auth Context). */
export interface GoogleIdentity {
  sub: string;
  email?: string;
  emailVerified?: boolean;
  name?: string;
  picture?: string;
}

export interface GoogleLoginRequest {
  id_token: string;
}

// --- Raw backend payloads (snake_case) ---------------------------------------

export interface ApiUser {
  id: number;
  name: string;
  email?: string | null;
  phone?: string | null;
  avatar?: string | null;
  role?: string | null;
  permissions?: string[];
  staff_id?: number;
  teacher_id?: number;
  parent_id?: number;
  student_id?: number;
  school_id?: number;
  branch_id?: number;
  branches?: Array<{
    id: number;
    name: string;
    code?: string | null;
    school_id?: number | null;
    is_active?: boolean;
  }>;
}

export interface ApiLoginData {
  token: string;
  user: ApiUser;
  expires_at?: string | null;
}
