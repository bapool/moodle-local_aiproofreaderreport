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
 * Catalog of every field the anonymized export can include, tagged with its
 * source table and PII category. This is the single source of truth for
 * the field-picker checkboxes, the "Privacy defaults" button, and (later)
 * the actual export query - so the query never has to guess what's safe.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_aiproofreaderreport;

/**
 * Catalog of every field the anonymized export can include.
 */
class export_field_catalog {
    /** @var string Safe for aggregate/summary use; not deselected by the Privacy button. */
    const CATEGORY_STRUCTURAL = 'structural';

    /** @var string Demographic/program tag - shown separately from plain structural fields. */
    const CATEGORY_TAG = 'tag';

    /** @var string Personally identifying; deselected by default by the Privacy button. */
    const CATEGORY_PII = 'pii';

    /**
     * Whether the district contacts table is present on this install. Cached
     * per request - contacts-sourced fields are left out of the catalog
     * entirely (not just hidden) when it's false, so nothing downstream has
     * to special-case a missing table.
     *
     * @return bool
     */
    public static function contacts_table_exists() {
        global $DB;
        static $exists = null;
        if ($exists === null) {
            $exists = $DB->get_manager()->table_exists('contacts');
        }
        return $exists;
    }

    /**
     * The full field catalog, keyed by a stable field key used everywhere
     * else (checkbox names, export column selection, etc).
     *
     * @return array[] Each entry: table, column, category, subgroup, label.
     */
    public static function get_catalog() {
        $catalog = [];

        // Lead columns, in the exact order requested for the export: the
        // anonymous ID first, then the core school/district identifiers.
        $catalog['anonid'] = self::entry('computed', 'anonid', self::CATEGORY_STRUCTURAL, 'anonid');

        if (self::contacts_table_exists()) {
            $leadstructural = [
                'contact_schoolyear'     => ['SchoolYear', self::CATEGORY_STRUCTURAL],
                'contact_schoolcode'     => ['SchoolCode', self::CATEGORY_STRUCTURAL],
                'contact_districtid'     => ['DistrictID', self::CATEGORY_STRUCTURAL],
                'contact_schoolid'       => ['SchoolID', self::CATEGORY_STRUCTURAL],
                'contact_studentstatus'  => ['StudentStatus', self::CATEGORY_STRUCTURAL],
            ];
            foreach ($leadstructural as $key => $def) {
                $catalog[$key] = self::entry('contacts', $def[0], $def[1], $key);
            }

            // Lead PII columns, in the order requested.
            $leadpii = [
                'contact_studentnumber' => ['StudentNumber', self::CATEGORY_PII],
                'contact_lastname'      => ['LastName', self::CATEGORY_PII],
                'contact_firstname'     => ['FirstName', self::CATEGORY_PII],
                'contact_city'          => ['City', self::CATEGORY_PII],
                'contact_state'         => ['State', self::CATEGORY_PII],
            ];
            foreach ($leadpii as $key => $def) {
                $catalog[$key] = self::entry('contacts', $def[0], $def[1], $key);
            }
        }

        // Student's own login email, from Moodle's user table - not the
        // parent/guardian email that lives in contacts.Email. Available
        // regardless of whether the contacts table exists.
        $catalog['user_studentemail'] = self::entry('user', 'email', self::CATEGORY_PII, 'user_studentemail');

        // Aiproofreader_submission. Note: sub_initialtext/sub_finaltext
        // source from the *redacted* columns (initialtextredacted /
        // finaltextredacted), not the raw initialtext/finaltext - those are
        // the AI de-identified copies meant for release. They stay null
        // until mod_aiproofreader's nightly PII redaction task processes
        // that submission (off by default there too).
        $submissionfields = [
            'sub_status'                 => ['status', self::CATEGORY_STRUCTURAL],
            'sub_initialsubmissiontype'  => ['initialsubmissiontype', self::CATEGORY_STRUCTURAL],
            'sub_initialtext'            => ['initialtextredacted', self::CATEGORY_STRUCTURAL],
            'sub_initialgdrivelink'      => ['initialgdrivelink', self::CATEGORY_PII],
            'sub_initialtimesubmitted'   => ['initialtimesubmitted', self::CATEGORY_STRUCTURAL],
            'sub_feedbackgrammar'        => ['feedbackgrammar', self::CATEGORY_STRUCTURAL],
            'sub_feedbackassignment'     => ['feedbackassignment', self::CATEGORY_STRUCTURAL],
            'sub_feedbacktimecreated'    => ['feedbacktimecreated', self::CATEGORY_STRUCTURAL],
            'sub_feedbackaimodel'        => ['feedbackaimodel', self::CATEGORY_STRUCTURAL],
            'sub_finalsubmissiontype'    => ['finalsubmissiontype', self::CATEGORY_STRUCTURAL],
            'sub_finaltext'              => ['finaltextredacted', self::CATEGORY_STRUCTURAL],
            'sub_finalgdrivelink'        => ['finalgdrivelink', self::CATEGORY_PII],
            'sub_finaltimesubmitted'     => ['finaltimesubmitted', self::CATEGORY_STRUCTURAL],
            'sub_aicomparison'           => ['aicomparison', self::CATEGORY_STRUCTURAL],
            'sub_aicomparisontimecreated' => ['aicomparisontimecreated', self::CATEGORY_STRUCTURAL],
            'sub_comparisonaimodel'      => ['comparisonaimodel', self::CATEGORY_STRUCTURAL],
            'sub_aifollowedscore'        => ['aifollowedscore', self::CATEGORY_STRUCTURAL],
            'sub_timecreated'            => ['timecreated', self::CATEGORY_STRUCTURAL],
            'sub_timemodified'           => ['timemodified', self::CATEGORY_STRUCTURAL],
        ];
        foreach ($submissionfields as $key => $def) {
            $catalog[$key] = self::entry('submission', $def[0], $def[1], $key);
        }

        // Aiproofreader_grade.
        $gradefields = [
            'grade_graderid'            => ['graderid', self::CATEGORY_PII],
            'grade_grade'               => ['grade', self::CATEGORY_STRUCTURAL],
            'grade_instructorcomments'  => ['instructorcomments', self::CATEGORY_PII],
            'grade_timemodified'        => ['timemodified', self::CATEGORY_STRUCTURAL],
        ];
        foreach ($gradefields as $key => $def) {
            $catalog[$key] = self::entry('grade', $def[0], $def[1], $key);
        }

        // Aiproofreader_studentsurvey.
        $studentsurveyfields = [
            'ssurvey_q1overallfeedback'   => ['q1overallfeedback', self::CATEGORY_STRUCTURAL],
            'ssurvey_q2specificfeedback'  => ['q2specificfeedback', self::CATEGORY_STRUCTURAL],
            'ssurvey_q3usedfeedback'      => ['q3usedfeedback', self::CATEGORY_STRUCTURAL],
            'ssurvey_q4categoryhelped'    => ['q4categoryhelped', self::CATEGORY_STRUCTURAL],
            'ssurvey_q5confidence'        => ['q5confidence', self::CATEGORY_STRUCTURAL],
            'ssurvey_freetext'            => ['freetext', self::CATEGORY_PII],
            'ssurvey_timecreated'         => ['timecreated', self::CATEGORY_STRUCTURAL],
        ];
        foreach ($studentsurveyfields as $key => $def) {
            $catalog[$key] = self::entry('studentsurvey', $def[0], $def[1], $key);
        }

        // Aiproofreader_teachersurvey.
        $teachersurveyfields = [
            'tsurvey_q1overallfeedback'   => ['q1overallfeedback', self::CATEGORY_STRUCTURAL],
            'tsurvey_q2specificfeedback'  => ['q2specificfeedback', self::CATEGORY_STRUCTURAL],
            'tsurvey_q3usedfeedback'      => ['q3usedfeedback', self::CATEGORY_STRUCTURAL],
            'tsurvey_q4feedbackfollowed'  => ['q4feedbackfollowed', self::CATEGORY_STRUCTURAL],
            'tsurvey_q5aiscaffold'        => ['q5aiscaffold', self::CATEGORY_STRUCTURAL],
            'tsurvey_q6aiaccuracy'        => ['q6aiaccuracy', self::CATEGORY_STRUCTURAL],
            'tsurvey_freetext'            => ['freetext', self::CATEGORY_PII],
            'tsurvey_timecreated'         => ['timecreated', self::CATEGORY_STRUCTURAL],
        ];
        foreach ($teachersurveyfields as $key => $def) {
            $catalog[$key] = self::entry('teachersurvey', $def[0], $def[1], $key);
        }

        // Contacts tag fields - only added if the table actually exists on
        // this install. Homeroom, Locker, and Combination are deliberately
        // left out: not relevant to writing-feedback reporting.
        if (self::contacts_table_exists()) {
            $tagfields = [
                'contact_gradelevel'     => ['GradeLevelCode', self::CATEGORY_TAG],
                'contact_iep'            => ['IEP', self::CATEGORY_TAG],
                'contact_504'            => ['Status504', self::CATEGORY_TAG],
                'contact_lunch'          => ['StudentFreeLunchStatus', self::CATEGORY_TAG],
                'contact_gifted'         => ['Gifted', self::CATEGORY_TAG],
                'contact_gender'         => ['Gender', self::CATEGORY_TAG],
                'contact_ethnicity'      => ['StudentEthnicity', self::CATEGORY_TAG],
            ];
            foreach ($tagfields as $key => $def) {
                $catalog[$key] = self::entry('contacts', $def[0], $def[1], $key);
            }
        }

        // Aiproofreader (the activity instance itself) - shared across every
        // submission for that activity, not student-specific. Grouped under
        // the Demographic/program tags column, in their own subsection,
        // since like a tag this is descriptive context rather than a
        // student response.
        $catalog['ap_instructions'] = self::entry(
            'aiproofreader',
            'intro',
            self::CATEGORY_TAG,
            'ap_instructions',
            'assignmentspecifics'
        );
        $catalog['ap_aiinstructions'] = self::entry(
            'aiproofreader',
            'aiinstructions',
            self::CATEGORY_TAG,
            'ap_aiinstructions',
            'assignmentspecifics'
        );

        return $catalog;
    }

    /**
     * The subset of catalog keys the "Privacy defaults" button should
     * uncheck. Everything else (structural + tag fields) stays checked.
     *
     * @return string[]
     */
    public static function get_privacy_default_deselected_keys() {
        $keys = [];
        foreach (self::get_catalog() as $key => $field) {
            if ($field['category'] === self::CATEGORY_PII) {
                $keys[] = $key;
            }
        }
        return $keys;
    }

    /**
     * Builds one catalog entry.
     *
     * @param string $table Logical source table name (submission, grade,
     *                       studentsurvey, teachersurvey, contacts).
     * @param string $column Column name in that source table.
     * @param string $category One of the CATEGORY_* constants.
     * @param string $key Catalog key, used to build the language string id.
     * @param string|null $subgroup Optional subgroup key, used to divide fields within a column.
     * @return array
     */
    protected static function entry($table, $column, $category, $key, $subgroup = null) {
        return [
            'table'    => $table,
            'column'   => $column,
            'category' => $category,
            'subgroup' => $subgroup,
            'label'    => get_string('exportfield_' . $key, 'local_aiproofreaderreport'),
        ];
    }
}
