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
 * Printable stipend summary for the treasurer.
 *
 * Opened from the Reimbursement tab's "Print stipend summary" button. Shows
 * the same teachers and counts as the Notify teachers confirmation page
 * (grant roster, or every grader when no roster is uploaded), plus whether
 * each teacher met the goal, a total line and signature lines. The site
 * navigation, footer and buttons are hidden when printed (styles.css).
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

$filterparams = report_manager::filters_to_params($filters);
$returnurl = new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => 'reimbursement'] + $filterparams);

$PAGE->set_url(new moodle_url('/local/aiproofreaderreport/printsummary.php', $filterparams + ['target' => $target]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('print');
$PAGE->set_title(get_string('printsummary_title', 'local_aiproofreaderreport'));
$PAGE->set_heading(get_string('pageheading', 'local_aiproofreaderreport'));

if (!report_manager::has_valid_date_range($filters)) {
    redirect(
        $returnurl,
        get_string('notify_needrange', 'local_aiproofreaderreport'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$rows = report_manager::get_notification_rows($filters);
$totals = report_manager::get_teacher_activity_totals($filters, array_column($rows, 'teacherid'));

$dateformat = get_string('strftimedate', 'langconfig');
$from = userdate(strtotime($filters['datefrom'] . ' 00:00:00'), $dateformat);
$to = userdate(strtotime($filters['dateto'] . ' 23:59:59'), $dateformat);

// Use the saved quarter's name when the printed dates are that quarter.
$quarter = report_manager::get_quarter();
$label = '';
if ($quarter->isset && $quarter->label !== ''
        && $quarter->datefrom === $filters['datefrom'] && $quarter->dateto === $filters['dateto']) {
    $label = $quarter->label;
}

// Print button: opens the browser print dialog.
$PAGE->requires->js_init_code(
    "document.getElementById('aiproofreaderreport-printbtn').addEventListener('click', function() { window.print(); });"
);

echo $OUTPUT->header();

echo html_writer::start_div('aiproofreaderreport-noprint mb-3');
echo html_writer::tag(
    'button',
    get_string('printsummary_print', 'local_aiproofreaderreport'),
    ['type' => 'button', 'id' => 'aiproofreaderreport-printbtn', 'class' => 'btn btn-primary']
);
echo html_writer::link($returnurl, get_string('printsummary_back', 'local_aiproofreaderreport'), ['class' => 'btn btn-secondary ml-1']);
echo html_writer::end_div();

echo html_writer::start_div('aiproofreaderreport-printsummary');
echo html_writer::tag('h2', get_string('printsummary_heading', 'local_aiproofreaderreport'));
if ($label !== '') {
    echo html_writer::tag('h3', s($label));
}

$a = (object) ['from' => $from, 'to' => $to];
echo html_writer::tag('p', get_string('printsummary_period', 'local_aiproofreaderreport', $a));
if ($target > 0) {
    echo html_writer::tag('p', get_string('printsummary_goal', 'local_aiproofreaderreport', $target));
}
echo html_writer::tag('p', get_string('printsummary_basis', 'local_aiproofreaderreport'), ['class' => 'aiproofreaderreport-printnote']);

if (empty($rows)) {
    echo $OUTPUT->notification(get_string('notify_norecipients', 'local_aiproofreaderreport'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('reimbursement_teacher', 'local_aiproofreaderreport'),
        get_string('overview_submitted', 'local_aiproofreaderreport'),
        get_string('overview_finalsubmitted', 'local_aiproofreaderreport'),
        get_string('reimbursement_gradedcount', 'local_aiproofreaderreport'),
    ];
    if ($target > 0) {
        $table->head[] = get_string('printsummary_goalmet', 'local_aiproofreaderreport');
    }
    $table->attributes['class'] = 'generaltable aiproofreaderreport-table aiproofreaderreport-printtable';

    $sumgraded = 0;
    $metcount = 0;
    foreach ($rows as $row) {
        $t = $totals[$row->teacherid] ?? (object) ['submitted' => 0, 'final' => 0];
        $cells = [$row->teachername, $t->submitted, $t->final, $row->gradedcount];
        $sumgraded += (int) $row->gradedcount;
        if ($target > 0) {
            $met = (int) $row->gradedcount >= $target;
            $metcount += $met ? 1 : 0;
            $cells[] = $met ? get_string('yes') : get_string('no');
        }
        $table->data[] = $cells;
    }
    echo html_writer::table($table);

    $sum = (object) ['teachers' => count($rows), 'graded' => $sumgraded, 'met' => $metcount];
    echo html_writer::tag('p', html_writer::tag('strong', get_string('printsummary_totals', 'local_aiproofreaderreport', $sum)));
    if ($target > 0) {
        echo html_writer::tag('p', html_writer::tag('strong', get_string('printsummary_metcount', 'local_aiproofreaderreport', $sum)));
    }
}

// Signature lines for the hard copy.
$line = html_writer::span('', 'aiproofreaderreport-sigline');
echo html_writer::start_div('aiproofreaderreport-signatures');
echo html_writer::div(get_string('printsummary_preparedby', 'local_aiproofreaderreport') . ' ' . $line .
    ' ' . get_string('date') . ' ' . $line);
echo html_writer::div(get_string('printsummary_approvedby', 'local_aiproofreaderreport') . ' ' . $line .
    ' ' . get_string('date') . ' ' . $line);
echo html_writer::end_div();

echo html_writer::tag(
    'p',
    get_string('printsummary_printed', 'local_aiproofreaderreport', userdate(time(), get_string('strftimedatetime', 'langconfig'))),
    ['class' => 'aiproofreaderreport-printnote']
);
echo html_writer::end_div();

echo $OUTPUT->footer();
