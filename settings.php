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
 * Report navigation entry and site-wide admin settings for local_aiproofreaderreport.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($hassiteconfig) {
    // Entry under Site administration > Reports.
    $ADMIN->add('reports', new admin_externalpage(
        'local_aiproofreaderreport',
        get_string('pluginname', 'local_aiproofreaderreport'),
        new moodle_url('/local/aiproofreaderreport/index.php'),
        'local/aiproofreaderreport:view'
    ));

    // This plugin has no entry under Site administration > Plugins > Local
    // plugins; its config lives on its own settings page below instead.
    $settings = null;

    $reportsettings = new admin_settingpage(
        'local_aiproofreaderreport_settings',
        get_string('settings', 'local_aiproofreaderreport')
    );

    if ($ADMIN->fulltree) {
        // Section 1: report scope (which students count as "in scope").
        // Stored under this plugin's own component.
        $reportsettings->add(new \local_aiproofreaderreport\admin_setting_fieldgroup(
            'local_aiproofreaderreport/scopegroup',
            get_string('scopegroup', 'local_aiproofreaderreport'),
            'local_aiproofreaderreport',
            [
                [
                    'type' => 'text',
                    'key' => 'hscourseid',
                    'label' => get_string('hscourseid', 'local_aiproofreaderreport'),
                    'desc' => get_string('hscourseid_desc', 'local_aiproofreaderreport'),
                    'default' => 53,
                ],
                [
                    'type' => 'text',
                    'key' => 'mscourseid',
                    'label' => get_string('mscourseid', 'local_aiproofreaderreport'),
                    'desc' => get_string('mscourseid_desc', 'local_aiproofreaderreport'),
                    'default' => 54,
                ],
            ]
        ));

        // Section 2: survey on/off, Google Doc text retention, and every
        // survey question's show/hide + wording. Stored under mod_aiproofreader's
        // own component ('aiproofreader') since that's what the mod reads -
        // this page is simply where a site admin edits them.
        $surveyfields = [
            [
                'type' => 'checkbox',
                'key' => 'surveyenabled',
                'label' => get_string('surveyenabled', 'local_aiproofreaderreport'),
                'desc' => get_string('surveyenabled_desc', 'local_aiproofreaderreport'),
                'default' => 0,
            ],
            [
                'type' => 'checkbox',
                'key' => 'collectgdrivetext',
                'label' => get_string('collectgdrivetext', 'local_aiproofreaderreport'),
                'desc' => get_string('collectgdrivetext_desc', 'local_aiproofreaderreport'),
                'default' => 0,
            ],
            [
                'type' => 'text',
                'key' => 'currentaimodellabel',
                'label' => get_string('currentaimodellabel', 'local_aiproofreaderreport'),
                'desc' => get_string('currentaimodellabel_desc', 'local_aiproofreaderreport'),
                'default' => '',
            ],
            ['type' => 'subheading', 'label' => get_string('studentsurveyheading', 'local_aiproofreaderreport')],
        ];

        $studentquestions = [
            'q1overallfeedback'  => 'q1overallfeedback',
            'q2specificfeedback' => 'q2specificfeedback',
            'q3usedfeedback'     => 'q3usedfeedback',
            'q4categoryhelped'   => 'q4categoryhelped',
            'q5confidence'       => 'q5confidence',
            'freetext'           => 'freetextlabel',
        ];
        foreach ($studentquestions as $qkey => $defaultstringkey) {
            $default = get_string($defaultstringkey, 'aiproofreader');
            $surveyfields[] = [
                'type' => 'checkbox',
                'key' => 'studentsurvey_' . $qkey . '_enabled',
                'label' => get_string('question_enabled', 'local_aiproofreaderreport', $default),
                'default' => 1,
            ];
            $surveyfields[] = [
                'type' => 'text',
                'key' => 'studentsurvey_' . $qkey . '_text',
                'label' => get_string('question_text', 'local_aiproofreaderreport', $default),
                'default' => $default,
            ];
        }

        $surveyfields[] = ['type' => 'subheading', 'label' => get_string('teachersurveyheading', 'local_aiproofreaderreport')];

        $teacherquestions = [
            'q1overallfeedback'  => 'graderq1overallfeedback',
            'q2specificfeedback' => 'graderq2specificfeedback',
            'q3usedfeedback'     => 'graderq3usedfeedback',
            'q4feedbackfollowed' => 'graderq4feedbackfollowed',
            'q5aiscaffold'       => 'graderq5aiscaffold',
            'q6aiaccuracy'       => 'graderq6aiaccuracy',
            'freetext'           => 'teacherfreetextlabel',
        ];
        foreach ($teacherquestions as $qkey => $defaultstringkey) {
            $default = get_string($defaultstringkey, 'aiproofreader');
            $surveyfields[] = [
                'type' => 'checkbox',
                'key' => 'teachersurvey_' . $qkey . '_enabled',
                'label' => get_string('question_enabled', 'local_aiproofreaderreport', $default),
                'default' => 1,
            ];
            $surveyfields[] = [
                'type' => 'text',
                'key' => 'teachersurvey_' . $qkey . '_text',
                'label' => get_string('question_text', 'local_aiproofreaderreport', $default),
                'default' => $default,
            ];
        }

        $reportsettings->add(new \local_aiproofreaderreport\admin_setting_fieldgroup(
            'local_aiproofreaderreport/surveygroup',
            get_string('surveysettings', 'local_aiproofreaderreport'),
            'aiproofreader',
            $surveyfields
        ));
    }

    $ADMIN->add('localplugins', $reportsettings);
}
