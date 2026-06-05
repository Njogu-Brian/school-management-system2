import type {
  ApplicationDetail,
  ApplicationDetailRecord,
  ApplicationListRecord,
  ApplicationSummary,
} from '../types/admissions';

export function normalizeApplicationSummary(row: ApplicationListRecord): ApplicationSummary {
  return {
    id: row.id,
    fullName: row.full_name,
    applicationStatus: row.application_status,
    applicationDate: row.application_date,
    preferredClassName: row.preferred_class_name,
    className: row.class_name,
    waitlistPosition: row.waitlist_position,
    passportPhotoUrl: row.passport_photo_url,
  };
}

export function normalizeApplicationDetail(row: ApplicationDetailRecord): ApplicationDetail {
  return {
    id: row.id,
    fullName: row.full_name,
    firstName: row.first_name,
    middleName: row.middle_name,
    lastName: row.last_name,
    dob: row.dob,
    gender: row.gender,
    applicationStatus: row.application_status,
    applicationDate: row.application_date,
    applicationSource: row.application_source,
    enrolled: row.enrolled,
    waitlistPosition: row.waitlist_position,
    preferredClassName: row.preferred_class_name,
    className: row.class_name,
    streamName: row.stream_name,
    passportPhotoUrl: row.passport_photo_url,
    reviewedByName: row.reviewed_by_name,
    reviewDate: row.review_date,
    reviewNotes: row.review_notes,
    residentialArea: row.residential_area,
    previousSchool: row.previous_school,
    transferReason: row.transfer_reason,
    nemisNumber: row.nemis_number,
    knecAssessmentNumber: row.knec_assessment_number,
    maritalStatus: row.marital_status,
    preferredHospital: row.preferred_hospital,
    hasAllergies: row.has_allergies,
    allergiesNotes: row.allergies_notes,
    isFullyImmunized: row.is_fully_immunized,
    emergencyContactName: row.emergency_contact_name,
    emergencyContactPhone: row.emergency_contact_phone,
    transportNeeded: row.transport_needed,
    dropOffPointOther: row.drop_off_point_other,
    father: row.father,
    mother: row.mother,
    guardian: row.guardian,
    documents: row.documents,
    timeline: row.timeline,
    enrollment: row.enrollment,
  };
}

export function applicationStatusLabel(status: string): string {
  switch (status) {
    case 'pending':
      return 'Pending';
    case 'under_review':
      return 'Under Review';
    case 'waitlisted':
      return 'Waitlisted';
    case 'enrolled':
      return 'Enrolled';
    case 'rejected':
      return 'Rejected';
    default:
      return status;
  }
}
