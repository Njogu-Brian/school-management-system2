/**
 * Shared param shape for the Raise Concern flow, reused across every role's stack
 * (Teacher, Parent, Driver, Student) so the screen doesn't depend on any one
 * role's `*StackParamList`.
 */
export type ConcernsSharedParamList = {
  RaiseConcern: { studentId?: number } | undefined;
  ConcernsList: undefined;
};
