export type ApplicationStatus =
  | 'pending'
  | 'under_review'
  | 'waitlisted'
  | 'enrolled'
  | 'rejected';

export interface AdmissionsStatsRecord {
  pending: number;
  under_review: number;
  waitlisted: number;
  enrolled: number;
  rejected: number;
  total: number;
}

export interface ApplicationListRecord {
  id: number;
  first_name: string;
  middle_name: string | null;
  last_name: string;
  full_name: string;
  dob: string | null;
  gender: string | null;
  application_status: ApplicationStatus;
  application_date: string | null;
  application_source: string | null;
  enrolled: boolean;
  waitlist_position: number | null;
  preferred_classroom_id: number | null;
  preferred_class_name: string | null;
  classroom_id: number | null;
  class_name: string | null;
  stream_id: number | null;
  stream_name: string | null;
  passport_photo_url: string | null;
  reviewed_by_name: string | null;
  review_date: string | null;
}

export interface ApplicationParentRecord {
  name: string | null;
  phone: string | null;
  phone_country_code: string | null;
  email: string | null;
  id_number: string | null;
  relationship: string | null;
}

export interface ApplicationDocumentRecord {
  field: string;
  label: string;
  uploaded: boolean;
  view_url: string | null;
  download_path: string | null;
  is_private: boolean;
}

export interface ApplicationTimelineRecord {
  id: string;
  type: string;
  title: string;
  description: string | null;
  occurred_on: string | null;
}

export interface EnrollmentTermOption {
  year: number;
  term: number;
  label: string;
}

export interface ApplicationEnrollmentRecord {
  enrolled: boolean;
  application_status: ApplicationStatus;
  can_enroll: boolean;
  preferred_classroom_id: number | null;
  preferred_class_name: string | null;
  classroom_id: number | null;
  class_name: string | null;
  stream_id: number | null;
  stream_name: string | null;
  transport_needed: boolean;
  drop_off_point_id: number | null;
  drop_off_point_other: string | null;
  trip_id: number | null;
  enrollment_term_options: EnrollmentTermOption[];
  student_categories: Array<{ id: number; name: string }>;
  classrooms: Array<{ id: number; name: string }>;
  drop_off_points: Array<{ id: number; name: string }>;
  trips: Array<{ id: number; name: string }>;
}

export interface EnrollApplicationPayload {
  classroom_id: number;
  stream_id?: number | null;
  category_id: number;
  trip_id?: number | null;
  drop_off_point_id?: number | null;
  drop_off_point_other?: string | null;
  transport_fee_amount?: number | null;
  residential_area: string;
  preferred_hospital?: string | null;
  enrollment_year?: number;
  enrollment_term?: number;
  admission_date?: string;
  has_allergies?: boolean;
  allergies_notes?: string | null;
  is_fully_immunized?: boolean;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  marital_status?: string | null;
}

export interface EnrolledStudentRecord {
  id: number;
  admission_number: string;
  full_name: string;
  class_name: string | null;
  stream_name: string | null;
  classroom_id: number | null;
  stream_id: number | null;
  gender: string | null;
  status: string;
  photo_url: string | null;
}

export interface EnrollApplicationResult {
  student: EnrolledStudentRecord;
  application: ApplicationDetailRecord;
}

export interface UpdateApplicationStatusPayload {
  application_status: ApplicationStatus;
  review_notes?: string | null;
  classroom_id?: number | null;
  stream_id?: number | null;
}

export interface ApplicationDetailRecord extends ApplicationListRecord {
  nemis_number: string | null;
  knec_assessment_number: string | null;
  marital_status: string | null;
  residential_area: string | null;
  preferred_hospital: string | null;
  previous_school: string | null;
  transfer_reason: string | null;
  application_notes: string | null;
  review_notes: string | null;
  reviewed_by_id: number | null;
  has_allergies: boolean;
  allergies_notes: string | null;
  is_fully_immunized: boolean;
  emergency_contact_name: string | null;
  emergency_contact_phone: string | null;
  transport_needed: boolean;
  drop_off_point_id: number | null;
  drop_off_point_other: string | null;
  trip_id: number | null;
  father: ApplicationParentRecord;
  mother: ApplicationParentRecord;
  guardian: ApplicationParentRecord;
  documents: ApplicationDocumentRecord[];
  timeline: ApplicationTimelineRecord[];
  enrollment: ApplicationEnrollmentRecord;
}

export interface ApplicationListFilters {
  search?: string;
  status?: ApplicationStatus | null;
  waitlist_only?: boolean;
  page?: number;
  per_page?: number;
}

export interface ApplicationSummary {
  id: number;
  fullName: string;
  applicationStatus: ApplicationStatus;
  applicationDate: string | null;
  preferredClassName: string | null;
  className: string | null;
  waitlistPosition: number | null;
  passportPhotoUrl: string | null;
}

export interface ApplicationDetail {
  id: number;
  fullName: string;
  firstName: string;
  middleName: string | null;
  lastName: string;
  dob: string | null;
  gender: string | null;
  applicationStatus: ApplicationStatus;
  applicationDate: string | null;
  applicationSource: string | null;
  enrolled: boolean;
  waitlistPosition: number | null;
  preferredClassName: string | null;
  className: string | null;
  streamName: string | null;
  passportPhotoUrl: string | null;
  reviewedByName: string | null;
  reviewDate: string | null;
  reviewNotes: string | null;
  residentialArea: string | null;
  previousSchool: string | null;
  transferReason: string | null;
  nemisNumber: string | null;
  knecAssessmentNumber: string | null;
  maritalStatus: string | null;
  preferredHospital: string | null;
  hasAllergies: boolean;
  allergiesNotes: string | null;
  isFullyImmunized: boolean;
  emergencyContactName: string | null;
  emergencyContactPhone: string | null;
  transportNeeded: boolean;
  dropOffPointOther: string | null;
  father: ApplicationParentRecord;
  mother: ApplicationParentRecord;
  guardian: ApplicationParentRecord;
  documents: ApplicationDocumentRecord[];
  timeline: ApplicationTimelineRecord[];
  enrollment: ApplicationEnrollmentRecord;
}
