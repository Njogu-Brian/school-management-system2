/**
 * Teacher Home action catalog — all daily + account tools live on Home (no More tab).
 */

export type TeacherHomeActionId =
  | 'attendance'
  | 'transport'
  | 'diary'
  | 'homework'
  | 'marks'
  | 'notifications'
  | 'classes'
  | 'lesson_plans'
  | 'settings'
  | 'profile'
  | 'staff_clock'
  | 'leave'
  | 'payslips'
  | 'announcements'
  | 'requirements'
  | 'academics';

export type TeacherHomeJump = {
  tab: string;
  screen: string;
  tabHome?: string;
};

export type TeacherHomeActionDef = {
  id: TeacherHomeActionId;
  label: string;
  category: 'A' | 'B' | 'C' | 'D';
  icon:
    | 'checkbox-outline'
    | 'bus-outline'
    | 'chatbubbles-outline'
    | 'book-outline'
    | 'create-outline'
    | 'notifications-outline'
    | 'people-outline'
    | 'document-text-outline'
    | 'settings-outline'
    | 'person-outline'
    | 'time-outline'
    | 'calendar-outline'
    | 'wallet-outline'
    | 'megaphone-outline'
    | 'clipboard-outline'
    | 'school-outline';
  jump: TeacherHomeJump;
};

/** Category A — core daily teacher work (surface on Home). */
export const TEACHER_HOME_CORE_ACTIONS: TeacherHomeActionDef[] = [
  {
    id: 'attendance',
    label: 'Attendance',
    category: 'A',
    icon: 'checkbox-outline',
    jump: { tab: 'Attendance', screen: 'AttendanceMain' },
  },
  {
    id: 'homework',
    label: 'Homework',
    category: 'A',
    icon: 'book-outline',
    jump: { tab: 'Home', screen: 'AssignmentsHub', tabHome: 'HomeMain' },
  },
  {
    id: 'marks',
    label: 'Marks',
    category: 'A',
    icon: 'create-outline',
    jump: { tab: 'Home', screen: 'MarksHub', tabHome: 'HomeMain' },
  },
  {
    id: 'diary',
    label: 'Diary',
    category: 'A',
    icon: 'chatbubbles-outline',
    jump: { tab: 'Home', screen: 'DiaryList', tabHome: 'HomeMain' },
  },
  {
    id: 'transport',
    label: 'Transport',
    category: 'A',
    icon: 'bus-outline',
    jump: { tab: 'Home', screen: 'TeacherTransportHub', tabHome: 'HomeMain' },
  },
  {
    id: 'classes',
    label: 'My classes',
    category: 'A',
    icon: 'people-outline',
    jump: { tab: 'Classes', screen: 'ClassesMain' },
  },
  {
    id: 'notifications',
    label: 'Notifications',
    category: 'A',
    icon: 'notifications-outline',
    jump: { tab: 'Home', screen: 'Notifications', tabHome: 'HomeMain' },
  },
  {
    id: 'lesson_plans',
    label: 'Lesson plans',
    category: 'A',
    icon: 'document-text-outline',
    jump: { tab: 'Home', screen: 'LessonPlansHub', tabHome: 'HomeMain' },
  },
];

/** Secondary tools formerly under More — now on Home. */
export const TEACHER_HOME_MORE_ACTIONS: TeacherHomeActionDef[] = [
  {
    id: 'academics',
    label: 'Academics',
    category: 'B',
    icon: 'school-outline',
    jump: { tab: 'Home', screen: 'Academics', tabHome: 'HomeMain' },
  },
  {
    id: 'requirements',
    label: 'Requirements',
    category: 'B',
    icon: 'clipboard-outline',
    jump: { tab: 'Home', screen: 'RequirementsHub', tabHome: 'HomeMain' },
  },
  {
    id: 'staff_clock',
    label: 'My attendance',
    category: 'B',
    icon: 'time-outline',
    jump: { tab: 'Home', screen: 'StaffClock', tabHome: 'HomeMain' },
  },
  {
    id: 'leave',
    label: 'Leave',
    category: 'B',
    icon: 'calendar-outline',
    jump: { tab: 'Home', screen: 'MyLeaveList', tabHome: 'HomeMain' },
  },
  {
    id: 'payslips',
    label: 'Payslips',
    category: 'B',
    icon: 'wallet-outline',
    jump: { tab: 'Home', screen: 'MyPayslips', tabHome: 'HomeMain' },
  },
  {
    id: 'announcements',
    label: 'Announcements',
    category: 'B',
    icon: 'megaphone-outline',
    jump: { tab: 'Home', screen: 'Announcements', tabHome: 'HomeMain' },
  },
];

export const TEACHER_HOME_ACCOUNT_ACTIONS: TeacherHomeActionDef[] = [
  {
    id: 'profile',
    label: 'Profile',
    category: 'D',
    icon: 'person-outline',
    jump: { tab: 'Home', screen: 'MyProfile', tabHome: 'HomeMain' },
  },
  {
    id: 'settings',
    label: 'Settings',
    category: 'D',
    icon: 'settings-outline',
    jump: { tab: 'Home', screen: 'Settings', tabHome: 'HomeMain' },
  },
];
