# Changelog

All notable changes to `local_aiproofreaderreport` are documented here.

## v0.8.7 - 2026-11-06

### Changed
- **The activity's due date now decides which quarter it counts in**, when it can be trusted. Order: the due date; if there is none or it is stale, the "Allow submissions from" date; if neither is set or trusted, the majority-final date from v0.8.6.
  - A date is stale when most students' own final submissions came in more than 4 weeks after it (`report_manager::STALE_DATE_SECS`). Finals coming in early never make a date stale.
  - Fixes activities a teacher built near the end of one quarter but set due in the next (example: a play script created in Q1, opened 10/19 and due 10/22, was counted in Q1).
  - The majority-final date still uses only students' own finals; teacher-finalized submissions are left out.

## v0.8.6 - 2026-11-05

### Changed
- **Which quarter an activity counts in now follows the majority of student final submissions**, not the first student to start. An activity belongs to the period in which more than half of its MS/HS students' own final submissions came in. If that date is inside the range, every submission to the activity counts there; if not, the whole activity is left out.
  - Teacher-finalized submissions are left out of the majority, so closing out work after a quarter ends can't move an activity into the next quarter. They still count toward the stipend in whichever quarter the activity belongs to.
  - If no student has made a final submission yet, draft submission dates are used, then submission record creation dates.
  - Worked out in PHP (new `report_manager::get_activity_ids_started_in_range()`) so it runs on every database Moodle supports.
  - Applies to Overview, Reimbursement, notifications, the print stipend summary and My AI Proofreader grant numbers. Survey tabs are unchanged.

### Fixed
- An activity created and tried by a few students at the end of one quarter, but assigned and completed in the next, was counted in the earlier quarter (v0.8.5 used the first student record). Example: a Q2 assignment with a handful of 10/13-10/15 records was reported in Q1.
- `build_filter_sql()` doc comment had its description after `@param`; moved before it.

## v0.8.5 - 2026-10-21

### Added
- **Print stipend summary** button on the Reimbursement tab, next to Notify teachers (new `printsummary.php`). Opens a printable page in a new tab for the treasurer: period, quarter name (when the dates match the saved quarter), goal, each teacher's students submitted / final submissions / final & graded, Goal met (Yes/No), totals, and Prepared by / Approved by (Treasurer) signature lines. Same teacher list as Notify teachers. Menus, footer and buttons are hidden by print CSS.
- Overview tab activity names now link to the activity, opening in a new tab.

### Changed
- **Date range now uses the activity start date.** An activity counts in the period in which its first MS/HS student started a submission; if that is inside the range, all its submissions count, otherwise the whole activity is left out. Previously each submission was bounded by its own start date, so a Q1 activity with late starters during an extended grading window also showed up in Q2. Applies to Overview, Reimbursement, notifications and My AI Proofreader grant numbers (all use `build_filter_sql()` with `s.timecreated`). Survey tabs are unchanged.

## v0.8.4 - 2026-10-14

### Added
- **Data Export dictionary** on the Data Dictionary tab, with a **Download Data Export dictionary (CSV)** button (`export.php?type=dataexportdictionary`). One row per column the Data Export button selects, in the same order and with the same headings as the CSV: Column, Type, Values, Description. It is the codebook to post alongside the public dataset. Built from `export_field_catalog::get_dataexport_keys()`, the same list as the button, so the two can't drift apart (new `get_dataexport_dictionary()`).
  - District codes (school year, grade level, lunch, IEP, 504) are listed from the distinct values actually in the `contacts` table at download time, since each district has its own codes.
  - Survey descriptions quote the question wording the site actually uses (`aiproofreader_question_text()` from mod_aiproofreader), so customized wording is shown, not the default.
  - All text is in language strings (`dictdesc_*`, `dicttype_*`, `dictvalues_*`).
- The Data Dictionary tab now has two headed sections: Data Export dictionary first, then the Database dictionary.

### Changed
- **Database dictionary** now covers every field the Anonymized Export can include. Added: the assignment instructions and AI instructions, submission types, Google Drive links, feedback/comparison/record timestamps, instructor comments, the survey free-text answers and times, the two `user` fields read, and the 17 `contacts` columns read. The README's database dictionary is regenerated from the same list, so the two match.

