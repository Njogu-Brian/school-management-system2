import React from 'react';
import Svg, { Circle, Path, Rect } from 'react-native-svg';
import type { AppIconName } from './names';

/**
 * Royal Kings application icon set.
 * Each name has its own shape and palette from the icon sheet — not a single theme stroke.
 */
const BLUE = '#3B82F6';
const BLUE_DEEP = '#1D4ED8';
const SKY = '#93C5FD';
const PURPLE = '#7C3AED';
const PURPLE_SOFT = '#C4B5FD';
const CYAN = '#06B6D4';
const CYAN_DEEP = '#0E7490';
const GREEN = '#22C55E';
const GREEN_DEEP = '#15803D';
const YELLOW = '#F59E0B';
const ORANGE = '#F97316';
const RED = '#EF4444';
const TEAL = '#14B8A6';
const INK = '#1E293B';
const PAPER = '#F8FAFC';
const CREAM = '#FEF3C7';

function Frame({ size, children }: { size: number; children: React.ReactNode }) {
  return (
    <Svg width={size} height={size} viewBox="0 0 32 32">
      {children}
    </Svg>
  );
}

function House() {
  return (
    <>
      <Path d="M5 15 L16 6 L27 15 V26 H5 Z" fill={BLUE} />
      <Path d="M5 15 L16 6 L27 15 L16 12 Z" fill={SKY} />
      <Rect x="13" y="18" width="6" height="8" rx="1" fill={CREAM} />
    </>
  );
}

function Cap() {
  return (
    <>
      <Path d="M4 14 L16 8 L28 14 L16 20 Z" fill={PURPLE} />
      <Path d="M10 16 V22 C12 25 20 25 22 22 V16" fill={PURPLE_SOFT} />
      <Path d="M26 14 V22" stroke={YELLOW} strokeWidth={2} strokeLinecap="round" />
      <Circle cx="26" cy="23" r="1.6" fill={YELLOW} />
    </>
  );
}

function People({ a = CYAN, b = CYAN_DEEP }: { a?: string; b?: string }) {
  return (
    <>
      <Circle cx="12" cy="11" r="4" fill={a} />
      <Path d="M5 26 C5 19 8 16 12 16 C16 16 19 19 19 26 Z" fill={b} />
      <Circle cx="21" cy="12" r="3.4" fill={a} />
      <Path d="M16 26 C16 20 18 18 21 18 C25 18 28 20 28 26 Z" fill={b} />
    </>
  );
}

function Person({ fill = BLUE }: { fill?: string }) {
  return (
    <>
      <Circle cx="16" cy="11" r="5" fill={fill} />
      <Path d="M7 27 C7 19 11 16 16 16 C21 16 25 19 25 27 Z" fill={fill} />
    </>
  );
}

function Book({ cover = BLUE, page = PAPER }: { cover?: string; page?: string }) {
  return (
    <>
      <Path d="M6 8 H16 V26 H8 C6.5 26 6 25 6 23 Z" fill={cover} />
      <Path d="M16 8 H26 V23 C26 25 25.5 26 24 26 H16 Z" fill={page} />
      <Path d="M16 8 V26" stroke={BLUE_DEEP} strokeWidth={1.2} />
      <Rect x="18" y="12" width="6" height="1.4" rx="0.7" fill={SKY} />
      <Rect x="18" y="15.5" width="5" height="1.4" rx="0.7" fill={SKY} />
    </>
  );
}

function Wallet({ badge = GREEN }: { badge?: string }) {
  return (
    <>
      <Path d="M6 11 H22 V16 H6 Z" fill="#86EFAC" />
      <Rect x="5" y="14" width="22" height="12" rx="3" fill={GREEN} />
      <Rect x="18" y="17" width="8" height="6" rx="2" fill={CREAM} />
      <Circle cx="22" cy="20" r="1.3" fill={GREEN_DEEP} />
      {badge === GREEN ? <Circle cx="25" cy="10" r="4" fill={GREEN_DEEP} /> : null}
      {badge === GREEN ? <Path d="M25 8 V12 M23 10 H27" stroke="#fff" strokeWidth={1.4} strokeLinecap="round" /> : null}
    </>
  );
}

