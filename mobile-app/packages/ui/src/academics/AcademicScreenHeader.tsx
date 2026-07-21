import { ScreenHeader } from '../layout/ScreenHeader';
import React from 'react';

export interface AcademicScreenHeaderProps {
  title: string;
  subtitle?: string;
  onBack?: () => void;
}

/** Thin domain wrapper — V3 shared ScreenHeader. */
export const AcademicScreenHeader: React.FC<AcademicScreenHeaderProps> = (props) => (
  <ScreenHeader {...props} />
);