### Fixed
- The **Teacher-finalized** export field had no language string (missing since v0.8.0), so its checkbox and CSV column heading showed `[[exportfield_sub_teacherfinalized]]`. Added `exportfield_sub_teacherfinalized`: "Teacher-finalized (1 = teacher used the draft as the final)".

## v0.8.3 - 2026-10-13

Requires mod_aiproofreader v0.8.3 (2026101300) or later.

### Changed
- Anonymized Export: **AI feedback: grammar and spelling**, **AI feedback: assignment specifics** and **AI feedback-followed analysis** now export mod_aiproofreader's de-identified copies (`feedbackgrammarredacted`, `feedbackassignmentredacted`, `aicomparisonredacted`), made by its nightly PII redaction task, and are labelled "(de-identified)". The feedback and comparison often quote the student's own sentences, so the raw versions can contain names.
- The **Data Export** button now also selects those three de-identified fields.

### Added
- Three internal-only fields in the Personally identifying column - the original, NOT de-identified grammar feedback, assignment feedback and AI comparison - each next to its de-identified twin in the CSV, for checking the redaction. Off by default and cleared by "Privacy defaults".
- Data Dictionary lists the raw and de-identified feedback and comparison fields.

## v0.8.2 - 2026-10-13

### Added
- Anonymized Export: **Data Export** button next to "Deselect all". It ticks exactly the fields for the grant's public research dataset (Hugging Face) and clears all others. The list is `export_field_catalog::get_dataexport_keys()`, modelled on PERSUADE 2.0 and ArgRewrite V.2. Described in the README under "Data Export".
- Anonymized Export: two new fields under Assignment Submission Specifics - **Target grade level (Lexile setting)** (`aiproofreader.gradelevel`) and **Maximum points** (`aiproofreader.grade`), so the reading-level condition is known and grades can be read as percentages.

## v0.8.1 - 2026-10-12

### Fixed
- The graded count on the Reimbursement tab, in the notification email and on the My AI Proofreader grant numbers totals row didn't match the per-activity Graded column, and could be higher than Final submissions. It counted every grade a teacher saved, including early "Grade now" grades on students who never finished (often a zero to push them to finish), and used a different date basis (when the grade was saved) and attribution (who saved the grade). Teachers could appear to meet the grant goal without the work being done.
- Now one definition is used everywhere: **Final & graded** - submissions that reached a final version (made by the student, or by the teacher with "Finalize grade") and were then graded (status `graded`), in the teacher's courses, for submissions started within the date range. This is exactly what the activity table counts, so each teacher's total equals the sum of their activity rows. `report_manager::get_reimbursement_rows()` rewritten; goal outlook and notifications follow automatically.

### Changed
- "Graded count" / "Graded" columns are now labelled **Final & graded** on the Reimbursement tab, Overview tab and teacher page. The notification email, goal wording, quarter goal field and page intros say Final & graded, and explain that early Grade now grades don't count until the work is finalized and graded.
- In a course with two editing teachers, both are now credited with its final & graded submissions on the Reimbursement tab (as the Overview tab already did), rather than only the teacher who saved each grade.

## v0.8.0 - 2026-10-10

Requires mod_aiproofreader v0.8.0 (2026101000) or later.

### Added
- **My AI Proofreader grant numbers** page for grant teachers (`mynumbers.php`), linked from the Reports section of their own profile page (new `lib.php`, `local_aiproofreaderreport_myprofile_navigation()`). Shows the saved quarter's name and dates, the teacher's totals (the same numbers as the Reimbursement tab and their notification email, with goal and outlook when a goal is saved) and their per-activity breakdown. Only roster teachers can open it, and only for themselves; report viewers can preview any roster teacher's page.
- **Save quarter dates** and **Apply quarter dates** buttons on the filter bar. Saving (new `savequarter.php`, `classes/form/quarter_form.php`) stores a quarter name, the date range and an optional graded goal without sending anything; Apply puts the saved dates back into the filters. The saved quarter is shown under the filter bar. Sending notifications now also saves its dates (and goal, if entered), and the Notify goal box starts from the saved goal.
- **Teacher-finalized** column on the Overview and Reimbursement tabs and the teacher page, for drafts graded as final with mod_aiproofreader's new "Finalize grade". They also count in Final submissions. New `sub_teacherfinalized` field in the Anonymized Export and the Data Dictionary.

