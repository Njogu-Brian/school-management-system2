import React from 'react';
import Svg, { Circle, Ellipse, G, Path, Rect } from 'react-native-svg';

export type Soft3DGlyphKey =
  | 'home'
  | 'grid'
  | 'students'
  | 'people'
  | 'person'
  | 'finance'
  | 'cash'
  | 'wallet'
  | 'card'
  | 'receipt'
  | 'briefcase'
  | 'leave'
  | 'payroll'
  | 'clock'
  | 'attendance'
  | 'approvals'
  | 'admissions'
  | 'school'
  | 'book'
  | 'bus'
  | 'car'
  | 'clipboard'
  | 'visitor'
  | 'chart'
  | 'megaphone'
  | 'chat'
  | 'mail'
  | 'settings'
  | 'notifications'
  | 'search'
  | 'shield'
  | 'checkmark'
  | 'add'
  | 'generic';

/** Map Ionicons-style names → soft-3D glyph keys. */
export function resolveSoft3DGlyph(
  name?: string,
  explicit?: Soft3DGlyphKey,
): Soft3DGlyphKey {
  if (explicit) return explicit;
  if (!name) return 'generic';
  const n = name.toLowerCase().replace(/-outline$/, '').replace(/-sharp$/, '');
  const map: Record<string, Soft3DGlyphKey> = {
    home: 'home',
    grid: 'grid',
    'people-circle': 'students',
    people: 'people',
    person: 'person',
    'person-add': 'admissions',
    cash: 'cash',
    wallet: 'wallet',
    card: 'card',
    receipt: 'receipt',
    briefcase: 'briefcase',
    calendar: 'leave',
    'calendar-clear': 'leave',
    time: 'clock',
    'checkmark-done': 'approvals',
    checkbox: 'approvals',
    school: 'school',
    book: 'book',
    bus: 'bus',
    car: 'car',
    clipboard: 'attendance',
    'bar-chart': 'chart',
    'pie-chart': 'chart',
    analytics: 'chart',
    'stats-chart': 'chart',
    megaphone: 'megaphone',
    chatbubble: 'chat',
    mail: 'mail',
    settings: 'settings',
    notifications: 'notifications',
    search: 'search',
    shield: 'shield',
    'shield-checkmark': 'shield',
    checkmark: 'checkmark',
    add: 'add',
    create: 'add',
    ribbon: 'school',
    'id-card': 'person',
    navigate: 'bus',
  };
  if (map[n]) return map[n];
  if (n.includes('leave')) return 'leave';
  if (n.includes('payroll')) return 'payroll';
  if (n.includes('cash') || n.includes('collect')) return 'cash';
  if (n.includes('clock') || n.includes('time')) return 'clock';
  if (n.includes('attend') || n.includes('clipboard')) return 'attendance';
  if (n.includes('approv') || n.includes('checkmark-done')) return 'approvals';
  if (n.includes('admit') || n.includes('application')) return 'admissions';
  if (n.includes('student')) return 'students';
  if (n.includes('staff') || n.includes('people') || n.includes('person')) return 'people';
  if (n.includes('finance') || n.includes('wallet')) return 'wallet';
  if (n.includes('sms') || n.includes('chat')) return 'chat';
  if (n.includes('announce') || n.includes('megaphone')) return 'megaphone';
  if (n.includes('report') || n.includes('chart')) return 'chart';
  if (n.includes('setting')) return 'settings';
  if (n.includes('notif')) return 'notifications';
  if (n.includes('transport') || n.includes('bus')) return 'bus';
  if (n.includes('visitor')) return 'visitor';
  if (n.includes('requisition')) return 'clipboard';
  if (n.includes('log-out') || n.includes('logout')) return 'shield';
  if (n.includes('check')) return 'approvals';
  return 'generic';
}

type GlyphProps = { size: number };

function Shell({ size, children }: { size: number; children: React.ReactNode }) {
  return (
    <Svg width={size} height={size} viewBox="0 0 64 64">
      {/* Soft contact shadow under the illustration — not a colored well */}
      <Ellipse cx="32" cy="56" rx="18" ry="4" fill="#000" opacity={0.18} />
      {children}
    </Svg>
  );
}

