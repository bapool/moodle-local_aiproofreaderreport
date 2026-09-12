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

$string['aiproofreaderreport:export'] = 'Export anonymized AI Proofreader student data';
$string['aiproofreaderreport:view'] = 'View the AI Proofreader analytics report';
$string['anonexport_columnheading_pii'] = 'Personally identifying (off by default)';
$string['anonexport_columnheading_structural'] = 'Submission & feedback data';
$string['anonexport_columnheading_tag'] = 'Demographic / program tags';
$string['anonexport_downloadbutton'] = 'Download CSV';
$string['anonexport_intro'] = 'Export de-identified student data for sharing with a third party.';
$string['anonexport_nocontacts'] = 'The district contacts table was not found on this install, so grade level, IEP/504/Lunch/Gifted, and other contacts-sourced fields are not available to export here.';
$string['anonexport_nofields'] = 'Choose at least one field to export.';
$string['anonexport_privacybutton'] = 'Privacy defaults (deselect PII)';
$string['anonexport_subgroupheading_assignmentspecifics'] = 'Assignment Submission Specifics';
$string['cleanupstaledata'] = 'Clean up stale AI Proofreader data';
$string['collectgdrivetext'] = 'Retain Google Doc text';
$string['collectgdrivetext_desc'] = 'Off by default. The text of a submitted Google Doc is always fetched briefly so the AI can generate feedback and comparison, but is cleared back out afterward (leaving only the link) unless this is turned on. Turn this on if you need the actual submitted text available for data export/research - a link alone is not usable research data.';
$string['currentaimodellabel'] = 'Current AI model label';
$string['currentaimodellabel_desc'] = 'A free-text label describing whichever AI model/provider is currently configured for this site (e.g. "GPT-4o", "Claude Sonnet 4.5 via Anthropic provider"). Moodle\'s AI subsystem does not reliably report which model actually answered a request - that depends on the provider plugin and is inconsistent - so this label is stamped onto every AI Proofreader feedback and comparison as the reliable record of what was in use. Update it whenever you change the site\'s AI provider or model, e.g. each quarter, so the report can compare survey results across models.';
$string['datadictionary_col_description'] = 'Description';
$string['datadictionary_col_field'] = 'Field';
$string['datadictionary_col_table'] = 'Table';
$string['datadictionary_download'] = 'Download data dictionary (CSV)';
$string['datadictionary_intro'] = 'Download a reference of every AI Proofreader database field and what it contains.';
$string['exportfield_anonid'] = 'Anonymous student ID';
$string['exportfield_ap_aiinstructions'] = 'AI instructions';
$string['exportfield_ap_instructions'] = 'Assignment instructions';
$string['exportfield_contact_504'] = '504';
$string['exportfield_contact_city'] = 'City';
$string['exportfield_contact_districtid'] = 'District ID';
$string['exportfield_contact_ethnicity'] = 'Ethnicity';
$string['exportfield_contact_firstname'] = 'First name';
$string['exportfield_contact_gender'] = 'Gender';
$string['exportfield_contact_gifted'] = 'Gifted';
$string['exportfield_contact_gradelevel'] = 'Grade level';
$string['exportfield_contact_iep'] = 'IEP';
$string['exportfield_contact_lastname'] = 'Last name';
$string['exportfield_contact_lunch'] = 'Lunch (free lunch program)';
$string['exportfield_contact_schoolcode'] = 'School code';
$string['exportfield_contact_schoolid'] = 'School ID';
$string['exportfield_contact_schoolyear'] = 'School year';
$string['exportfield_contact_state'] = 'State';
$string['exportfield_contact_studentnumber'] = 'Student number (SSID)';
$string['exportfield_contact_studentstatus'] = 'Student status';
$string['exportfield_grade_grade'] = 'Grade (points)';
$string['exportfield_grade_graderid'] = 'Grading teacher';
$string['exportfield_grade_instructorcomments'] = 'Instructor comments';
$string['exportfield_grade_timemodified'] = 'Grade last modified';
$string['exportfield_ssurvey_freetext'] = 'Student survey: free-text comments';
$string['exportfield_ssurvey_q1overallfeedback'] = 'Student survey: overall feedback useful';
$string['exportfield_ssurvey_q2specificfeedback'] = 'Student survey: assignment-specific feedback useful';
$string['exportfield_ssurvey_q3usedfeedback'] = 'Student survey: used feedback to improve';
$string['exportfield_ssurvey_q4categoryhelped'] = 'Student survey: which category helped';
$string['exportfield_ssurvey_q5confidence'] = 'Student survey: confidence in final vs draft';
$string['exportfield_ssurvey_timecreated'] = 'Student survey submitted time';
$string['exportfield_sub_aicomparison'] = 'AI feedback-followed analysis';
$string['exportfield_sub_aicomparisontimecreated'] = 'Comparison generated time';
$string['exportfield_sub_aifollowedscore'] = 'Feedback-followed score (1-5)';
$string['exportfield_sub_comparisonaimodel'] = 'Comparison AI model';
$string['exportfield_sub_feedbackaimodel'] = 'Feedback AI model';
$string['exportfield_sub_feedbackassignment'] = 'AI feedback: assignment specifics';
$string['exportfield_sub_feedbackgrammar'] = 'AI feedback: grammar and spelling';
$string['exportfield_sub_feedbacktimecreated'] = 'Feedback generated time';
$string['exportfield_sub_finalgdrivelink'] = 'Final Google Drive link';
$string['exportfield_sub_finalsubmissiontype'] = 'Final submission type';
$string['exportfield_sub_finaltext'] = 'Final submission text (de-identified)';
$string['exportfield_sub_finaltimesubmitted'] = 'Final submitted time';
$string['exportfield_sub_initialgdrivelink'] = 'Draft Google Drive link';
$string['exportfield_sub_initialsubmissiontype'] = 'Draft submission type';
$string['exportfield_sub_initialtext'] = 'Draft submission text (de-identified)';
$string['exportfield_sub_initialtimesubmitted'] = 'Draft submitted time';
$string['exportfield_sub_status'] = 'Submission status';
$string['exportfield_sub_timecreated'] = 'Submission record created';
$string['exportfield_sub_timemodified'] = 'Submission record modified';
$string['exportfield_tsurvey_freetext'] = 'Teacher survey: free-text comments';
$string['exportfield_tsurvey_q1overallfeedback'] = 'Teacher survey: overall feedback useful';
$string['exportfield_tsurvey_q2specificfeedback'] = 'Teacher survey: assignment-specific feedback useful';
$string['exportfield_tsurvey_q3usedfeedback'] = 'Teacher survey: student used feedback';
$string['exportfield_tsurvey_q4feedbackfollowed'] = 'Teacher survey: feedback was followed';
$string['exportfield_tsurvey_q5aiscaffold'] = 'Teacher survey: AI helped scaffold the student';
$string['exportfield_tsurvey_q6aiaccuracy'] = 'Teacher survey: AI feedback was accurate';
$string['exportfield_tsurvey_timecreated'] = 'Teacher survey submitted time';
$string['exportfield_user_studentemail'] = 'Student email';
$string['filter_allcourses'] = 'All courses (MS &amp; HS)';
$string['filter_allgrades'] = 'All grade levels';
$string['filter_allgroups'] = 'All groups';
$string['filter_allteachers'] = 'All teachers';
$string['filter_apply'] = 'Apply filters';
$string['filter_clear'] = 'Clear filters';
$string['filter_course'] = 'Course';
$string['filter_datefrom'] = 'Date from';
$string['filter_dateto'] = 'Date to';
$string['filter_gradelevel'] = 'Grade level';
$string['filter_group'] = 'Group';
$string['filter_selectcoursefirst'] = 'Select a course to filter by group';
$string['filter_teacher'] = 'Teacher';
$string['filters'] = 'Filters';
$string['hscourseid'] = 'High School management course ID';
$string['hscourseid_desc'] = 'The course ID used as the High School management/building course, used to determine which students fall in scope for this report.';
$string['mscourseid'] = 'Middle School management course ID';
$string['mscourseid_desc'] = 'The course ID used as the Middle School management/building course, used to determine which students fall in scope for this report.';
$string['optout_importbutton'] = 'Import opt-out roster';
$string['optout_importsuccess'] = 'Opt-out roster updated: {$a} student(s) will now be excluded from the export.';
$string['optout_rostercount'] = '{$a} student(s) currently opted out';
$string['optout_uploaderror'] = 'The CSV file could not be read. Please try again.';
$string['overview_activity'] = 'Activity';
$string['overview_aimodels'] = 'AI model(s) used';
$string['overview_course'] = 'Course';
$string['overview_enrolled'] = 'Students enrolled';
$string['overview_finalsubmitted'] = 'Final submissions';
$string['overview_graded'] = 'Graded';
$string['overview_gradelevel'] = 'Grade level';
$string['overview_intro'] = 'Teacher and course usage of AI Proofreader across all in-scope MS/HS courses.';
$string['overview_nodata'] = 'No AI Proofreader activity matches the current filters.';
$string['overview_submitted'] = 'Students submitted';
$string['overview_teacher'] = 'Teacher';
$string['pageheading'] = 'AI Proofreader usage report';
$string['pagetitle'] = 'AI Proofreader Report';
$string['pluginname'] = 'AI Proofreader Report';
$string['privacy:metadata'] = 'The AI Proofreader Report plugin does not store any personal data itself. It only displays and exports aggregated or de-identified data already stored by mod_aiproofreader.';
$string['question_enabled'] = 'Show: {$a}';
$string['question_text'] = 'Wording for: {$a}';
$string['reimbursement_gradedcount'] = 'Graded count';
$string['reimbursement_intro'] = 'Count of graded AI Proofreader activities per teacher within the selected date range, for reimbursement purposes.';
$string['reimbursement_nodata'] = 'No graded submissions match the current filters and date range.';
$string['reimbursement_norange'] = 'Choose a date range above and apply filters to see reimbursement counts.';
$string['reimbursement_teacher'] = 'Teacher';
$string['scopegroup'] = 'Report scope';
$string['settings'] = 'AI Proofreader Report settings';
$string['studentsurvey_q1'] = 'Overall feedback was useful';
$string['studentsurvey_q2'] = 'Assignment-specific feedback was useful';
$string['studentsurvey_q3'] = 'Used the feedback to improve submission';
$string['studentsurvey_q4'] = 'Which feedback category helped more';
$string['studentsurvey_q4_assignment'] = 'Assignment Specifics';
$string['studentsurvey_q4_both'] = 'Both equally';
$string['studentsurvey_q4_grammar'] = 'Grammar and Spelling';
$string['studentsurvey_q5'] = 'Confidence in final vs. draft';
$string['studentsurveyheading'] = 'Student survey questions';
$string['survey_average'] = 'Average score';
$string['survey_nodata'] = 'No survey responses match the current filters.';
$string['survey_question'] = 'Question';
$string['survey_responses'] = 'Responses';
$string['surveyenabled'] = 'Turn on student and teacher surveys';
$string['surveyenabled_desc'] = 'Off by default. While off, no survey questions are shown to students or teachers at all, and AI Proofreader runs in feedback-only mode - there is no way to see survey data without this plugin, so nothing is collected until this is turned on.';
$string['surveysettings'] = 'Survey &amp; data settings';
$string['tab_anonexport'] = 'Anonymized Export';
$string['tab_datadictionary'] = 'Data Dictionary';
$string['tab_overview'] = 'Overview';
$string['tab_reimbursement'] = 'Reimbursement';
$string['tab_studentsurvey'] = 'Student Survey';
$string['tab_teachersurvey'] = 'Teacher Survey';
$string['teachersurvey_q1'] = 'Overall feedback was useful';
$string['teachersurvey_q2'] = 'Assignment-specific feedback was useful';
$string['teachersurvey_q3'] = 'Student used the feedback';
$string['teachersurvey_q4'] = 'Feedback was followed';
$string['teachersurvey_q5'] = 'AI helped scaffold the student';
$string['teachersurvey_q6'] = 'AI feedback was accurate for this assignment';
$string['teachersurveyheading'] = 'Teacher survey questions';
