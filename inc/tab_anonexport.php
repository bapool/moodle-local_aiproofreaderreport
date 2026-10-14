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

    // A saved selection (Save export items) wins over the privacy defaults.
    $savedkeys = export_field_catalog::get_saved_keys();

    $bycategory = [
        export_field_catalog::CATEGORY_PII => [],
        export_field_catalog::CATEGORY_TAG => [],
        export_field_catalog::CATEGORY_STRUCTURAL => [],
    ];
    foreach ($catalog as $key => $field) {
        $bycategory[$field['category']][$key] = $field;
    }

    $exporturl = new moodle_url('/local/aiproofreaderreport/export.php');
    $saveurl = new moodle_url('/local/aiproofreaderreport/save_exportfields.php');
    $optoutimporturl = new moodle_url('/local/aiproofreaderreport/optout_import.php');
    $optoutcount = \local_aiproofreaderreport\report_manager::get_optout_roster_count();

    if ($savedkeys !== null) {
        echo html_writer::tag(
            'p',
            get_string('anonexport_usingsaved', 'local_aiproofreaderreport', count($savedkeys)),
            ['class' => 'text-muted']
        );
    }

    // POST so the field list stays out of the URL; the same form either
    // downloads the CSV (export.php) or saves the selection
    // (save_exportfields.php, via the Save button's formaction).
    echo html_writer::start_tag('form', ['method' => 'post', 'action' => $exporturl->out(false)]);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'type', 'value' => 'anonexport']);
    echo html_writer::empty_tag('input', ['type' => 'hidden', 'name' => 'sesskey', 'value' => sesskey()]);

    // Top button row, inside the form: the two selection helpers
    // (client-side, type="button" so they never submit), then Download CSV
    // and Save export items (submit buttons).
    echo html_writer::start_div('aiproofreaderreport-topbuttonrow');
    echo html_writer::tag(
        'button',
        get_string('anonexport_privacybutton', 'local_aiproofreaderreport'),
        ['type' => 'button', 'id' => 'aiproofreaderreport-privacybutton', 'class' => 'btn btn-secondary']
    );
    echo html_writer::tag(
        'button',
        get_string('anonexport_deselectallbutton', 'local_aiproofreaderreport'),
        ['type' => 'button', 'id' => 'aiproofreaderreport-deselectallbutton', 'class' => 'btn btn-secondary']
    );
    echo html_writer::tag(
        'button',
        get_string('anonexport_dataexportbutton', 'local_aiproofreaderreport'),
        [
            'type' => 'button',
            'id' => 'aiproofreaderreport-dataexportbutton',
            'class' => 'btn btn-secondary',
            'title' => get_string('anonexport_dataexportbutton_help', 'local_aiproofreaderreport'),
        ]
    );
    echo html_writer::tag(
        'button',
        get_string('anonexport_downloadbutton', 'local_aiproofreaderreport'),
        ['type' => 'submit', 'class' => 'btn btn-primary']
    );
    echo html_writer::tag(
        'button',
        get_string('anonexport_savebutton', 'local_aiproofreaderreport'),
        ['type' => 'submit', 'formaction' => $saveurl->out(false), 'class' => 'btn btn-secondary']
    );
    echo html_writer::end_div();

    echo html_writer::start_div('aiproofreaderreport-exportfieldcolumns');

    $columns = [
        export_field_catalog::CATEGORY_PII => 'anonexport_columnheading_pii',
        export_field_catalog::CATEGORY_TAG => 'anonexport_columnheading_tag',
        export_field_catalog::CATEGORY_STRUCTURAL => 'anonexport_columnheading_structural',
    ];

    $dataexportkeys = export_field_catalog::get_dataexport_keys();

    $renderfieldrow = function ($key, $label, $category) use ($privacydefaultkeys, $savedkeys, $dataexportkeys) {
        $checkboxclass = 'aiproofreaderreport-exportfield-checkbox';
        if ($category === export_field_catalog::CATEGORY_PII) {
            $checkboxclass .= ' aiproofreaderreport-exportfield-pii';
        }
        if (in_array($key, $dataexportkeys, true)) {
            $checkboxclass .= ' aiproofreaderreport-exportfield-dataexport';
        }
        if ($savedkeys !== null) {
            $checked = in_array($key, $savedkeys, true);
        } else {
            $checked = !in_array($key, $privacydefaultkeys, true);
        }
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
    };

    foreach ($columns as $category => $headingstringkey) {
        echo html_writer::start_div('aiproofreaderreport-exportfieldcolumn');
        echo html_writer::tag('h5', get_string($headingstringkey, 'local_aiproofreaderreport'));

        $maingroup = [];
        $subgroups = [];
        foreach ($bycategory[$category] as $key => $field) {
            if (!empty($field['subgroup'])) {
                $subgroups[$field['subgroup']][$key] = $field['label'];
            } else {
                $maingroup[$key] = $field['label'];
            }
        }

        foreach ($maingroup as $key => $label) {
            $renderfieldrow($key, $label, $category);
        }

        foreach ($subgroups as $subgroupkey => $fields) {
            echo html_writer::empty_tag('hr', ['class' => 'aiproofreaderreport-exportfield-subgroupdivider']);
            echo html_writer::tag('h6', get_string('anonexport_subgroupheading_' . $subgroupkey, 'local_aiproofreaderreport'));
            foreach ($fields as $key => $label) {
                $renderfieldrow($key, $label, $category);
            }
        }

        echo html_writer::end_div();
    }

    echo html_writer::end_div();

    echo html_writer::end_tag('form');

    // Opt-out roster import, at the bottom and out of the way since it is
    // only needed when the opt-out list changes. Its own form - a <form>
    // can't nest inside the export form above.
    echo html_writer::empty_tag('hr');
    echo html_writer::tag('h5', get_string('optout_heading', 'local_aiproofreaderreport'));
    echo html_writer::start_div('aiproofreaderreport-topbuttonrow');
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

    // Small inline script for the three selection buttons. Privacy defaults
    // unchecks every checkbox tagged aiproofreaderreport-exportfield-pii;
    // Deselect all unchecks every field; Data Export checks exactly the
    // public-dataset fields (tagged aiproofreaderreport-exportfield-dataexport)
    // and unchecks everything else. One-off conveniences like these don't
    // warrant a full AMD module - they do one small thing on one page.
    echo html_writer::script("
        document.getElementById('aiproofreaderreport-privacybutton').addEventListener('click', function() {
            document.querySelectorAll('.aiproofreaderreport-exportfield-pii').forEach(function(box) {
                box.checked = false;
            });
        });
        document.getElementById('aiproofreaderreport-deselectallbutton').addEventListener('click', function() {
            document.querySelectorAll('.aiproofreaderreport-exportfield-checkbox').forEach(function(box) {
                box.checked = false;
            });
        });
        document.getElementById('aiproofreaderreport-dataexportbutton').addEventListener('click', function() {
            document.querySelectorAll('.aiproofreaderreport-exportfield-checkbox').forEach(function(box) {
                box.checked = box.classList.contains('aiproofreaderreport-exportfield-dataexport');
            });
        });
    ");
}
