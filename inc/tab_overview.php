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
 * Overview tab content. Expects $filters to already be set by index.php.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_aiproofreaderreport\report_manager;

$rows = report_manager::get_overview_rows($filters);

echo html_writer::tag('p', get_string('overview_intro', 'local_aiproofreaderreport'));

if (empty($rows)) {
    echo $OUTPUT->notification(get_string('overview_nodata', 'local_aiproofreaderreport'), 'info');
} else {
    $table = new html_table();
    $table->head = [
        get_string('overview_teacher', 'local_aiproofreaderreport'),
        get_string('overview_course', 'local_aiproofreaderreport'),
        get_string('overview_activity', 'local_aiproofreaderreport'),
        get_string('overview_gradelevel', 'local_aiproofreaderreport'),
        get_string('overview_enrolled', 'local_aiproofreaderreport'),
        get_string('overview_submitted', 'local_aiproofreaderreport'),
        get_string('overview_finalsubmitted', 'local_aiproofreaderreport'),
        get_string('overview_graded', 'local_aiproofreaderreport'),
        get_string('overview_aimodels', 'local_aiproofreaderreport'),
    ];
    $table->attributes['class'] = 'generaltable aiproofreaderreport-table';

    foreach ($rows as $row) {
        $table->data[] = [
            $row->teachername,
            $row->coursename,
            $row->activityname,
            $row->gradelevel,
            $row->enrolledcount,
            $row->submittedcount,
            $row->finalcount,
            $row->gradedcount,
            $row->aimodels !== '' ? $row->aimodels : '-',
        ];
    }

    echo html_writer::table($table);
}
