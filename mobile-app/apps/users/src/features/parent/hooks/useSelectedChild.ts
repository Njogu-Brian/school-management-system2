import {
  getSelectedChildId,
  resolveSelectedChildId,
  setSelectedChildId,
  useCurrentUser,
} from '@erp/core';
import { useCallback, useEffect, useMemo, useState } from 'react';

type ChildLike = {
  id: number;
  fullName?: string;
  admissionNumber?: string | null;
  className?: string | null;
};

/**
 * Parent Home child selection — scoped to the authenticated user's accessible children only.
 */
export function useSelectedChild<T extends ChildLike>(children: T[]) {
  const user = useCurrentUser();
  const userId = user?.id;
  const availableIds = useMemo(() => children.map((c) => c.id), [children]);
  const [preferredId, setPreferredId] = useState<number | null>(null);
  const [ready, setReady] = useState(false);

  useEffect(() => {
    let cancelled = false;
    (async () => {
      if (!userId) {
        if (!cancelled) {
          setPreferredId(null);
          setReady(true);
        }
        return;
      }
      const stored = await getSelectedChildId(userId);
      if (!cancelled) {
        setPreferredId(stored);
        setReady(true);
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [userId]);

  const selectedId = useMemo(
    () => resolveSelectedChildId(availableIds, preferredId),
    [availableIds, preferredId],
  );

  const selectedChild = useMemo(
    () => children.find((c) => c.id === selectedId) ?? null,
    [children, selectedId],
  );

  const selectChild = useCallback(
    async (studentId: number) => {
      if (!availableIds.includes(studentId)) return;
      setPreferredId(studentId);
      if (userId) await setSelectedChildId(userId, studentId);
    },
    [availableIds, userId],
  );

  return {
    ready,
    selectedId,
    selectedChild,
    selectChild,
    availableIds,
  };
}
