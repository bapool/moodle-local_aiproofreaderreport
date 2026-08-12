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
}
