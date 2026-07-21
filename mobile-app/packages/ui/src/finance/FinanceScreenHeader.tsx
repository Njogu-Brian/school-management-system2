import { ScreenHeader } from '../layout/ScreenHeader';
import React from 'react';

export interface FinanceScreenHeaderProps {
  title: string;
  subtitle?: string;
  onBack?: () => void;
}

/** Thin domain wrapper — V3 shared ScreenHeader. */
export const FinanceScreenHeader: React.FC<FinanceScreenHeaderProps> = (props) => (
  <ScreenHeader {...props} />
);
