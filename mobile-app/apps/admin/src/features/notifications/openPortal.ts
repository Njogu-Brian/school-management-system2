import { apiClient } from '@erp/core';
import { Linking } from 'react-native';

type ExamMarkAlertInput = {
  deep_link?: string | null;
  metadata?: Record<string, unknown> | null;
  data?: Record<string, unknown> | null;
};

/** The mark-change review screen lives on the portal. The Android app does not have that screen yet. */
export function isExamMarkChangeAlert(input: ExamMarkAlertInput): boolean {
  const nested = input.data?.metadata;
  const meta = input.metadata ?? (nested && typeof nested === 'object' ? (nested as Record<string, unknown>) : null);
  if (meta?.kind === 'exam_mark_change_digest') return true;

  const fromData = input.data?.deep_link;
  const link = input.deep_link ?? (typeof fromData === 'string' ? fromData : '');
  return link.includes('exam-marks/changes');
}

export function openExamMarkChangesInPortal(deepLink?: string | null): void {
  const path =
    deepLink && deepLink.includes('exam-marks/changes') ? deepLink : '/academics/exam-marks/changes';
  if (path.startsWith('http')) {
    void Linking.openURL(path);
    return;
  }

  const origin = apiClient.getBaseURL().replace(/\/api(?:\/v\d+)?\/?$/i, '');
  const url = `${origin}${path.startsWith('/') ? path : `/${path}`}`;
  void Linking.openURL(url);
}
