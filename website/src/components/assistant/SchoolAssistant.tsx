"use client";

import { useState } from "react";
import { enterpriseService } from "@/services/enterpriseService";

export function SchoolAssistant() {
  const [open, setOpen] = useState(false);
  const [sessionKey, setSessionKey] = useState<string | undefined>();
  const [input, setInput] = useState("");
  const [messages, setMessages] = useState<{ role: "user" | "assistant"; text: string }[]>([]);
  const [loading, setLoading] = useState(false);

  const send = async () => {
    if (!input.trim() || loading) return;
    const question = input.trim();
    setInput("");
    setMessages((m) => [...m, { role: "user", text: question }]);
    setLoading(true);
    try {
      const res = await enterpriseService.assistantChat(question, sessionKey);
      const data = res.data;
      if (data?.session_key) setSessionKey(data.session_key);
      setMessages((m) => [...m, { role: "assistant", text: data?.reply || "How can we help you today?" }]);
    } catch {
      setMessages((m) => [...m, { role: "assistant", text: "Sorry — please try again or contact our office." }]);
    } finally {
      setLoading(false);
    }
  };

  return (
    <>
      <button
        type="button"
        onClick={() => setOpen((o) => !o)}
        className="fixed bottom-24 right-4 z-50 flex h-14 w-14 items-center justify-center rounded-full bg-[#5B2C8E] text-white shadow-lg hover:bg-[#4a2475]"
        aria-label="School assistant"
      >
        💬
      </button>
      {open && (
        <div className="fixed bottom-40 right-4 z-50 flex h-[420px] w-[min(100vw-2rem,360px)] flex-col overflow-hidden rounded-2xl border border-[#D4AF37]/30 bg-white shadow-2xl">
          <div className="bg-[#5B2C8E] px-4 py-3 text-white">
            <strong className="font-serif">Royal Kings Assistant</strong>
            <p className="text-xs text-white/80">Admissions, fees, transport & more</p>
          </div>
          <div className="flex-1 space-y-2 overflow-y-auto p-3 text-sm">
            {messages.length === 0 && (
              <p className="text-[#4a3a5c]">Ask about admissions, school fees, transport, or the calendar.</p>
            )}
            {messages.map((m, i) => (
              <div key={i} className={`rounded-xl px-3 py-2 ${m.role === "user" ? "ml-8 bg-[#f3ecfa] text-[#2a1145]" : "mr-8 bg-[#faf6ef] text-[#2a1145]"}`}>
                {m.text}
              </div>
            ))}
          </div>
          <div className="flex gap-2 border-t p-3">
            <input
              value={input}
              onChange={(e) => setInput(e.target.value)}
              onKeyDown={(e) => e.key === "Enter" && send()}
              placeholder="Type your question..."
              className="flex-1 rounded-full border border-[#e0d4f0] px-3 py-2 text-sm outline-none focus:border-[#5B2C8E]"
            />
            <button type="button" onClick={send} disabled={loading} className="rounded-full bg-[#D4AF37] px-4 py-2 text-sm font-medium text-[#2a1145] disabled:opacity-50">
              Send
            </button>
          </div>
        </div>
      )}
    </>
  );
}