### Fixed (GitHub issues)
- Anonymized Export with a contacts field selected failed on PostgreSQL when any user had an empty or non-numeric ID number, because the join cast `user.idnumber` to an integer. It now compares the two as text (`sql_cast_to_char('c.StudentNumber')`). The expected contacts table columns are now documented in the README.
- The HS and MS management course settings defaulted to course ids 53 and 54, which could point at unrelated courses on another site and silently report the wrong students. They now default to blank, and every tab shows a setup notice with a link to the settings until at least one is chosen. Removed the reference to another plugin's convention from the README.
- The privacy provider now declares the opt-out roster (`local_aiproofreaderreport_optout`: student number, time added, uploader) alongside the grant roster: user lookup by ID number, export, and deletion (a deleted user's opt-out row is removed, and they are blanked out as uploader). README updated to say the plugin stores these two rosters.

## v0.7.3 - 2026-10-05

### Added
- Anonymized Export: **Deselect all** button next to "Privacy defaults".
- Anonymized Export: **Save export items** button next to "Download CSV". Saves the checked fields as a per-user preference (`local_aiproofreaderreport_exportfields`), so the same fields are checked each time you come back; a line above the buttons says when a saved selection is in use. With nothing saved, the page still starts from privacy defaults. New page `save_exportfields.php`. Declared in the privacy provider (user preference export).
- Anonymized Export: two new fields, **Draft submission text (original - NOT de-identified)** and **Final submission text (original - NOT de-identified)**, sourced from the raw `initialtext` / `finaltext` columns. For internal use only - re-running the AI to test changes and comparing against the de-identified versions. They are in the Personally identifying column (off by default, cleared by "Privacy defaults") and land in the CSV right after their de-identified twins for side-by-side comparison. Also listed in the Data Dictionary.

### Changed
- Anonymized Export: the opt-out roster import moved from the top button row to its own "Opt-out roster" section at the bottom of the tab.
- Anonymized Export: the export form now posts (with a sesskey) instead of putting the field list in the URL; `export.php` checks the sesskey for the anonymized export.

## v0.7.2 - 2026-10-05

### Added
- Reimbursement tab table now has per-teacher totals so it's easy to see who has assigned enough and where they are in the process: **Students assigned** (AI Proofreader activities x enrolled MS/HS students - the Overview tab's "Students enrolled" summed per teacher), **Drafts submitted** and **Final submissions** (the Overview tab's "Students submitted" and "Final submissions" summed per teacher), followed by the existing **Graded count**.
- New `report_manager` methods `get_teacher_assigned_totals()` and `get_reimbursement_table_rows()`.

### Changed
- The Reimbursement table now lists every grant roster teacher (with 0s if they have nothing yet) as well as everyone who graded in the range. Notifications are unchanged.

### Fixed
- Moodle code checker (phpcs) errors carried over from v0.7.1: multi-line function declaration of `report_manager::build_notification_message()`, and in `classes/privacy/provider.php` the blank line after the class opening brace and the multi-line `record_exists_select()` call inside an `if`.

## v0.7.1 - 2026-10-04

### Added
- Grant teacher roster on the Reimbursement tab. Upload a CSV or text file of grant teachers' email addresses once; it is saved and used for every later notification run. When a roster exists, "Notify teachers" goes only to roster teachers - including those with 0 graded - and to no one else. Each upload replaces the whole roster; unmatched emails are listed and skipped; an upload with no valid emails is rejected so the roster can't be wiped by a bad file. A "Show roster" list shows who is on it.
- New table `local_aiproofreaderreport_grantteacher` (upgrade step 2026100400; version 2026100401).
- New page `grantroster_import.php` handling the upload (requires the `view` and `notify` capabilities).
- New `report_manager` methods `get_notification_rows()`, `save_grantteacher_roster()` and `get_grantteacher_roster()`.
- Rewritten teacher notification. Each message now summarises the teacher's students submitted, final submissions and graded count across all their courses for the period. With a goal set: teachers who have met it get a congratulations (and a congratulations subject line); the others are told how many drafts and ungraded finals are outstanding, and either that finishing them will meet the goal (so no new assignment is needed) or how many they will still be short (so they may need another activity). Submitted and final counts use the Overview tab's attribution (editing teacher of the course); the graded count is the Reimbursement number.
- Notification confirmation page now shows submitted, final and graded columns, a "Goal outlook" column when a goal is entered, and a preview of the exact message the first teacher will get.
- New `report_manager` methods `get_teacher_activity_totals()`, `get_goal_outlook()` and `build_notification_message()`.

### Changed
- The "Notify teachers" section on the Reimbursement tab now shows even when no one has graded in the chosen range, so roster teachers with 0 can still be notified.
- The notification confirmation page says whether the grant roster is in use.
- Student Survey tab: removed the "Which feedback category helped more" chart, which drew on top of the score distribution chart. The category counts are still shown in the table below.
- Privacy provider: replaced the null provider with a full metadata/request provider covering the grant roster table and the core_message subsystem link.

## v0.7.0 - 2026-09-28

### Added
- Reimbursement tab: new "Notify teachers" section. After applying a complete date range, click "Notify teachers" to see a confirmation page listing each teacher and their graded count, then "Send notifications" to send each teacher a Moodle notification (popup and email by default) with their own graded count for that period. An optional "Goal for this period" adds a line telling each teacher how many more they need, or that they have met it.
- New message provider `reimbursementcount` (`db/messages.php`).
- New capability `local/aiproofreaderreport:notify` (manager archetype), required to send the notifications.
- Student Survey and Teacher Survey tabs now show small charts (Moodle core chart API): a stacked bar per question showing the percentage of responses at each score from 1 to 5, and on the Student Survey tab, a bar chart of which feedback category helped more. Each chart has Moodle's built-in "Show chart data" table.
- Both survey tabs now show a response-rate line: survey responses compared with final submissions (students) or graded submissions (teachers) under the same filters.

### Fixed
- Student and teacher survey totals capped at 5 responses. Both summary queries used a 1-5 score as the first selected column, and `get_records_sql()` keys results by the first column, so all responses with the same Q1 score collapsed into one row. The queries now select the survey row `id` first.

### Changed
- Overview tab now sorts by teacher last name, first name, then course name, then activity name (was course name, then activity name). Where a course has more than one editing teacher, the names in the cell are also listed in last-name order and the row sorts by the first one. Rows with no editing teacher sort last.
- Reimbursement tab now sorts by teacher last name, first name (was highest graded count first).
- Grade level filter dropdown now lists highest grade first, sorted numerically (so 12, 11, 10 come before 9), and no longer lists a blank grade level.
- Teacher names on the Reimbursement tab are now loaded in one query with all name fields, so `fullname()` has everything it needs.

## v0.6.1 - 2026-09-14

### Fixed
- Added missing `LICENSE` file (GNU GPL v3 text) to the plugin root, required by the Moodle Plugins directory review.
- Added the required Privacy API implementation (`classes/privacy/provider.php`), using the null provider since this plugin stores no personal data of its own.
- Data Dictionary tab's table headers ("Table", "Field", "Description") were hard-coded instead of using `get_string()`; now pull from three new language strings so the labels can be translated.
- Removed the manual `$PAGE->requires->css('/local/aiproofreaderreport/styles.css')` calls in `index.php` and `settings.php` — Moodle already auto-loads a plugin's `styles.css`, so this was redundant.
- Cleaned up all `moodle-plugin-ci phpcs` findings: removed unnecessary `MOODLE_INTERNAL` checks from several class files, wrapped over-length lines, capitalized inline comments, added a missing class docblock, fixed a blank-line-after-brace and a blank-line-at-start-of-control-structure, and fully re-sorted the language file's string keys into the alphabetical order the Moodle coding standard requires.
- Added a "Moodle.org marketplace listing" section to the README with ready-to-copy short/full descriptions, to keep the plugin directory listing aligned with the actual README.

## v0.6.0 - 2026-09-14

### Added
- Anonymized export can now include the activity's own "Assignment instructions" and "AI instructions" (mod_aiproofreader's `intro`/`aiinstructions` fields) - shared context across all submissions for that activity, not student-specific. These appear in the Demographic/program tags column, under a new "Assignment Submission Specifics" divider/subheading, and are checked by default.
 
