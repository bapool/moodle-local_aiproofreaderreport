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
> - **Reimbursement** — count of final & graded submissions per teacher within a chosen date range, for stipend/reimbursement purposes.
> - **Data Dictionary** — two downloadable CSV references: the Data Export dictionary (a codebook for the public dataset) and the database dictionary (every field the report reads).
> - **Anonymized Export** — de-identified student data export for research or third-party sharing, with an opt-out roster import and a field picker that defaults to excluding personally identifying columns.
>
> Grant teachers can check their own numbers for the current quarter from a link on their Moodle profile page.
>
> All report data is scoped to Middle School and High School students only, via two admin-configurable management course IDs. The plugin also provides a single settings page for controlling mod_aiproofreader's survey system site-wide — turning surveys on/off, showing or hiding individual questions, editing their wording, and tracking which AI model/provider generated each piece of feedback.

This plugin stores no report data of its own (only two small admin rosters - see the database dictionary below) — it reads directly from the existing `mod_aiproofreader` tables and reports on usage across every course, scoped to Middle School and High School students only.

## Requirements

Requires **mod_aiproofreader** already installed (declared as a hard plugin dependency — this plugin will not install or upgrade without it, since it exists to configure and report on it).

## What it shows

Accessible under **Site administration → Reports → AI Proofreader Report**.

- **Overview** — which teachers are using AI Proofreader, in which courses, how many students are enrolled/submitted/finished/graded per activity. Activity names link to the activity (opens in a new tab) so it can be inspected.
- **Student Survey** — average score and response count per student survey question, plus a breakdown of which feedback category students found more helpful.
- **Teacher Survey** — average score and response count per teacher survey question.
- **Reimbursement** — count of **Final & graded** AI Proofreader submissions per teacher within a chosen date range, for stipend/reimbursement purposes. Final & graded means the work reached a final version (made by the student, or by the teacher with "Finalize grade") and was then graded (submission status `graded`). A grade entered early with "Grade now" on unfinished work - often a zero to push a student to finish - does not count until the work is finalized and graded. Since v0.8.1 this is counted exactly like the Overview tab: for every activity in the courses where the teacher is an editing teacher, with the date range applied to the activity's deciding date - normally its due date (see "Which quarter an activity counts in" below), so a teacher's total always equals the sum of their activity rows (and the Reimbursement tab, notification email and My AI Proofreader grant numbers page always agree). In a course with two editing teachers, both are credited, as on the Overview tab. Since v0.7.2 each teacher row also shows Students assigned (activities x enrolled MS/HS students), Drafts submitted and Final submissions, so you can spot teachers who haven't assigned enough or are stuck partway and may need help. The table lists everyone who graded in the range plus every grant roster teacher, even with nothing yet. Users with `local/aiproofreaderreport:notify` can send each teacher a summary of their students submitted, final submissions and graded count across their courses for the chosen range. With an optional goal, teachers who have met it get a congratulations; the rest are told whether finishing their outstanding drafts and ungraded finals will meet the goal (no new assignment needed) or how many they will still be short (they may need another activity). The confirmation page previews the exact message before anything is sent.

  **Which quarter an activity counts in (since v0.8.5; due-date rule since v0.8.7).** Each activity counts in exactly one period, decided by its deciding date: (1) the activity's **due date**; if there is no due date or it is stale, (2) its **"Allow submissions from"** date; if neither is set or trusted, (3) its **majority-final date** - the date by which more than half of its MS/HS students had made their own final submission. A due or open date is stale when the majority-final date is more than 4 weeks after it (for example an activity reused from last year without updating its dates); finals coming in early never make a date stale. This lets a teacher who builds an activity at the end of one quarter, but sets it due in the next, have it count in the next quarter. If the deciding date is inside the date range, every submission to the activity counts - including students who finished late and work graded after the period ended; otherwise the whole activity is left out. Teacher-finalized submissions ("Finalize grade") are never used for the majority-final date, so closing out work after a quarter ends can't move an activity; they still count toward the stipend in whichever quarter the activity belongs to. If no student has made a final submission yet, the majority uses draft submission dates, then the dates the submission records were created. It applies to the Overview tab, the Reimbursement tab, notifications, the print stipend summary and the My AI Proofreader grant numbers page. Use back-to-back quarter dates (for example Q1 ending 10/18 and Q2 starting 10/19) so each activity lands in exactly one quarter.

  **Print stipend summary (since v0.8.5).** Next to "Notify teachers" is a **Print stipend summary** button. It opens a printable page in a new tab for the treasurer: the period (and quarter name, if the dates match the saved quarter), the goal, each grant teacher's students submitted, final submissions, final & graded and whether they met the goal (Yes/No), totals, and Prepared by / Approved by (Treasurer) signature lines. The site menus and buttons are hidden when printed. It lists the same teachers as Notify teachers (the grant roster, if uploaded) and uses the goal typed in the goal box.

  **Grant teacher roster.** Not every teacher who grades AI Proofreader activities is in the grant program, and some grant teachers may not have graded anything yet. On the Reimbursement tab, upload a CSV or text file of grant teachers' email addresses (one per line; a header row and extra columns are ignored). The roster is saved, so it only needs uploading again when the list changes - each upload replaces the whole list. Once a roster exists, "Notify teachers" goes to exactly the roster teachers, including those with a graded count of 0, and to no one else. Emails that don't match a Moodle account are listed after the upload and skipped. An upload with no valid email addresses is rejected rather than clearing the roster. With no roster uploaded, notifications go to every teacher on the Reimbursement table (the pre-v0.7.1 behaviour).
  **Quarter dates.** "Save quarter dates" on the filter bar saves a quarter name, the dates in the date boxes and an optional graded goal, without sending anything. "Apply quarter dates" puts the saved dates back into the filters. Sending notifications also saves the dates (and goal, if entered) it used, keeping the name. The saved quarter is what grant teachers see on their own page (below). Both the save and the notify buttons need `local/aiproofreaderreport:notify`.

  **Teacher-finalized.** The Overview and Reimbursement tabs show a "Teacher-finalized" column: drafts a teacher graded as final with AI Proofreader's "Finalize grade" (end of a grading period, the student never submitted a final version). They are also counted in Final submissions and, once graded, in Graded.
