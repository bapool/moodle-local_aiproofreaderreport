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
 * Reimbursement tab content. Expects $filters to already be set by index.php.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_aiproofreaderreport\report_manager;

echo html_writer::tag('p', get_string('reimbursement_intro', 'local_aiproofreaderreport'));

if (empty($filters['datefrom']) && empty($filters['dateto'])) {
    echo $OUTPUT->notification(get_string('reimbursement_norange', 'local_aiproofreaderreport'), 'info');
}

$rows = report_manager::get_reimbursement_rows($filters);

if (empty($rows)) {
    echo $OUTPUT->notification(get_string('reimbursement_nodata', 'local_aiproofreaderreport'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('reimbursement_teacher', 'local_aiproofreaderreport'),
        get_string('reimbursement_gradedcount', 'local_aiproofreaderreport'),
    ];
    $table->attributes['class'] = 'generaltable aiproofreaderreport-table';

    foreach ($rows as $row) {
        $table->data[] = [$row->teachername, $row->gradedcount];
    }
    echo html_writer::table($table);

    // Notify teachers of their own counts for the selected date range.
    if (has_capability('local/aiproofreaderreport:notify', context_system::instance())) {
        echo html_writer::tag('h3', get_string('notify_heading', 'local_aiproofreaderreport'));

        if (!report_manager::has_valid_date_range($filters)) {
            echo $OUTPUT->notification(get_string('notify_needrange', 'local_aiproofreaderreport'), 'warning');
        } else {
            echo html_writer::tag('p', get_string('notify_intro', 'local_aiproofreaderreport'));

            echo html_writer::start_tag('form', [
                'method' => 'get',
                'action' => new moodle_url('/local/aiproofreaderreport/notify.php'),
                'class' => 'aiproofreaderreport-notifyform',
            ]);
            foreach (report_manager::filters_to_params($filters) as $name => $value) {
                echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => $name, 'value' => $value]);
            }

            echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filterrow']);
            echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem']);
            echo html_writer::tag('label', get_string('notify_target', 'local_aiproofreaderreport'), ['for' => 'n_target']);
            echo html_writer::empty_tag('input', [
                'type' => 'number', 'name' => 'target', 'id' => 'n_target', 'min' => 0, 'step' => 1,
                'class' => 'form-control',
            ]);
            echo html_writer::end_tag('div');

            echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem aiproofreaderreport-filterbuttons']);
            echo html_writer::empty_tag('input', [
                'type' => 'submit', 'value' => get_string('notify_button', 'local_aiproofreaderreport'),
                'class' => 'btn btn-primary',
            ]);
            echo html_writer::end_tag('div');
            echo html_writer::end_tag('div');

            echo html_writer::end_tag('form');
        }
    }
}
