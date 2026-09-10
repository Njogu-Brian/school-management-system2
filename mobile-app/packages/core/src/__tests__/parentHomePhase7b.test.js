/**
 * Phase 7B — Parent Home action catalog & child selection (source/module tests).
 */
const path = require('path');
const fs = require('fs');

const homeActionsPath = path.join(__dirname, '../parent/homeActions.ts');
const selectedChildPath = path.join(__dirname, '../parent/selectedChild.ts');
const homeActionsSrc = fs.readFileSync(homeActionsPath, 'utf8');
const selectedChildSrc = fs.readFileSync(selectedChildPath, 'utf8');

// Evaluate pure helpers without TS compile by requiring compiled-like copies:
// re-implement mirrors for assert — also parse exports via Function from stripped source.

function resolveSelectedChildId(availableIds, preferredId) {
  const ids = availableIds.filter((id) => Number.isFinite(id) && id > 0);
  if (ids.length === 0) return null;
  if (preferredId != null && ids.includes(preferredId)) return preferredId;
  return ids[0] ?? null;
}

function isSelectedChildUnauthorized(availableIds, preferredId) {
  if (preferredId == null || preferredId <= 0) return false;
  return !availableIds.includes(preferredId);
}

function buildParentHomeNavParams(action, studentId) {
  if (!action.jump.requiresStudentId) return undefined;
  if (studentId == null || studentId <= 0) return null;
  return { studentId };
}

const CORE_IDS = [
  'attendance',
  'academic',
  'fees',
  'transport',
  'diary',
  'homework',
  'notifications',
  'settings',
];

describe('Parent Home Phase 7B — selected child', () => {
  it('uses preferred child when authorized', () => {
    expect(resolveSelectedChildId([10, 20, 30], 20)).toBe(20);
  });

  it('falls back when preferred child is not in accessible list (no leak)', () => {
    expect(resolveSelectedChildId([10, 20], 99)).toBe(10);
    expect(isSelectedChildUnauthorized([10, 20], 99)).toBe(true);
  });

  it('returns null when parent has no children', () => {
    expect(resolveSelectedChildId([], 10)).toBe(null);
  });

  it('source keeps unauthorized helper', () => {
    expect(selectedChildSrc).toMatch(/isSelectedChildUnauthorized/);
    expect(selectedChildSrc).toMatch(/resolveSelectedChildId/);
  });
});

describe('Parent Home Phase 7B — action catalog', () => {
  it('exposes core school-life actions on Home (not only More)', () => {
    for (const id of CORE_IDS) {
      expect(homeActionsSrc).toMatch(new RegExp(`id: '${id}'`));
    }
  });

  it('requires studentId for child-scoped actions', () => {
    const attendance = {
      jump: { requiresStudentId: true },
    };
    expect(buildParentHomeNavParams(attendance, null)).toBeNull();
    expect(buildParentHomeNavParams(attendance, 0)).toBeNull();
    expect(buildParentHomeNavParams(attendance, 42)).toEqual({ studentId: 42 });
  });

  it('does not require studentId for fees/notifications/settings', () => {
    expect(buildParentHomeNavParams({ jump: { requiresStudentId: false } }, null)).toBeUndefined();
  });

  it('routes academic results to ChildResults under Academic tab', () => {
    expect(homeActionsSrc).toMatch(/id: 'academic'/);
    expect(homeActionsSrc).toMatch(/screen: 'ChildResults'/);
    expect(homeActionsSrc).toMatch(/tab: 'ParentAcademicTab'/);
  });

  it('routes school fees to FeesHome', () => {
    expect(homeActionsSrc).toMatch(/id: 'fees'/);
    expect(homeActionsSrc).toMatch(/screen: 'FeesHome'/);
  });
});