### Changed
- The "Draft submission text (de-identified)" and "Final submission text (de-identified)" export fields moved from the "Personally identifying (off by default)" column to "Submission & feedback data" and are now checked by default, since they're the de-identified copies these settings exist to produce in the first place. The "Privacy defaults" button no longer deselects them.

## v0.5.1 - 2026-09-10

### Changed
- The anonymized export's "Draft submission text" and "Final submission text" columns now source from mod_aiproofreader's new `initialtextredacted`/`finaltextredacted` fields instead of the raw `initialtext`/`finaltext` - i.e. the AI de-identified copies, not the originals. These stay blank for any submission mod_aiproofreader's nightly PII redaction task hasn't processed yet (that task is off by default). Still categorized as PII and deselected by the "Privacy defaults" button, since AI-based redaction isn't guaranteed to be complete - still worth a human spot-check before this leaves the building.
- Bumped the `mod_aiproofreader` dependency to 2026091001, the version that introduced the redacted columns this now relies on.

## v0.5.0 - 2026-09-01

### Added
- Opt-out roster: new "Import opt-out roster" button on the Anonymized Export tab, next to Privacy defaults. Upload a one-column CSV of student SSIDs (a header row is auto-detected and skipped); the upload replaces the entire stored roster (not a merge - each upload represents the current, complete ParentSquare opt-out list).
- New `local_aiproofreaderreport_optout` table stores the roster (SSID, import time, importing admin).
- The anonymized export now excludes any student whose Moodle `idnumber` matches a row in the roster - applies regardless of which fields are selected.
- Current roster size shown next to the import button.

