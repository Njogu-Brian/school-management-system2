import { EmptyState } from '@erp/ui';
import React from 'react';

export const TrainingTab: React.FC = () => (
  <EmptyState
    title="Training & development"
    message="Professional development records are not exposed via the mobile API yet."
    icon="school-outline"
  />
);
