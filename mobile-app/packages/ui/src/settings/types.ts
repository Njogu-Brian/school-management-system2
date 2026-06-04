export type SettingsSectionId = 'school' | 'academic' | 'grading' | 'roles';

export interface SettingsSectionTab {
  id: SettingsSectionId;
  label: string;
  icon: string;
}

export interface SettingCardData {
  id?: string;
  label: string;
  value: string;
  hint?: string;
}
