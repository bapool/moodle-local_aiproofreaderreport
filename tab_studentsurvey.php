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
 * Student survey tab content. Expects $filters to already be set by index.php.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_aiproofreaderreport\report_manager;

$summary = report_manager::get_student_survey_summary($filters);

if ($summary['total'] === 0) {
    echo $OUTPUT->notification(get_string('survey_nodata', 'local_aiproofreaderreport'), 'info');
} else {
    $questionlabels = [
        'q1overallfeedback'  => get_string('studentsurvey_q1', 'local_aiproofreaderreport'),
        'q2specificfeedback' => get_string('studentsurvey_q2', 'local_aiproofreaderreport'),
        'q3usedfeedback'     => get_string('studentsurvey_q3', 'local_aiproofreaderreport'),
        'q5confidence'       => get_string('studentsurvey_q5', 'local_aiproofreaderreport'),
    ];

    $table = new html_table();
    $table->head = [
        get_string('survey_question', 'local_aiproofreaderreport'),
        get_string('survey_average', 'local_aiproofreaderreport'),
        get_string('survey_responses', 'local_aiproofreaderreport'),
    ];
    $table->attributes['class'] = 'generaltable aiproofreaderreport-table';

    foreach ($questionlabels as $field => $label) {
        $data = $summary['scores'][$field];
        $table->data[] = [$label, $data['average'] ?? '-', $data['count']];
    }
    echo html_writer::table($table);

    echo html_writer::tag('h4', get_string('studentsurvey_q4', 'local_aiproofreaderreport'));
    $categorytable = new html_table();
    $categorytable->head = [
        get_string('studentsurvey_q4_grammar', 'local_aiproofreaderreport'),
        get_string('studentsurvey_q4_assignment', 'local_aiproofreaderreport'),
        get_string('studentsurvey_q4_both', 'local_aiproofreaderreport'),
    ];
    $categorytable->attributes['class'] = 'generaltable aiproofreaderreport-table';
    $categorytable->data[] = [
        $summary['categorybreakdown']['grammar'],
        $summary['categorybreakdown']['assignment'],
        $summary['categorybreakdown']['both'],
    ];
    echo html_writer::table($categorytable);
}
