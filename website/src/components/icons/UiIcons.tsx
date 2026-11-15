import type { ReactNode } from "react";

type IconProps = { className?: string };

function base(props: IconProps & { children: ReactNode; label?: string }) {
  const { className = "h-5 w-5", children, label } = props;
  return (
    <svg
      className={className}
      viewBox="0 0 24 24"
      fill="none"
      stroke="currentColor"
      strokeWidth="1.75"
      strokeLinecap="round"
      strokeLinejoin="round"
      aria-hidden={label ? undefined : true}
      role={label ? "img" : undefined}
      aria-label={label}
    >
      {children}
    </svg>
  );
}

export function LeafIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M11 20A7 7 0 0 1 9.8 6.1C15.5 4 18 2 22 2c0 4-2.5 6.5-8.1 10.2" />
        <path d="M2 21c3-3 6.5-4.5 9-4.5" />
      </>
    ),
  });
}

export function HomeIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M3 10.5 12 3l9 7.5" />
        <path d="M5 9.5V21h14V9.5" />
        <path d="M9 21v-7h6v7" />
      </>
    ),
  });
}

export function HandshakeIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M8 13c1.5 1.5 3 2 4 2s2.5-.5 4-2" />
        <path d="M3 11 7.5 6.5a2 2 0 0 1 2.8 0L12 8.2" />
        <path d="M21 11 16.5 6.5a2 2 0 0 0-2.8 0L12 8.2" />
        <path d="M7 16.5 5.5 18a2.5 2.5 0 0 0 3.5 3.5L11 19" />
        <path d="M17 16.5 18.5 18a2.5 2.5 0 0 1-3.5 3.5L13 19" />
      </>
    ),
  });
}

export function CrossIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M12 3v18" />
        <path d="M7 8h10" />
      </>
    ),
  });
}

export function GemIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M6 3h12l4 6-10 12L2 9l4-6Z" />
        <path d="M2 9h20" />
        <path d="M12 21 8 9l2-6" />
        <path d="M12 21 16 9l-2-6" />
      </>
    ),
  });
}

export function CrownIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M3 18h18l-1.5-9-4.5 4L12 5l-3 8-4.5-4L3 18Z" />
        <path d="M3 18h18v2H3z" />
      </>
    ),
  });
}

export function CodeIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M8 8 3 12l5 4" />
        <path d="M16 8l5 4-5 4" />
        <path d="M13 5 11 19" />
      </>
    ),
  });
}

export function MusicIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M9 18V5l12-2v13" />
        <circle cx="6" cy="18" r="3" />
        <circle cx="18" cy="16" r="3" />
      </>
    ),
  });
}

export function BalletIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <circle cx="12" cy="5" r="2" />
        <path d="M12 7v5" />
        <path d="M8 21l4-9 4 9" />
        <path d="M7 12h10" />
      </>
    ),
  });
}

export function SkateIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M4 14h12l3 3H7l-3-3Z" />
        <path d="M6 17v2" />
        <path d="M16 17v2" />
        <circle cx="7" cy="20.5" r="1.5" />
        <circle cx="17" cy="20.5" r="1.5" />
        <path d="M8 10h7l1 4" />
      </>
    ),
  });
}

export function ArcheryIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <circle cx="12" cy="12" r="8" />
        <circle cx="12" cy="12" r="3" />
        <path d="M12 2v4" />
        <path d="M22 12h-4" />
      </>
    ),
  });
}

export function SportsIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <circle cx="12" cy="12" r="9" />
        <path d="M12 3c2.5 2.5 2.5 15.5 0 18" />
        <path d="M3.5 9h17" />
        <path d="M3.5 15h17" />
      </>
    ),
  });
}

export function DramaIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M4 8c0-2 2-4 5-4h1l1 3 1-3h1c3 0 5 2 5 4v6c0 2-2 4-5 4H9c-3 0-5-2-5-4V8Z" />
        <path d="M9 12h.01" />
        <path d="M15 12h.01" />
        <path d="M9.5 15.5c1 1 4 1 5 0" />
      </>
    ),
  });
}

export function BookIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M4 5a2 2 0 0 1 2-2h12v16H6a2 2 0 0 0-2 2V5Z" />
        <path d="M18 3v16" />
      </>
    ),
  });
}

export function MealIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M4 11h16v2a6 6 0 0 1-6 6H10a6 6 0 0 1-6-6v-2Z" />
        <path d="M8 11V5" />
        <path d="M12 11V4" />
        <path d="M16 11V6" />
      </>
    ),
  });
}

export function TrophyIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M8 21h8" />
        <path d="M12 17v4" />
        <path d="M7 4h10v5a5 5 0 0 1-10 0V4Z" />
        <path d="M17 5h2a2 2 0 0 1 0 4h-2" />
        <path d="M7 5H5a2 2 0 0 0 0 4h2" />
      </>
    ),
  });
}

export function BusIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M4 6a2 2 0 0 1 2-2h12a2 2 0 0 1 2 2v10H4V6Z" />
        <path d="M4 16h16v2a1 1 0 0 1-1 1h-1" />
        <path d="M6 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
        <path d="M18 19a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z" />
        <path d="M4 10h16" />
      </>
    ),
  });
}

