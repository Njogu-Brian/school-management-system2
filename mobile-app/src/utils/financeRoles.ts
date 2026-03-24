import { UserRole } from '@constants/roles';
import type { User } from '@types/auth.types';

/** Matches Laravel finance M-Pesa permissions (ApiMpesaPaymentController). */
export function canUseMpesaFinanceTools(user: User | null): boolean {
    if (!user) return false;
    return [
        UserRole.SUPER_ADMIN,
        UserRole.ADMIN,
        UserRole.SECRETARY,
        UserRole.ACCOUNTANT,
        UserRole.FINANCE,
    ].includes(user.role);
}
