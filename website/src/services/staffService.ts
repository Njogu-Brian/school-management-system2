import { api } from "@/lib/api";
import { getParentToken } from "./parentService";

const staffTokenKey = "rk_staff_token";

export function getStaffToken(): string | null {
  if (typeof window === "undefined") return null;
  return localStorage.getItem(staffTokenKey);
}

export function setStaffToken(token: string) {
  localStorage.setItem(staffTokenKey, token);
}

function authHeaders() {
  const token = getStaffToken();
  return token ? { Authorization: `Bearer ${token}` } : {};
}

export const staffService = {
  login: async (email: string, password: string) => {
    const { data } = await api.post("/login", { email, password });
    const token = data.data?.token || data.token;
    if (token) setStaffToken(token);
    return data;
  },

  dashboard: () => api.get("/website/staff/dashboard", { headers: authHeaders() }).then((r) => r.data),
  timetable: () => api.get("/website/staff/timetable", { headers: authHeaders() }).then((r) => r.data),
  lessonPlans: () => api.get("/website/staff/lesson-plans", { headers: authHeaders() }).then((r) => r.data),
  announcements: () => api.get("/website/staff/announcements", { headers: authHeaders() }).then((r) => r.data),
  clockToday: () => api.get("/website/staff/clock/today", { headers: authHeaders() }).then((r) => r.data),
  classAttendance: (params: { date: string; class_id: number; stream_id?: number }) =>
    api.get("/website/staff/attendance/class", { headers: authHeaders(), params }).then((r) => r.data),
};

// Re-export parent token helper for shared login component patterns
export { getParentToken };
