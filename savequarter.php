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
 * Save the current quarter (label, dates, graded goal) that grant teachers
 * see on their "My grant numbers" page, without sending any notifications.
 * Opened from the "Save quarter dates" button on the report's filter bar,
 * which passes the dates currently in the date boxes.
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
$tab = optional_param('tab', 'reimbursement', PARAM_ALPHA);
$returnurl = new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => $tab] + report_manager::filters_to_params($filters));

$pageurl = new moodle_url(
    '/local/aiproofreaderreport/savequarter.php',
    ['tab' => $tab] + report_manager::filters_to_params($filters)
);
$PAGE->set_url($pageurl);
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pagetitle', 'local_aiproofreaderreport'));
$PAGE->set_heading(get_string('pageheading', 'local_aiproofreaderreport'));

$form = new \local_aiproofreaderreport\form\quarter_form($pageurl);

if ($form->is_cancelled()) {
    redirect($returnurl);
}

if ($data = $form->get_data()) {
    $datefrom = date('Y-m-d', $data->datefrom);
    $dateto = date('Y-m-d', $data->dateto);
    report_manager::save_quarter($datefrom, $dateto, (string) $data->label, (int) ($data->target ?? 0));

    $applied = ['datefrom' => $datefrom, 'dateto' => $dateto] + $filters;
    redirect(
        new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => $tab] + report_manager::filters_to_params($applied)),
        get_string('quarter_saved', 'local_aiproofreaderreport'),
        null,
        \core\output\notification::NOTIFY_SUCCESS
    );
}

// Start from the dates in the filter bar, falling back to the saved quarter.
$quarter = report_manager::get_quarter();
$defaults = ['label' => $quarter->label, 'target' => $quarter->target ?: ''];
foreach (['datefrom', 'dateto'] as $key) {
    $date = report_manager::has_valid_date_range($filters) ? $filters[$key] : $quarter->$key;
    $timestamp = $date !== '' ? strtotime($date . ' 12:00:00') : false;
    if ($timestamp !== false) {
        $defaults[$key] = $timestamp;
    }
}
$form->set_data($defaults);

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('quarter_heading', 'local_aiproofreaderreport'));
echo html_writer::tag('p', get_string('quarter_intro', 'local_aiproofreaderreport'));
$form->display();
echo $OUTPUT->footer();
