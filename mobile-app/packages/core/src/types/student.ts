/** Raw student shape from `GET /students` and `GET /students/{id}`. */
export interface StudentRecord {
  id: number;
  admission_number: string;
  first_name: string;
  last_name: string;
  middle_name?: string | null;
  full_name: string;
  date_of_birth?: string;
  gender?: string;
  class_id?: number;
  classroom_id?: number;
  stream_id?: number | null;
  class_name?: string | null;
  stream_name?: string | null;
  status?: string;
  category?: string | null;
  category_id?: number | null;
  avatar?: string | null;
  phone?: string | null;
  email?: string | null;
  fee_status?: 'cleared' | 'pending' | string;
  outstanding_balance?: number | null;
  admission_date?: string | null;
  enrollment_year?: string | number | null;
  address?: string | null;
  residential_area?: string | null;
  religion?: string | null;
  nationality?: string | null;
  county_of_birth?: string | null;
  sub_county_of_birth?: string | null;
  location_of_birth?: string | null;
  birth_certificate_entry_no?: string | null;
  medical_condition?: string | null;
  learner_interests?: string[] | null;
  orphan_status?: string | null;
  has_special_needs?: boolean;
  disability_type?: string | null;
  nemis_number?: string | null;
  knec_assessment_number?: string | null;
  kcpe_kjsea_year?: number | null;
  trip_id?: number | null;
  trip_name?: string | null;
  trip_vehicle?: string | null;
  drop_off_point_id?: number | null;
  drop_off_point_name?: string | null;
  drop_off_point_other?: string | null;
  transport?: {
    mode?: string | null;
    summary?: string | null;
    morning?: {
      trip_id?: number | null;
      trip_name?: string | null;
      vehicle?: string | null;
      drop_off_point_id?: number | null;
      drop_off_point?: string | null;
    } | null;
    evening?: {
      trip_id?: number | null;
      trip_name?: string | null;
      vehicle?: string | null;
      drop_off_point_id?: number | null;
      drop_off_point?: string | null;
    } | null;
    legacy?: {
      trip_id?: number | null;
      trip_name?: string | null;
      vehicle?: string | null;
      drop_off_point_id?: number | null;
      drop_off_point?: string | null;
      drop_off_point_other?: string | null;
    };
  };
  preferred_hospital?: string | null;
  has_allergies?: boolean;
  allergies_notes?: string | null;
  is_fully_immunized?: boolean | null;
  blood_group?: string | null;
  emergency_contact_name?: string | null;
  emergency_contact_phone?: string | null;
  guardians?: StudentGuardianRecord[];
  parent?: StudentParentBlock | null;
  created_at?: string;
  updated_at?: string;
}

export interface StudentGuardianRecord {
  id: number;
  name: string;
  full_name?: string;
  relationship: string;
  phone: string;
  email?: string;
  is_primary?: boolean;
}

export interface StudentParentBlock {
  father_name?: string | null;
  mother_name?: string | null;
  father_first_name?: string | null;
  father_middle_name?: string | null;
  father_last_name?: string | null;
  mother_first_name?: string | null;
  mother_middle_name?: string | null;
  mother_last_name?: string | null;
  guardian_first_name?: string | null;
  guardian_middle_name?: string | null;
  guardian_last_name?: string | null;
  father_id_type?: string | null;
  mother_id_type?: string | null;
  guardian_id_type?: string | null;
  father_country_of_residence?: string | null;
  mother_country_of_residence?: string | null;
  guardian_country_of_residence?: string | null;
  father_phone?: string | null;
  mother_phone?: string | null;
  father_whatsapp?: string | null;
  mother_whatsapp?: string | null;
  guardian_whatsapp?: string | null;
  father_email?: string | null;
  mother_email?: string | null;
  guardian_name?: string | null;
  guardian_phone?: string | null;
  guardian_email?: string | null;
  guardian_relationship?: string | null;
  father_id_number?: string | null;
  mother_id_number?: string | null;
  guardian_id_number?: string | null;
  marital_status?: string | null;
  school_notifications_muted_parent?: string | null;
}

export interface ClassroomRecord {
  id: number;
  name: string;
  level?: number | string | null;
  code?: string | null;
}

export interface StreamRecord {
  id: number;
  name: string;
  class_id: number;
}