function Briefcase() {
  return (
    <>
      <Rect x="5" y="12" width="22" height="14" rx="3" fill={PURPLE} />
      <Path d="M12 12 V10 C12 8.5 13 8 14 8 H18 C19 8 20 8.5 20 10 V12" stroke={PURPLE_SOFT} strokeWidth={2} fill="none" />
      <Rect x="5" y="12" width="22" height="5" fill={PURPLE_SOFT} />
      <Rect x="14" y="18" width="4" height="3" rx="1" fill={YELLOW} />
    </>
  );
}

function Bus() {
  return (
    <>
      <Rect x="6" y="8" width="20" height="14" rx="3" fill={YELLOW} />
      <Rect x="8" y="10" width="6" height="5" rx="1" fill={SKY} />
      <Rect x="15" y="10" width="6" height="5" rx="1" fill={SKY} />
      <Rect x="8" y="22" width="16" height="3" rx="1" fill={INK} />
      <Circle cx="11" cy="25" r="2" fill={INK} />
      <Circle cx="21" cy="25" r="2" fill={INK} />
    </>
  );
}

function Bubbles() {
  return (
    <>
      <Path d="M5 8 H18 C20 8 21 9 21 11 V16 C21 18 20 19 18 19 H10 L6 23 V19 H5 C4 19 4 18 4 16 V11 C4 9 4 8 5 8 Z" fill={BLUE} />
      <Path d="M14 14 H26 C28 14 29 15 29 17 V21 C29 23 28 24 26 24 H22 L19 27 V24 H14 Z" fill={SKY} />
    </>
  );
}

function Gear() {
  return (
    <>
      <Circle cx="16" cy="16" r="4" fill={PAPER} />
      <Path
        d="M16 5 L18 8 L22 7 L23 11 L27 12 L25 16 L27 20 L23 21 L22 25 L18 24 L16 27 L14 24 L10 25 L9 21 L5 20 L7 16 L5 12 L9 11 L10 7 L14 8 Z"
        fill={PURPLE}
      />
      <Circle cx="16" cy="16" r="3.2" fill={PAPER} />
    </>
  );
}

function Doc({ fill, mark }: { fill: string; mark?: React.ReactNode }) {
  return (
    <>
      <Path d="M8 5 H18 L24 11 V27 H8 Z" fill={fill} />
      <Path d="M18 5 V11 H24" fill="#fff" opacity={0.45} />
      {mark}
    </>
  );
}

function Bars() {
  return (
    <>
      <Rect x="6" y="18" width="5" height="8" rx="1" fill={SKY} />
      <Rect x="13" y="12" width="5" height="14" rx="1" fill={BLUE} />
      <Rect x="20" y="8" width="5" height="18" rx="1" fill={BLUE_DEEP} />
    </>
  );
}

function Clock({ face = BLUE }: { face?: string }) {
  return (
    <>
      <Circle cx="16" cy="16" r="9" fill={face} />
      <Circle cx="16" cy="16" r="6.5" fill={PAPER} />
      <Path d="M16 12 V16 L19 18" stroke={face} strokeWidth={1.6} strokeLinecap="round" />
    </>
  );
}

function Bell() {
  return (
    <>
      <Path d="M16 6 C12 6 9 10 9 14 V18 L7 21 H25 L23 18 V14 C23 10 20 6 16 6 Z" fill={YELLOW} />
      <Circle cx="16" cy="23" r="2" fill={ORANGE} />
    </>
  );
}

function Pin({ fill }: { fill: string }) {
  return (
    <>
      <Path d="M16 4 C11 4 8 8 8 12 C8 18 16 28 16 28 C16 28 24 18 24 12 C24 8 21 4 16 4 Z" fill={fill} />
      <Circle cx="16" cy="12" r="3" fill={PAPER} />
    </>
  );
}

