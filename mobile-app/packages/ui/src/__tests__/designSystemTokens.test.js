/**
 * Design System V3 — token health tests (source scan, no RN runtime).
 */
const fs = require('fs');
const path = require('path');

const tokensPath = path.join(__dirname, '../theme/tokens.ts');
const tokens = fs.readFileSync(tokensPath, 'utf8');

describe('Design System V3 tokens', () => {
  it('keeps ScholarCore primary brand', () => {
    expect(tokens).toMatch(/primary:\s*'#004A99'/);
    expect(tokens).toMatch(/primaryOnDark:\s*'#4B9FFF'/);
  });

  it('defines premium radius aliases', () => {
    expect(tokens).toMatch(/control:\s*18/);
    expect(tokens).toMatch(/card:\s*24/);
    expect(tokens).toMatch(/sheet:\s*28/);
    expect(tokens).toMatch(/dialog:\s*32/);
  });

  it('uses strict spacing scale including 56 and 64', () => {
    expect(tokens).toMatch(/md:\s*16/);
    expect(tokens).toMatch(/mdLg:\s*20/);
    expect(tokens).toMatch(/'4xl':\s*56/);
    expect(tokens).toMatch(/'5xl':\s*64/);
  });

  it('exposes full typography ramp', () => {
    for (const role of [
      'displayLarge',
      'headline',
      'title',
      'button',
      'label',
      'caption',
      'overline',
      'tiny',
    ]) {
      expect(tokens).toContain(`${role}:`);
    }
  });

  it('defines motion durations 150/250/400', () => {
    expect(tokens).toMatch(/fast:\s*150/);
    expect(tokens).toMatch(/medium:\s*250/);
    expect(tokens).toMatch(/slow:\s*400/);
  });

  it('defines opacity and z-index layers', () => {
    expect(tokens).toMatch(/scrim:\s*0\.45/);
    expect(tokens).toMatch(/dialog:\s*50/);
    expect(tokens).toMatch(/toast:\s*60/);
  });

  it('has elevation level 5 for FAB', () => {
    expect(tokens).toMatch(/5:\s*\{/);
    expect(tokens).toMatch(/elevation:\s*12/);
  });

  it('includes AMOLED background keys', () => {
    expect(tokens).toMatch(/backgroundAmoled:\s*'#000000'/);
    expect(tokens).toMatch(/surfaceAmoled:\s*'#0a0a0a'/);
  });

  it('ships charcoal dark canvas (not navy legacy)', () => {
    expect(tokens).toMatch(/backgroundDark:\s*'#0c1018'/);
    expect(tokens).toMatch(/surfaceDark:\s*'#151a24'/);
  });

  it('defines dark semantic tones', () => {
    expect(tokens).toContain('SEMANTIC_DARK');
    expect(tokens).toContain('successFgDark');
  });
});
