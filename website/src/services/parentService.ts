import { api } from "@/lib/api";

const TOKEN_KEY = "rk_parent_token";

export function getParentToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(TOKEN_KEY);
}

export function setParentToken(token: string) {
  localStorage.setItem(TOKEN_KEY, token);
}

export function clearParentToken() {
  localStorage.removeItem(TOKEN_KEY);
}

function authHeaders() {
  const token = getParentToken();
  return token ? { Authorization: `Bearer ${token}` } : {};
}

export const parentService = {
  login: async (email: string, password: string) => {
    const { data } = await api.post("/login", { email, password });
    const token = data.data?.token || data.token;
    if (token) setParentToken(token);
    return data;
  },

  logout: async () => {
    try {
      await api.post("/logout", {}, { headers: authHeaders() });
    } finally {
      clearParentToken();
    }
  },

  dashboard: () => api.get("/website/parent/dashboard", { headers: authHeaders() }).then((r) => r.data),
  children: () => api.get("/website/parent/children", { headers: authHeaders() }).then((r) => r.data),
  child: (id: number) => api.get(`/website/parent/children/${id}`, { headers: authHeaders() }).then((r) => r.data),
  statement: (id: number, year?: number) =>
    api.get(`/website/parent/children/${id}/statement`, { headers: authHeaders(), params: { year } }).then((r) => r.data),
  attendance: (id: number, year?: number, month?: number) =>
    api.get(`/website/parent/children/${id}/attendance`, { headers: authHeaders(), params: { year, month } }).then((r) => r.data),
  reportCards: (studentId?: number) =>
    api.get("/website/parent/report-cards", { headers: authHeaders(), params: { student_id: studentId } }).then((r) => r.data),
  announcements: () => api.get("/website/parent/announcements", { headers: authHeaders() }).then((r) => r.data),

  homework: (studentId: number) =>
    api.get(`/website/parent/children/${studentId}/homework`, { headers: authHeaders() }).then((r) => r.data),

  paymentSummary: (studentId: number) =>
    api.get(`/website/parent/children/${studentId}/payments/summary`, { headers: authHeaders() }).then((r) => r.data),

  mpesaPay: (studentId: number, phone: string, amount: number) =>
    api.post(`/website/parent/children/${studentId}/payments/mpesa`, { phone_number: phone, amount }, { headers: authHeaders() }).then((r) => r.data),

  paymentLink: (studentId: number) =>
    api.get(`/website/parent/children/${studentId}/payments/link`, { headers: authHeaders() }).then((r) => r.data),

  paymentOptions: (studentId: number) =>
    api.get(`/website/parent/children/${studentId}/payments/options`, { headers: authHeaders() }).then((r) => r.data),

  requestPaymentPlan: (studentId: number, payload: { installment_count: number; reason?: string; requested_amount?: number }) =>
    api.post(`/website/parent/children/${studentId}/payments/plan-request`, payload, { headers: authHeaders() }).then((r) => r.data),

  receipts: (studentId: number) =>
    api.get(`/website/parent/children/${studentId}/payments/receipts`, { headers: authHeaders() }).then((r) => r.data),
};
