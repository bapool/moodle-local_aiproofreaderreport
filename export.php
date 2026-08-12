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
 * File-download handler for local_aiproofreaderreport.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_aiproofreaderreport\report_manager;

$type = required_param('type', PARAM_ALPHA);

require_login();
$context = context_system::instance();

if ($type === 'datadictionary') {
    require_capability('local/aiproofreaderreport:view', $context);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="aiproofreader_data_dictionary.csv"');

    $handle = fopen('php://output', 'w');
    fputcsv($handle, ['Table', 'Field', 'Description']);
    foreach (report_manager::get_data_dictionary() as $entry) {
        fputcsv($handle, $entry);
    }
    fclose($handle);
    exit;
}

if ($type === 'anonexport') {
    require_capability('local/aiproofreaderreport:export', $context);
    // Not yet implemented — see README/CHANGELOG. Field selection and PII
    // scrubbing logic will be added here in a future version.
    throw new moodle_exception('anonexport_notice', 'local_aiproofreaderreport');
}

throw new moodle_exception('invalidparameter', 'debug');
