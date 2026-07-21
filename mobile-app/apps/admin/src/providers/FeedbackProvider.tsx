import { ConfirmDialog, useToast } from '@erp/ui';
import React, { useCallback, useEffect, useRef, useState } from 'react';
import { registerFeedbackHandlers, showError } from '../features/shared/utils/feedback';

type ConfirmState = {
  title: string;
  message: string;
  confirmLabel: string;
  destructive: boolean;
  onConfirm: () => void | Promise<void>;
} | null;

/**
 * Hosts branded ConfirmDialog + routes showSuccess/showError/confirmAction
 * through Toast / ConfirmDialog for the whole Admin app.
 */
export const FeedbackProvider: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { showToast } = useToast();
  const [confirm, setConfirm] = useState<ConfirmState>(null);
  const [loading, setLoading] = useState(false);
  const confirmRef = useRef<ConfirmState>(null);
  confirmRef.current = confirm;

  const handleConfirm = useCallback(async () => {
    const current = confirmRef.current;
    if (!current) return;
    setLoading(true);
    try {
      await Promise.resolve(current.onConfirm());
      setConfirm(null);
    } catch (err) {
      showError('Action failed', (err as Error).message);
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    registerFeedbackHandlers({
      showToast: ({ title, message, tone }) => {
        showToast({
          message: message ? `${title}: ${message}` : title,
          tone,
        });
      },
      showConfirm: (req) => setConfirm(req),
    });
    return () => registerFeedbackHandlers(null);
  }, [showToast]);

  return (
    <>
      {children}
      <ConfirmDialog
        visible={confirm != null}
        title={confirm?.title ?? ''}
        message={confirm?.message}
        confirmLabel={confirm?.confirmLabel ?? 'Confirm'}
        destructive={confirm?.destructive}
        loading={loading}
        onConfirm={() => void handleConfirm()}
        onCancel={() => setConfirm(null)}
      />
    </>
  );
};
