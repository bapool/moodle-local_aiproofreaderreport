# local_aiproofreaderreport

An admin/manager-facing analytics report for **mod_aiproofreader** (AI Proofreader), built for National Trail Local Schools.

## Moodle.org marketplace listing

**Short description:**

> Admin analytics report for AI Proofreader (mod_aiproofreader): usage, survey scores, and reimbursement tracking scoped to Middle and High School students.

**Full description:**

> AI Proofreader Report is an admin/manager-facing companion report for the AI Proofreader (mod_aiproofreader) activity module. It stores no data of its own — it reads directly from mod_aiproofreader's existing tables — and gives site administrators and managers a single place to see how the activity is being used across every course.
>
> Requires mod_aiproofreader to already be installed; this plugin will not install or upgrade without it.
>
> Accessible under Site administration → Reports → AI Proofreader Report, with six tabs:
>
> - **Overview** — which teachers are using AI Proofreader, in which courses, and enrolled/submitted/finished/graded counts per activity.
> - **Student Survey** and **Teacher Survey** — average score and response count per survey question.
> - **Reimbursement** — count of graded activities per teacher within a chosen date range, for stipend/reimbursement purposes.
> - **Data Dictionary** — a downloadable CSV reference of every field the report reads.
> - **Anonymized Export** — de-identified student data export for research or third-party sharing, with an opt-out roster import and a field picker that defaults to excluding personally identifying columns.
>
> All report data is scoped to Middle School and High School students only, via two admin-configurable management course IDs. The plugin also provides a single settings page for controlling mod_aiproofreader's survey system site-wide — turning surveys on/off, showing or hiding individual questions, editing their wording, and tracking which AI model/provider generated each piece of feedback.

This plugin does **not** store any data of its own — it reads directly from the existing `mod_aiproofreader` tables and reports on usage across every course, scoped to Middle School and High School students only.

## Requirements

Requires **mod_aiproofreader** already installed (declared as a hard plugin dependency — this plugin will not install or upgrade without it, since it exists to configure and report on it).

## What it shows

Accessible under **Site administration → Reports → AI Proofreader Report**.

- **Overview** — which teachers are using AI Proofreader, in which courses, how many students are enrolled/submitted/finished/graded per activity.
- **Student Survey** — average score and response count per student survey question, plus a breakdown of which feedback category students found more helpful.
- **Teacher Survey** — average score and response count per teacher survey question.
- **Reimbursement** — count of graded AI Proofreader activities per teacher within a chosen date range, for stipend/reimbursement purposes.
- **Data Dictionary** — downloadable CSV reference of every field this report reads.
- **Anonymized Export** — placeholder for a future de-identified student data export (see "Planned: anonymized export" below).

All report tabs (except Data Dictionary and Anonymized Export) share one filter bar: course, teacher, grade level, group (once a course is selected), and a date range.

Averages on the Student Survey and Teacher Survey tabs only count questions that actually have an answer — a question that's been turned off (see below) doesn't drag the average down as a zero.

## Survey & data settings

The **Survey & data settings** section of the plugin's settings page (Site administration → Plugins → Local plugins → AI Proofreader Report settings) controls `mod_aiproofreader` itself:

- **Turn on student and teacher surveys** — off by default. While off, AI Proofreader shows no survey questions to anyone and runs in feedback-only mode. There's no point collecting survey data with no plugin installed to see it, so nothing is collected until this is on.
- **Retain Google Doc text** — off by default. A submitted Google Doc's text is always fetched briefly so the AI can generate feedback/comparison, but is cleared back to just the stored link afterward unless this is on. Turn it on if you need the actual submitted text for data export/research — a bare link isn't usable research data.
- **Current AI model label** — a free-text label (e.g. "GPT-4o", "Claude Sonnet 4.5") describing whichever AI provider/model the site currently has configured. See "AI model tracking" below.
- **Per-question controls** — for each of the 5 student questions, 6 teacher questions, and each side's free-text box: a show/hide checkbox and an editable wording field. Unchecking a question stops it from being asked and stops it from being collected at all; the original wording is the default and can be edited back at any time.

These settings are stored under `mod_aiproofreader`'s own config (component `aiproofreader`), not this plugin's — this is just where they're edited.

## AI model tracking

Every AI Proofreader submission records which AI model/provider generated its feedback and its comparison (`feedbackaimodel`, `comparisonaimodel` on `aiproofreader_submission`), shown in the Overview tab's "AI model(s) used" column.