/** `GET /classes/{id}/subjects` — already scoped server-side to the teacher's own subjects. */
export interface ClassroomSubjectRecord {
  id: number;
  name: string;
  code: string | null;
}

/** List row model (registry). */
export interface StudentSummary {
  id: number;
  admissionNumber: string;
  fullName: string;
  className: string | null;
  streamName: string | null;
  classroomId: number | null;
  streamId: number | null;
  gender: string;
  enrollmentStatus: 'active' | 'archived' | string;
  feeStatus: 'cleared' | 'pending' | null;
  avatarUrl: string | null;
  gradeLevel: number | string | null;
}

/** Detail header model (no 360 tabs in Batch 1). */
export interface StudentDetail extends StudentSummary {
  dateOfBirth: string | null;
  phone: string | null;
  email: string | null;
  admissionDate: string | null;
  enrollmentYear: string | number | null;
  address: string | null;
  category: string | null;
  nemisNumber: string | null;
  outstandingBalance: number | null;
  parent: StudentParentInfo | null;
  guardians: StudentGuardianContact[];
  emergencyContact: StudentEmergencyContact;
  tripId: number | null;
  tripName: string | null;
  tripVehicle: string | null;
  dropOffPointId: number | null;
  dropOffPointName: string | null;
  dropOffPointOther: string | null;
  transportSummary: string | null;
  transportMorning: {
    tripName: string | null;
    vehicle: string | null;
    dropOffPoint: string | null;
  } | null;
  transportEvening: {
    tripName: string | null;
    vehicle: string | null;
    dropOffPoint: string | null;
  } | null;
  preferredHospital: string | null;
  hasAllergies: boolean;
  allergiesNotes: string | null;
  isFullyImmunized: boolean | null;
  bloodGroup: string | null;
  religion: string | null;
  nationality: string | null;
  countyOfBirth: string | null;
  subCountyOfBirth: string | null;
  locationOfBirth: string | null;
  birthCertificateEntryNo: string | null;
  medicalCondition: string | null;
  learnerInterests: string[];
  orphanStatus: string | null;
  hasSpecialNeeds: boolean;
  disabilityType: string | null;
}

export interface StudentGuardianContact {
  id: number;
  name: string;
  relationship: string;
  phone: string;
  email?: string;
  isPrimary: boolean;
}

export interface StudentParentInfo {
  fatherName?: string | null;
  motherName?: string | null;
  fatherFirstName?: string | null;
  fatherMiddleName?: string | null;
  fatherLastName?: string | null;
  motherFirstName?: string | null;
  motherMiddleName?: string | null;
  motherLastName?: string | null;
  guardianFirstName?: string | null;
  guardianMiddleName?: string | null;
  guardianLastName?: string | null;
  fatherIdType?: string | null;
  motherIdType?: string | null;
  guardianIdType?: string | null;
  fatherCountryOfResidence?: string | null;
  motherCountryOfResidence?: string | null;
  guardianCountryOfResidence?: string | null;
  fatherPhone?: string | null;
  motherPhone?: string | null;
  fatherWhatsapp?: string | null;
  motherWhatsapp?: string | null;
  guardianWhatsapp?: string | null;
  fatherEmail?: string | null;
  motherEmail?: string | null;
  guardianName?: string | null;
  guardianPhone?: string | null;
  guardianEmail?: string | null;
  guardianRelationship?: string | null;
  fatherIdNumber?: string | null;
  motherIdNumber?: string | null;
  guardianIdNumber?: string | null;
  maritalStatus?: string | null;
}

export interface StudentEmergencyContact {
  name: string | null;
  phone: string | null;
}

export type StudentEnrollmentStatusFilter = 'all' | 'active' | 'fee_pending' | 'fee_cleared';

export type StudentGenderFilter = 'all' | 'male' | 'female' | 'other';

export interface StudentListFilters {
  search?: string;
  classroomId?: number | null;
  streamId?: number | null;
  /** Matches `ClassroomRecord.level` — narrows class picker; client-filter when no class selected. */
  gradeLevel?: number | string | null;
  status?: StudentEnrollmentStatusFilter;
  gender?: StudentGenderFilter;
  perPage?: number;
}

export interface StudentListQueryParams {
  search?: string;
  class_id?: number;
  stream_id?: number;
  page?: number;
  per_page?: number;
}
