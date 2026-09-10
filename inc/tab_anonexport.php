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
 * Anonymized export tab: field picker + CSV download.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

use local_aiproofreaderreport\export_field_catalog;

echo html_writer::tag('h4', get_string('tab_anonexport', 'local_aiproofreaderreport'));
echo html_writer::tag('p', get_string('anonexport_intro', 'local_aiproofreaderreport'));

if (!has_capability('local/aiproofreaderreport:export', context_system::instance())) {
    echo $OUTPUT->notification(get_string('nopermissions', 'error', ''), 'error');
} else {
    if (!export_field_catalog::contacts_table_exists()) {
        echo $OUTPUT->notification(get_string('anonexport_nocontacts', 'local_aiproofreaderreport'), 'info');
    }

    $catalog = export_field_catalog::get_catalog();
    $privacydefaultkeys = export_field_catalog::get_privacy_default_deselected_keys();

    $bycategory = [
        export_field_catalog::CATEGORY_PII => [],
        export_field_catalog::CATEGORY_TAG => [],
        export_field_catalog::CATEGORY_STRUCTURAL => [],
    ];
    foreach ($catalog as $key => $field) {
        $bycategory[$field['category']][$key] = $field['label'];
    }

    $exporturl = new moodle_url('/local/aiproofreaderreport/export.php', ['type' => 'anonexport']);
    $optoutimporturl = new moodle_url('/local/aiproofreaderreport/optout_import.php');
    $optoutcount = \local_aiproofreaderreport\report_manager::get_optout_roster_count();

    // Top button row: Privacy defaults (client-side, acts on the export
    // form below) and the opt-out roster import (its own small form - a
    // <form> can't nest inside the export form, so both live here, outside
    // it, laid out side by side).
    echo html_writer::start_div('aiproofreaderreport-topbuttonrow');

    echo html_writer::tag(
        'button',
        get_string('anonexport_privacybutton', 'local_aiproofreaderreport'),
        ['type' => 'button', 'id' => 'aiproofreaderreport-privacybutton', 'class' => 'btn btn-secondary']
    );

    echo html_writer::start_tag('form', [
        'method' => 'post',
        'action' => $optoutimporturl->out(false),
        'enctype' => 'multipart/form-data',
        'class' => 'aiproofreaderreport-optoutform',
    ]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);
    echo html_writer::empty_tag('input', [
        'type' => 'file',
        'name' => 'optoutfile',
        'accept' => '.csv',
        'required' => 'required',
    ]);
    echo html_writer::tag(
        'button',
        get_string('optout_importbutton', 'local_aiproofreaderreport'),
        ['type' => 'submit', 'class' => 'btn btn-secondary']
    );
    echo html_writer::end_tag('form');

    echo html_writer::tag(
        'span',
        get_string('optout_rostercount', 'local_aiproofreaderreport', $optoutcount),
        ['class' => 'aiproofreaderreport-optoutcount']
    );

    echo html_writer::end_div();

    echo html_writer::start_tag('form', ['method' => 'get', 'action' => $exporturl->out_omit_querystring()]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'type', 'value' => 'anonexport']);

    echo html_writer::start_div('aiproofreaderreport-exportfieldcolumns');

    $columns = [
        export_field_catalog::CATEGORY_PII => 'anonexport_columnheading_pii',
        export_field_catalog::CATEGORY_TAG => 'anonexport_columnheading_tag',
        export_field_catalog::CATEGORY_STRUCTURAL => 'anonexport_columnheading_structural',
    ];

    foreach ($columns as $category => $headingstringkey) {
        echo html_writer::start_div('aiproofreaderreport-exportfieldcolumn');
        echo html_writer::tag('h5', get_string($headingstringkey, 'local_aiproofreaderreport'));
        foreach ($bycategory[$category] as $key => $label) {
            $checkboxclass = 'aiproofreaderreport-exportfield-checkbox';
            if ($category === export_field_catalog::CATEGORY_PII) {
                $checkboxclass .= ' aiproofreaderreport-exportfield-pii';
            }
            $checked = !in_array($key, $privacydefaultkeys, true);
            $attributes = [
                'type' => 'checkbox',
                'name' => 'fields[]',
                'value' => $key,
                'id' => 'aiproofreaderreport-field-' . $key,
                'class' => $checkboxclass,
            ];
            if ($checked) {
                $attributes['checked'] = 'checked';
            }
            echo html_writer::start_div('aiproofreaderreport-exportfieldrow');
            echo html_writer::empty_tag('input', $attributes);
            echo html_writer::tag(
                'label',
                $label,
                ['for' => 'aiproofreaderreport-field-' . $key]
            );
            echo html_writer::end_div();
        }
        echo html_writer::end_div();
    }

    echo html_writer::end_div();

    echo html_writer::start_div('aiproofreaderreport-exportfieldbuttons');
    echo html_writer::tag(
        'button',
        get_string('anonexport_downloadbutton', 'local_aiproofreaderreport'),
        ['type' => 'submit', 'class' => 'btn btn-primary']
    );
    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    // Small inline script for the Privacy-defaults button: unchecks every
    // checkbox tagged aiproofreaderreport-exportfield-pii, nothing else.
    // A one-off convenience toggle like this doesn't warrant a full AMD
    // module - it does one small thing on one page.
    echo html_writer::script("
        document.getElementById('aiproofreaderreport-privacybutton').addEventListener('click', function() {
            document.querySelectorAll('.aiproofreaderreport-exportfield-pii').forEach(function(box) {
                box.checked = false;
            });
        });
    ");
}