- **Data Dictionary** — two downloadable CSVs. The **Data Export dictionary** describes each column the Data Export button selects: the heading exactly as it appears in the CSV, its type, its allowed values and a plain description. It is the codebook that goes alongside the public dataset. The **Database dictionary** lists every AI Proofreader database field the report reads, for internal use.
- **Anonymized Export** — de-identified student data export as a CSV. Tick the fields you want; "Privacy defaults" clears the personally identifying ones, "Deselect all" clears everything, and "Data Export" ticks exactly the fields for the public research dataset (see below). "Save export items" remembers your ticked fields (per user) so they are ticked again next time. The Personally identifying column also offers the original, NOT de-identified draft and final text: these are for internal testing only (re-running the AI after prompt changes, and checking the redaction against the original) and must never be shared; they sit next to the de-identified text in the CSV for comparison. The opt-out roster import (students to leave out of every export) is at the bottom of the tab.

### My AI Proofreader grant numbers (teachers)

Teachers on the grant roster get a **My AI Proofreader grant numbers** link in the Reports section of their own Moodle profile page (`local/aiproofreaderreport/mynumbers.php`). It shows the saved quarter's name and dates, their totals row (the same numbers as the Reimbursement tab and their notification email, plus their goal and outlook when a goal is saved), and their per-activity breakdown from the Overview tab. Teachers can't change the dates and only ever see their own numbers; teachers not on the roster get no link and can't open the page. Anyone with `local/aiproofreaderreport:view` can open the page and pick any roster teacher to see exactly what that teacher sees.

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

"In scope" means a student is enrolled in either the High School or Middle School **management course**, whose course IDs are set under the plugin's settings page (Site administration → Plugins → Local plugins → AI Proofreader Report settings). Both are blank on a new install, and every tab shows a setup notice (with a link to the settings) until at least one is set, so the report can never silently count the wrong students. Elementary students are excluded because they are not enrolled in either management course.

## Optional district contacts table

If the Moodle database has a table named `contacts` (a district student information export, not part of Moodle), the Anonymized Export offers its school and demographic fields. Rows are matched to Moodle users by `contacts.StudentNumber` = the user's **ID number** (`user.idnumber`), compared as text, so an empty or non-numeric ID number simply finds no match. Columns read, all optional apart from `StudentNumber`: `StudentNumber`, `SchoolYear`, `SchoolCode`, `DistrictID`, `SchoolID`, `StudentStatus`, `LastName`, `FirstName`, `City`, `State`, `GradeLevelCode`, `IEP`, `Status504`, `StudentFreeLunchStatus`, `Gifted`, `Gender`, `StudentEthnicity`. Sites without the table see none of these fields.

## Permissions

