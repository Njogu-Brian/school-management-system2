/**
 * Canonical semantic icon names shared by Admin, Users, and Edulynk.
 * One feature → one name → one SVG, everywhere.
 */
export const APP_ICON_NAMES = [
  // Navigation
  'dashboard',
  'admissions',
  'students',
  'academics',
  'finance',
  'hr',
  'operations',
  'communication',
  'settings',
  'reports',
  'workspace',
  'home',

  // Chrome / shared
  'add',
  'search',
  'notifications',
  'dark-mode',
  'profile',
  'sign-out',
  'help',
  'todays-list',
  'archive',
  'lock',
  'key',
  'alert',
  'check',
  'close',
  'info',

  // Finance
  'billing',
  'record-payment',
  'collections',
  'fee-balances',
  'statements',
  'outstanding-fees',
  'invoices',
  'credit-note',
  'wallet',

  // Admissions
  'admissions-workspace',
  'applications',
  'requisitions',
  'enrol-student',
  'view-applications',
  'prospects',
  'convert-to-student',
  'archived-applications',

  // Students
  'student-registry',
  'student-profile',
  'parent-contacts',
  'archived-students',
  'student-id',
  'transfer-student',
  'deactivate-student',
  'student-records',
  'attendance',
  'attendance-report',
  'mark-attendance',
  'mark-absent',

  // Academics
  'subjects',
  'classrooms',
  'exams',
  'results',
  'report-cards',
  'timetable',
  'quizzes',
  'grading-schemes',
  'assessments',
  'marks',
  'moderation',

  // HR
  'staff-registry',
  'leave-approvals',
  'apply-leave',
  'leave-types',
  'staff-advances',
  'payroll',
  'staff-attendance',
  'require-password-change',
  'people',
  'person',

  // Operations
  'transport',
  'routes',
  'trips',
  'drop-off-points',
  'vehicles',
  'maintenance',
  'visitors',
  'visitor-check-in',
  'inventory',
  'library',
  'assets',

  // Communication
  'send-sms',
  'create-announcement',
  'messages',
  'chat',
  'mail',
  'megaphone',

  // General
  'report-concern',
  'clipboard',
  'calendar',
  'clock',
  'book',
  'bus',
  'briefcase',
  'chart',
  'document',
  'graduation',
  'id-card',
  'map-pin',
  'wrench',
  'navigation',
  'award',
  'list',
  'shield',
  'cube',
  'car',
  'flash',
  'download',
  'bug',
  'activities',
] as const;

export type AppIconName = (typeof APP_ICON_NAMES)[number];

const NAME_SET = new Set<string>(APP_ICON_NAMES);

export function isAppIconName(value: string): value is AppIconName {
  return NAME_SET.has(value);
}

/**
 * Ionicons (and a few Soft3D keys) → canonical AppIconName.
 * Used so existing `icon="cash-outline"` call sites pick the right SVG
 * without rewriting every screen at once.
 */
export const LEGACY_ICON_MAP: Record<string, AppIconName> = {
  home: 'home',
  grid: 'dashboard',
  'people-circle': 'students',
  people: 'people',
  person: 'person',
  'person-add': 'enrol-student',
  'person-add-outline': 'enrol-student',
  cash: 'collections',
  wallet: 'wallet',
  card: 'invoices',
  receipt: 'billing',
  briefcase: 'hr',
  calendar: 'calendar',
  'calendar-clear': 'calendar',
  time: 'clock',
  today: 'todays-list',
  'checkmark-done': 'check',
  checkbox: 'mark-attendance',
  school: 'admissions',
  book: 'book',
  bus: 'transport',
  car: 'vehicles',
  'car-sport': 'vehicles',
  clipboard: 'clipboard',
  'bar-chart': 'chart',
  'pie-chart': 'chart',
  analytics: 'assessments',
  'stats-chart': 'chart',
  megaphone: 'megaphone',
  chatbubble: 'send-sms',
  chatbubbles: 'messages',
  mail: 'mail',
  settings: 'settings',
  notifications: 'notifications',
  search: 'search',
  shield: 'shield',
  'shield-checkmark': 'moderation',
  checkmark: 'check',
  add: 'add',
  create: 'add',
  'add-circle': 'add',
  'log-out': 'sign-out',
  'log-in': 'visitor-check-in',
  call: 'parent-contacts',
  archive: 'archive',
  'document-text': 'statements',
  'alert-circle': 'alert',
  'close-circle': 'mark-absent',
  close: 'close',
  key: 'require-password-change',
  'lock-closed': 'lock',
  list: 'list',
  apps: 'workspace',
  flash: 'flash',
  library: 'library',
  ribbon: 'report-cards',
  navigate: 'navigation',
  cube: 'inventory',
  download: 'download',
  'hardware-chip': 'assets',
  bug: 'bug',
  sparkles: 'activities',
  trophy: 'award',
  fitness: 'activities',
  moon: 'dark-mode',
  'help-circle': 'help',
  'information-circle': 'info',
  business: 'workspace',
  'swap-horizontal': 'transfer-student',
  'id-card': 'student-id',
  map: 'routes',
  'map-outline': 'routes',
  'book-outline': 'book',
  'cash-outline': 'collections',
  'people-outline': 'people',
  'briefcase-outline': 'hr',
  'grid-outline': 'dashboard',
  'chatbubbles-outline': 'messages',
  'settings-outline': 'settings',
  'time-outline': 'clock',
  'wallet-outline': 'wallet',
  'receipt-outline': 'billing',
  'document-text-outline': 'statements',
  'clipboard-outline': 'clipboard',
  'calendar-outline': 'calendar',
  'archive-outline': 'archive',
  'call-outline': 'parent-contacts',
  'school-outline': 'admissions',
  'megaphone-outline': 'megaphone',
  'alert-circle-outline': 'alert',
  'close-circle-outline': 'mark-absent',
  'person-outline': 'person',
  'bus-outline': 'transport',
  'car-outline': 'vehicles',
  'car-sport-outline': 'vehicles',
  'navigate-outline': 'navigation',
  'cube-outline': 'inventory',
  'download-outline': 'download',
  'log-in-outline': 'visitor-check-in',
  'checkbox-outline': 'mark-attendance',
  'key-outline': 'require-password-change',
  'list-outline': 'list',
  'add-circle-outline': 'add',
  'analytics-outline': 'assessments',
  'apps-outline': 'workspace',
  'ribbon-outline': 'report-cards',
  'flash-outline': 'flash',
  'library-outline': 'library',
  'bug-outline': 'bug',
  'sparkles-outline': 'activities',
  'lock-closed-outline': 'lock',
  'chatbubble-outline': 'send-sms',
  'bar-chart-outline': 'chart',
};

