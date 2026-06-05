import { EmptyState } from '@erp/ui';
import React from 'react';

export const PerformanceTab: React.FC = () => (
  <EmptyState
    title="Performance reviews"
    message="Staff performance appraisals are managed on the web ERP. A mobile read API is not available yet."
    icon="trophy-outline"
  />
);
