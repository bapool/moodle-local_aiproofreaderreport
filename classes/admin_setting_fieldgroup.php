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

namespace local_aiproofreaderreport;

/**
 * Renders a whole group of settings (checkboxes, text fields, and plain
 * sub-heading dividers) as ONE admin_setting, wrapped in a native
 * <details>/<summary> element so it collapses/expands with no JavaScript
 * and no dependency on the admin theme's internal markup.
 *
 * All fields in a group share one config component (e.g. all site-wide
 * mod_aiproofreader settings live under component 'aiproofreader'), but
 * each field is its own independent config value under that component -
 * this class is purely a rendering/grouping convenience, not a new kind
 * of stored value.
 *
 * Field definition array, one entry per row, each an associative array:
 *   ['type' => 'checkbox', 'key' => 'surveyenabled', 'label' => '...', 'default' => 0]
 *   ['type' => 'text',     'key' => 'hscourseid',     'label' => '...', 'default' => 53, 'desc' => '...']
 *   ['type' => 'subheading', 'label' => 'Student survey questions']
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class admin_setting_fieldgroup extends \admin_setting {
    /** @var string Config component every field in this group is stored under. */
    protected $component;

    /** @var array Field definitions - see class docblock. */
    protected $fields;

    /** @var string Text shown in the collapsed <summary>. */
    protected $summary;

    /**
     * Creates a collapsible group of related settings rendered as one admin_setting.
     *
     * @param string $name Unique setting name (this class itself stores nothing under it).
     * @param string $summary Heading text shown in the clickable summary bar.
     * @param string $component Config component all fields in $fields are read/written under.
     * @param array $fields Field definitions - see class docblock.
     */
    public function __construct($name, $summary, $component, array $fields) {
        $this->component = $component;
        $this->fields = $fields;
        $this->summary = $summary;
        parent::__construct($name, $summary, '', '');
    }

    /**
     * This setting has no single value of its own - each field manages its
     * own config entry - so there is nothing meaningful to return here.
     *
     * @return string
     */
    public function get_setting() {
        return '1';
    }

    /**
     * Writes every field's value from the submitted array to its own config
     * entry under the shared component.
     *
     * @param mixed $data Associative array keyed by field key, from the group's array-named inputs.
     * @return string Empty string on success, or an error message.
     */
    public function write_setting($data) {
        if (!is_array($data)) {
            $data = [];
        }

        foreach ($this->fields as $field) {
            if ($field['type'] === 'subheading') {
                continue;
            }

            $key = $field['key'];

            if ($field['type'] === 'checkbox') {
                $value = !empty($data[$key]) ? '1' : '0';
            } else {
                $value = clean_param($data[$key] ?? '', PARAM_TEXT);
            }

            set_config($key, $value, $this->component);
        }

        return '';
    }

    /**
     * Renders the whole group as one <details>/<summary> block containing
     * a row per field. Deliberately does not call format_admin_setting() -
     * this is a self-contained, full-width block, not a label/input row.
     *
     * @param mixed $data Ignored - each field reads its own current value from config.
     * @param string $query Search-highlight term from the admin settings search box.
     * @return string HTML
     */
    public function output_html($data, $query = '') {
        $fullname = $this->get_full_name();

        $out = \html_writer::start_tag('details', ['class' => 'aiproofreaderreport-settingsgroup']);
        $out .= \html_writer::tag('summary', $this->summary);
        $out .= \html_writer::start_div('aiproofreaderreport-settingsgroup-body');

        foreach ($this->fields as $field) {
            if ($field['type'] === 'subheading') {
                $out .= \html_writer::tag('h4', $field['label'], ['class' => 'aiproofreaderreport-subheading']);
                continue;
            }

            $key = $field['key'];
            $inputname = $fullname . '[' . $key . ']';
            $inputid = 'id_' . $fullname . '_' . $key;
            $current = get_config($this->component, $key);

            $out .= \html_writer::start_div('aiproofreaderreport-settingsgroup-row');

            if ($field['type'] === 'checkbox') {
                $checked = $current === false ? !empty($field['default']) : (bool) $current;
                $out .= \html_writer::checkbox(
                    $inputname,
                    '1',
                    $checked,
                    $field['label'],
                    ['id' => $inputid]
                );
            } else {
                $value = $current === false ? ($field['default'] ?? '') : $current;
                $out .= \html_writer::tag('label', $field['label'], ['for' => $inputid]);
                $out .= \html_writer::empty_tag('input', [
                    'type' => 'text',
                    'id' => $inputid,
                    'name' => $inputname,
                    'value' => $value,
                    'size' => 60,
                    'class' => 'form-control',
                ]);
            }

            if (!empty($field['desc'])) {
                $out .= \html_writer::div($field['desc'], 'aiproofreaderreport-settingsgroup-desc');
            }

            $out .= \html_writer::end_div();
        }

        $out .= \html_writer::end_div();
        $out .= \html_writer::end_tag('details');

        return \html_writer::div($out, 'form-item aiproofreaderreport-settingsgroup-wrap');
    }
}