- `local/aiproofreaderreport:view` — Managers and Site Administrators. Grants access to all report tabs and the data dictionary download.
- `local/aiproofreaderreport:notify` — Managers and Site Administrators. Upload the grant roster, save quarter dates and send teachers their counts.
- Teachers on the grant roster need no capability to open their own My AI Proofreader grant numbers page.
- `local/aiproofreaderreport:export` — no role has this by default; only Site Administrators (via the built-in "doanything" override) can use it. This will gate the anonymized student data export once it's built.

## Anonymized export: how it works

- The anonymous student ID is computed by mod_aiproofreader (`aiproofreader_get_anon_id()`).
- "Draft/Final submission text (de-identified)" come from `initialtextredacted` / `finaltextredacted`, and the two AI feedback fields and the AI feedback-followed analysis (de-identified) from `feedbackgrammarredacted` / `feedbackassignmentredacted` / `aicomparisonredacted`. mod_aiproofreader's nightly PII redaction task fills these in (v0.8.3 or later for the feedback and comparison); they are blank until it has processed the submission.
- The "(original - NOT de-identified)" fields are the raw text, feedback and comparison. Internal testing only, never for release.
- Students on the opt-out roster are left out of every export.
- Saved export items are stored per user in the `local_aiproofreaderreport_exportfields` user preference.

## Data Export (public research dataset)

The **Data Export** button ticks only the fields meant for the grant's public dataset on Hugging Face, and clears everything else. The list (`export_field_catalog::get_dataexport_keys()`) is modelled on comparable public student-writing datasets - PERSUADE 2.0 (essays, human scores, demographics) and ArgRewrite V.2 (draft, feedback, revision, participant surveys) - no more, no less:

- Anonymous student ID, school year, grade level
- Lunch (economic status), IEP, 504
- Assignment instructions, target grade level (Lexile setting), maximum points
- De-identified draft text, AI feedback (both sections), final text and AI comparison; teacher-finalized flag, feedback-followed score, feedback and comparison AI model
- Teacher's grade (points)
- Student survey ratings (q1-q5) and teacher survey ratings (q1-q6)

Left out on purpose: names, emails, links, student number, school and district IDs, the teacher's extra AI instructions, free-text survey answers, instructor comments, grader, all timestamps, submission type and status, gender, ethnicity and gifted.

Download the **Data Export dictionary** from the Data Dictionary tab and post it with the data: it describes exactly these columns, in the same order and with the same headings, because it is built from the same field list as the button. Two parts of it are read live when you download it, so it always matches the site:

