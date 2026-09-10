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
use local_aiproofreaderreport\export_field_catalog;

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

    $selectedkeys = optional_param_array('fields', [], PARAM_ALPHANUMEXT);
    if (empty($selectedkeys)) {
        throw new moodle_exception('anonexport_nofields', 'local_aiproofreaderreport');
    }

    // Mod_aiproofreader owns the anonid computation (it's the plugin that
    // "keeps track of the users") - pull in its lib.php explicitly since a
    // plain global function isn't autoloaded across plugins.
    require_once($CFG->dirroot . '/mod/aiproofreader/lib.php');

    $catalog = export_field_catalog::get_catalog();
    // Output columns follow the catalog's own order, restricted to keys
    // that were both selected and still exist in the catalog (a stale
    // checkbox from an old page load is simply dropped, not an error).
    $orderedkeys = array_values(array_intersect(array_keys($catalog), $selectedkeys));
    if (empty($orderedkeys)) {
        throw new moodle_exception('anonexport_nofields', 'local_aiproofreaderreport');
    }

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="aiproofreader_anonymized_export_' . date('Ymd_His') . '.csv"');

    $handle = fopen('php://output', 'w');

    $headerrow = [];
    foreach ($orderedkeys as $key) {
        $headerrow[] = $catalog[$key]['label'];
    }
    fputcsv($handle, $headerrow);

    $rs = report_manager::get_anonexport_recordset($orderedkeys);
    foreach ($rs as $row) {
        $csvrow = [];
        foreach ($orderedkeys as $key) {
            if ($key === 'anonid') {
                $csvrow[] = aiproofreader_get_anon_id($row->__idnumber ?? '');
            } else {
                $csvrow[] = $row->$key ?? '';
            }
        }
        fputcsv($handle, $csvrow);
    }
    $rs->close();

    fclose($handle);
    exit;
}

throw new moodle_exception('invalidparameter', 'debug');
