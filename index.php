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
 * Main report page for local_aiproofreaderreport.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require_once(__DIR__ . '/../../config.php');

use local_aiproofreaderreport\report_manager;

$tab = optional_param('tab', 'overview', PARAM_ALPHA);
$validtabs = ['overview', 'studentsurvey', 'teachersurvey', 'reimbursement', 'datadictionary', 'anonexport'];
if (!in_array($tab, $validtabs, true)) {
    $tab = 'overview';
}

require_login();
$context = context_system::instance();
require_capability('local/aiproofreaderreport:view', $context);

$PAGE->set_url(new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => $tab]));
$PAGE->set_context($context);
$PAGE->set_pagelayout('report');
$PAGE->set_title(get_string('pagetitle', 'local_aiproofreaderreport'));
$PAGE->set_heading(get_string('pageheading', 'local_aiproofreaderreport'));
$PAGE->requires->css('/local/aiproofreaderreport/styles.css');

$filters = report_manager::get_filters_from_request();

echo $OUTPUT->header();
echo html_writer::tag('h2', get_string('pageheading', 'local_aiproofreaderreport'));

// ── Tabs ─────────────────────────────────────────────────────────────────
$tabdefs = [
    'overview' => 'tab_overview',
    'studentsurvey' => 'tab_studentsurvey',
    'teachersurvey' => 'tab_teachersurvey',
    'reimbursement' => 'tab_reimbursement',
    'datadictionary' => 'tab_datadictionary',
    'anonexport' => 'tab_anonexport',
];

$tabobjects = [];
foreach ($tabdefs as $tabid => $stringkey) {
    $taburl = new moodle_url('/local/aiproofreaderreport/index.php', ['tab' => $tabid]);
    $tablabel = get_string($stringkey, 'local_aiproofreaderreport');
    $tabobjects[] = new tabobject($tabid, $taburl, $tablabel);
}
print_tabs([$tabobjects], $tab);

// ── Filter bar (shared across all tabs; datadictionary/anonexport ignore it) ──
if (!in_array($tab, ['datadictionary', 'anonexport'], true)) {
    require(__DIR__ . '/inc/filter_bar.php');
}

// ── Tab content ──────────────────────────────────────────────────────────
require(__DIR__ . '/inc/tab_' . $tab . '.php');

echo $OUTPUT->footer();
