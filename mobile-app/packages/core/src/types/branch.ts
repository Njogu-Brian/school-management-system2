/**
 * A branch/campus within a school. Branch scoping is a future backend deliverable
 * (Backlog E2); the active branch drives query scope from a later batch (build plan §7.4).
 */
export interface Branch {
  id: number;
  name: string;
  code?: string | null;
  schoolId?: number | null;
  isActive?: boolean;
}