/**
 * Standalone colorful 3D glyphs — each owns its palette (no shared tone fill).
 */
export const Soft3DGlyph: React.FC<{ glyph: Soft3DGlyphKey; size: number; muted?: boolean }> = ({
  glyph,
  size,
}) => {
  switch (glyph) {
    case 'home':
    case 'grid':
      return <HomeGlyph size={size} />;
    case 'students':
    case 'people':
      return <PeopleGlyph size={size} />;
    case 'person':
      return <PersonGlyph size={size} />;
    case 'finance':
    case 'cash':
    case 'payroll':
      return <CashGlyph size={size} />;
    case 'wallet':
      return <WalletGlyph size={size} />;
    case 'card':
      return <CardGlyph size={size} />;
    case 'receipt':
      return <ReceiptGlyph size={size} />;
    case 'briefcase':
      return <BriefcaseGlyph size={size} />;
    case 'leave':
      return <CalendarGlyph size={size} />;
    case 'clock':
      return <ClockGlyph size={size} />;
    case 'attendance':
      return <AttendanceGlyph size={size} />;
    case 'approvals':
    case 'checkmark':
      return <CheckGlyph size={size} />;
    case 'admissions':
    case 'visitor':
      return <AdmitGlyph size={size} />;
    case 'school':
    case 'book':
      return <SchoolGlyph size={size} />;
    case 'bus':
    case 'car':
      return <BusGlyph size={size} />;
    case 'clipboard':
      return <ClipboardGlyph size={size} />;
    case 'chart':
      return <ChartGlyph size={size} />;
    case 'megaphone':
      return <MegaphoneGlyph size={size} />;
    case 'chat':
    case 'mail':
      return <ChatGlyph size={size} />;
    case 'settings':
      return <SettingsGlyph size={size} />;
    case 'notifications':
      return <BellGlyph size={size} />;
    case 'search':
      return <SearchGlyph size={size} />;
    case 'shield':
      return <ShieldGlyph size={size} />;
    case 'add':
      return <AddGlyph size={size} />;
    default:
      return <GenericGlyph size={size} />;
  }
};

/** Blue house — dashboard / home */
function HomeGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M10 30 L32 12 L54 30 V50 A5 5 0 0 1 49 55 H15 A5 5 0 0 1 10 50 Z" fill="#1D4ED8" />
      <Path d="M10 30 L32 12 L54 30 L32 22 Z" fill="#60A5FA" />
      <Rect x="26" y="36" width="12" height="19" rx="2" fill="#FDE68A" />
      <Rect x="28" y="40" width="3" height="4" rx="0.5" fill="#92400E" opacity={0.5} />
      <Ellipse cx="32" cy="16" rx="10" ry="3.5" fill="#fff" opacity={0.35} />
    </Shell>
  );
}

/** Teal/cyan people group — students / people */
function PeopleGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Circle cx="24" cy="20" r="9" fill="#67E8F9" />
      <Circle cx="24" cy="18" r="5" fill="#ECFEFF" opacity={0.5} />
      <Path d="M8 52 C8 38 16 32 24 32 C32 32 40 38 40 52 Z" fill="#0891B2" />
      <Circle cx="42" cy="22" r="8" fill="#22D3EE" />
      <Path d="M32 52 C32 40 36 34 42 34 C50 34 56 40 56 52 Z" fill="#0E7490" />
      <Ellipse cx="24" cy="16" rx="5" ry="2.5" fill="#fff" opacity={0.45} />
    </Shell>
  );
}

function PersonGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Circle cx="32" cy="20" r="11" fill="#FDBA74" />
      <Circle cx="32" cy="18" r="6" fill="#FFEDD5" opacity={0.55} />
      <Path d="M12 54 C12 38 20 32 32 32 C44 32 52 38 52 54 Z" fill="#EA580C" />
      <Path d="M12 54 C20 44 26 40 32 40 C40 40 48 44 52 54 Z" fill="#9A3412" opacity={0.35} />
    </Shell>
  );
}

