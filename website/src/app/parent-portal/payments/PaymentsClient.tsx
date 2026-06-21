"use client";

import { useEffect, useState } from "react";
import { useRouter, useSearchParams } from "next/navigation";
import { SiteShell } from "@/components/layout/SiteShell";
import { getParentToken, parentService } from "@/services/parentService";

export default function PaymentsClient() {
  const router = useRouter();
  const params = useSearchParams();
  const studentId = Number(params.get("student"));
  const [phone, setPhone] = useState("");
  const [amount, setAmount] = useState("");
  const [summary, setSummary] = useState<{
    outstanding?: number;
    payment_options?: { methods?: { id: string; label: string; url?: string }[] };
  } | null>(null);
  const [message, setMessage] = useState("");

  useEffect(() => {
    if (!getParentToken()) {
      router.replace("/parent-portal");
      return;
    }
    if (studentId) {
      parentService.paymentSummary(studentId).then((r) => setSummary(r.data || r));
    }
  }, [router, studentId]);

  const payMpesa = async () => {
    if (!studentId) return;
    const res = await parentService.mpesaPay(studentId, phone, Number(amount));
    setMessage(res.message || (res.success ? "STK sent" : "Payment failed"));
  };

  const requestPlan = async () => {
    if (!studentId) return;
    await parentService.requestPaymentPlan(studentId, { installment_count: 3, reason: "Parent portal request" });
    setMessage("Payment plan request submitted for finance review.");
  };

  return (
    <SiteShell>
      <div className="mx-auto max-w-xl px-4 py-16">
        <h1 className="font-serif text-3xl text-[#2a1145]">Pay School Fees</h1>
        {!studentId && <p className="mt-4 text-[#4a3a5c]">Open from a child profile in Parent Portal.</p>}
        {summary && (
          <p className="mt-4 text-lg">
            Outstanding: <strong>KES {summary.outstanding?.toLocaleString()}</strong>
          </p>
        )}
        <div className="mt-6 space-y-3">
          <input value={phone} onChange={(e) => setPhone(e.target.value)} placeholder="M-Pesa phone 07..." className="w-full rounded-lg border px-3 py-2" />
          <input value={amount} onChange={(e) => setAmount(e.target.value)} placeholder="Amount (KES)" type="number" className="w-full rounded-lg border px-3 py-2" />
          <button type="button" onClick={payMpesa} className="w-full rounded-full bg-[#5B2C8E] py-2 text-white">
            Pay with M-Pesa
          </button>
          <button type="button" onClick={requestPlan} className="w-full rounded-full border border-[#5B2C8E] py-2 text-[#5B2C8E]">
            Request installment plan
          </button>
        </div>
        {summary?.payment_options?.methods && (
          <div className="mt-6 space-y-2 text-sm">
            <p className="font-medium text-[#5B2C8E]">Payment methods</p>
            {summary.payment_options.methods.map((m) => (
              <div key={m.id} className="rounded-lg border p-3">
                <strong>{m.label}</strong>
                {m.url && (
                  <a href={m.url} target="_blank" rel="noreferrer" className="ml-2 text-[#5B2C8E] underline">
                    Open link
                  </a>
                )}
              </div>
            ))}
          </div>
        )}
        {message && <p className="mt-4 text-sm text-[#4a3a5c]">{message}</p>}
      </div>
    </SiteShell>
  );
}
