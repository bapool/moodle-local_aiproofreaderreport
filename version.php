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
 * Version information for local_aiproofreaderreport.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$plugin->component = 'local_aiproofreaderreport';
$plugin->version   = 2026081100;      // YYYYMMDDXX.
$plugin->requires  = 2024042200;      // Moodle 4.5.
$plugin->maturity  = MATURITY_ALPHA;
$plugin->release   = 'v0.3.1';

// This plugin is the admin control surface for mod_aiproofreader (survey
// on/off, question wording, data collection settings) and reports on its
// data - it has nothing to do without it, so refuse to install/upgrade
// unless mod_aiproofreader is already present at a compatible version.
$plugin->dependencies = [
    'mod_aiproofreader' => 2026081100,
];
