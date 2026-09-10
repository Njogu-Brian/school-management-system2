/**
 * Teacher Home action catalog — classify daily work vs secondary More items.
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
  | 'profile';

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
    | 'person-outline';
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
    jump: { tab: 'More', screen: 'AssignmentsHub', tabHome: 'MoreMain' },
  },
  {
    id: 'marks',
    label: 'Marks',
    category: 'A',
    icon: 'create-outline',
    jump: { tab: 'More', screen: 'MarksHub', tabHome: 'MoreMain' },
  },
  {
    id: 'diary',
    label: 'Diary',
    category: 'A',
    icon: 'chatbubbles-outline',
    jump: { tab: 'More', screen: 'DiaryList', tabHome: 'MoreMain' },
  },
  {
    id: 'transport',
    label: 'Transport',
    category: 'A',
    icon: 'bus-outline',
    jump: { tab: 'More', screen: 'TeacherTransportHub', tabHome: 'MoreMain' },
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
    jump: { tab: 'More', screen: 'Notifications', tabHome: 'MoreMain' },
  },
  {
    id: 'lesson_plans',
    label: 'Lesson plans',
    category: 'A',
    icon: 'document-text-outline',
    jump: { tab: 'More', screen: 'LessonPlansHub', tabHome: 'MoreMain' },
  },
];

export const TEACHER_HOME_ACCOUNT_ACTIONS: TeacherHomeActionDef[] = [
  {
    id: 'profile',
    label: 'Profile',
    category: 'D',
    icon: 'person-outline',
    jump: { tab: 'More', screen: 'MyProfile', tabHome: 'MoreMain' },
  },
  {
    id: 'settings',
    label: 'Settings',
    category: 'D',
    icon: 'settings-outline',
    jump: { tab: 'More', screen: 'Settings', tabHome: 'MoreMain' },
  },
];
