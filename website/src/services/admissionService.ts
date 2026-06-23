import { api } from "@/lib/api";

export interface AdmissionApplication {
  application_no: string;
  draft_token?: string;
  status: string;
  current_step: number;
  parent_name?: string;
  child_name?: string;
}

export const admissionService = {
  options: () =>
    api
      .get<{ data: { classrooms: Array<{ id: number; name: string }>; enrollment_terms: Array<{ year: number; term: number; label: string }> } }>(
        "/website/admissions/options"
      )
      .then((r) => r.data.data),

  start: () =>
    api.post<{ data: AdmissionApplication }>("/website/admissions/start").then((r) => r.data.data),

  saveStep: (token: string, step: number, data: Record<string, unknown>) =>
    api.post(`/website/admissions/${token}/step`, { step, data }).then((r) => r.data.data),

  uploadDocument: (token: string, documentType: string, file: File) => {
    const form = new FormData();
    form.append("document_type", documentType);
    form.append("file", file);
    return api.post(`/website/admissions/${token}/documents`, form, {
      headers: { "Content-Type": "multipart/form-data" },
    });
  },

  submit: (token: string, payload: Record<string, string | number>) =>
    api.post(`/website/admissions/${token}/submit`, payload).then((r) => r.data),

  track: (applicationNo: string) =>
    api.get<{ data: AdmissionApplication }>(`/website/admissions/track/${applicationNo}`).then((r) => r.data.data),
};