- **District codes** (school year, grade level, lunch, IEP, 504) are listed from the values actually in the `contacts` table, since each district's student information system has its own codes.
- **Survey questions** are quoted with the wording the site actually uses (set in this plugin's survey settings), not the default wording.

Before posting: make sure the nightly redaction has run on every submission (blank de-identified text means not yet processed), and in any grade level with only a few IEP/504 students, blank or merge those columns so no student can be singled out. Read the assignment instructions too: they are written by teachers and are not de-identified, so a teacher who signed their instructions would be named.

## Database dictionary (tables this plugin reads)

The `aiproofreader...` tables below belong to `mod_aiproofreader`; `user` is Moodle's and `contacts` is the optional district table. This plugin's own two tables are small admin rosters, not report data:

- `local_aiproofreaderreport_optout` - district student numbers (matched to `user.idnumber`) whose parents opted out; excluded from the anonymized export. Each row also records when it was uploaded and by whom. Personal data, declared in the privacy provider (export and deletion).
- `local_aiproofreaderreport_grantteacher` - Moodle user ids of grant program teachers; decides who gets reimbursement notifications and who can open the My AI Proofreader grant numbers page. Declared in the privacy provider.

The saved quarter is stored as plugin settings (`quarterlabel`, `quarterdatefrom`, `quarterdateto`, `quartertarget`), not in a table.

### `aiproofreader`
| Field | Description |
|---|---|
| id | Unique ID of the activity instance |
| course | Course ID the activity belongs to |
| name | Assignment name |
| intro | Assignment instructions written by the teacher (HTML). Also the main instructions given to the AI |
| aiinstructions | Extra teacher-only instructions for the AI (HTML), not shown to students |
| gradelevel | Target grade level (3-12) for AI feedback tone/lexile |
| grade | Maximum points for the activity |

### `aiproofreader_submission`
| Field | Description |
|---|---|
| id | Unique ID of the submission row (one per student per activity) |
| aiproofreaderid | Links to the aiproofreader activity instance |
| userid | Student user ID |
| status | draft, feedbackpending, feedbackready, finalsubmitted, graded |
| initialsubmissiontype | How the draft was submitted: text, file (Word) or gdrive (Google Doc) |
| initialtext | Original draft text as the student submitted it (NOT de-identified) |
| initialtextredacted | AI de-identified copy of the draft text, for release |
| initialtimesubmitted | Unix timestamp of the draft submission |
| initialgdrivelink | Google Drive link pasted for the draft, if that submission type was used |
| finalsubmissiontype | How the final was submitted: text, file (Word) or gdrive (Google Doc) |
| finaltext | Original final text as the student submitted it (NOT de-identified) |
| finaltextredacted | AI de-identified copy of the final text, for release |
| finaltimesubmitted | Unix timestamp of the final submission |
| finalgdrivelink | Google Drive link pasted for the final, if that submission type was used |
| feedbackgrammar | AI Grammar and Spelling feedback (NOT de-identified) |
| feedbackgrammarredacted | AI de-identified copy of the grammar feedback, for release |
| feedbackassignment | AI Assignment Specifics feedback (NOT de-identified) |
| feedbackassignmentredacted | AI de-identified copy of the assignment feedback, for release |
| aicomparison | AI analysis of whether feedback was followed (NOT de-identified) |
| aicomparisonredacted | AI de-identified copy of the AI comparison, for release |
| feedbacktimecreated | Unix timestamp the AI feedback was generated |
| aicomparisontimecreated | Unix timestamp the AI comparison was generated |
| timecreated | Unix timestamp the submission record was created |
| timemodified | Unix timestamp the submission record was last changed |
| aifollowedscore | 1-5 AI-generated score of how well feedback was followed |
| teacherfinalized | 1 if the teacher closed the assignment with "Finalize grade" at the end of the grading period, so the final version is a copy of the draft; 0 if the student made their own final submission |
| feedbackaimodel | Label identifying the AI model/provider that generated the feedback (site-configured, plus provider-reported detail when available) |
| comparisonaimodel | Label identifying the AI model/provider that generated the comparison (site-configured, plus provider-reported detail when available) |

### `aiproofreader_studentsurvey`
Any field below may be `null` if that question has been individually disabled in survey settings, or if the whole survey system is off.

| Field | Description |
|---|---|
| q1overallfeedback | 1-5: overall feedback useful |
| q2specificfeedback | 1-5: assignment-specific feedback useful |
| q3usedfeedback | 1-5: used feedback to improve submission |
| q4categoryhelped | grammar, assignment, or both |
| q5confidence | 1-5: confidence in final vs. draft |
| freetext | Optional free-text answer: what the AI feedback missed. May contain names |
| timecreated | Unix timestamp the student survey was submitted |

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
| freetext | Optional free-text concerns from the teacher. May contain names |
| timecreated | Unix timestamp the teacher survey was submitted |

### `aiproofreader_grade`
| Field | Description |
|---|---|
| grade | Points awarded by the teacher |
| graderid | Teacher user ID who graded the submission |
| instructorcomments | Teacher written comments to the student. May contain names |
| timemodified | Unix timestamp the grade was last saved |

### `user`
Moodle's own user table - only these two fields are read.

| Field | Description |
|---|---|
| idnumber | District student ID (SSID). Source of the anonymous student ID; never exported for release |
| email | Student login email |

### `contacts`
Optional district student information table - see "Optional district contacts table" above. Only these columns are read.

| Field | Description |
|---|---|
| StudentNumber | Optional district SIS table. District student ID (SSID). Matched to user.idnumber |
| SchoolYear | Optional district SIS table. School year of the student record |
| SchoolCode | Optional district SIS table. School code |
| DistrictID | Optional district SIS table. District ID |
| SchoolID | Optional district SIS table. School ID |
| StudentStatus | Optional district SIS table. Enrollment status |
| LastName | Optional district SIS table. Student last name |
| FirstName | Optional district SIS table. Student first name |
| City | Optional district SIS table. City of residence |
| State | Optional district SIS table. State of residence |
| GradeLevelCode | Optional district SIS table. Student grade level |
| IEP | Optional district SIS table. Individualized Education Program (special education) status |
| Status504 | Optional district SIS table. Section 504 plan status |
| StudentFreeLunchStatus | Optional district SIS table. Free/reduced lunch status (economic disadvantage) |
| Gifted | Optional district SIS table. Gifted identification |
| Gender | Optional district SIS table. Gender |
| StudentEthnicity | Optional district SIS table. Ethnicity |

## Copyright

2026 Brian Pool. Licensed under the GNU GPL v3 or later.
