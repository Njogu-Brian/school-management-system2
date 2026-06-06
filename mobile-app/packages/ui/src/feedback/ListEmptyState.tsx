import React from 'react';
import { EmptyState, type EmptyStateProps } from './EmptyState';

export interface ListEmptyStateProps extends Omit<EmptyStateProps, 'title'> {
  title?: string;
  entityName?: string;
  onClearFilters?: () => void;
}

/** Standard empty state for registry/list screens. */
export const ListEmptyState: React.FC<ListEmptyStateProps> = ({
  title,
  entityName = 'items',
  message,
  icon = 'file-tray-outline',
  onClearFilters,
  onAction,
  actionLabel,
  ...rest
}) => {
  const resolvedTitle = title ?? `No ${entityName} found`;
  const resolvedMessage = message ?? `No ${entityName} match your current filters.`;
  const resolvedAction = onClearFilters ?? onAction;
  const resolvedActionLabel = onClearFilters ? 'Clear filters' : actionLabel;

  return (
    <EmptyState
      title={resolvedTitle}
      message={resolvedMessage}
      icon={icon}
      actionLabel={resolvedActionLabel}
      onAction={resolvedAction}
      {...rest}
    />
  );
};

/** Celebration empty state when a work queue is clear. */
export const QueueEmptyState: React.FC<Omit<EmptyStateProps, 'icon'>> = (props) => (
  <EmptyState icon="checkmark-circle-outline" {...props} />
);
