# Changelog

All notable changes to `local_aiproofreaderreport` are documented here.

## v0.3.0 - 2026-08-11

### Added
- "Current AI model label" text setting in the Survey & data settings section - a free-text label (e.g. "GPT-4o", "Claude Sonnet 4.5") that gets stamped onto every AI Proofreader feedback and comparison, since Moodle's AI subsystem doesn't reliably report which model actually answered a request. Update it whenever the site's AI provider/model changes.
- Overview tab now shows an "AI model(s) used" column per activity.
- Data Dictionary now documents the two new AI-model fields (`feedbackaimodel`, `comparisonaimodel`) for use once the anonymized export is built.
- Now requires `mod_aiproofreader` v0.3.0 or later (adds the underlying database columns this depends on).

## v0.2.3 - 2026-08-10

### Changed
- Consolidated all admin settings (report scope + survey & data settings, previously two separate settings pages) into a single "AI Proofreader Report settings" page, organized as two collapsible sections ("Report scope" and "Survey & data settings") using native `<details>`/`<summary>` - collapsed by default, click to expand. No settings were renamed or moved to a different config location; this is purely a UI reorganization.

### Fixed
- `classes/admin_setting_fieldgroup.php`: cleaned up two local_codechecker formatting flags (blank line after class brace, missing one-line docblock description). No behavior change.

## v0.2.2 - 2026-08-10

### Fixed
- `classes/task/cleanup_stale_data.php`: `get_name()` was missing a native `: string` return type declaration to match its docblock, flagged by local_codechecker. No behavior change.

## v0.2.1 - 2026-08-10

### Fixed
- `classes/task/cleanup_stale_data.php`: opening brace of the class declaration was followed by a blank line, flagged by local_codechecker. Reformatted; no behavior changes.
- Now requires `mod_aiproofreader` v0.2.1 or later (matching its own codechecker fixes).

## v0.2.0 - 2026-08-10

### Added
- New "AI Proofreader survey & data settings" page:
  - Turn the entire student/teacher survey system on or off site-wide (off by default).
  - Show/hide checkbox and editable wording for each of the 5 student questions, 6 teacher questions, and each side's free-text box.
  - "Retain Google Doc text" toggle - off by default, so a submitted Google Doc's text is only kept in the database (for export/research) if explicitly turned on; otherwise it's cleared back to just the link once AI processing is done with it.
- Weekly scheduled task (`cleanup_stale_data`, Sundays at 2 AM by default) that removes AI Proofreader submissions left behind by deleted activities or deleted user accounts, so the database doesn't accumulate orphaned data over time. Runs like any other Moodle scheduled task - adjust the schedule under Site administration → Server → Scheduled tasks.
- Hard dependency on `mod_aiproofreader`: this plugin will not install or upgrade unless a compatible version of the mod is already present, since it has nothing to configure or report on without it.

### Fixed
- Student/Teacher Survey tab averages now correctly skip questions that have been individually disabled, instead of counting a missing answer as a zero and skewing the average down.

## v0.1.0 - 2026-08-08

Initial version.

- Overview tab: teacher/course/activity usage with enrolled, submitted, final-submitted, and graded counts.
- Student Survey tab: average score and response count per question, plus feedback-category breakdown.
- Teacher Survey tab: average score and response count per question.
- Reimbursement tab: graded-submission count per teacher within a chosen date range.
- Data Dictionary tab: downloadable CSV reference of every field this report reads.
- Anonymized Export tab: placeholder page only — export logic not yet implemented.
- Shared filter bar: course, teacher, grade level, group (course-scoped), and date range.
- Scope limited to MS/HS students via two admin-configurable management course IDs (default HS=53, MS=54).
- Capabilities: `local/aiproofreaderreport:view` (Manager, Admin), `local/aiproofreaderreport:export` (Admin only).

### Planned for a future release
- Anonymized student data export: fresh anonymous ID per export run, PII scrubbing of submission/feedback text, admin-selectable export fields.
