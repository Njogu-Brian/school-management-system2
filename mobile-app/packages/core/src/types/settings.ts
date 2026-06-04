export type SettingsSectionId = 'school' | 'academic' | 'grading' | 'roles';

export interface SchoolSettingsRecord {
  school_name: string;
  school_email: string | null;
  school_phone: string | null;
  school_address: string | null;
  timezone: string;
  currency: string;
  logo_url: string | null;
  login_background_url: string | null;
  colors: Record<string, string>;
  enabled_modules: string[];
  system_version: string | null;
}

export interface AcademicYearRecord {
  id: number;
  year: number;
  is_active: boolean;
  label: string;
}

export interface TermRecord {
  id: number;
  name: string;
  academic_year_id: number;
  academic_year: number | null;
  is_current: boolean;
  opening_date: string | null;
  closing_date: string | null;
  expected_school_days: number | null;
}

export interface SettingsClassroomRecord {
  id: number;
  name: string;
  level: string | null;
  code: string | null;
}

export interface SettingsStreamRecord {
  id: number;
  name: string;
  classroom_id: number;
}

export interface SettingsSubjectRecord {
  id: number;
  name: string;
  code: string | null;
  learning_area: string | null;
  is_active: boolean;
  is_optional: boolean;
}

export interface GradingBandRecord {
  id: number;
  min: number | null;
  max: number | null;
  label: string | null;
  descriptor: string | null;
  rank: number | null;
}

export interface GradingSchemeRecord {
  id: number;
  name: string;
  type: string | null;
  is_default: boolean;
  bands: GradingBandRecord[];
}

export interface ExamTypeRecord {
  id: number;
  name: string;
  code: string | null;
  default_min_mark: number | null;
  default_max_mark: number | null;
}

export interface GradingSettingsRecord {
  schemes: GradingSchemeRecord[];
  exam_types: ExamTypeRecord[];
}

export interface RoleSettingsRecord {
  id: number;
  name: string;
  guard_name: string;
  permissions_count: number;
  permissions: string[];
}
