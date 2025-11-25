## Description

<!-- Provide a clear description of what this PR does -->

## Type of Change

- [ ] 🐛 Bug fix (non-breaking change which fixes an issue)
- [ ] ✨ New feature (non-breaking change which adds functionality)
- [ ] 💥 Breaking change (fix or feature that would cause existing functionality to not work as expected)
- [ ] 📚 Documentation update
- [ ] 🔧 Refactoring (no functional changes)
- [ ] ⚡ Performance improvement
- [ ] 🧪 Test addition/update

## Database Changes

- [ ] No database changes
- [ ] New migration(s) added
- [ ] Existing migration(s) modified
- [ ] Destructive migration(s) (drop column/table) - **REQUIRES APPROVAL**

### Migration Details

<!-- If database changes, list migration files: -->
- Migration files:
  - `database/migrations/YYYY_MM_DD_description.php`

### Backup Status

- [ ] Database backup created and verified
- [ ] Backup file location: `backups/backup_filename.sql`
- [ ] Backup checksum verified: ✅

**⚠️ If this PR contains destructive migrations, you MUST:**
1. ✅ Verify backup exists and is valid
2. ✅ Get manual approval from database admin
3. ✅ Document rollback procedure below
4. ✅ Test on staging environment first

### Rollback Procedure

<!-- If destructive changes, document how to rollback: -->
```
1. Restore database from backup: backups/backup_filename.sql
2. Revert code: git revert <commit-hash>
3. Clear caches: php artisan config:clear
```

## Routes/Endpoints Changed

<!-- List any new or modified routes: -->
- `GET /new-endpoint` - Description
- `POST /modified-endpoint` - Description

## Tests Added

- [ ] Unit tests added
- [ ] Feature tests added
- [ ] Integration tests added
- [ ] E2E tests added (if applicable)

### Test Coverage

<!-- List test files added/modified: -->
- `tests/Unit/NewServiceTest.php`
- `tests/Feature/NewControllerTest.php`

## Checklist

- [ ] My code follows the project's style guidelines
- [ ] I have performed a self-review of my own code
- [ ] I have commented my code, particularly in hard-to-understand areas
- [ ] I have made corresponding changes to the documentation
- [ ] My changes generate no new warnings
- [ ] I have added tests that prove my fix is effective or that my feature works
- [ ] New and existing unit tests pass locally with my changes
- [ ] Any dependent changes have been merged and published

## Screenshots (if applicable)

<!-- Add screenshots for UI changes -->

## Related Issues

<!-- Link to related issues: -->
- Closes #123
- Related to #456

## Additional Notes

<!-- Any additional information reviewers should know -->

---

**⚠️ IMPORTANT:** 
- Destructive migrations require explicit approval
- All database changes must have backups
- All new code must have tests
- CI must pass before merge

