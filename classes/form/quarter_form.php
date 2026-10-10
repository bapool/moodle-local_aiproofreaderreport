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
 * Form for saving the current quarter (label, dates and graded goal) that
 * grant teachers see on their "My grant numbers" page.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiproofreaderreport\form;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/formslib.php');

/**
 * Save quarter form.
 */
class quarter_form extends \moodleform {
    /**
     * Defines the label, date range and goal fields.
     */
    protected function definition() {
        $mform = $this->_form;
        $component = 'local_aiproofreaderreport';

        $mform->addElement('text', 'label', get_string('quarter_label', $component), ['size' => 30]);
        $mform->setType('label', PARAM_TEXT);
        $mform->addHelpButton('label', 'quarter_label', $component);

        $mform->addElement('date_selector', 'datefrom', get_string('filter_datefrom', $component));
        $mform->addElement('date_selector', 'dateto', get_string('filter_dateto', $component));

        $mform->addElement('text', 'target', get_string('quarter_target', $component), ['size' => 5]);
        $mform->setType('target', PARAM_INT);
        $mform->addHelpButton('target', 'quarter_target', $component);

        $this->add_action_buttons(true, get_string('quarter_save', $component));
    }

    /**
     * Checks the date range runs forwards and the goal isn't negative.
     *
     * @param array $data
     * @param array $files
     * @return array
     */
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);
        if ((int) $data['dateto'] < (int) $data['datefrom']) {
            $errors['dateto'] = get_string('quarter_err_range', 'local_aiproofreaderreport');
        }
        if ((int) ($data['target'] ?? 0) < 0) {
            $errors['target'] = get_string('quarter_err_target', 'local_aiproofreaderreport');
        }
        return $errors;
    }
}