/** Gold/green banknotes & coins — collections / cash / payroll */
function CashGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Rect x="8" y="22" width="40" height="24" rx="4" fill="#16A34A" />
      <Rect x="12" y="18" width="40" height="24" rx="4" fill="#4ADE80" />
      <Circle cx="32" cy="30" r="7" fill="#FEF08A" />
      <Circle cx="32" cy="30" r="4.5" fill="#EAB308" />
      <Path d="M32 26 V34 M29 28 H35 M29 32 H35" stroke="#854D0E" strokeWidth={1.8} strokeLinecap="round" />
      <Circle cx="48" cy="42" r="10" fill="#FACC15" />
      <Circle cx="48" cy="42" r="7" fill="#EAB308" />
      <Ellipse cx="28" cy="22" rx="12" ry="3" fill="#fff" opacity={0.3} />
    </Shell>
  );
}

/** Brown wallet with gold clasp */
function WalletGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M10 18 H42 A7 7 0 0 1 49 25 V47 A7 7 0 0 1 42 54 H10 A7 7 0 0 1 3 47 V25 A7 7 0 0 1 10 18 Z" fill="#92400E" />
      <Path d="M10 18 H38 V30 H10 Z" fill="#D97706" />
      <Rect x="36" y="32" width="18" height="16" rx="4" fill="#FBBF24" />
      <Circle cx="47" cy="40" r="3" fill="#FEF3C7" />
      <Ellipse cx="24" cy="22" rx="10" ry="3" fill="#fff" opacity={0.25} />
    </Shell>
  );
}

/** Orange card */
function CardGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Rect x="6" y="16" width="52" height="32" rx="6" fill="#EA580C" />
      <Rect x="6" y="16" width="52" height="10" fill="#9A3412" />
      <Rect x="12" y="36" width="20" height="5" rx="2" fill="#FDBA74" />
      <Rect x="38" y="36" width="12" height="5" rx="2" fill="#FED7AA" />
      <Ellipse cx="24" cy="20" rx="14" ry="3" fill="#fff" opacity={0.25} />
    </Shell>
  );
}

/** Paper receipt with green check */
function ReceiptGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M16 8 H48 V52 L42 48 L36 52 L30 48 L24 52 L18 48 Z" fill="#E0F2FE" />
      <Path d="M16 8 H48 V18 H16 Z" fill="#38BDF8" />
      <Rect x="22" y="24" width="20" height="3" rx="1.5" fill="#0284C7" opacity={0.45} />
      <Rect x="22" y="32" width="16" height="3" rx="1.5" fill="#0284C7" opacity={0.35} />
      <Circle cx="44" cy="44" r="10" fill="#22C55E" />
      <Path d="M38 44 L42 48 L50 38" stroke="#fff" strokeWidth={3} fill="none" strokeLinecap="round" strokeLinejoin="round" />
    </Shell>
  );
}

/** Navy briefcase — HR */
function BriefcaseGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Rect x="8" y="22" width="48" height="30" rx="6" fill="#1E3A8A" />
      <Path d="M22 22 V18 A4 4 0 0 1 26 14 H38 A4 4 0 0 1 42 18 V22" stroke="#93C5FD" strokeWidth={4} fill="none" />
      <Rect x="8" y="22" width="48" height="12" fill="#3B82F6" />
      <Rect x="28" y="34" width="8" height="6" rx="2" fill="#FBBF24" />
    </Shell>
  );
}

/** Coral calendar — leave */
function CalendarGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Rect x="10" y="14" width="44" height="40" rx="6" fill="#FFF7ED" />
      <Rect x="10" y="14" width="44" height="14" fill="#F97316" />
      <Circle cx="20" cy="12" r="3.5" fill="#FB923C" />
      <Circle cx="44" cy="12" r="3.5" fill="#FB923C" />
      <Rect x="18" y="36" width="10" height="10" rx="2" fill="#FED7AA" />
      <Rect x="34" y="36" width="10" height="10" rx="2" fill="#EA580C" />
    </Shell>
  );
}

