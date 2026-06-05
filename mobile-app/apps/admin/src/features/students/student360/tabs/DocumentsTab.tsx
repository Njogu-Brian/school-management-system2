import { EmptyState } from '@erp/ui';
import React from 'react';

/** No student documents API on mobile — placeholder until backend exposes read endpoints. */
export const DocumentsTab: React.FC = () => (
  <EmptyState
    title="Documents not available"
    message="Student document storage exists on the web ERP but has no mobile read API yet. Upload and retrieval will appear here when GET /students/{id}/documents is exposed."
    icon="document-text-outline"
  />
);
