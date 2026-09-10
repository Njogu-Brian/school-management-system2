/**
 * Parent Home action catalog — navigation targets for school-life functions.
 * Pure data so Home UI and tests share one source of truth.
 */

export type ParentHomeActionId =
  | 'attendance'
  | 'transport'
  | 'diary'
  | 'homework'
  | 'academic'
  | 'fees'
  | 'notifications'
  | 'settings'
  | 'child_profile'
  | 'children'
  | 'announcements'
  | 'concerns'
  | 'co_curricular'
  | 'my_profile';

export type ParentHomeJump = {
  tab: string;
  screen: string;
  tabHome?: string;
  /** When true, navigation params must include `{ studentId }`. */
  requiresStudentId: boolean;
};

export type ParentHomeActionDef = {
  id: ParentHomeActionId;
  label: string;
  icon:
    | 'calendar-outline'
    | 'bus-outline'
    | 'chatbubbles-outline'
    | 'book-outline'
    | 'school-outline'
    | 'cash-outline'
    | 'notifications-outline'
    | 'settings-outline'
    | 'person-outline'
    | 'people-outline'
    | 'megaphone-outline'
    | 'alert-circle-outline'
    | 'sparkles-outline';
  jump: ParentHomeJump;
};

/** Core school-life tiles shown on Parent Home (not buried in More). */
export const PARENT_HOME_CORE_ACTIONS: ParentHomeActionDef[] = [
  {
    id: 'attendance',
    label: 'Attendance',
    icon: 'calendar-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'ChildAttendance',
      tabHome: 'ParentHome',
      requiresStudentId: true,
    },
  },
  {
    id: 'academic',
    label: 'Results',
    icon: 'school-outline',
    jump: {
      tab: 'ParentAcademicTab',
      screen: 'ChildResults',
      tabHome: 'AcademicHome',
      requiresStudentId: true,
    },
  },
  {
    id: 'fees',
    label: 'School fees',
    icon: 'cash-outline',
    jump: {
      tab: 'ParentFeesTab',
      screen: 'FeesHome',
      requiresStudentId: false,
    },
  },
  {
    id: 'transport',
    label: 'Transport',
    icon: 'bus-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'Transport',
      tabHome: 'ParentHome',
      requiresStudentId: true,
    },
  },
  {
    id: 'diary',
    label: 'Diary',
    icon: 'chatbubbles-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'DiaryChat',
      tabHome: 'ParentHome',
      requiresStudentId: true,
    },
  },
  {
    id: 'homework',
    label: 'Homework',
    icon: 'book-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'ChildHomework',
      tabHome: 'ParentHome',
      requiresStudentId: true,
    },
  },
  {
    id: 'notifications',
    label: 'Notifications',
    icon: 'notifications-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'Notifications',
      tabHome: 'ParentHome',
      requiresStudentId: false,
    },
  },
  {
    id: 'settings',
    label: 'Settings',
    icon: 'settings-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'Settings',
      tabHome: 'ParentHome',
      requiresStudentId: false,
    },
  },
];

export const PARENT_HOME_CHILD_ACTIONS: ParentHomeActionDef[] = [
  {
    id: 'child_profile',
    label: 'Child profile',
    icon: 'person-outline',
    jump: {
      tab: 'ParentChildrenTab',
      screen: 'ChildProfile',
      tabHome: 'ChildrenList',
      requiresStudentId: true,
    },
  },
  {
    id: 'children',
    label: 'All children',
    icon: 'people-outline',
    jump: {
      tab: 'ParentChildrenTab',
      screen: 'ChildrenList',
      requiresStudentId: false,
    },
  },
  {
    id: 'my_profile',
    label: 'My profile',
    icon: 'person-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'MyProfile',
      tabHome: 'ParentHome',
      requiresStudentId: false,
    },
  },
  {
    id: 'co_curricular',
    label: 'Co-curricular',
    icon: 'sparkles-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'CoCurricularHub',
      tabHome: 'ParentHome',
      requiresStudentId: false,
    },
  },
  {
    id: 'announcements',
    label: 'Announcements',
    icon: 'megaphone-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'Announcements',
      tabHome: 'ParentHome',
      requiresStudentId: false,
    },
  },
  {
    id: 'concerns',
    label: 'Concerns',
    icon: 'alert-circle-outline',
    jump: {
      tab: 'ParentHomeTab',
      screen: 'ConcernsList',
      tabHome: 'ParentHome',
      requiresStudentId: false,
    },
  },
];

export function getParentHomeAction(id: ParentHomeActionId): ParentHomeActionDef | undefined {
  return [...PARENT_HOME_CORE_ACTIONS, ...PARENT_HOME_CHILD_ACTIONS].find((a) => a.id === id);
}

/**
 * Build navigation params for a Home action.
 * Returns null when a student-scoped action is requested without a valid child.
 */
export function buildParentHomeNavParams(
  action: ParentHomeActionDef,
  studentId: number | null,
): { studentId: number } | undefined | null {
  if (!action.jump.requiresStudentId) return undefined;
  if (studentId == null || studentId <= 0) return null;
  return { studentId };
}
