/**
 * Consistency health checks for Admin Design System V3 adoption.
 */
const fs = require('fs');
const path = require('path');

const ADMIN_FEATURES = path.resolve(__dirname, '../../../../apps/admin/src/features');
const ADMIN_SRC = path.resolve(__dirname, '../../../../apps/admin/src');
const UI_SRC = path.resolve(__dirname, '..');

function walk(dir, acc = []) {
  if (!fs.existsSync(dir)) return acc;
  for (const entry of fs.readdirSync(dir, { withFileTypes: true })) {
    const full = path.join(dir, entry.name);
    if (entry.isDirectory()) walk(full, acc);
    else if (/\.(tsx|ts)$/.test(entry.name) && !entry.name.includes('.test.')) acc.push(full);
  }
  return acc;
}

describe('Admin V3 consistency health', () => {
  const featureFiles = walk(ADMIN_FEATURES);
  const adminSrcFiles = walk(ADMIN_SRC);

  it('finds admin feature source files', () => {
    expect(featureFiles.length).toBeGreaterThan(50);
  });

  it('has zero fontSizes usage in admin features', () => {
    const offenders = [];
    for (const file of featureFiles) {
      const text = fs.readFileSync(file, 'utf8');
      if (/\bfontSizes\b/.test(text)) {
        offenders.push(path.relative(ADMIN_FEATURES, file));
      }
    }
    expect(offenders).toEqual([]);
  });

  it('exports required V3 feedback/layout/primitives from @erp/ui', () => {
    const feedback = fs.readFileSync(path.join(UI_SRC, 'feedback/index.ts'), 'utf8');
    expect(feedback).toContain('Dialogs');
    expect(feedback).toContain('Toast');

    const layout = fs.readFileSync(path.join(UI_SRC, 'layout/index.ts'), 'utf8');
    expect(layout).toContain('ScreenHeader');
    expect(layout).toContain('PremiumTabBar');

    const primitives = fs.readFileSync(path.join(UI_SRC, 'primitives/index.ts'), 'utf8');
    expect(primitives).toContain('AccentIcon');

    expect(fs.existsSync(path.join(UI_SRC, 'theme/useReducedMotion.ts'))).toBe(true);
  });

  it('wires FeedbackProvider and Toast in AppThemeProvider', () => {
    const provider = fs.readFileSync(
      path.resolve(__dirname, '../../../../apps/admin/src/providers/AppThemeProvider.tsx'),
      'utf8',
    );
    expect(provider).toContain('ToastProvider');
    expect(provider).toContain('FeedbackProvider');
    expect(provider).toContain('surfaceMode');
  });

  it('wires PremiumTabBar in BottomTabsNavigator', () => {
    const tabs = fs.readFileSync(
      path.resolve(__dirname, '../../../../apps/admin/src/navigation/BottomTabsNavigator.tsx'),
      'utf8',
    );
    expect(tabs).toContain('PremiumTabBar');
    expect(tabs).toContain('tabBar=');
  });

  it('keeps Alert.alert only in feedback fallbacks', () => {
    const offenders = [];
    for (const file of adminSrcFiles) {
      const text = fs.readFileSync(file, 'utf8');
      if (!text.includes('Alert.alert')) continue;
      const rel = path.relative(ADMIN_SRC, file).replace(/\\/g, '/');
      if (rel === 'features/shared/utils/feedback.ts') continue;
      offenders.push(rel);
    }
    expect(offenders).toEqual([]);
  });

  it('aligns splash to brand primary', () => {
    const config = fs.readFileSync(
      path.resolve(__dirname, '../../../../apps/admin/app.config.ts'),
      'utf8',
    );
    expect(config).toMatch(/primaryColor\s*=\s*'#004A99'/);
  });
});
