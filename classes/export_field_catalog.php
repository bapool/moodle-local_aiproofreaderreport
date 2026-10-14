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

        // Aiproofreader_submission. Note: sub_initialtext/sub_finaltext,
        // sub_feedbackgrammar/sub_feedbackassignment and sub_aicomparison
        // source from the *redacted* columns (initialtextredacted,
        // feedbackgrammarredacted, aicomparisonredacted, etc.), not the raw
        // columns - those are the AI de-identified copies meant for release.
        // The AI feedback and comparison often quote the student's own
        // sentences, so they need the same treatment as the writing. They stay null
        // until mod_aiproofreader's nightly PII redaction task processes
        // that submission (off by default there too).
        //
        // The ...original keys are the raw, NOT de-identified texts, for internal use only (re-running the AI
        // to test prompt changes, and checking the redaction against the
        // original). They are PII, so they are off by default and cleared
        // by "Privacy defaults". Each sits right after its redacted twin so
        // the two land in neighbouring CSV columns for easy comparison.
        $submissionfields = [
            'sub_status'                 => ['status', self::CATEGORY_STRUCTURAL],
            'sub_initialsubmissiontype'  => ['initialsubmissiontype', self::CATEGORY_STRUCTURAL],
            'sub_initialtext'            => ['initialtextredacted', self::CATEGORY_STRUCTURAL],
            'sub_initialtextoriginal'    => ['initialtext', self::CATEGORY_PII],
            'sub_initialgdrivelink'      => ['initialgdrivelink', self::CATEGORY_PII],
            'sub_initialtimesubmitted'   => ['initialtimesubmitted', self::CATEGORY_STRUCTURAL],
            'sub_feedbackgrammar'        => ['feedbackgrammarredacted', self::CATEGORY_STRUCTURAL],
            'sub_feedbackgrammaroriginal' => ['feedbackgrammar', self::CATEGORY_PII],
            'sub_feedbackassignment'     => ['feedbackassignmentredacted', self::CATEGORY_STRUCTURAL],
            'sub_feedbackassignmentoriginal' => ['feedbackassignment', self::CATEGORY_PII],
            'sub_feedbacktimecreated'    => ['feedbacktimecreated', self::CATEGORY_STRUCTURAL],
            'sub_feedbackaimodel'        => ['feedbackaimodel', self::CATEGORY_STRUCTURAL],
            'sub_finalsubmissiontype'    => ['finalsubmissiontype', self::CATEGORY_STRUCTURAL],
            'sub_finaltext'              => ['finaltextredacted', self::CATEGORY_STRUCTURAL],
            'sub_finaltextoriginal'      => ['finaltext', self::CATEGORY_PII],
            'sub_finalgdrivelink'        => ['finalgdrivelink', self::CATEGORY_PII],
            'sub_finaltimesubmitted'     => ['finaltimesubmitted', self::CATEGORY_STRUCTURAL],
            'sub_aicomparison'           => ['aicomparisonredacted', self::CATEGORY_STRUCTURAL],
            'sub_aicomparisonoriginal'   => ['aicomparison', self::CATEGORY_PII],
            'sub_aicomparisontimecreated' => ['aicomparisontimecreated', self::CATEGORY_STRUCTURAL],
            'sub_comparisonaimodel'      => ['comparisonaimodel', self::CATEGORY_STRUCTURAL],
            'sub_aifollowedscore'        => ['aifollowedscore', self::CATEGORY_STRUCTURAL],
            'sub_teacherfinalized'       => ['teacherfinalized', self::CATEGORY_STRUCTURAL],
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
        // The activity's grade level / Lexile setting - the reading-level
        // condition the AI feedback was written under.
        $catalog['ap_gradelevel'] = self::entry(
            'aiproofreader',
            'gradelevel',
            self::CATEGORY_TAG,
            'ap_gradelevel',
            'assignmentspecifics'
        );
        // Maximum points, so the teacher's points can be read as a
        // percentage across activities with different point totals.
        $catalog['ap_maxgrade'] = self::entry(
            'aiproofreader',
            'grade',
            self::CATEGORY_TAG,
            'ap_maxgrade',
            'assignmentspecifics'
        );

        return $catalog;
    }

    /**
     * The fields the "Data Export" button selects: the public research
     * dataset (Hugging Face) release. Modelled on comparable public
     * student-writing datasets (PERSUADE 2.0 for essays, scores and
     * demographics; ArgRewrite V.2 for draft -> feedback -> revision with
     * participant surveys) - no more, no less.
     *
     * Deliberately left out: names, emails, links, student number, school
     * and district IDs, the teacher's extra AI instructions, free-text
     * survey answers, instructor comments, grader, all timestamps,
     * submission types and status, gender/ethnicity/gifted.
     *
     * Keys that aren't in the catalog on this install (e.g. contacts
     * fields with no contacts table) are dropped.
     *
     * @return string[]
     */
    public static function get_dataexport_keys(): array {
        return self::clean_keys([
            // Who / when (de-identified).
            'anonid',
            'contact_schoolyear',
            'contact_gradelevel',
            // Demographics, as PERSUADE 2.0 includes them.
            'contact_lunch',
            'contact_iep',
            'contact_504',
            // The task and its conditions.
            'ap_instructions',
            'ap_gradelevel',
            'ap_maxgrade',
            // The writing cycle (de-identified text only).
            'sub_initialtext',
            'sub_feedbackgrammar',
            'sub_feedbackassignment',
            'sub_finaltext',
            'sub_aicomparison',
            'sub_teacherfinalized',
            'sub_aifollowedscore',
            'sub_feedbackaimodel',
            'sub_comparisonaimodel',
            // Score.
            'grade_grade',
            // Student survey (ratings only).
            'ssurvey_q1overallfeedback',
            'ssurvey_q2specificfeedback',
            'ssurvey_q3usedfeedback',
            'ssurvey_q4categoryhelped',
            'ssurvey_q5confidence',
            // Teacher survey (ratings only) - the human labels.
            'tsurvey_q1overallfeedback',
            'tsurvey_q2specificfeedback',
            'tsurvey_q3usedfeedback',
            'tsurvey_q4feedbackfollowed',
            'tsurvey_q5aiscaffold',
            'tsurvey_q6aiaccuracy',
        ]);
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
     * Column type of each Data Export field, for the Data Export dictionary.
     * Each value names a dicttype_* language string.
     *
     * @return string[] catalog key => type
     */
    protected static function get_dataexport_types(): array {
        return [
            'anonid' => 'id',
            'contact_schoolyear' => 'code',
            'contact_gradelevel' => 'code',
            'contact_lunch' => 'code',
            'contact_iep' => 'code',
            'contact_504' => 'code',
            'ap_instructions' => 'html',
            'ap_gradelevel' => 'integer',
            'ap_maxgrade' => 'integer',
            'sub_initialtext' => 'text',
            'sub_feedbackgrammar' => 'text',
            'sub_feedbackassignment' => 'text',
            'sub_feedbackaimodel' => 'text',
            'sub_finaltext' => 'text',
            'sub_aicomparison' => 'text',
            'sub_comparisonaimodel' => 'text',
            'sub_aifollowedscore' => 'rating',
            'sub_teacherfinalized' => 'flag',
            'grade_grade' => 'decimal',
            'ssurvey_q1overallfeedback' => 'rating',
            'ssurvey_q2specificfeedback' => 'rating',
            'ssurvey_q3usedfeedback' => 'rating',
            'ssurvey_q4categoryhelped' => 'code',
            'ssurvey_q5confidence' => 'rating',
            'tsurvey_q1overallfeedback' => 'rating',
            'tsurvey_q2specificfeedback' => 'rating',
            'tsurvey_q3usedfeedback' => 'rating',
            'tsurvey_q4feedbackfollowed' => 'rating',
            'tsurvey_q5aiscaffold' => 'rating',
            'tsurvey_q6aiaccuracy' => 'rating',
        ];
    }

    /**
     * The Data Export dictionary (codebook): one row per column the "Data
     * Export" button selects, in the same order the CSV puts them, with the
     * column heading exactly as it appears in the CSV. Built from the same
     * field list as the button, so the two can never drift apart. This is
     * the file that goes alongside the public dataset.
     *
     * Allowed values for district codes (school year, grade level, lunch,
     * IEP, 504) are not fixed - each district's student information system
     * has its own - so they are read from the contacts table at download
     * time.
     *
     * @return array[] rows of [column, type, values, description]
     */
    public static function get_dataexport_dictionary(): array {
        global $CFG;

        // Survey questions are worded by each site (local plugin settings),
        // so the dictionary quotes the wording actually in use, via the mod.
        require_once($CFG->dirroot . '/mod/aiproofreader/lib.php');

        $catalog = self::get_catalog();
        $types = self::get_dataexport_types();
        $strings = get_string_manager();
        $rows = [];
        foreach (self::get_dataexport_keys() as $key) {
            $field = $catalog[$key];
            $type = $types[$key] ?? 'text';

            if ($field['table'] === 'contacts') {
                $values = self::get_contacts_values($field['column']);
            } else if ($strings->string_exists('dictvalues_' . $key, 'local_aiproofreaderreport')) {
                $values = get_string('dictvalues_' . $key, 'local_aiproofreaderreport');
            } else if ($type === 'rating' && in_array($field['table'], ['studentsurvey', 'teachersurvey'], true)) {
                $values = get_string('dictvalues_survey', 'local_aiproofreaderreport');
            } else {
                $values = '';
            }

            if ($field['table'] === 'studentsurvey') {
                $description = get_string(
                    'dictdesc_studentsurvey',
                    'local_aiproofreaderreport',
                    aiproofreader_question_text('student', $field['column'])
                );
            } else if ($field['table'] === 'teachersurvey') {
                $description = get_string(
                    'dictdesc_teachersurvey',
                    'local_aiproofreaderreport',
                    aiproofreader_question_text('teacher', $field['column'])
                );
            } else {
                $description = get_string('dictdesc_' . $key, 'local_aiproofreaderreport');
            }

            $rows[] = [
                $field['label'],
                get_string('dicttype_' . $type, 'local_aiproofreaderreport'),
                $values,
                $description,
            ];
        }
        return $rows;
    }

    /**
     * The distinct values of one contacts column, for the dictionary's
     * allowed-values column. Capped, since a code list is only useful while
     * it is short.
     *
     * @param string $column Contacts column name, from the catalog (never user input)
     * @return string
     */
    protected static function get_contacts_values(string $column): string {
        global $DB;

        if (!self::contacts_table_exists()) {
            return '';
        }
        try {
            $values = $DB->get_fieldset_sql(
                "SELECT DISTINCT $column FROM {contacts} WHERE $column IS NOT NULL ORDER BY $column"
            );
        } catch (\dml_exception $e) {
            return get_string('dictvalues_district', 'local_aiproofreaderreport');
        }
        $values = array_values(array_filter(array_map('trim', array_map('strval', $values)), static function ($value) {
            return $value !== '';
        }));
        if (empty($values)) {
            return get_string('dictvalues_district', 'local_aiproofreaderreport');
        }
        $more = count($values) > 25;
        $values = implode(', ', array_slice($values, 0, 25));
        return $more ? $values . ', ...' : $values;
    }

    /** @var string User preference holding the saved export field selection. */
    const PREF_SAVEDFIELDS = 'local_aiproofreaderreport_exportfields';

    /**
     * Keep only keys that exist in the catalog, in catalog order.
     *
     * @param string[] $keys
     * @return string[]
     */
    public static function clean_keys(array $keys): array {
        return array_values(array_intersect(array_keys(self::get_catalog()), $keys));
    }

    /**
     * The current user's saved export field selection, or null if they
     * have never saved one (the page then falls back to privacy defaults).
     * Keys no longer in the catalog are dropped; new catalog fields added
     * after the save come up unchecked.
     *
     * @return string[]|null
     */
    public static function get_saved_keys(): ?array {
        $saved = get_user_preferences(self::PREF_SAVEDFIELDS, null);
        if ($saved === null || $saved === '') {
            return null;
        }
        return self::clean_keys(explode(',', $saved));
    }

    /**
     * Save the current user's export field selection.
     *
     * @param string[] $keys
     * @return int number of fields saved
     */
    public static function save_keys(array $keys): int {
        $keys = self::clean_keys($keys);
        set_user_preference(self::PREF_SAVEDFIELDS, implode(',', $keys));
        return count($keys);
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
