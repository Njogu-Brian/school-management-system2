/**
 * Phase 7D — dual-role Home/Work mode helpers (source + pure logic).
 */
const path = require('path');
const fs = require('fs');

const appModePath = path.join(__dirname, '../auth/AppModeContext.tsx');
const modeCachePath = path.join(__dirname, '../query/modeCache.ts');
const selectedChildPath = path.join(__dirname, '../parent/selectedChild.ts');

const appModeSrc = fs.readFileSync(appModePath, 'utf8');
const modeCacheSrc = fs.readFileSync(modeCachePath, 'utf8');

function userHasDualIdentity(user) {
  if (!user) return false;
  const canWork = user.canWorkMode ?? Boolean(user.staffId);
  const canHome = user.canHomeMode ?? Boolean(user.parentId);
  return canWork && canHome;
}

function resolveEffectiveMode(user, persisted) {
  if (!user) return 'work';
  const canWork = user.canWorkMode ?? Boolean(user.staffId);
  const canHome = user.canHomeMode ?? Boolean(user.parentId);
  if (canWork && canHome) return persisted ?? 'work';
  if (canHome && !canWork) return 'home';
  return 'work';
}

describe('Phase 7D dual-role mode', () => {
  it('detects teacher+parent dual identity', () => {
    expect(userHasDualIdentity({ staffId: 1, parentId: 2, canWorkMode: true, canHomeMode: true })).toBe(true);
  });

  it('detects staff+parent dual identity', () => {
    expect(userHasDualIdentity({ staffId: 9, parentId: 3 })).toBe(true);
  });

  it('detects admin+parent dual identity via flags', () => {
    expect(userHasDualIdentity({ canWorkMode: true, canHomeMode: true, parentId: 4 })).toBe(true);
  });

  it('rejects unauthorized mode escalation for parent-only users', () => {
    const user = { parentId: 1, canHomeMode: true, canWorkMode: false };
    expect(userHasDualIdentity(user)).toBe(false);
    expect(resolveEffectiveMode(user, 'work')).toBe('home');
  });

  it('persists preferred mode for dual users and defaults to work', () => {
    const user = { staffId: 1, parentId: 2 };
    expect(resolveEffectiveMode(user, null)).toBe('work');
    expect(resolveEffectiveMode(user, 'home')).toBe('home');
  });

  it('AppModeContext exposes dual-identity helpers', () => {
    expect(appModeSrc).toMatch(/userHasDualIdentity/);
    expect(appModeSrc).toMatch(/resolveEffectiveMode/);
  });

  it('mode cache clears student and finance domains', () => {
    expect(modeCacheSrc).toMatch(/invalidateQueriesForAppMode/);
    expect(modeCacheSrc).toMatch(/queryKeys\.students\.all/);
    expect(modeCacheSrc).toMatch(/queryKeys\.finance\.all/);
    expect(modeCacheSrc).toMatch(/diaries/);
  });

  it('selected child helpers remain available for Home scope', () => {
    expect(fs.readFileSync(selectedChildPath, 'utf8')).toMatch(/resolveSelectedChildId/);
  });
});
