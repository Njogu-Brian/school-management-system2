/** Time-of-day greeting with optional first name, e.g. "Good morning, Jane". */
export function timeOfDayGreeting(displayName?: string | null): string {
  const hour = new Date().getHours();
  const part = hour < 12 ? 'Good morning' : hour < 17 ? 'Good afternoon' : 'Good evening';
  const first = (displayName ?? '').trim().split(/\s+/)[0];
  return first ? `${part}, ${first}` : part;
}
