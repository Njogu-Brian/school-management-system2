import { EmptyState } from '@erp/ui';
import React from 'react';

export const DocumentsTab: React.FC = () => (
  <EmptyState
    title="Staff documents"
    message="HR document vault is web-only today. Mobile document listing will ship when staff document APIs are exposed."
    icon="folder-open-outline"
  />
);
