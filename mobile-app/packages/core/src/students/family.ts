import type {
  StudentEmergencyContact,
  StudentGuardianContact,
  StudentParentInfo,
} from '../types/student';
import type { StudentRecord } from '../types/student';

export function mapParentBlock(raw: StudentRecord['parent']): StudentParentInfo | null {
  if (!raw) return null;
  return {
    fatherName: raw.father_name,
    motherName: raw.mother_name,
    fatherFirstName: raw.father_first_name,
    fatherMiddleName: raw.father_middle_name,
    fatherLastName: raw.father_last_name,
    motherFirstName: raw.mother_first_name,
    motherMiddleName: raw.mother_middle_name,
    motherLastName: raw.mother_last_name,
    guardianFirstName: raw.guardian_first_name,
    guardianMiddleName: raw.guardian_middle_name,
    guardianLastName: raw.guardian_last_name,
    fatherIdType: raw.father_id_type,
    motherIdType: raw.mother_id_type,
    guardianIdType: raw.guardian_id_type,
    fatherCountryOfResidence: raw.father_country_of_residence,
    motherCountryOfResidence: raw.mother_country_of_residence,
    guardianCountryOfResidence: raw.guardian_country_of_residence,
    fatherPhone: raw.father_phone,
    motherPhone: raw.mother_phone,
    fatherWhatsapp: raw.father_whatsapp,
    motherWhatsapp: raw.mother_whatsapp,
    guardianWhatsapp: raw.guardian_whatsapp,
    fatherEmail: raw.father_email,
    motherEmail: raw.mother_email,
    guardianName: raw.guardian_name,
    guardianPhone: raw.guardian_phone,
    guardianEmail: raw.guardian_email,
    guardianRelationship: raw.guardian_relationship,
    fatherIdNumber: raw.father_id_number,
    motherIdNumber: raw.mother_id_number,
    guardianIdNumber: raw.guardian_id_number,
    maritalStatus: raw.marital_status,
  };
}

export function mapGuardians(raw: StudentRecord['guardians']): StudentGuardianContact[] {
  if (!raw?.length) return [];
  return raw.map((g) => ({
    id: g.id,
    name: g.full_name ?? g.name,
    relationship: g.relationship,
    phone: g.phone,
    email: g.email,
    isPrimary: Boolean(g.is_primary),
  }));
}

export function mapEmergencyContact(raw: StudentRecord): StudentEmergencyContact {
  return {
    name: raw.emergency_contact_name ?? null,
    phone: raw.emergency_contact_phone ?? null,
  };
}
