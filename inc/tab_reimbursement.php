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

// Graders in the range plus every grant roster teacher, with their
// assigned / drafts / finals totals alongside the graded count.
$rows = report_manager::get_reimbursement_table_rows($filters);

if (empty($rows)) {
    echo $OUTPUT->notification(get_string('reimbursement_nodata', 'local_aiproofreaderreport'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('reimbursement_teacher', 'local_aiproofreaderreport'),
        get_string('reimbursement_studentsassigned', 'local_aiproofreaderreport'),
        get_string('reimbursement_draftssubmitted', 'local_aiproofreaderreport'),
        get_string('reimbursement_finalsubmissions', 'local_aiproofreaderreport'),
        get_string('reimbursement_gradedcount', 'local_aiproofreaderreport'),
    ];
    $table->attributes['class'] = 'generaltable aiproofreaderreport-table';

    foreach ($rows as $row) {
        $table->data[] = [
            $row->teachername,
            $row->assigned,
            $row->submitted,
            $row->final,
            $row->gradedcount,
        ];
    }
    echo html_writer::table($table);
}

// Notify teachers of their own counts for the selected date range. This
// section shows even when no one has graded yet, because grant roster
// teachers with 0 graded still need to be notified.
if (has_capability('local/aiproofreaderreport:notify', context_system::instance())) {
    echo html_writer::tag('h3', get_string('notify_heading', 'local_aiproofreaderreport'));

    // Grant teacher roster: upload once, used for every notification run.
    $roster = report_manager::get_grantteacher_roster();

    echo html_writer::tag('h4', get_string('grantroster_heading', 'local_aiproofreaderreport'));
    echo html_writer::tag('p', get_string('grantroster_intro', 'local_aiproofreaderreport'));

    echo html_writer::start_div('aiproofreaderreport-topbuttonrow');
    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => (new moodle_url('/local/aiproofreaderreport/grantroster_import.php'))->out(false),
        'enctype' => 'multipart/form-data',
        'class' => 'aiproofreaderreport-optoutform',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', [
        'type' => 'file',
        'name' => 'grantrosterfile',
        'accept' => '.csv,.txt',
        'required' => 'required',
    ]);
    echo html_writer::tag(
        'button',
        get_string('grantroster_importbutton', 'local_aiproofreaderreport'),
        ['type' => 'submit', 'class' => 'btn btn-secondary']
    );
    echo html_writer::end_tag('form');
    echo html_writer::tag(
        'span',
        get_string('grantroster_count', 'local_aiproofreaderreport', count($roster)),
        ['class' => 'aiproofreaderreport-optoutcount']
    );
    echo html_writer::end_div();

    if (!empty($roster)) {
        $items = [];
        foreach ($roster as $user) {
            $label = fullname($user) . ' (' . $user->email . ')';
            if (!empty($user->suspended)) {
                $label .= ' - ' . get_string('suspended', 'moodle');
            }
            $items[] = s($label);
        }
        echo html_writer::tag(
            'details',
            html_writer::tag('summary', get_string('grantroster_show', 'local_aiproofreaderreport')) .
                html_writer::alist($items),
            ['class' => 'aiproofreaderreport-grantroster']
        );
        echo $OUTPUT->notification(get_string('grantroster_inuse', 'local_aiproofreaderreport'), 'info');
    } else {
        echo $OUTPUT->notification(get_string('grantroster_none', 'local_aiproofreaderreport'), 'warning');
    }

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
