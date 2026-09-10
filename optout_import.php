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
 * Handles the opt-out roster CSV upload: replaces the stored roster, then
 * redirects back to the Anonymized Export tab.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_aiproofreaderreport\report_manager;

require_login();
$context = context_system::instance();
require_capability('local/aiproofreaderreport:export', $context);

$PAGE->set_url(new moodle_url('/local/aiproofreaderreport/optout_import.php'));
$PAGE->set_context($context);

$returnurl = new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => 'anonexport']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($returnurl);
}

require_sesskey();

if (empty($_FILES['optoutfile']) || $_FILES['optoutfile']['error'] !== UPLOAD_ERR_OK) {
    redirect($returnurl, get_string('optout_uploaderror', 'local_aiproofreaderreport'), null,
        \core\output\notification::NOTIFY_ERROR);
}

$tmppath = $_FILES['optoutfile']['tmp_name'];
$handle = fopen($tmppath, 'r');
if ($handle === false) {
    redirect($returnurl, get_string('optout_uploaderror', 'local_aiproofreaderreport'), null,
        \core\output\notification::NOTIFY_ERROR);
}

$studentnumbers = [];
$first = true;
while (($row = fgetcsv($handle)) !== false) {
    if (empty($row)) {
        continue;
    }
    $value = trim((string) $row[0]);

    // Skip a header row: if the very first non-empty cell isn't a bare
    // number, assume it's a column heading (e.g. "StudentNumber", "SSID").
    if ($first) {
        $first = false;
        if ($value !== '' && !ctype_digit($value)) {
            continue;
        }
    }

    if ($value !== '') {
        $studentnumbers[] = $value;
    }
}
fclose($handle);

$count = report_manager::save_optout_roster($studentnumbers, $USER->id);

redirect(
    $returnurl,
    get_string('optout_importsuccess', 'local_aiproofreaderreport', $count),
    null,
    \core\output\notification::NOTIFY_SUCCESS
);
