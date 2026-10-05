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
 * Handles the grant teacher roster upload: replaces the stored roster, then
 * redirects back to the Reimbursement tab.
 *
 * The file is a CSV or plain text list of teacher email addresses. The first
 * cell on each line that looks like an email address is used, so a header
 * row or extra columns (names, buildings) are simply ignored.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_aiproofreaderreport\report_manager;

require_login();
$context = context_system::instance();
require_capability('local/aiproofreaderreport:view', $context);
require_capability('local/aiproofreaderreport:notify', $context);

$PAGE->set_url(new moodle_url('/local/aiproofreaderreport/grantroster_import.php'));
$PAGE->set_context($context);

$returnurl = new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => 'reimbursement']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect($returnurl);
}

require_sesskey();

if (empty($_FILES['grantrosterfile']) || $_FILES['grantrosterfile']['error'] !== UPLOAD_ERR_OK) {
    redirect(
        $returnurl,
        get_string('grantroster_uploaderror', 'local_aiproofreaderreport'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$handle = fopen($_FILES['grantrosterfile']['tmp_name'], 'r');
if ($handle === false) {
    redirect(
        $returnurl,
        get_string('grantroster_uploaderror', 'local_aiproofreaderreport'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$emails = [];
while (($row = fgetcsv($handle)) !== false) {
    foreach ($row as $cell) {
        // Strip a UTF-8 byte order mark Excel may put on the first cell.
        $cell = trim(preg_replace('/^\xEF\xBB\xBF/', '', (string) $cell));
        if ($cell !== '' && validate_email($cell)) {
            $emails[] = $cell;
            break;
        }
    }
}
fclose($handle);

// Refuse to replace the roster with nothing - an empty or wrong file
// would otherwise silently fall back to notifying every grader.
if (empty($emails)) {
    redirect(
        $returnurl,
        get_string('grantroster_noemails', 'local_aiproofreaderreport'),
        null,
        \core\output\notification::NOTIFY_ERROR
    );
}

$result = report_manager::save_grantteacher_roster($emails, $USER->id);

$message = get_string('grantroster_importsuccess', 'local_aiproofreaderreport', $result->saved);
$type = \core\output\notification::NOTIFY_SUCCESS;
if (!empty($result->notfound)) {
    $message .= ' ' . get_string('grantroster_notfound', 'local_aiproofreaderreport', s(implode(', ', $result->notfound)));
    $type = \core\output\notification::NOTIFY_WARNING;
}

redirect($returnurl, $message, null, $type);