/** White/blue clock */
function ClockGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Circle cx="32" cy="32" r="22" fill="#1E40AF" />
      <Circle cx="32" cy="32" r="17" fill="#EFF6FF" />
      <Path d="M32 18 V32 L44 38" stroke="#1D4ED8" strokeWidth={3.5} strokeLinecap="round" fill="none" />
      <Circle cx="32" cy="32" r="3" fill="#F59E0B" />
      <Ellipse cx="26" cy="22" rx="8" ry="3" fill="#fff" opacity={0.5} />
    </Shell>
  );
}

/** Clipboard with green check — attendance */
function AttendanceGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Rect x="14" y="10" width="36" height="46" rx="6" fill="#F8FAFC" />
      <Rect x="14" y="10" width="36" height="12" fill="#6366F1" />
      <Rect x="24" y="6" width="16" height="10" rx="3" fill="#A5B4FC" />
      <Path d="M24 36 L30 42 L44 26" stroke="#16A34A" strokeWidth={4} strokeLinecap="round" strokeLinejoin="round" fill="none" />
    </Shell>
  );
}

/** Green check badge — approvals */
function CheckGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Circle cx="32" cy="32" r="22" fill="#15803D" />
      <Circle cx="32" cy="32" r="17" fill="#4ADE80" />
      <Path d="M20 33 L28 41 L46 22" stroke="#14532D" strokeWidth={5} strokeLinecap="round" strokeLinejoin="round" fill="none" />
      <Ellipse cx="26" cy="22" rx="8" ry="3" fill="#fff" opacity={0.4} />
    </Shell>
  );
}

/** Schoolhouse + person add — admissions */
function AdmitGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M6 34 L24 20 L42 34 V50 H6 Z" fill="#7C3AED" />
      <Path d="M6 34 L24 20 L42 34 L24 28 Z" fill="#C4B5FD" />
      <Rect x="18" y="38" width="10" height="12" fill="#FDE68A" />
      <Circle cx="48" cy="28" r="12" fill="#F59E0B" />
      <Path d="M48 21 V35 M41 28 H55" stroke="#fff" strokeWidth={3.5} strokeLinecap="round" />
    </Shell>
  );
}

/** Purple school / book */
function SchoolGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M6 30 L32 14 L58 30 L32 46 Z" fill="#6D28D9" />
      <Path d="M6 30 L32 14 L58 30 L32 24 Z" fill="#A78BFA" />
      <Rect x="26" y="42" width="12" height="12" fill="#FBBF24" />
      <Path d="M54 32 V48" stroke="#C4B5FD" strokeWidth={4} strokeLinecap="round" />
    </Shell>
  );
}

/** Yellow/blue bus — transport */
function BusGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Rect x="8" y="16" width="48" height="28" rx="8" fill="#EAB308" />
      <Rect x="12" y="20" width="14" height="12" rx="2" fill="#EFF6FF" />
      <Rect x="30" y="20" width="14" height="12" rx="2" fill="#EFF6FF" />
      <Rect x="46" y="24" width="6" height="8" rx="1" fill="#1D4ED8" />
      <Circle cx="18" cy="46" r="7" fill="#1E293B" />
      <Circle cx="46" cy="46" r="7" fill="#1E293B" />
      <Circle cx="18" cy="46" r="3" fill="#94A3B8" />
      <Circle cx="46" cy="46" r="3" fill="#94A3B8" />
    </Shell>
  );
}

function ClipboardGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Rect x="14" y="12" width="36" height="44" rx="6" fill="#FEF3C7" />
      <Rect x="22" y="8" width="20" height="12" rx="3" fill="#F59E0B" />
      <Rect x="20" y="28" width="24" height="3" rx="1.5" fill="#B45309" opacity={0.45} />
      <Rect x="20" y="36" width="18" height="3" rx="1.5" fill="#B45309" opacity={0.35} />
      <Rect x="20" y="44" width="20" height="3" rx="1.5" fill="#B45309" opacity={0.28} />
    </Shell>
  );
}