function Pie() {
  return (
    <>
      <Path d="M16 6 A10 10 0 1 0 26 16 H16 Z" fill={BLUE} />
      <Path d="M16 6 A10 10 0 0 1 26 16 H16 Z" fill={SKY} />
      <Path d="M16 16 L22 8 A10 10 0 0 0 16 6 Z" fill={PURPLE_SOFT} />
    </>
  );
}

function Star() {
  return <Path d="M16 5 L19 12 H26 L20 17 L22 25 L16 20 L10 25 L12 17 L6 12 H13 Z" fill={ORANGE} />;
}

function LockShape({ fill = PURPLE }: { fill?: string }) {
  return (
    <>
      <Rect x="8" y="14" width="16" height="12" rx="2" fill={fill} />
      <Path d="M11 14 V11 C11 8 13 6 16 6 C19 6 21 8 21 11 V14" stroke={fill} strokeWidth={2.4} fill="none" />
      <Circle cx="16" cy="20" r="1.5" fill={PAPER} />
    </>
  );
}

const DRAW: Record<AppIconName, () => React.ReactNode> = {
  dashboard: House,
  home: House,
  admissions: Cap,
  graduation: Cap,
  students: () => <People />,
  people: () => <People />,
  academics: () => <Book />,
  subjects: () => <Book />,
  book: () => <Book />,
  finance: () => <Wallet />,
  wallet: () => <Wallet badge="none" />,
  hr: Briefcase,
  briefcase: Briefcase,
  operations: Bus,
  transport: Bus,
  bus: Bus,
  communication: Bubbles,
  chat: Bubbles,
  messages: () => (
    <>
      <Rect x="6" y="8" width="20" height="14" rx="2" fill={BLUE} />
      <Path d="M6 10 L16 17 L26 10" stroke={PAPER} strokeWidth={1.6} fill="none" />
    </>
  ),
  mail: () => (
    <>
      <Rect x="5" y="9" width="22" height="14" rx="2" fill={BLUE} />
      <Path d="M5 11 L16 18 L27 11" stroke={PAPER} strokeWidth={1.6} fill="none" />
    </>
  ),
  settings: Gear,
  reports: Pie,
  chart: Bars,
  workspace: () => (
    <Doc fill={BLUE} mark={<Rect x="11" y="15" width="8" height="2" rx="1" fill={PAPER} />} />
  ),
  add: () => (
    <>
      <Circle cx="16" cy="16" r="10" fill={PURPLE} />
      <Path d="M16 10 V22 M10 16 H22" stroke="#fff" strokeWidth={2.4} strokeLinecap="round" />
    </>
  ),
  search: () => (
    <>
      <Circle cx="14" cy="14" r="7" fill="none" stroke={BLUE} strokeWidth={2.6} />
      <Path d="M19 19 L26 26" stroke={BLUE_DEEP} strokeWidth={2.6} strokeLinecap="round" />
    </>
  ),
  notifications: Bell,
  'dark-mode': () => <Path d="M18 6 A10 10 0 1 0 26 18 A7 7 0 0 1 18 6 Z" fill={PURPLE} />,
  profile: () => <Person fill={PURPLE} />,
  person: () => <Person fill={BLUE} />,
  'student-profile': () => <Person fill={BLUE} />,
  'sign-out': () => (
    <>
      <Path d="M8 8 H16 V24 H8 Z" fill={RED} />
      <Path d="M16 16 H26 M22 12 L26 16 L22 20" stroke={RED} strokeWidth={2.2} fill="none" strokeLinecap="round" />
    </>
  ),
  help: () => (
    <>
      <Circle cx="16" cy="16" r="10" fill={BLUE} />
      <Path d="M12 13 C12 10 20 10 20 14 C20 16 16 16 16 19" stroke="#fff" strokeWidth={2} fill="none" strokeLinecap="round" />
      <Circle cx="16" cy="22" r="1.3" fill="#fff" />
    </>
  ),
  'todays-list': Star,
  activities: Star,
  archive: () => (
    <>
      <Path d="M6 10 H26 V14 H6 Z" fill={BLUE_DEEP} />
      <Path d="M8 14 H24 V25 H8 Z" fill={BLUE} />
      <Rect x="13" y="17" width="6" height="2" rx="1" fill={PAPER} />
    </>
  ),
  lock: () => <LockShape />,
  'require-password-change': () => <LockShape fill={PURPLE} />,
  key: () => (
    <>
      <Circle cx="12" cy="16" r="5" fill={PURPLE} />
      <Path d="M16 16 H27 M23 16 V20 M26 16 V19" stroke={PURPLE} strokeWidth={2.2} strokeLinecap="round" />
    </>
  ),
  alert: () => (
    <>
      <Path d="M16 5 L28 26 H4 Z" fill={RED} />
      <Path d="M16 12 V19" stroke="#fff" strokeWidth={2} strokeLinecap="round" />
      <Circle cx="16" cy="22" r="1.2" fill="#fff" />
    </>
  ),
  'report-concern': () => (
    <>
      <Path d="M16 5 L28 26 H4 Z" fill={RED} />
      <Path d="M16 12 V19" stroke="#fff" strokeWidth={2} strokeLinecap="round" />
      <Circle cx="16" cy="22" r="1.2" fill="#fff" />
    </>
  ),
  check: () => <Path d="M6 17 L13 24 L26 8" stroke={GREEN} strokeWidth={3} fill="none" strokeLinecap="round" />,
  close: () => <Path d="M8 8 L24 24 M24 8 L8 24" stroke={RED} strokeWidth={3} strokeLinecap="round" />,
  info: () => (
    <>
      <Circle cx="16" cy="16" r="10" fill={BLUE} />
      <Path d="M16 14 V22" stroke="#fff" strokeWidth={2} strokeLinecap="round" />
      <Circle cx="16" cy="10" r="1.3" fill="#fff" />
    </>
  ),
  billing: () => (
    <Doc
      fill={GREEN}
      mark={<Path d="M16 15 V23 M12 19 H20" stroke="#fff" strokeWidth={1.8} strokeLinecap="round" />}
    />
  ),
  'record-payment': () => (
    <Doc
      fill={PURPLE}
      mark={<Path d="M18 18 L22 14 L24 16 L20 20 Z" fill={YELLOW} />}
    />
  ),
  applications: () => (
    <Doc fill={PURPLE} mark={<Path d="M12 18 H20 M12 22 H17" stroke="#fff" strokeWidth={1.4} strokeLinecap="round" />} />
  ),
  collections: Bars,
  'fee-balances': () => <Doc fill={BLUE} mark={<Rect x="11" y="16" width="10" height="2" rx="1" fill={PAPER} />} />,
  statements: () => (
    <>
      <Rect x="8" y="8" width="14" height="16" rx="2" fill={SKY} />
      <Rect x="11" y="6" width="14" height="16" rx="2" fill={BLUE} />
    </>
  ),
  'outstanding-fees': () => <Clock />,
  clock: () => <Clock />,
  'staff-attendance': () => <Clock face={BLUE} />,
  invoices: () => (
    <Doc fill={GREEN} mark={<Path d="M16 15 V22 M13 17 H18" stroke={CREAM} strokeWidth={1.6} strokeLinecap="round" />} />
  ),
  payroll: () => (
    <>
      <Circle cx="16" cy="16" r="9" fill={GREEN} />
      <Path d="M16 11 V21 M13 14 H18 M13 18 H18" stroke="#fff" strokeWidth={1.6} strokeLinecap="round" />
    </>
  ),
  'credit-note': () => (
    <Doc
      fill={RED}
      mark={<Path d="M12 19 H20 M16 16 L20 19 L16 22" stroke="#fff" strokeWidth={1.5} fill="none" strokeLinecap="round" />}
    />
  ),
  document: () => <Doc fill={BLUE} />,
  'admissions-workspace': () => (
    <>
      <People a={PURPLE_SOFT} b={PURPLE} />
      <Circle cx="24" cy="8" r="4" fill={GREEN} />
      <Path d="M24 6 V10 M22 8 H26" stroke="#fff" strokeWidth={1.3} strokeLinecap="round" />
    </>
  ),
  requisitions: () => (
    <>
      <Rect x="8" y="5" width="16" height="22" rx="2" fill={BLUE} />
      <Path d="M12 14 L14 16 L18 11" stroke="#fff" strokeWidth={1.6} fill="none" strokeLinecap="round" />
      <Rect x="12" y="19" width="8" height="1.6" rx="0.8" fill={SKY} />
    </>
  ),
  clipboard: () => (
    <>
      <Rect x="8" y="7" width="16" height="20" rx="2" fill={BLUE} />
      <Rect x="12" y="4" width="8" height="4" rx="1" fill={SKY} />
    </>
  ),
  exams: () => (
    <>
      <Rect x="8" y="6" width="16" height="20" rx="2" fill={BLUE} />
      <Path d="M12 15 L14 17 L19 12" stroke="#fff" strokeWidth={1.8} fill="none" strokeLinecap="round" />
    </>
  ),
  assessments: () => (
    <>
      <Rect x="8" y="6" width="16" height="20" rx="2" fill={TEAL} />
      <Rect x="12" y="12" width="8" height="1.6" rx="0.8" fill={PAPER} />
      <Rect x="12" y="16" width="6" height="1.6" rx="0.8" fill={PAPER} />
    </>
  ),
  quizzes: () => (
    <>
      <Rect x="7" y="6" width="18" height="20" rx="2" fill={BLUE} />
      <Circle cx="12" cy="13" r="1.2" fill={PAPER} />
      <Circle cx="12" cy="18" r="1.2" fill={PAPER} />
      <Rect x="15" y="12" width="6" height="1.6" rx="0.8" fill={SKY} />
      <Rect x="15" y="17" width="6" height="1.6" rx="0.8" fill={SKY} />
    </>
  ),
  'enrol-student': () => (
    <>
      <Person fill={BLUE} />
      <Circle cx="24" cy="22" r="4" fill={GREEN} />
      <Path d="M22 22 L23.5 23.5 L26 20" stroke="#fff" strokeWidth={1.3} fill="none" strokeLinecap="round" />
    </>
  ),
  'view-applications': () => (
    <>
      <Doc fill={BLUE} />
      <Circle cx="22" cy="22" r="5" fill={PAPER} />
      <Circle cx="22" cy="22" r="3" fill="none" stroke={BLUE_DEEP} strokeWidth={1.4} />
    </>
  ),
  prospects: () => <People a={BLUE} b={BLUE_DEEP} />,
  classrooms: () => <People a={CYAN} b={CYAN_DEEP} />,
  'parent-contacts': () => <People a={CYAN} b={BLUE} />,
  'staff-registry': () => <People a={CYAN} b={CYAN_DEEP} />,
  'student-registry': () => (
    <>
      <People />
      <Circle cx="25" cy="8" r="3.5" fill={GREEN} />
      <Path d="M25 6.5 V9.5 M23.5 8 H26.5" stroke="#fff" strokeWidth={1.2} strokeLinecap="round" />
    </>
  ),
  'convert-to-student': Cap,
  'archived-applications': () => (
    <>
      <Path d="M7 11 H25 V15 H7 Z" fill={BLUE_DEEP} />
      <Path d="M9 15 H23 V26 H9 Z" fill={BLUE} />
    </>
  ),
  'archived-students': () => (
    <>
      <Ellipse cx="16" cy="10" rx="8" ry="3" fill={BLUE} />
      <Path d="M8 10 V16 C8 18 12 19 16 19 C20 19 24 18 24 16 V10" fill={SKY} />
      <Path d="M8 16 V22 C8 24 12 25 16 25 C20 25 24 24 24 22 V16" fill={BLUE_DEEP} />
    </>
  ),
  'student-id': () => (
    <>
      <Rect x="5" y="8" width="22" height="16" rx="2" fill={BLUE} />
      <Circle cx="12" cy="15" r="3" fill={SKY} />
      <Rect x="17" y="13" width="7" height="1.6" rx="0.8" fill={PAPER} />
      <Rect x="17" y="17" width="5" height="1.6" rx="0.8" fill={SKY} />
    </>
  ),
  'id-card': () => (
    <>
      <Rect x="6" y="7" width="20" height="18" rx="2" fill={TEAL} />
      <Circle cx="13" cy="14" r="3" fill={PAPER} />
      <Rect x="18" y="12" width="5" height="1.4" rx="0.7" fill={PAPER} />
      <Rect x="18" y="16" width="4" height="1.4" rx="0.7" fill="#99F6E4" />
    </>
  ),
  'transfer-student': () => (
    <>
      <Path d="M6 12 H22 M18 8 L22 12 L18 16" stroke={BLUE} strokeWidth={2.2} fill="none" strokeLinecap="round" />
      <Path d="M26 20 H10 M14 16 L10 20 L14 24" stroke={CYAN} strokeWidth={2.2} fill="none" strokeLinecap="round" />
    </>
  ),
  'deactivate-student': () => (
    <>
      <Person fill={RED} />
      <Circle cx="23" cy="22" r="4" fill={INK} />
      <Path d="M21 22 H25" stroke="#fff" strokeWidth={1.4} strokeLinecap="round" />
    </>
  ),
  'mark-absent': () => (
    <>
      <Person fill={RED} />
      <Circle cx="23" cy="10" r="4" fill={RED} />
      <Path d="M21 8 L25 12 M25 8 L21 12" stroke="#fff" strokeWidth={1.3} strokeLinecap="round" />
    </>
  ),
  'student-records': () => (
    <>
      <Path d="M6 10 H14 L16 13 H26 V25 H6 Z" fill={BLUE} />
      <Rect x="10" y="17" width="10" height="1.6" rx="0.8" fill={SKY} />
    </>
  ),
  attendance: () => (
    <>
      <Rect x="8" y="6" width="16" height="20" rx="2" fill={BLUE} />
      <Rect x="12" y="4" width="8" height="4" rx="1" fill={YELLOW} />
    </>
  ),
  'attendance-report': () => (
    <>
      <Rect x="6" y="8" width="20" height="18" rx="2" fill={BLUE} />
      <Rect x="6" y="8" width="20" height="5" fill={BLUE_DEEP} />
      <Circle cx="11" cy="10.5" r="1" fill={PAPER} />
      <Circle cx="21" cy="10.5" r="1" fill={PAPER} />
    </>
  ),
  calendar: () => (
    <>
      <Rect x="6" y="8" width="20" height="18" rx="2" fill={ORANGE} />
      <Rect x="6" y="8" width="20" height="5" fill="#C2410C" />
    </>
  ),
  timetable: () => (
    <>
      <Rect x="6" y="7" width="20" height="18" rx="2" fill={PURPLE} />
      <Rect x="6" y="7" width="20" height="5" fill={PURPLE_SOFT} />
      <Rect x="10" y="16" width="4" height="4" rx="0.5" fill={PAPER} />
      <Rect x="16" y="16" width="4" height="4" rx="0.5" fill={YELLOW} />
    </>
  ),
  'leave-approvals': () => (
    <>
      <Rect x="6" y="8" width="20" height="18" rx="2" fill={ORANGE} />
      <Path d="M12 18 L15 21 L21 14" stroke="#fff" strokeWidth={1.8} fill="none" strokeLinecap="round" />
    </>
  ),
  'mark-attendance': () => (
    <>
      <Rect x="8" y="6" width="16" height="20" rx="2" fill={GREEN} />
      <Path d="M12 16 L15 19 L21 12" stroke="#fff" strokeWidth={1.8} fill="none" strokeLinecap="round" />
    </>
  ),
  results: () => (
    <>
      <Rect x="6" y="6" width="20" height="20" rx="3" fill={BLUE} />
      <Path d="M10 20 L12 12 H15 L17 20 M11 17 H16" stroke="#fff" strokeWidth={1.4} fill="none" />
      <Path d="M19 12 H23 V20 H19 Z" fill={YELLOW} />
    </>
  ),
  marks: () => (
    <>
      <Circle cx="16" cy="16" r="10" fill={BLUE} />
      <Path d="M11 18 L13 12 H16 L18 18" stroke="#fff" strokeWidth={1.5} fill="none" />
    </>
  ),
  'report-cards': Bars,
  'grading-schemes': () => (
    <>
      <Circle cx="16" cy="13" r="6" fill={ORANGE} />
      <Path d="M10 18 L16 28 L22 18" fill={YELLOW} />
    </>
  ),
  award: () => (
    <>
      <Circle cx="16" cy="12" r="6" fill={YELLOW} />
      <Path d="M11 17 L8 26 L16 22 L24 26 L21 17" fill={ORANGE} />
    </>
  ),
  moderation: () => (
    <>
      <Path d="M16 5 L26 9 V16 C26 21 21 25 16 27 C11 25 6 21 6 16 V9 Z" fill={BLUE} />
      <Path d="M12 16 L15 19 L21 13" stroke="#fff" strokeWidth={1.8} fill="none" strokeLinecap="round" />
    </>
  ),
  shield: () => <Path d="M16 5 L26 9 V16 C26 21 21 25 16 27 C11 25 6 21 6 16 V9 Z" fill={TEAL} />,
  'apply-leave': () => (
    <Doc fill={BLUE} mark={<Path d="M16 15 V22 M13 18.5 H19" stroke="#fff" strokeWidth={1.6} strokeLinecap="round" />} />
  ),
  'leave-types': () => (
    <>
      <Path d="M16 26 C16 26 8 20 8 14 C8 10 12 8 16 12 C20 8 24 10 24 14 C24 20 16 26 16 26 Z" fill={TEAL} />
      <Path d="M16 26 V14" stroke={GREEN_DEEP} strokeWidth={1.4} />
    </>
  ),
  'staff-advances': () => (
    <>
      <Rect x="6" y="12" width="20" height="12" rx="2" fill={ORANGE} />
      <Circle cx="16" cy="18" r="3" fill={CREAM} />
    </>
  ),
  visitors: () => <Person fill={TEAL} />,
  'visitor-check-in': () => (
    <>
      <House />
      <Circle cx="24" cy="22" r="4" fill={GREEN} />
      <Path d="M24 20 V24 M22 22 H26" stroke="#fff" strokeWidth={1.2} strokeLinecap="round" />
    </>
  ),
  routes: () => <Pin fill={PURPLE} />,
  'drop-off-points': () => <Pin fill={BLUE} />,
  'map-pin': () => <Pin fill={RED} />,
  trips: () => (
    <>
      <Circle cx="8" cy="22" r="3" fill={TEAL} />
      <Circle cx="24" cy="8" r="3" fill={ORANGE} />
      <Path d="M10 20 C14 20 14 10 22 10" stroke={TEAL} strokeWidth={2} fill="none" />
    </>
  ),
  navigation: () => <Path d="M6 16 L26 6 L18 26 L15 17 Z" fill={BLUE} />,
  vehicles: () => (
    <>
      <Path d="M8 20 L10 12 H22 L24 20 Z" fill={ORANGE} />
      <Circle cx="12" cy="21" r="2" fill={INK} />
      <Circle cx="20" cy="21" r="2" fill={INK} />
    </>
  ),
  car: () => (
    <>
      <Path d="M7 18 L10 12 H22 L25 18 V22 H7 Z" fill={BLUE} />
      <Circle cx="11" cy="22" r="2" fill={INK} />
      <Circle cx="21" cy="22" r="2" fill={INK} />
    </>
  ),
  wrench: () => <Path d="M20 6 A6 6 0 0 0 14 14 L6 22 L10 26 L18 18 A6 6 0 0 0 26 12 L22 14 L18 10 Z" fill={ORANGE} />,
  maintenance: () => (
    <>
      <Rect x="8" y="6" width="16" height="20" rx="2" fill={GREEN} />
      <Path d="M12 14 H20 M12 18 H17" stroke="#fff" strokeWidth={1.5} strokeLinecap="round" />
    </>
  ),
  inventory: () => <Rect x="7" y="8" width="18" height="16" rx="2" fill={ORANGE} />,
  cube: () => (
    <>
      <Path d="M16 5 L26 10 V22 L16 27 L6 22 V10 Z" fill={PURPLE} />
      <Path d="M16 5 L26 10 L16 15 L6 10 Z" fill={PURPLE_SOFT} />
    </>
  ),
  library: () => (
    <>
      <Rect x="6" y="8" width="4" height="16" fill={RED} />
      <Rect x="11" y="6" width="4" height="18" fill={BLUE} />
      <Rect x="16" y="9" width="4" height="15" fill={GREEN} />
      <Rect x="21" y="7" width="4" height="17" fill={YELLOW} />
    </>
  ),
  assets: () => (
    <>
      <Rect x="8" y="8" width="16" height="16" rx="2" fill={INK} />
      <Circle cx="16" cy="16" r="4" fill={CYAN} />
    </>
  ),
  'send-sms': () => <Path d="M6 16 L26 6 L18 26 L15 17 Z" fill={BLUE} />,
  'create-announcement': () => (
    <>
      <Path d="M8 14 L22 8 V24 L8 18 Z" fill={ORANGE} />
      <Path d="M8 14 V18" stroke={YELLOW} strokeWidth={3} />
      <Path d="M22 12 C25 14 25 18 22 20" stroke={ORANGE} strokeWidth={1.6} fill="none" />
    </>
  ),
  megaphone: () => (
    <>
      <Path d="M7 13 L22 8 V24 L7 19 Z" fill={ORANGE} />
      <Rect x="6" y="13" width="4" height="6" fill={YELLOW} />
    </>
  ),
  list: () => (
    <>
      <Rect x="6" y="7" width="20" height="18" rx="2" fill={BLUE} />
      <Rect x="10" y="11" width="12" height="1.6" rx="0.8" fill={PAPER} />
      <Rect x="10" y="15" width="12" height="1.6" rx="0.8" fill={PAPER} />
      <Rect x="10" y="19" width="8" height="1.6" rx="0.8" fill={SKY} />
    </>
  ),
  flash: () => <Path d="M18 4 L8 18 H15 L13 28 L24 13 H17 Z" fill={YELLOW} />,
  download: () => (
    <>
      <Path d="M16 5 V20 M10 15 L16 21 L22 15" stroke={BLUE} strokeWidth={2.4} fill="none" strokeLinecap="round" />
      <Path d="M7 25 H25" stroke={BLUE_DEEP} strokeWidth={2.4} strokeLinecap="round" />
    </>
  ),
  bug: () => (
    <>
      <Ellipse cx="16" cy="17" rx="6" ry="7" fill={RED} />
      <Path d="M10 12 L6 8 M22 12 L26 8 M10 22 L6 26 M22 22 L26 26" stroke={INK} strokeWidth={1.4} strokeLinecap="round" />
    </>
  ),
};

export function SheetIcon({ name, size }: { name: AppIconName; size: number }) {
  return <Frame size={size}>{DRAW[name]()}</Frame>;
}
