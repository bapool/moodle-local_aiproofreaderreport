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
 * Saves the current user's Anonymized Export field selection (the
 * "Save export items" button), then returns to the Anonymized Export tab
 * with those fields checked.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_aiproofreaderreport\export_field_catalog;

require_login();
$context = context_system::instance();
require_capability('local/aiproofreaderreport:export', $context);

$PAGE->set_url(new moodle_url('/local/aiproofreaderreport/save_exportfields.php'));
$PAGE->set_context($context);

$returnurl = new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => 'anonexport']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($returnurl);
}

require_sesskey();

$selectedkeys = export_field_catalog::clean_keys(optional_param_array('fields', [], PARAM_ALPHANUMEXT));
if (empty($selectedkeys)) {
    // Saving nothing would just put the page back to privacy defaults,
    // which is unlikely to be what was meant - say so instead.
    redirect(
        $returnurl,
        get_string('anonexport_nofields', 'local_aiproofreaderreport'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$count = export_field_catalog::save_keys($selectedkeys);

redirect(
    $returnurl,
    get_string('anonexport_saved', 'local_aiproofreaderreport', $count),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
