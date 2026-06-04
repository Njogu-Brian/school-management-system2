/**
 * A tenant (school / school group). Multi-tenancy is a future backend deliverable
 * (Backlog E1); modeled here so the client is ready and scope can be threaded through
 * later batches without reshaping types.
 */
export interface School {
  id: number;
  name: string;
  slug?: string;
  logoUrl?: string | null;
}
