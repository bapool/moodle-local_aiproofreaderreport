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
 * Confirm and send reimbursement-count notifications to teachers.
 *
 * First visit (from the Reimbursement tab) shows who will be notified and
 * with what count - the grant roster when one has been uploaded, otherwise
 * every teacher with graded submissions; the confirm button POSTs back here with a sesskey to send.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_aiproofreaderreport\report_manager;

require_login();
$context = context_system::instance();
require_capability('local/aiproofreaderreport:view', $context);
require_capability('local/aiproofreaderreport:notify', $context);

$filters = report_manager::get_filters_from_request();
$target = max(0, optional_param('target', 0, PARAM_INT));
$confirm = optional_param('confirm', 0, PARAM_BOOL);

$filterparams = report_manager::filters_to_params($filters);
$returnurl = new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => 'reimbursement'] + $filterparams);

$PAGE->set_url(new moodle_url('/local/aiproofreaderreport/notify.php', $filterparams + ['target' => $target]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pagetitle', 'local_aiproofreaderreport'));
$PAGE->set_heading(get_string('pageheading', 'local_aiproofreaderreport'));

if (!report_manager::has_valid_date_range($filters)) {
    redirect(
        $returnurl,
        get_string('notify_needrange', 'local_aiproofreaderreport'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

// The grant roster (if one has been uploaded) decides who is notified,
// including roster teachers with 0 graded.
$rows = report_manager::get_notification_rows($filters);
if (empty($rows)) {
    redirect(
        $returnurl,
        get_string('notify_norecipients', 'local_aiproofreaderreport'),
        null,
        \core\output\notification::NOTIFY_WARNING
    );
}

if ($confirm && confirm_sesskey()) {
    $result = report_manager::send_reimbursement_notifications($filters, $target);
    $type = $result->failed > 0 ? \core\output\notification::NOTIFY_WARNING : \core\output\notification::NOTIFY_SUCCESS;
    redirect($returnurl, get_string('notify_sent', 'local_aiproofreaderreport', $result), null, $type);
}

$dateformat = get_string('strftimedate', 'langconfig');
$a = (object) [
    'count' => count($rows),
    'from'  => userdate(strtotime($filters['datefrom'] . ' 00:00:00'), $dateformat),
    'to'    => userdate(strtotime($filters['dateto'] . ' 23:59:59'), $dateformat),
];

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('notify_heading', 'local_aiproofreaderreport'));

$rosterkey = empty(report_manager::get_grantteacher_roster()) ? 'grantroster_none' : 'grantroster_inuse';
echo $OUTPUT->notification(get_string($rosterkey, 'local_aiproofreaderreport'), 'info');

$totals = report_manager::get_teacher_activity_totals($filters, array_column($rows, 'teacherid'));

$table = new html_table();
$table->head = [
    get_string('reimbursement_teacher', 'local_aiproofreaderreport'),
    get_string('overview_submitted', 'local_aiproofreaderreport'),
    get_string('overview_finalsubmitted', 'local_aiproofreaderreport'),
    get_string('reimbursement_gradedcount', 'local_aiproofreaderreport'),
];
if ($target > 0) {
    $table->head[] = get_string('notify_outlook', 'local_aiproofreaderreport');
}
$table->attributes['class'] = 'generaltable aiproofreaderreport-table';
foreach ($rows as $row) {
    $t = $totals[$row->teacherid];
    $cells = [$row->teachername, $t->submitted, $t->final, $row->gradedcount];
    if ($target > 0) {
        $outlook = report_manager::get_goal_outlook($row->gradedcount, $t, $target);
        $cells[] = get_string('notify_outlook_' . $outlook->status, 'local_aiproofreaderreport', $outlook->short);
    }
    $table->data[] = $cells;
}
echo html_writer::table($table);

// Preview the exact message the first teacher on the list will get.
$first = reset($rows);
$preview = report_manager::build_notification_message(
    $first,
    $totals[$first->teacherid],
    $target,
    $a->from,
    $a->to,
    $first->firstname
);
echo html_writer::tag(
    'details',
    html_writer::tag('summary', get_string('notify_preview', 'local_aiproofreaderreport', $first->teachername)) .
        html_writer::tag('p', html_writer::tag('strong', s($preview->subject))) .
        html_writer::tag('pre', s($preview->body), ['class' => 'aiproofreaderreport-preview']),
    ['class' => 'aiproofreaderreport-grantroster']
);

$message = get_string('notify_confirm', 'local_aiproofreaderreport', $a);
if ($target > 0) {
    $message .= ' ' . get_string('notify_confirm_target', 'local_aiproofreaderreport', $target);
}
$confirmurl = new moodle_url('/local/aiproofreaderreport/notify.php', $filterparams + ['target' => $target, 'confirm' => 1]);
$confirmbutton = new single_button(
    $confirmurl,
    get_string('notify_send', 'local_aiproofreaderreport'),
    'post',
    single_button::BUTTON_PRIMARY
);
echo $OUTPUT->confirm($message, $confirmbutton, $returnurl);

echo $OUTPUT->footer();
