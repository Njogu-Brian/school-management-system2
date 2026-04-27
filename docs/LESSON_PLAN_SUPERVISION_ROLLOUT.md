## Lesson plan supervision rollout checklist

### Migrations
- Run migrations (adds submission/rejection fields and timetable linkage to `lesson_plans`).
- Confirm `lesson_plans` now has: `submission_status`, `submitted_at`, `is_late`, `timetable_id`, `rejected_*`.

### Roles / access
- Confirm **Teachers** can create/edit drafts and submit.
- Confirm **Senior Teacher** and **Academic Administrator** can view review queue and approve/reject.
- Confirm **Admin/Super Admin/Director** retain global access.

### Web portal smoke tests
- **Review queue**: `Academics → Lesson Plans → Review Queue`
  - Submitted plans appear.
  - Approve sets `submission_status=approved`.
  - Reject requires notes and sets `submission_status=rejected`.
- **Analytics**: `Academics → Lesson Plans → Analytics`
  - Consistency table renders without errors.

### Mobile app smoke tests
- **Teacher**
  - Lesson plans list shows `draft/submitted/approved/rejected`.
  - Create new plan from timetable, save draft, submit.
  - Draft edit works; post-submit edit is blocked.
- **Supervisor / Senior Teacher**
  - Review queue loads and opens plan details.
  - Approve/reject actions work; rejection requires notes.

### Notifications & scheduler
- Confirm scheduler is running (`php artisan schedule:work` or cron).
- Verify hourly reminders command runs: `reminders:lesson-plans-upcoming`.
- Verify daily pace check command runs: `lesson-plans:recompute-pace`.
- Confirm in-app notifications appear in mobile `Notifications` screen.