/** Multi-color bar chart */
function ChartGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Rect x="10" y="32" width="12" height="20" rx="3" fill="#38BDF8" />
      <Rect x="26" y="20" width="12" height="32" rx="3" fill="#22C55E" />
      <Rect x="42" y="12" width="12" height="40" rx="3" fill="#F59E0B" />
      <Ellipse cx="32" cy="12" rx="16" ry="3" fill="#fff" opacity={0.2} />
    </Shell>
  );
}

/** Magenta megaphone */
function MegaphoneGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M12 26 H28 L50 12 V52 L28 38 H12 Z" fill="#DB2777" />
      <Path d="M28 12 L50 12 V28 L28 28 Z" fill="#F9A8D4" />
      <Rect x="8" y="26" width="10" height="14" rx="2" fill="#9D174D" />
      <Path d="M52 22 C58 28 58 36 52 42" stroke="#F472B6" strokeWidth={3} fill="none" strokeLinecap="round" />
    </Shell>
  );
}

/** Cyan chat bubble */
function ChatGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M10 14 H50 A6 6 0 0 1 56 20 V36 A6 6 0 0 1 50 42 H28 L14 54 V42 H10 A6 6 0 0 1 4 36 V20 A6 6 0 0 1 10 14 Z" fill="#06B6D4" />
      <Path d="M10 14 H50 A6 6 0 0 1 56 20 V26 H10 Z" fill="#67E8F9" />
      <Circle cx="22" cy="30" r="2.8" fill="#fff" />
      <Circle cx="32" cy="30" r="2.8" fill="#fff" />
      <Circle cx="42" cy="30" r="2.8" fill="#fff" />
    </Shell>
  );
}

function SettingsGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Circle cx="32" cy="32" r="14" fill="#64748B" />
      <Circle cx="32" cy="32" r="7" fill="#E2E8F0" />
      <G fill="#94A3B8">
        <Rect x="29" y="6" width="6" height="12" rx="2" />
        <Rect x="29" y="46" width="6" height="12" rx="2" />
        <Rect x="6" y="29" width="12" height="6" rx="2" />
        <Rect x="46" y="29" width="12" height="6" rx="2" />
      </G>
    </Shell>
  );
}

function BellGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M18 28 C18 16 24 12 32 12 C40 12 46 16 46 28 V36 L52 46 H12 L18 36 Z" fill="#F59E0B" />
      <Path d="M18 28 C18 16 24 12 32 12 C40 12 46 16 46 28 V30 H18 Z" fill="#FCD34D" />
      <Ellipse cx="32" cy="50" rx="9" ry="5" fill="#B45309" />
      <Circle cx="32" cy="10" r="3.5" fill="#FDE68A" />
    </Shell>
  );
}

function SearchGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Circle cx="26" cy="26" r="16" fill="#0EA5E9" />
      <Circle cx="26" cy="26" r="11" fill="#E0F2FE" />
      <Path d="M38 38 L54 54" stroke="#0369A1" strokeWidth={6} strokeLinecap="round" />
      <Ellipse cx="22" cy="20" rx="6" ry="3" fill="#fff" opacity={0.55} />
    </Shell>
  );
}

/** Gold shield */
function ShieldGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Path d="M32 8 L52 16 V34 C52 48 40 56 32 58 C24 56 12 48 12 34 V16 Z" fill="#CA8A04" />
      <Path d="M32 8 L52 16 V22 L32 16 Z" fill="#FDE047" />
      <Path d="M24 32 L30 38 L42 24" stroke="#713F12" strokeWidth={3.5} fill="none" strokeLinecap="round" />
    </Shell>
  );
}

function AddGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Circle cx="32" cy="32" r="22" fill="#2563EB" />
      <Circle cx="32" cy="32" r="17" fill="#60A5FA" />
      <Path d="M32 18 V46 M18 32 H46" stroke="#fff" strokeWidth={5} strokeLinecap="round" />
    </Shell>
  );
}

function GenericGlyph({ size }: GlyphProps) {
  return (
    <Shell size={size}>
      <Circle cx="32" cy="32" r="20" fill="#6366F1" />
      <Circle cx="32" cy="32" r="12" fill="#C4B5FD" />
      <Circle cx="32" cy="32" r="5" fill="#EEF2FF" />
    </Shell>
  );
}
