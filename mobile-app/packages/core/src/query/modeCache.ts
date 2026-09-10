import type { QueryClient } from '@tanstack/react-query';
import type { AppMode } from '../storage/appModeStorage';
import { queryKeys } from './queryKeys';

/**
 * Drop mode-sensitive cached data when switching Home ↔ Work so child finance/attendance
 * cannot appear on work screens and class marks cannot appear on parent screens.
 */
export function invalidateQueriesForAppMode(queryClient: QueryClient, _nextMode: AppMode): void {
  void queryClient.cancelQueries();
  void queryClient.removeQueries({ queryKey: queryKeys.students.all });
  void queryClient.removeQueries({ queryKey: queryKeys.finance.all });
  void queryClient.removeQueries({ queryKey: queryKeys.academics.all });
  void queryClient.removeQueries({ queryKey: queryKeys.notifications.all });
  void queryClient.removeQueries({ queryKey: queryKeys.staff.all });
  void queryClient.removeQueries({ queryKey: queryKeys.staffClock.all });
  void queryClient.removeQueries({ queryKey: queryKeys.teacherTransport.all });
  void queryClient.removeQueries({ queryKey: queryKeys.coCurricular.all });
  void queryClient.removeQueries({ queryKey: ['diaries'] });
  void queryClient.removeQueries({ queryKey: ['homework'] });
  void queryClient.removeQueries({ queryKey: ['assignments'] });
  void queryClient.removeQueries({ queryKey: ['parent-wallet'] });
  void queryClient.removeQueries({ queryKey: ['parent-transport-options'] });
  void queryClient.removeQueries({ queryKey: ['transport-special'] });
  void queryClient.removeQueries({ queryKey: ['attendance'] });
  void queryClient.removeQueries({ queryKey: ['parent'] });
  void queryClient.removeQueries({ queryKey: ['lesson-plans'] });
}
