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
 * Anonymized export tab. Stub only for v1 — see CHANGELOG for what's planned.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

echo html_writer::tag('h4', get_string('tab_anonexport', 'local_aiproofreaderreport'));
echo html_writer::tag('p', get_string('anonexport_intro', 'local_aiproofreaderreport'));

if (has_capability('local/aiproofreaderreport:export', context_system::instance())) {
    echo $OUTPUT->notification(get_string('anonexport_notice', 'local_aiproofreaderreport'), 'info');
} else {
    echo $OUTPUT->notification(get_string('nopermissions', 'error', ''), 'error');
}
