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
 * Shared filter bar, included from index.php. Expects $tab and $filters
 * to already be set by the including page.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_aiproofreaderreport\report_manager;

$courseoptions = ['0' => get_string('filter_allcourses', 'local_aiproofreaderreport')] + report_manager::get_course_options();
$teacheroptions = ['0' => get_string('filter_allteachers', 'local_aiproofreaderreport')] + report_manager::get_teacher_options();
$gradeoptions = ['' => get_string('filter_allgrades', 'local_aiproofreaderreport')];
foreach (report_manager::get_gradelevel_options() as $grade) {
    $gradeoptions[$grade] = $grade;
}

$groupoptions = [];
if (!empty($filters['courseid'])) {
    $groupoptions = ['0' => get_string('filter_allgroups', 'local_aiproofreaderreport')]
        + report_manager::get_group_options($filters['courseid']);
}

echo html_writer::start_tag('form', [
    'method' => 'get',
    'action' => new moodle_url('/local/aiproofreaderreport/index.php'),
    'class' => 'aiproofreaderreport-filterbar',
]);
echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'tab', 'value' => $tab]);

echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filterrow']);

echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem']);
echo html_writer::tag('label', get_string('filter_course', 'local_aiproofreaderreport'), ['for' => 'f_course']);
echo html_writer::select($courseoptions, 'f_course', $filters['courseid'], null, ['id' => 'f_course']);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem']);
echo html_writer::tag('label', get_string('filter_teacher', 'local_aiproofreaderreport'), ['for' => 'f_teacher']);
echo html_writer::select($teacheroptions, 'f_teacher', $filters['teacherid'], null, ['id' => 'f_teacher']);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem']);
echo html_writer::tag('label', get_string('filter_gradelevel', 'local_aiproofreaderreport'), ['for' => 'f_gradelevel']);
echo html_writer::select($gradeoptions, 'f_gradelevel', $filters['gradelevel'], null, ['id' => 'f_gradelevel']);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem']);
echo html_writer::tag('label', get_string('filter_group', 'local_aiproofreaderreport'), ['for' => 'f_group']);
if (empty($filters['courseid'])) {
    $hint = get_string('filter_selectcoursefirst', 'local_aiproofreaderreport');
    echo html_writer::tag('div', $hint, ['class' => 'aiproofreaderreport-filterhint']);
} else {
    echo html_writer::select($groupoptions, 'f_group', $filters['groupid'], null, ['id' => 'f_group']);
}
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem']);
echo html_writer::tag('label', get_string('filter_datefrom', 'local_aiproofreaderreport'), ['for' => 'f_datefrom']);
echo html_writer::empty_tag('input', [
    'type' => 'date', 'name' => 'f_datefrom', 'id' => 'f_datefrom', 'value' => $filters['datefrom'],
]);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem']);
echo html_writer::tag('label', get_string('filter_dateto', 'local_aiproofreaderreport'), ['for' => 'f_dateto']);
echo html_writer::empty_tag('input', [
    'type' => 'date', 'name' => 'f_dateto', 'id' => 'f_dateto', 'value' => $filters['dateto'],
]);
echo html_writer::end_tag('div');

echo html_writer::start_tag('div', ['class' => 'aiproofreaderreport-filteritem aiproofreaderreport-filterbuttons']);
echo html_writer::empty_tag('input', [
    'type' => 'submit', 'value' => get_string('filter_apply', 'local_aiproofreaderreport'), 'class' => 'btn btn-primary',
]);
echo html_writer::link(
    new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => $tab]),
    get_string('filter_clear', 'local_aiproofreaderreport'),
    ['class' => 'btn btn-secondary ml-1']
);
echo html_writer::end_tag('div');

echo html_writer::end_tag('div');
echo html_writer::end_tag('form');
