export function getErpBaseUrl(): string {
  const api = process.env.NEXT_PUBLIC_API_URL?.replace(/\/api\/?$/, "") || "https://erp.royalkingsschools.sc.ke";
  return api.replace(/\/$/, "");
}

/** Royal Kings Users app (parents, teachers, drivers) on Google Play. */
export function getUsersAppUrl(): string {
  return "https://play.google.com/store/apps/details?id=com.royalkingsschools.users";
}

/** ERP parent dashboard — unauthenticated users are sent to login, then returned here. */
export function getErpParentPortalUrl(): string {
  return `${getErpBaseUrl()}/parent/home`;
}

export function getErpLoginUrl(): string {
  return process.env.NEXT_PUBLIC_ERP_LOGIN_URL?.replace(/\/$/, "") || `${getErpBaseUrl()}/login`;
}
