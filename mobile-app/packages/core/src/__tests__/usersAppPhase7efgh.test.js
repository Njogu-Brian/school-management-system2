/**
 * Phase 7E/7F/7H source contract tests.
 */
const fs = require('fs');
const path = require('path');

const teacherHome = fs.readFileSync(
  path.join(__dirname, '../teacher/homeActions.ts'),
  'utf8',
);
const homeworkApi = fs.readFileSync(
  path.join(__dirname, '../api/homework.api.ts'),
  'utf8',
);
const diaryApi = fs.readFileSync(path.join(__dirname, '../api/diary.api.ts'), 'utf8');

describe('Phase 7E teacher home catalog', () => {
  it('exposes core daily work actions', () => {
    for (const id of ['attendance', 'homework', 'marks', 'diary', 'transport', 'notifications']) {
      expect(teacherHome).toMatch(new RegExp(`id: '${id}'`));
    }
  });
});

describe('Phase 7F homework targeting', () => {
  it('supports students target_scope and student_ids', () => {
    expect(homeworkApi).toMatch(/'class' \| 'stream' \| 'students'/);
    expect(homeworkApi).toMatch(/student_ids/);
  });
});

describe('Phase 7G diary channels', () => {
  it('supports teacher_parent and admin_parent', () => {
    expect(diaryApi).toMatch(/teacher_parent/);
    expect(diaryApi).toMatch(/admin_parent/);
    expect(diaryApi).toMatch(/channel/);
  });
});