export function ShieldIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M12 3 4 6v6c0 5 3.5 8 8 9 4.5-1 8-4 8-9V6l-8-3Z" />
        <path d="M9 12l2 2 4-4" />
      </>
    ),
  });
}

export function SearchIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <circle cx="11" cy="11" r="7" />
        <path d="M20 20l-3.5-3.5" />
      </>
    ),
  });
}

export function ChatIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M4 6a3 3 0 0 1 3-3h10a3 3 0 0 1 3 3v8a3 3 0 0 1-3 3H9l-5 4V6Z" />
      </>
    ),
  });
}

export function EyeIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M2 12s3.5-7 10-7 10 7 10 7-3.5 7-10 7S2 12 2 12Z" />
        <circle cx="12" cy="12" r="3" />
      </>
    ),
  });
}

export function LockIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <rect x="4" y="10" width="16" height="11" rx="2" />
        <path d="M8 10V7a4 4 0 0 1 8 0v3" />
      </>
    ),
  });
}

export function DoorIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M6 21V4a1 1 0 0 1 1-1h10a1 1 0 0 1 1 1v17" />
        <path d="M3 21h18" />
        <path d="M14 12h.01" />
      </>
    ),
  });
}

export function CheckIcon(p: IconProps) {
  return base({
    ...p,
    children: <path d="M5 12.5 10 17l9-10" />,
  });
}

export function SchoolIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M3 10 12 4l9 6" />
        <path d="M5 10v8h14v-8" />
        <path d="M9 18v-4h6v4" />
      </>
    ),
  });
}

export function MegaphoneIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M3 11v2a3 3 0 0 0 3 3h1l2 4h2v-4h2l6 2V5l-6 2H4a1 1 0 0 0-1 1Z" />
      </>
    ),
  });
}

export function MapPinIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M12 21s7-5.5 7-12a7 7 0 1 0-14 0c0 6.5 7 12 7 12Z" />
        <circle cx="12" cy="9" r="2.5" />
      </>
    ),
  });
}

export function PhoneIcon(p: IconProps) {
  return base({
    ...p,
    children: (
      <>
        <path d="M6.5 3.5 9 6l-1.5 2.5a14 14 0 0 0 8 8L18 15l2.5 2.5-1 2a2 2 0 0 1-2.2 1.2C9.5 19.5 4.5 14.5 3.3 7.7A2 2 0 0 1 4.5 5.5l2-2Z" />
      </>
    ),
  });
}

export function StarIcon(p: IconProps) {
  return base({
    ...p,
    children: <path d="M12 3l2.5 6.5L21 10l-5 4.5L17.5 21 12 17.5 6.5 21 8 14.5 3 10l6.5-.5L12 3Z" />,
  });
}

const ICON_MAP: Record<string, (p: IconProps) => ReactNode> = {
  holistic: LeafIcon,
  "🌱": LeafIcon,
  nurturing: HomeIcon,
  "🏡": HomeIcon,
  collaborative: HandshakeIcon,
  "🤝": HandshakeIcon,
  faith: CrossIcon,
  "✝️": CrossIcon,
  "✝": CrossIcon,
  character: GemIcon,
  "💎": GemIcon,
  leaders: CrownIcon,
  "👑": CrownIcon,
  coding: CodeIcon,
  "💻": CodeIcon,
  music: MusicIcon,
  "🎵": MusicIcon,
  ballet: BalletIcon,
  "🩰": BalletIcon,
  skating: SkateIcon,
  "⛸️": SkateIcon,
  "⛸": SkateIcon,
  archery: ArcheryIcon,
  "🏹": ArcheryIcon,
  sports: SportsIcon,
  "⚽": SportsIcon,
  drama: DramaIcon,
  "🎭": DramaIcon,
  worship: CrossIcon,
  book: BookIcon,
  "📚": BookIcon,
  meal: MealIcon,
  "🍽️": MealIcon,
  "🍽": MealIcon,
  trophy: TrophyIcon,
  "🏆": TrophyIcon,
  bus: BusIcon,
  "🚌": BusIcon,
  shield: ShieldIcon,
  "🛡️": ShieldIcon,
  "🛡": ShieldIcon,
  search: SearchIcon,
  "🔎": SearchIcon,
  chat: ChatIcon,
  "💬": ChatIcon,
  eye: EyeIcon,
  "👁️": EyeIcon,
  "👁": EyeIcon,
  lock: LockIcon,
  "🔐": LockIcon,
  door: DoorIcon,
  "🚪": DoorIcon,
  check: CheckIcon,
  "✓": CheckIcon,
  school: SchoolIcon,
  "🏫": SchoolIcon,
  megaphone: MegaphoneIcon,
  "📢": MegaphoneIcon,
  pin: MapPinIcon,
  "📍": MapPinIcon,
  phone: PhoneIcon,
  "☎": PhoneIcon,
  "📞": PhoneIcon,
  star: StarIcon,
  "✦": StarIcon,
};

export function UiIcon({ name, className = "h-5 w-5" }: { name: string; className?: string }) {
  const Icon = ICON_MAP[name] || StarIcon;
  return <>{Icon({ className })}</>;
}
