import {
  clearSelectedChildId,
  invalidateQueriesForAppMode,
  useAppMode,
  useCurrentUser,
  type AppMode,
} from '@erp/core';
import { ModeSwitchControl } from '@erp/ui';
import { useQueryClient } from '@tanstack/react-query';
import React, { useCallback } from 'react';

/**
 * Work | Home segmented switch for dual-identity users.
 * Clears mode-scoped React Query caches and remounts the role shell via mode change.
 */
export const AppModeSwitch: React.FC<{
  style?: object;
  variant?: 'segmented' | 'banner';
  workLabel?: string;
  homeLabel?: string;
}> = ({ style, variant = 'segmented', workLabel = 'Work', homeLabel = 'Home' }) => {
  const { mode, canSwitch, setMode } = useAppMode();
  const user = useCurrentUser();
  const queryClient = useQueryClient();

  const switchMode = useCallback(
    async (next: AppMode) => {
      if (next === mode) return;
      invalidateQueriesForAppMode(queryClient, next);
      if (next === 'work' && user?.id) {
        await clearSelectedChildId(user.id);
      }
      await setMode(next);
    },
    [mode, queryClient, setMode, user?.id],
  );

  if (!canSwitch) {
    return null;
  }

  return (
    <ModeSwitchControl
      mode={mode}
      onChange={(next) => void switchMode(next)}
      workLabel={workLabel}
      homeLabel={homeLabel}
      variant={variant}
      style={style}
    />
  );
};
