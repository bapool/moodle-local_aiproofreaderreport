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
 * Data dictionary tab content.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_aiproofreaderreport\report_manager;

echo html_writer::tag('p', get_string('datadictionary_intro', 'local_aiproofreaderreport'));

echo html_writer::link(
    new moodle_url('/local/aiproofreaderreport/export.php', ['type' => 'datadictionary']),
    get_string('datadictionary_download', 'local_aiproofreaderreport'),
    ['class' => 'btn btn-primary']
);

$table = new html_table();
$table->head = [
    get_string('datadictionary_col_table', 'local_aiproofreaderreport'),
    get_string('datadictionary_col_field', 'local_aiproofreaderreport'),
    get_string('datadictionary_col_description', 'local_aiproofreaderreport'),
];
$table->attributes['class'] = 'generaltable aiproofreaderreport-table';
foreach (report_manager::get_data_dictionary() as $entry) {
    $table->data[] = $entry;
}
echo html_writer::tag('div', html_writer::table($table), ['class' => 'mt-3']);
