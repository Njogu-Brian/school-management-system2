/**
 * Phase 7C — parent absence API contract (source scan).
 */
const fs = require('fs');
const path = require('path');

const apiPath = path.join(__dirname, '../api/parentAbsence.api.ts');
const hookPath = path.join(__dirname, '../query/hooks/useParentAbsence.ts');
const apiSrc = fs.readFileSync(apiPath, 'utf8');
const hookSrc = fs.readFileSync(hookPath, 'utf8');

describe('Phase 7C parent absence client', () => {
  it('posts to attendance-absence endpoint', () => {
    expect(apiSrc).toMatch(/\/students\/\$\{studentId\}\/attendance-absence/);
    expect(apiSrc).toMatch(/start_date/);
    expect(apiSrc).toMatch(/end_date/);
    expect(apiSrc).toMatch(/reason/);
  });

  it('exposes history and reason codes', () => {
    expect(apiSrc).toMatch(/listReasonCodes/);
    expect(apiSrc).toMatch(/history/);
  });

  it('invalidates attendance caches after report', () => {
    expect(hookSrc).toMatch(/useReportParentAbsence/);
    expect(hookSrc).toMatch(/absence-history/);
  });
});
