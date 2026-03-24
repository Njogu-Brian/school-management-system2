/**
 * Poll public M-Pesa transaction status (same JSON as web /pay/transaction/{id}/status).
 */
export type MpesaPollStatus = {
    status?: string;
    message?: string;
    failure_reason?: string;
    receipt_number?: string;
};

export async function fetchMpesaTransactionStatus(statusPollUrl: string): Promise<MpesaPollStatus> {
    const res = await fetch(statusPollUrl, { headers: { Accept: 'application/json' } });
    if (!res.ok) {
        throw new Error(`Status check failed (${res.status})`);
    }
    return res.json() as Promise<MpesaPollStatus>;
}
