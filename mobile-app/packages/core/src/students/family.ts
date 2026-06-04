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
    fatherPhone: raw.father_phone,
    motherPhone: raw.mother_phone,
    fatherEmail: raw.father_email,
    motherEmail: raw.mother_email,
    guardianName: raw.guardian_name,
    guardianPhone: raw.guardian_phone,
    guardianEmail: raw.guardian_email,
    guardianRelationship: raw.guardian_relationship,
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
