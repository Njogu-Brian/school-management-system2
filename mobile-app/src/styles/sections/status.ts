/**
 * Semantic status colors (leave, exams, attendance, report cards, notifications).
 */
import { COLORS } from '@constants/theme';

export const StatusColors = {
    leavePending: COLORS.warning,
    examOpen: COLORS.success,
    examDefault: COLORS.success,
    payrollPending: COLORS.warning,
    salaryPending: COLORS.warning,
    lessonFilterActive: COLORS.warning,
    notificationMedium: '#ff9800',
    attendanceLate: COLORS.warning,
    attendanceExcused: COLORS.info,
    reportGood: COLORS.success,
    reportAverage: COLORS.warning,
    reportWeak: '#f97316',
    skillGood: COLORS.success,
    skillAverage: COLORS.warning,
    skillPoor: '#f97316',
} as const;
