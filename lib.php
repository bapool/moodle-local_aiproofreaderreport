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
 * Library callbacks for local_aiproofreaderreport.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Adds a "My AI Proofreader grant numbers" link to the Reports section of
 * a grant teacher's own profile page.
 *
 * @param \core_user\output\myprofile\tree $tree
 * @param stdClass $user The user whose profile is being viewed
 * @param bool $iscurrentuser
 * @param stdClass|null $course
 */
function local_aiproofreaderreport_myprofile_navigation(\core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {
    if (!$iscurrentuser || isguestuser($user)) {
        return;
    }
    if (!\local_aiproofreaderreport\report_manager::is_grant_teacher((int) $user->id)) {
        return;
    }

    $node = new \core_user\output\myprofile\node(
        'reports',
        'local_aiproofreaderreport_mynumbers',
        get_string('mynumbers_link', 'local_aiproofreaderreport'),
        null,
        new moodle_url('/local/aiproofreaderreport/mynumbers.php')
    );
    $tree->add_node($node);
}
