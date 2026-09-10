import type { DiaryChannel } from '@erp/core';

export function diaryChannelCopy(
  channel: DiaryChannel,
  viewer: 'parent' | 'staff',
): { label: string; hint: string } {
  if (viewer === 'parent') {
    if (channel === 'admin_parent') {
      return {
        label: 'School office',
        hint: 'Messages go to the school office.',
      };
    }
    return {
      label: 'Class teacher',
      hint: 'Messages go to your class teacher.',
    };
  }

  if (channel === 'admin_parent') {
    return {
      label: 'Admin only',
      hint: 'Only school administrators see this thread.',
    };
  }

  return {
    label: 'Teacher only',
    hint: 'This conversation is with the class teacher.',
  };
}
