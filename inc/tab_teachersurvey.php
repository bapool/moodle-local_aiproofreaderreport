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
 * Teacher survey tab content. Expects $filters to already be set by index.php.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_aiproofreaderreport\report_manager;
use local_aiproofreaderreport\survey_charts;

$summary = report_manager::get_teacher_survey_summary($filters);

if ($summary['total'] === 0) {
    echo $OUTPUT->notification(get_string('survey_nodata', 'local_aiproofreaderreport'), 'info');
} else {
    $questionlabels = [
        'q1overallfeedback'  => get_string('teachersurvey_q1', 'local_aiproofreaderreport'),
        'q2specificfeedback' => get_string('teachersurvey_q2', 'local_aiproofreaderreport'),
        'q3usedfeedback'     => get_string('teachersurvey_q3', 'local_aiproofreaderreport'),
        'q4feedbackfollowed' => get_string('teachersurvey_q4', 'local_aiproofreaderreport'),
        'q5aiscaffold'       => get_string('teachersurvey_q5', 'local_aiproofreaderreport'),
        'q6aiaccuracy'       => get_string('teachersurvey_q6', 'local_aiproofreaderreport'),
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

    // Response rate against graded submissions under the same filters.
    $rate = (object) [
        'total' => $summary['total'],
        'eligible' => $summary['eligible'],
        'percent' => $summary['eligible'] > 0 ? round($summary['total'] / $summary['eligible'] * 100) : 0,
    ];
    echo html_writer::tag(
        'p',
        get_string('survey_responserate_teacher', 'local_aiproofreaderreport', $rate),
        ['class' => 'aiproofreaderreport-responserate']
    );

    $chart = survey_charts::score_distribution($questionlabels, $summary['scores']);
    if ($chart) {
        echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-charts']);
        echo html_writer::div($OUTPUT->render($chart), 'aiproofreaderreport-chart');
        echo html_writer::end_tag('div');
    }

    echo html_writer::table($table);
}
