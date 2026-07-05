/** Strip subject prefix — show Opener / Midterm / End term instead of "Mathematics Midterm". */
export function simplifyExamLabel(name: string, examTypeName?: string | null): string {
  if (examTypeName?.trim()) {
    return examTypeName.trim();
  }
  const lower = name.toLowerCase();
  if (lower.includes('midterm') || lower.includes('mid term')) return 'Midterm';
  if (lower.includes('endterm') || lower.includes('end term')) return 'End term';
  if (lower.includes('opener') || lower.includes('opening')) return 'Opener';
  const parts = name.trim().split(/\s+/);
  if (parts.length <= 1) return name;
  const last = parts[parts.length - 1];
  if (/midterm|endterm|opener/i.test(last)) return last.charAt(0).toUpperCase() + last.slice(1).toLowerCase();
  return name;
}

export function sessionDisplayLabel(session: {
  name: string;
  exam_type_name?: string | null;
}): string {
  return simplifyExamLabel(session.name, session.exam_type_name);
}
