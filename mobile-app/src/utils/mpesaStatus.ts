/**
 * Poll public M-Pesa transaction status (same JSON as web /pay/transaction/{id}/status).
 */
import { API_BASE_URL, getWebBaseUrl } from '@utils/env';

export type MpesaPollStatus = {
    status?: string;
    message?: string;
    failure_reason?: string;
    receipt_number?: string;
};

const webOrigin = new URL(getWebBaseUrl()).origin;
const apiOrigin = new URL(API_BASE_URL).origin;
const allowedOrigins = new Set([webOrigin, apiOrigin]);

function isAllowedProtocol(protocol: string): boolean {
    if (protocol === 'https:') {
        return true;
    }
    return __DEV__ && protocol === 'http:';
}

export function isTrustedMpesaUrl(rawUrl: string): boolean {
    try {
        const parsed = new URL(rawUrl);
        return isAllowedProtocol(parsed.protocol) && allowedOrigins.has(parsed.origin);
    } catch {
        return false;
    }
}

export async function fetchMpesaTransactionStatus(statusPollUrl: string): Promise<MpesaPollStatus> {
    if (!isTrustedMpesaUrl(statusPollUrl)) {
        throw new Error('Untrusted status URL');
    }

    const res = await fetch(statusPollUrl, { headers: { Accept: 'application/json' } });
    if (!res.ok) {
        throw new Error(`Status check failed (${res.status})`);
    }
    return res.json() as Promise<MpesaPollStatus>;
}
