export type StudentFeeStatus = 'cleared' | 'pending' | null;

export type StudentEnrollmentStatus = 'active' | 'archived' | string;

export interface StudentListItemData {
  id: number;
  fullName: string;
  admissionNumber: string;
  classLabel: string;
  streamName?: string | null;
  gender?: string;
  feeStatus?: StudentFeeStatus;
  enrollmentStatus?: StudentEnrollmentStatus;
  avatarUrl?: string | null;
  onPress?: () => void;
}
