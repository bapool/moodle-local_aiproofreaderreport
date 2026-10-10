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
 * "My grant numbers": a grant teacher's own AI Proofreader numbers for the
 * saved quarter - the same totals as the Reimbursement tab and their
 * notification email, plus their per-activity breakdown from the Overview
 * tab. Linked from the teacher's profile page (Reports section).
 *
 * Only teachers on the grant roster can open it, and they only ever see
 * their own numbers. Users who can view the full report can pick any
 * roster teacher, to see exactly what that teacher sees.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_aiproofreaderreport\report_manager;

require_login();
$context = context_system::instance();
$component = 'local_aiproofreaderreport';

$canviewall = has_capability('local/aiproofreaderreport:view', $context);
if ($canviewall) {
    $teacherid = optional_param('teacherid', 0, PARAM_INT);
} else {
    $teacherid = (int) $USER->id;
    if (!report_manager::is_grant_teacher($teacherid)) {
        throw new moodle_exception('mynumbers_notongrant', $component);
    }
}

$pageurl = new moodle_url('/local/aiproofreaderreport/mynumbers.php', $teacherid ? ['teacherid' => $teacherid] : []);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('mynumbers_title', $component));
$PAGE->set_heading(get_string('mynumbers_title', $component));

$roster = report_manager::get_grantteacher_roster();
if ($canviewall && $teacherid && !isset($roster[$teacherid])) {
    $teacherid = 0;
}

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('mynumbers_title', $component));

// Report viewers: choose whose page to preview.
if ($canviewall) {
    $options = [];
    foreach ($roster as $user) {
        $options[$user->id] = fullname($user);
    }
    echo html_writer::tag('p', get_string('mynumbers_preview', $component));
    echo $OUTPUT->single_select(
        new moodle_url('/local/aiproofreaderreport/mynumbers.php'),
        'teacherid',
        $options,
        $teacherid,
        ['' => get_string('mynumbers_chooseteacher', $component)],
        null,
        ['label' => get_string('filter_teacher', $component)]
    );
    if (empty($roster)) {
        echo $OUTPUT->notification(get_string('grantroster_none', $component), 'warning');
    }
    if (!$teacherid) {
        echo $OUTPUT->footer();
        exit;
    }
}

$quarter = report_manager::get_quarter();
if (!$quarter->isset) {
    echo $OUTPUT->notification(get_string('mynumbers_noquarter', $component), 'info');
    echo $OUTPUT->footer();
    exit;
}

$dateformat = get_string('strftimedate', 'langconfig');
$a = (object) [
    'label' => $quarter->label !== '' ? s($quarter->label) : get_string('quarter_nolabel', $component),
    'from' => userdate(strtotime($quarter->datefrom . ' 00:00:00'), $dateformat),
    'to' => userdate(strtotime($quarter->dateto . ' 23:59:59'), $dateformat),
    'target' => $quarter->target,
];
echo html_writer::tag('h3', get_string('mynumbers_quarter', $component, $a));
echo html_writer::tag('p', get_string('mynumbers_intro', $component));

$filters = [
    'courseid' => 0,
    'teacherid' => $teacherid,
    'gradelevel' => '',
    'groupid' => 0,
    'datefrom' => $quarter->datefrom,
    'dateto' => $quarter->dateto,
];

// Totals - the same row as the Reimbursement tab and the notification email.
$totalsrow = null;
foreach (report_manager::get_reimbursement_table_rows($filters) as $row) {
    if ((int) $row->teacherid === $teacherid) {
        $totalsrow = $row;
        break;
    }
}
if ($totalsrow === null) {
    $totalsrow = (object) ['assigned' => 0, 'submitted' => 0, 'final' => 0, 'finalized' => 0, 'gradedcount' => 0];
}
$activitytotals = report_manager::get_teacher_activity_totals($filters, [$teacherid])[$teacherid];

echo html_writer::tag('h4', get_string('mynumbers_totals', $component));
$table = new html_table();
$table->head = [
    get_string('reimbursement_studentsassigned', $component),
    get_string('reimbursement_draftssubmitted', $component),
    get_string('reimbursement_finalsubmissions', $component),
    get_string('overview_teacherfinalized', $component),
    get_string('reimbursement_gradedcount', $component),
];
$cells = [
    $totalsrow->assigned,
    $totalsrow->submitted,
    $totalsrow->final,
    $totalsrow->finalized,
    $totalsrow->gradedcount,
];
if ($quarter->target > 0) {
    $outlook = report_manager::get_goal_outlook((int) $totalsrow->gradedcount, $activitytotals, $quarter->target);
    $table->head[] = get_string('mynumbers_goal', $component);
    $table->head[] = get_string('notify_outlook', $component);
    $cells[] = $quarter->target;
    $cells[] = get_string('notify_outlook_' . $outlook->status, $component, $outlook->short);
}
$table->data[] = $cells;
$table->attributes['class'] = 'generaltable aiproofreaderreport-table';
echo html_writer::table($table);

// Per-activity breakdown - the Overview tab's rows for this teacher.
echo html_writer::tag('h4', get_string('mynumbers_activities', $component));
$rows = report_manager::get_overview_rows($filters);
if (empty($rows)) {
    echo $OUTPUT->notification(get_string('mynumbers_noactivities', $component), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('overview_course', $component),
        get_string('overview_activity', $component),
        get_string('overview_gradelevel', $component),
        get_string('overview_enrolled', $component),
        get_string('overview_submitted', $component),
        get_string('overview_finalsubmitted', $component),
        get_string('overview_teacherfinalized', $component),
        get_string('overview_graded', $component),
    ];
    $table->attributes['class'] = 'generaltable aiproofreaderreport-table';
    foreach ($rows as $row) {
        $table->data[] = [
            format_string($row->coursename),
            format_string($row->activityname),
            $row->gradelevel,
            $row->enrolledcount,
            $row->submittedcount,
            $row->finalcount,
            $row->finalizedcount,
            $row->gradedcount,
        ];
    }
    echo html_writer::table($table);
}

echo $OUTPUT->footer();
