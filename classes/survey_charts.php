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

namespace local_aiproofreaderreport;

/**
 * Small charts for the Student Survey and Teacher Survey tabs, built with
 * Moodle's core chart API (Chart.js, with a built-in "show chart data" table).
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class survey_charts {
    /**
     * Diverging colours for scores 1-5: red for low, gray for neutral, blue for high.
     * Checked for colour-blind separation between neighbouring scores.
     */
    private const SCORE_COLOURS = [
        1 => '#b8321f',
        2 => '#e8835e',
        3 => '#d6d6d1',
        4 => '#4f93d6',
        5 => '#1d4f91',
    ];

    /** Single colour for one-series charts. */
    private const SINGLE_COLOUR = '#1d4f91';

    /**
     * One horizontal stacked bar per question showing the percentage of
     * responses at each score from 1 to 5.
     *
     * @param array $questionlabels field => label, in display order
     * @param array $scores output of report_manager's survey summaries ('scores' key)
     * @return \core\chart_bar|null null when no question has any responses
     */
    public static function score_distribution(array $questionlabels, array $scores): ?\core\chart_bar {
        $labels = [];
        $percentages = array_fill(1, 5, []);

        foreach ($questionlabels as $field => $label) {
            $data = $scores[$field] ?? null;
            if (empty($data['count'])) {
                // Leave out questions nobody answered (e.g. a question turned off).
                continue;
            }
            $labels[] = $label;
            foreach ($data['distribution'] as $score => $count) {
                $percentages[$score][] = round($count / $data['count'] * 100, 1);
            }
        }

        if (empty($labels)) {
            return null;
        }

        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $chart->set_stacked(true);
        $chart->set_title(get_string('chart_distribution', 'local_aiproofreaderreport'));
        $chart->set_labels($labels);

        foreach (self::SCORE_COLOURS as $score => $colour) {
            $series = new \core\chart_series(self::score_label($score), $percentages[$score]);
            $series->set_color($colour);
            $chart->add_series($series);
        }
        return $chart;
    }

    /**
     * Horizontal bar of how many students picked each "which feedback
     * category helped more" answer.
     *
     * @param array $breakdown ['grammar' => n, 'assignment' => n, 'both' => n]
     * @return \core\chart_bar|null null when there are no answers
     */
    public static function category_breakdown(array $breakdown): ?\core\chart_bar {
        if (array_sum($breakdown) === 0) {
            return null;
        }

        $chart = new \core\chart_bar();
        $chart->set_horizontal(true);
        $chart->set_title(get_string('studentsurvey_q4', 'local_aiproofreaderreport'));
        $chart->set_labels([
            get_string('studentsurvey_q4_grammar', 'local_aiproofreaderreport'),
            get_string('studentsurvey_q4_assignment', 'local_aiproofreaderreport'),
            get_string('studentsurvey_q4_both', 'local_aiproofreaderreport'),
        ]);
        $series = new \core\chart_series(
            get_string('survey_responses', 'local_aiproofreaderreport'),
            [$breakdown['grammar'], $breakdown['assignment'], $breakdown['both']]
        );
        $series->set_color(self::SINGLE_COLOUR);
        $chart->add_series($series);
        return $chart;
    }

    /**
     * Legend label for a score.
     *
     * @param int $score 1-5
     * @return string
     */
    private static function score_label(int $score): string {
        if ($score === 1) {
            return get_string('chart_score_low', 'local_aiproofreaderreport');
        }
        if ($score === 5) {
            return get_string('chart_score_high', 'local_aiproofreaderreport');
        }
        return (string) $score;
    }
}
