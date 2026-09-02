export type PasswordCheckId = 'length' | 'upper' | 'lower' | 'digit';

export type PasswordCheck = {
  id: PasswordCheckId;
  label: string;
  ok: boolean;
};

export const PASSWORD_MIN_LENGTH = 8;

export function passwordChecklist(value: string): PasswordCheck[] {
  return [
    { id: 'length', label: `At least ${PASSWORD_MIN_LENGTH} characters`, ok: value.length >= PASSWORD_MIN_LENGTH },
    { id: 'upper', label: 'A capital letter (A–Z)', ok: /[A-Z]/.test(value) },
    { id: 'lower', label: 'A small letter (a–z)', ok: /[a-z]/.test(value) },
    { id: 'digit', label: 'A number (0–9)', ok: /\d/.test(value) },
  ];
}

export function isStrongPassword(value: string): boolean {
  return passwordChecklist(value).every((item) => item.ok);
}

export function generatePassword(length = 10): string {
  const size = Math.max(PASSWORD_MIN_LENGTH, length);
  const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
  const lower = 'abcdefghijkmnopqrstuvwxyz';
  const digits = '23456789';
  const all = upper + lower + digits;
  const pick = (source: string) => source[Math.floor(Math.random() * source.length)]!;
  const chars = [pick(upper), pick(lower), pick(digits)];
  while (chars.length < size) chars.push(pick(all));
  for (let i = chars.length - 1; i > 0; i -= 1) {
    const j = Math.floor(Math.random() * (i + 1));
    [chars[i], chars[j]] = [chars[j]!, chars[i]!];
  }
  return chars.join('');
}