Moodle's AI subsystem doesn't reliably report which model actually answered a request — that depends entirely on the provider plugin, and it's inconsistent across providers. So the reliable source is the **Current AI model label** setting above: whatever you type there gets stamped onto every submission's feedback and comparison at the moment they're generated. If a provider happens to also report its own model identifier in the response, that's appended automatically in parentheses as a bonus detail — but the setting is what you can actually count on.

**Update this label whenever the site's AI provider/model configuration changes** — e.g. once a quarter, if that's the cadence you're comparing student/teacher preference across. Since the label is stamped at generation time, older submissions keep whatever label was set when they were generated; you don't need to (and can't) retroactively relabel past data.

## Scheduled cleanup

A weekly scheduled task (`local_aiproofreaderreport\task\cleanup_stale_data`, Sundays 2 AM by default) removes AI Proofreader submissions — and their survey/grade/file data — left behind by deleted activities or deleted user accounts, so the database doesn't slowly fill with data nothing can reference anymore. This is a safety net on top of the mod's own course-reset and activity-delete cleanup, for data orphaned some other way. Adjust the schedule under **Site administration → Server → Scheduled tasks**, or run it manually:

```
php admin/cli/scheduled_task.php --execute='\local_aiproofreaderreport\task\cleanup_stale_data'
```

## Scope: MS/HS only

"In scope" means a student is enrolled in either the High School or Middle School **management course**, whose course IDs are set under the plugin's settings page (Site administration → Plugins → Local plugins → AI Proofreader Report settings). Defaults match `mod_mtssdashboard`'s convention: HS = 53, MS = 54. Elementary students are excluded because they are not enrolled in either management course.

## Permissions

- `local/aiproofreaderreport:view` — Managers and Site Administrators. Grants access to all report tabs and the data dictionary download.
- `local/aiproofreaderreport:export` — no role has this by default; only Site Administrators (via the built-in "doanything" override) can use it. This will gate the anonymized student data export once it's built.

## Planned: anonymized export

Not implemented in this version. When built, it will:
- Generate a fresh anonymous student ID each export run (no persistent identity mapping is kept between exports).
- Strip PII (student names and other identifying text) out of submission and feedback text, not just replace the student ID.
- Let the site admin choose which fields to include per export.

## Database dictionary (tables this plugin reads)

All tables below belong to `mod_aiproofreader`; this plugin creates no tables of its own.

### `aiproofreader`
| Field | Description |
|---|---|
| id | Unique ID of the activity instance |
| course | Course ID the activity belongs to |
| name | Assignment name |
| gradelevel | Target grade level (3-12) for AI feedback tone/lexile |
| grade | Maximum points for the activity |

### `aiproofreader_submission`
| Field | Description |
|---|---|
| id | Unique ID of the submission row (one per student per activity) |
| aiproofreaderid | Links to the aiproofreader activity instance |
| userid | Student user ID |
| status | draft, feedbackpending, feedbackready, finalsubmitted, graded |
| initialtimesubmitted | Unix timestamp of the draft submission |
| finaltimesubmitted | Unix timestamp of the final submission |
| feedbackaimodel | Label identifying the AI model/provider that generated the feedback - see "AI model tracking" below |
| comparisonaimodel | Label identifying the AI model/provider that generated the comparison - see "AI model tracking" below |
| aifollowedscore | 1-5 AI-generated score of how well feedback was followed |

### `aiproofreader_studentsurvey`
Any field below may be `null` if that question has been individually disabled in survey settings, or if the whole survey system is off.

| Field | Description |
|---|---|
| q1overallfeedback | 1-5: overall feedback useful |
| q2specificfeedback | 1-5: assignment-specific feedback useful |
| q3usedfeedback | 1-5: used feedback to improve submission |
| q4categoryhelped | grammar, assignment, or both |
| q5confidence | 1-5: confidence in final vs. draft |

### `aiproofreader_teachersurvey`
Any field below may be `null` if that question has been individually disabled in survey settings, or if the whole survey system is off.

| Field | Description |
|---|---|
| q1overallfeedback | 1-5: overall feedback useful |
| q2specificfeedback | 1-5: assignment-specific feedback useful |
| q3usedfeedback | 1-5: did student use the feedback |
| q4feedbackfollowed | 1-5: was the feedback followed |
| q5aiscaffold | 1-5: did the AI help scaffold the student |
| q6aiaccuracy | 1-5: was AI feedback accurate for this assignment |

### `aiproofreader_grade`
| Field | Description |
|---|---|
| grade | Points awarded by the teacher |
| graderid | Teacher user ID who graded the submission |
| timemodified | Unix timestamp the grade was last saved (used for reimbursement counts) |

## Copyright

2026 Brian Pool. Licensed under the GNU GPL v3 or later.