/**
 * Resolve any caller-supplied name (canonical, Ionicons, or Soft3D key) to an AppIconName.
 */
export function resolveAppIconName(name?: string | null, explicit?: AppIconName | string | null): AppIconName {
  if (explicit && isAppIconName(explicit)) return explicit;
  if (!name) return 'help';
  if (isAppIconName(name)) return name;

  const raw = name.trim();
  if (LEGACY_ICON_MAP[raw]) return LEGACY_ICON_MAP[raw];

  const n = raw.toLowerCase().replace(/-outline$/, '').replace(/-sharp$/, '');
  if (isAppIconName(n)) return n;
  if (LEGACY_ICON_MAP[n]) return LEGACY_ICON_MAP[n];

  if (n.includes('attend') && n.includes('absent')) return 'mark-absent';
  if (n.includes('attend') && n.includes('report')) return 'attendance-report';
  if (n.includes('attend') && n.includes('mark')) return 'mark-attendance';
  if (n.includes('attend') && n.includes('staff')) return 'staff-attendance';
  if (n.includes('attend')) return 'attendance';
  if (n.includes('parent') && n.includes('contact')) return 'parent-contacts';
  if (n.includes('archiv') && n.includes('student')) return 'archived-students';
  if (n.includes('archiv')) return 'archive';
  if (n.includes('payroll')) return 'payroll';
  if (n.includes('wallet') || n.includes('advance')) return 'staff-advances';
  if (n.includes('sms') || n.includes('chatbubble')) return 'send-sms';
  if (n.includes('announce') || n.includes('megaphone')) return 'megaphone';
  if (n.includes('report') && n.includes('concern')) return 'report-concern';
  if (n.includes('report') || n.includes('chart') || n.includes('analytics')) return 'chart';
  if (n.includes('student')) return 'students';
  if (n.includes('staff') || n.includes('people')) return 'people';
  if (n.includes('person')) return 'person';
  if (n.includes('finance') || n.includes('cash') || n.includes('collect')) return 'finance';
  if (n.includes('setting')) return 'settings';
  if (n.includes('notif')) return 'notifications';
  if (n.includes('search')) return 'search';
  if (n.includes('transport') || n.includes('bus')) return 'transport';
  if (n.includes('visitor')) return 'visitors';
  if (n.includes('requisition')) return 'requisitions';
  if (n.includes('lock') || n.includes('password')) return 'lock';
  if (n.includes('help')) return 'help';
  if (n.includes('sign-out') || n.includes('log-out')) return 'sign-out';
  if (n.includes('calendar') || n.includes('leave')) return 'calendar';
  if (n.includes('clock') || n.includes('time')) return 'clock';
  if (n.includes('book')) return 'book';
  if (n.includes('shield')) return 'shield';
  if (n.includes('key')) return 'key';
  if (n.includes('bug')) return 'bug';
  if (n.includes('library')) return 'library';
  if (n.includes('car') || n.includes('vehicle')) return 'vehicles';
  if (n.includes('map') || n.includes('route')) return 'routes';
  if (n.includes('wrench') || n.includes('maintenance')) return 'maintenance';
  if (n.includes('graduation') || n.includes('school')) return 'graduation';
  return 'help';
}
