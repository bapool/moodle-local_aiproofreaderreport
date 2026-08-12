<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * English language strings for local_aiproofreaderreport.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['pluginname'] = 'AI Proofreader Report';
$string['aiproofreaderreport:view'] = 'View the AI Proofreader analytics report';
$string['aiproofreaderreport:export'] = 'Export anonymized AI Proofreader student data';

// Settings.
$string['settings'] = 'AI Proofreader Report settings';
$string['hscourseid'] = 'High School management course ID';
$string['hscourseid_desc'] = 'The course ID used as the High School management/building course, used to determine which students fall in scope for this report.';
$string['mscourseid'] = 'Middle School management course ID';
$string['mscourseid_desc'] = 'The course ID used as the Middle School management/building course, used to determine which students fall in scope for this report.';

$string['scopegroup'] = 'Report scope';
$string['surveysettings'] = 'Survey &amp; data settings';
$string['surveyenabled'] = 'Turn on student and teacher surveys';
$string['surveyenabled_desc'] = 'Off by default. While off, no survey questions are shown to students or teachers at all, and AI Proofreader runs in feedback-only mode - there is no way to see survey data without this plugin, so nothing is collected until this is turned on.';
$string['collectgdrivetext'] = 'Retain Google Doc text';
$string['collectgdrivetext_desc'] = 'Off by default. The text of a submitted Google Doc is always fetched briefly so the AI can generate feedback and comparison, but is cleared back out afterward (leaving only the link) unless this is turned on. Turn this on if you need the actual submitted text available for data export/research - a link alone is not usable research data.';
$string['currentaimodellabel'] = 'Current AI model label';
$string['currentaimodellabel_desc'] = 'A free-text label describing whichever AI model/provider is currently configured for this site (e.g. "GPT-4o", "Claude Sonnet 4.5 via Anthropic provider"). Moodle\'s AI subsystem does not reliably report which model actually answered a request - that depends on the provider plugin and is inconsistent - so this label is stamped onto every AI Proofreader feedback and comparison as the reliable record of what was in use. Update it whenever you change the site\'s AI provider or model, e.g. each quarter, so the report can compare survey results across models.';
$string['studentsurveyheading'] = 'Student survey questions';
$string['teachersurveyheading'] = 'Teacher survey questions';
$string['question_enabled'] = 'Show: {$a}';
$string['question_text'] = 'Wording for: {$a}';

// Cleanup task.
$string['cleanupstaledata'] = 'Clean up stale AI Proofreader data';

// Page / navigation.
$string['pagetitle'] = 'AI Proofreader Report';
$string['pageheading'] = 'AI Proofreader usage report';
$string['tab_overview'] = 'Overview';
$string['tab_studentsurvey'] = 'Student Survey';
$string['tab_teachersurvey'] = 'Teacher Survey';
$string['tab_reimbursement'] = 'Reimbursement';
$string['tab_datadictionary'] = 'Data Dictionary';
$string['tab_anonexport'] = 'Anonymized Export';

// Filters.
$string['filters'] = 'Filters';
$string['filter_course'] = 'Course';
$string['filter_allcourses'] = 'All courses (MS &amp; HS)';
$string['filter_teacher'] = 'Teacher';
$string['filter_allteachers'] = 'All teachers';
$string['filter_gradelevel'] = 'Grade level';
$string['filter_allgrades'] = 'All grade levels';
$string['filter_group'] = 'Group';
$string['filter_allgroups'] = 'All groups';
$string['filter_selectcoursefirst'] = 'Select a course to filter by group';
$string['filter_datefrom'] = 'Date from';
$string['filter_dateto'] = 'Date to';
$string['filter_apply'] = 'Apply filters';
$string['filter_clear'] = 'Clear filters';

// Overview tab.
$string['overview_intro'] = 'Teacher and course usage of AI Proofreader across all in-scope MS/HS courses.';
$string['overview_teacher'] = 'Teacher';
$string['overview_course'] = 'Course';
$string['overview_activity'] = 'Activity';
$string['overview_gradelevel'] = 'Grade level';
$string['overview_enrolled'] = 'Students enrolled';
$string['overview_submitted'] = 'Students submitted';
$string['overview_finalsubmitted'] = 'Final submissions';
$string['overview_graded'] = 'Graded';
$string['overview_aimodels'] = 'AI model(s) used';
$string['overview_nodata'] = 'No AI Proofreader activity matches the current filters.';

// Survey tabs (shared strings).
$string['survey_question'] = 'Question';
$string['survey_average'] = 'Average score';
$string['survey_responses'] = 'Responses';
$string['survey_nodata'] = 'No survey responses match the current filters.';

$string['studentsurvey_q1'] = 'Overall feedback was useful';
$string['studentsurvey_q2'] = 'Assignment-specific feedback was useful';
$string['studentsurvey_q3'] = 'Used the feedback to improve submission';
$string['studentsurvey_q5'] = 'Confidence in final vs. draft';
$string['studentsurvey_q4'] = 'Which feedback category helped more';
$string['studentsurvey_q4_grammar'] = 'Grammar and Spelling';
$string['studentsurvey_q4_assignment'] = 'Assignment Specifics';
$string['studentsurvey_q4_both'] = 'Both equally';

$string['teachersurvey_q1'] = 'Overall feedback was useful';
$string['teachersurvey_q2'] = 'Assignment-specific feedback was useful';
$string['teachersurvey_q3'] = 'Student used the feedback';
$string['teachersurvey_q4'] = 'Feedback was followed';
$string['teachersurvey_q5'] = 'AI helped scaffold the student';
$string['teachersurvey_q6'] = 'AI feedback was accurate for this assignment';

// Reimbursement tab.
$string['reimbursement_intro'] = 'Count of graded AI Proofreader activities per teacher within the selected date range, for reimbursement purposes.';
$string['reimbursement_teacher'] = 'Teacher';
$string['reimbursement_gradedcount'] = 'Graded count';
$string['reimbursement_nodata'] = 'No graded submissions match the current filters and date range.';
$string['reimbursement_norange'] = 'Choose a date range above and apply filters to see reimbursement counts.';

// Data dictionary tab.
$string['datadictionary_intro'] = 'Download a reference of every AI Proofreader database field and what it contains.';
$string['datadictionary_download'] = 'Download data dictionary (CSV)';

// Anonymized export tab.
$string['anonexport_intro'] = 'Export de-identified student data for sharing with a third party.';
$string['anonexport_notice'] = 'This feature is not yet built. When ready, it will strip student identity (assigning a fresh anonymous ID each export) and scrub PII from submission and feedback text, and let you choose which fields to include.';

$string['privacy:metadata'] = 'The AI Proofreader Report plugin does not store any personal data itself. It only displays and exports aggregated or de-identified data already stored by mod_aiproofreader.';