### Not yet included
- No free-text PII scrubbing yet - `initialtext`/`finaltext`/survey free-text fields are exported as-is when selected.
- No grade-level summary/aggregate export yet - only the individual-record export exists so far.

## v0.4.1 - 2026-09-01

### Changed
- Export field order: anonymous ID, school year, school code, district ID, school ID, and student status now lead the safe/structural column; student number (SSID), last name, first name, city, and state now lead the PII column - matching the order they should appear as CSV columns.
- Fixed field mislabeled "Contact email": it was pulling `contacts.Email`, which is the parent/guardian contact email, not the student's own. Replaced with "Student email", sourced from Moodle's own `{user}.email` instead.

## v0.4.0 - 2026-09-01

### Added
- Anonymized export tab is now functional (was a stub). Combines `mod_aiproofreader` submission/grade/survey data with the district `contacts` table (grade level, IEP, 504, Lunch, Gifted, Gender, Ethnicity, and a handful of structural fields), joined via `user.idnumber` = `contacts.StudentNumber`.
- New field-picker checkbox UI, grouped into three columns: personally identifying (off by default), demographic/program tags, and submission/feedback data. A "Privacy defaults" button deselects every PII field in one click; any field can still be manually re-checked for internal use.
- Export can include a stable anonymous student ID (`anonid`), computed on the fly by `mod_aiproofreader`'s new `aiproofreader_get_anon_id()` - no mapping table, no raw identifier in the CSV unless `StudentNumber` is explicitly selected.
- Gracefully degrades when the `contacts` table doesn't exist on an install: contacts-sourced fields are simply left out of the field picker, with a notice explaining why, instead of erroring.
- Now requires `mod_aiproofreader` v0.3.4 or later (adds `aiproofreader_get_anon_id()`).

### Not yet included
- No opt-out roster filtering yet (students whose parents opted out via ParentSquare) - still to be built.
- No free-text PII scrubbing yet - `initialtext`/`finaltext`/survey free-text fields are exported as-is when selected; there's no automated name-stripping pass on them yet.
- No grade-level summary/aggregate export (counts + stats by grade/IEP/504/Lunch/Gifted) - only the individual-record export exists so far.

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
