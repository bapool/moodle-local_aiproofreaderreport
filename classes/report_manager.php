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
 * All data-access logic for the AI Proofreader report lives here.
 *
 * This plugin creates no tables of its own — every query reads directly
 * from the existing mod_aiproofreader tables, scoped to MS/HS students.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_manager {
    /**
     * Read filters from the current request. Every page/tab shares the
     * same filter bar, so this is the single source of truth for params.
     *
     * @return array
     */
    public static function get_filters_from_request(): array {
        return [
            'courseid'   => optional_param('f_course', 0, PARAM_INT),
            'teacherid'  => optional_param('f_teacher', 0, PARAM_INT),
            'gradelevel' => optional_param('f_gradelevel', '', PARAM_ALPHANUM),
            'groupid'    => optional_param('f_group', 0, PARAM_INT),
            'datefrom'   => optional_param('f_datefrom', '', PARAM_TEXT),
            'dateto'     => optional_param('f_dateto', '', PARAM_TEXT),
        ];
    }

    /**
     * Student user IDs considered in scope: enrolled in either the HS or
     * MS management course, as configured in the plugin settings.
     *
     * @return int[]
     */
    public static function get_scope_userids(): array {
        global $DB;

        $hscourseid = (int) get_config('local_aiproofreaderreport', 'hscourseid');
        $mscourseid = (int) get_config('local_aiproofreaderreport', 'mscourseid');
        $courseids  = array_filter([$hscourseid, $mscourseid]);

        if (empty($courseids)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal($courseids, SQL_PARAMS_NAMED);
        $sql = "SELECT DISTINCT ue.userid
                  FROM {user_enrolments} ue
                  JOIN {enrol} e ON e.id = ue.enrolid
                 WHERE e.courseid $insql
                   AND ue.status = 0";
        $records = $DB->get_records_sql($sql, $inparams);
        return array_map('intval', array_keys($records));
    }

    /**
     * Courses that contain at least one aiproofreader instance.
     *
     * @return array courseid => coursename
     */
    public static function get_course_options(): array {
        global $DB;

        $sql = "SELECT DISTINCT c.id, c.fullname
                  FROM {aiproofreader} ap
                  JOIN {course} c ON c.id = ap.course
              ORDER BY c.fullname ASC";
        $records = $DB->get_records_sql($sql);

        $options = [];
        foreach ($records as $record) {
            $options[$record->id] = $record->fullname;
        }
        return $options;
    }

    /**
     * Teachers (editingteacher role) in courses that contain an
     * aiproofreader instance.
     *
     * @return array userid => full name
     */
    public static function get_teacher_options(): array {
        global $DB;

        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname
                  FROM {aiproofreader} ap
                  JOIN {context} ctx ON ctx.instanceid = ap.course AND ctx.contextlevel = :contextcourse
                  JOIN {role_assignments} ra ON ra.contextid = ctx.id
                  JOIN {role} r ON r.id = ra.roleid AND r.shortname = :roleshortname
                  JOIN {user} u ON u.id = ra.userid
              ORDER BY u.lastname ASC, u.firstname ASC";
        $records = $DB->get_records_sql($sql, [
            'contextcourse' => CONTEXT_COURSE,
            'roleshortname' => 'editingteacher',
        ]);

        $options = [];
        foreach ($records as $record) {
            $options[$record->id] = fullname($record);
        }
        return $options;
    }

    /**
     * Distinct grade levels currently set on any aiproofreader instance,
     * highest grade first.
     *
     * @return string[]
     */
    public static function get_gradelevel_options(): array {
        global $DB;

        $records = $DB->get_fieldset_sql(
            "SELECT DISTINCT gradelevel FROM {aiproofreader} WHERE gradelevel IS NOT NULL"
        );
        $levels = array_values(array_filter(array_map('strval', $records), static function ($level) {
            return $level !== '';
        }));
        // Highest grade first. Sorted numerically in PHP because the column
        // is text on some installs, where SQL would order 9 above 12.
        rsort($levels, SORT_NUMERIC);
        return $levels;
    }

    /**
     * Groups within a specific course, for the group filter dropdown.
     * The group filter only applies once a course is chosen.
     *
     * @param int $courseid
     * @return array groupid => groupname
     */
    public static function get_group_options(int $courseid): array {
        global $DB;

        if (empty($courseid)) {
            return [];
        }

        $records = $DB->get_records('groups', ['courseid' => $courseid], 'name ASC', 'id, name');
        $options = [];
        foreach ($records as $record) {
            $options[$record->id] = $record->name;
        }
        return $options;
    }

    /**
     * Build the shared WHERE clause / params for filters that apply to
     * queries joined against aiproofreader_submission (aliased "s") and
     * aiproofreader (aliased "ap").
     *
     * @param array $filters
     * @param string $datefield fully-qualified column to apply date range to, or '' to skip
     * @return array [string $where, array $params]
     */
    private static function build_filter_sql(array $filters, string $datefield = ''): array {
        global $DB;

        $where = [];
        $params = [];

        // Always scope to MS/HS students.
        $scopeids = self::get_scope_userids();
        if (empty($scopeids)) {
            // No scoped students configured/found: force an empty result
            // rather than silently showing everyone.
            $where[] = '1 = 0';
            return [implode(' AND ', $where), $params];
        }
        [$insql, $inparams] = $DB->get_in_or_equal($scopeids, SQL_PARAMS_NAMED, 'scopeuser');
        $where[] = "s.userid $insql";
        $params = array_merge($params, $inparams);

        if (!empty($filters['courseid'])) {
            $where[] = 'ap.course = :fcourseid';
            $params['fcourseid'] = $filters['courseid'];
        }

        if (!empty($filters['gradelevel'])) {
            $where[] = 'ap.gradelevel = :fgradelevel';
            $params['fgradelevel'] = $filters['gradelevel'];
        }

        if (!empty($filters['groupid'])) {
            $where[] = 'EXISTS (SELECT 1 FROM {groups_members} gm
                                  WHERE gm.groupid = :fgroupid AND gm.userid = s.userid)';
            $params['fgroupid'] = $filters['groupid'];
        }

        if ($datefield !== '') {
            if (!empty($filters['datefrom'])) {
                $timestamp = strtotime($filters['datefrom'] . ' 00:00:00');
                if ($timestamp !== false) {
                    $where[] = "$datefield >= :fdatefrom";
                    $params['fdatefrom'] = $timestamp;
                }
            }
            if (!empty($filters['dateto'])) {
                $timestamp = strtotime($filters['dateto'] . ' 23:59:59');
                if ($timestamp !== false) {
                    $where[] = "$datefield <= :fdateto";
                    $params['fdateto'] = $timestamp;
                }
            }
        }

        return [implode(' AND ', $where), $params];
    }

    /**
     * Overview tab: one row per aiproofreader instance, with enrolled,
     * submitted, final-submitted, and graded counts (all scoped). Sorted by
     * teacher last name, first name, then course name, then activity name.
     *
     * @param array $filters
     * @return array
     */
    public static function get_overview_rows(array $filters): array {
        global $DB;

        [$where, $params] = self::build_filter_sql($filters, 's.timecreated');

        $sql = "SELECT ap.id AS apid, ap.name AS activityname, ap.course AS courseid,
                       ap.gradelevel AS gradelevel, c.fullname AS coursename,
                       COUNT(DISTINCT s.userid) AS submittedcount,
                       COUNT(DISTINCT CASE WHEN s.finaltimesubmitted > 0 THEN s.userid END) AS finalcount,
                       COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.userid END) AS gradedcount
                  FROM {aiproofreader} ap
                  JOIN {course} c ON c.id = ap.course
                  JOIN {aiproofreader_submission} s ON s.aiproofreaderid = ap.id
                 WHERE $where
                   " . (!empty($filters['teacherid']) ? "AND ap.course IN (
                        SELECT ctx.instanceid FROM {context} ctx
                        JOIN {role_assignments} ra ON ra.contextid = ctx.id
                        WHERE ctx.contextlevel = :contextcourse AND ra.userid = :fteacherid
                    )" : '') . "
              GROUP BY ap.id, ap.name, ap.course, ap.gradelevel, c.fullname";

        if (!empty($filters['teacherid'])) {
            $params['contextcourse'] = CONTEXT_COURSE;
            $params['fteacherid'] = $filters['teacherid'];
        }

        $rows = $DB->get_records_sql($sql, $params);

        // Attach enrolled-student counts and teacher names (not part of the
        // aggregate query above since enrolment isn't submission-driven).
        $scopeids = self::get_scope_userids();
        $teacherroleid = $DB->get_field('role', 'id', ['shortname' => 'editingteacher']);
        foreach ($rows as $row) {
            $enrolled = get_enrolled_users(
                \context_course::instance($row->courseid),
                'mod/aiproofreader:submit'
            );
            $row->enrolledcount = count(array_intersect(array_keys($enrolled), $scopeids));

            $teachers = array_values(get_role_users(
                $teacherroleid,
                \context_course::instance($row->courseid),
                false,
                '',
                'u.lastname ASC, u.firstname ASC'
            ));
            $row->teachername = implode(', ', array_map('fullname', $teachers));
            $row->teacherlastname = $teachers ? $teachers[0]->lastname : '';
            $row->teacherfirstname = $teachers ? $teachers[0]->firstname : '';

            $models = $DB->get_fieldset_select(
                'aiproofreader_submission',
                'DISTINCT feedbackaimodel',
                'aiproofreaderid = ? AND feedbackaimodel IS NOT NULL AND feedbackaimodel != ?',
                [$row->apid, '']
            );
            $row->aimodels = implode(', ', $models);
        }

        // Teacher last name, first name, then course, then activity. Rows
        // with no editing teacher sort to the bottom.
        $rows = array_values($rows);
        usort($rows, static function ($a, $b) {
            if (($a->teacherlastname === '') !== ($b->teacherlastname === '')) {
                return $a->teacherlastname === '' ? 1 : -1;
            }
            return strnatcasecmp($a->teacherlastname, $b->teacherlastname)
                ?: strnatcasecmp($a->teacherfirstname, $b->teacherfirstname)
                ?: strnatcasecmp($a->coursename, $b->coursename)
                ?: strnatcasecmp($a->activityname, $b->activityname);
        });

        return $rows;
    }

    /**
     * Student survey tab: average score (and response count) per question,
     * plus the distribution of the "which category helped more" question.
     *
     * @param array $filters
     * @return array ['scores' => [...], 'categorybreakdown' => [...]]
     */
    public static function get_student_survey_summary(array $filters): array {
        global $DB;

        [$where, $params] = self::build_filter_sql($filters, 'ss.timecreated');

        // The unique ss.id must come first: get_records_sql() keys results by
        // the first column, so leading with a 1-5 score collapsed every
        // response down to at most five rows.
        $sql = "SELECT ss.id, ss.q1overallfeedback, ss.q2specificfeedback, ss.q3usedfeedback,
                       ss.q5confidence, ss.q4categoryhelped
                  FROM {aiproofreader_studentsurvey} ss
                  JOIN {aiproofreader_submission} s ON s.id = ss.submissionid
                  JOIN {aiproofreader} ap ON ap.id = s.aiproofreaderid
                 WHERE $where";
        $records = $DB->get_records_sql($sql, $params);

        $fields = ['q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback', 'q5confidence'];
        $scores = self::summarise_scores($records, $fields);

        $categorybreakdown = ['grammar' => 0, 'assignment' => 0, 'both' => 0];
        foreach ($records as $record) {
            if (isset($categorybreakdown[$record->q4categoryhelped])) {
                $categorybreakdown[$record->q4categoryhelped]++;
            }
        }

        // Response rate: final submissions in scope under the same filters.
        [$fwhere, $fparams] = self::build_filter_sql($filters, 's.finaltimesubmitted');
        $eligible = (int) $DB->count_records_sql(
            "SELECT COUNT(s.id)
               FROM {aiproofreader_submission} s
               JOIN {aiproofreader} ap ON ap.id = s.aiproofreaderid
              WHERE $fwhere AND s.finaltimesubmitted > 0",
            $fparams
        );

        return [
            'scores' => $scores,
            'categorybreakdown' => $categorybreakdown,
            'total' => count($records),
            'eligible' => $eligible,
        ];
    }

    /**
     * Average, response count, and 1-5 distribution for each survey question.
     *
     * @param array $records survey rows
     * @param string[] $fields question columns to summarise
     * @return array field => ['sum', 'count', 'average', 'distribution' => [1 => n, ..., 5 => n]]
     */
    private static function summarise_scores(array $records, array $fields): array {
        $scores = [];
        foreach ($fields as $field) {
            $scores[$field] = ['sum' => 0, 'count' => 0, 'distribution' => array_fill(1, 5, 0)];
        }

        foreach ($records as $record) {
            foreach ($fields as $field) {
                if ($record->$field === null) {
                    continue;
                }
                $value = (int) $record->$field;
                $scores[$field]['sum'] += $value;
                $scores[$field]['count']++;
                if ($value >= 1 && $value <= 5) {
                    $scores[$field]['distribution'][$value]++;
                }
            }
        }

        foreach ($scores as $field => $data) {
            $scores[$field]['average'] = $data['count'] > 0 ? round($data['sum'] / $data['count'], 2) : null;
        }
        return $scores;
    }

    /**
     * Teacher survey tab: average score (and response count) per question.
     *
     * @param array $filters
     * @return array
     */
    public static function get_teacher_survey_summary(array $filters): array {
        global $DB;

        [$where, $params] = self::build_filter_sql($filters, 'ts.timecreated');

        $teacherclause = '';
        if (!empty($filters['teacherid'])) {
            $teacherclause = 'AND ts.graderid = :fteacherid';
            $params['fteacherid'] = $filters['teacherid'];
        }

        // The unique ts.id must come first - see get_student_survey_summary().
        $sql = "SELECT ts.id, ts.q1overallfeedback, ts.q2specificfeedback, ts.q3usedfeedback,
                       ts.q4feedbackfollowed, ts.q5aiscaffold, ts.q6aiaccuracy
                  FROM {aiproofreader_teachersurvey} ts
                  JOIN {aiproofreader_submission} s ON s.id = ts.submissionid
                  JOIN {aiproofreader} ap ON ap.id = s.aiproofreaderid
                 WHERE $where $teacherclause";
        $records = $DB->get_records_sql($sql, $params);

        $fields = [
            'q1overallfeedback', 'q2specificfeedback', 'q3usedfeedback',
            'q4feedbackfollowed', 'q5aiscaffold', 'q6aiaccuracy',
        ];
        $scores = self::summarise_scores($records, $fields);

        // Response rate: graded submissions in scope under the same filters.
        [$gwhere, $gparams] = self::build_filter_sql($filters, 'g.timemodified');
        $gradedclause = '';
        if (!empty($filters['teacherid'])) {
            $gradedclause = 'AND g.graderid = :fteacherid';
            $gparams['fteacherid'] = $filters['teacherid'];
        }
        $eligible = (int) $DB->count_records_sql(
            "SELECT COUNT(g.id)
               FROM {aiproofreader_grade} g
               JOIN {aiproofreader_submission} s ON s.id = g.submissionid
               JOIN {aiproofreader} ap ON ap.id = s.aiproofreaderid
              WHERE $gwhere $gradedclause",
            $gparams
        );

        return ['scores' => $scores, 'total' => count($records), 'eligible' => $eligible];
    }

    /**
     * Reimbursement tab: count of graded submissions per teacher within
     * the selected date range (defaults to no bound if not provided),
     * sorted by teacher last name, first name.
     *
     * @param array $filters
     * @return array indexed rows: teacherid, teachername, lastname, firstname, gradedcount
     */
    public static function get_reimbursement_rows(array $filters): array {
        global $DB;

        [$where, $params] = self::build_filter_sql($filters, 'g.timemodified');

        $teacherclause = '';
        if (!empty($filters['teacherid'])) {
            $teacherclause = 'AND g.graderid = :fteacherid';
            $params['fteacherid'] = $filters['teacherid'];
        }

        $sql = "SELECT g.graderid, COUNT(*) AS gradedcount
                  FROM {aiproofreader_grade} g
                  JOIN {aiproofreader_submission} s ON s.id = g.submissionid
                  JOIN {aiproofreader} ap ON ap.id = s.aiproofreaderid
                 WHERE $where $teacherclause
              GROUP BY g.graderid";
        $records = $DB->get_records_sql($sql, $params);
        if (empty($records)) {
            return [];
        }

        // Load every grader's name fields in one query.
        [$uinsql, $uinparams] = $DB->get_in_or_equal(array_keys($records), SQL_PARAMS_NAMED, 'grader');
        $namefields = \core_user\fields::for_name()->get_sql('u')->selects;
        $users = $DB->get_records_sql("SELECT u.id $namefields FROM {user} u WHERE u.id $uinsql", $uinparams);

        $rows = [];
        foreach ($records as $record) {
            $user = $users[$record->graderid] ?? null;
            $rows[] = (object) [
                'teacherid'   => (int) $record->graderid,
                'teachername' => $user ? fullname($user) : get_string('unknownuser', 'moodle'),
                'lastname'    => $user ? $user->lastname : '',
                'firstname'   => $user ? $user->firstname : '',
                'gradedcount' => (int) $record->gradedcount,
            ];
        }

        // Last name, first name. Unknown users sort to the bottom.
        usort($rows, static function ($a, $b) {
            if (($a->lastname === '') !== ($b->lastname === '')) {
                return $a->lastname === '' ? 1 : -1;
            }
            return strnatcasecmp($a->lastname, $b->lastname)
                ?: strnatcasecmp($a->firstname, $b->firstname);
        });

        return $rows;
    }

    /**
     * Turn a filter array back into the f_* URL parameters the filter bar
     * uses, leaving out empty values.
     *
     * @param array $filters
     * @return array
     */
    public static function filters_to_params(array $filters): array {
        $map = [
            'courseid'   => 'f_course',
            'teacherid'  => 'f_teacher',
            'gradelevel' => 'f_gradelevel',
            'groupid'    => 'f_group',
            'datefrom'   => 'f_datefrom',
            'dateto'     => 'f_dateto',
        ];
        $params = [];
        foreach ($map as $key => $param) {
            if (!empty($filters[$key])) {
                $params[$param] = $filters[$key];
            }
        }
        return $params;
    }

    /**
     * Whether the filters hold a complete, valid date range (both ends set,
     * in YYYY-MM-DD form, start on or before end). Teacher notifications
     * need one so the message can state the period being counted.
     *
     * @param array $filters
     * @return bool
     */
    public static function has_valid_date_range(array $filters): bool {
        foreach (['datefrom', 'dateto'] as $key) {
            if (empty($filters[$key]) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $filters[$key])) {
                return false;
            }
        }
        $from = strtotime($filters['datefrom'] . ' 00:00:00');
        $to = strtotime($filters['dateto'] . ' 23:59:59');
        return $from !== false && $to !== false && $from <= $to;
    }

    /**
     * Send each teacher on the reimbursement list a notification with their
     * own graded count for the selected date range.
     *
     * @param array $filters Must hold a valid date range (see has_valid_date_range()).
     * @param int $target Optional grant target for the period; 0 leaves it out of the message.
     * @return \stdClass counts: sent, failed
     */
    public static function send_reimbursement_notifications(array $filters, int $target = 0): \stdClass {
        global $USER;

        $result = (object) ['sent' => 0, 'failed' => 0];
        if (!self::has_valid_date_range($filters)) {
            return $result;
        }

        $dateformat = get_string('strftimedate', 'langconfig');
        $from = userdate(strtotime($filters['datefrom'] . ' 00:00:00'), $dateformat);
        $to = userdate(strtotime($filters['dateto'] . ' 23:59:59'), $dateformat);

        foreach (self::get_reimbursement_rows($filters) as $row) {
            $recipient = \core_user::get_user($row->teacherid);
            if (!$recipient || $recipient->deleted || $recipient->suspended) {
                $result->failed++;
                continue;
            }

            $a = (object) [
                'firstname' => $recipient->firstname,
                'count'     => $row->gradedcount,
                'from'      => $from,
                'to'        => $to,
                'target'    => $target,
                'remaining' => max(0, $target - $row->gradedcount),
            ];

            $body = get_string('notify_body', 'local_aiproofreaderreport', $a);
            if ($target > 0) {
                $targetkey = $row->gradedcount >= $target ? 'notify_body_targetmet' : 'notify_body_target';
                $body .= "\n\n" . get_string($targetkey, 'local_aiproofreaderreport', $a);
            }
            $body .= "\n\n" . get_string('notify_body_footer', 'local_aiproofreaderreport');

            $message = new \core\message\message();
            $message->component = 'local_aiproofreaderreport';
            $message->name = 'reimbursementcount';
            $message->userfrom = $USER;
            $message->userto = $recipient;
            $message->subject = get_string('notify_subject', 'local_aiproofreaderreport', $a);
            $message->fullmessage = $body;
            $message->fullmessageformat = FORMAT_PLAIN;
            $message->fullmessagehtml = text_to_html(s($body), false, false, true);
            $message->smallmessage = get_string('notify_small', 'local_aiproofreaderreport', $a);
            $message->notification = 1;
            $message->courseid = SITEID;

            if (message_send($message)) {
                $result->sent++;
            } else {
                $result->failed++;
            }
        }

        return $result;
    }

    /**
     * Static data dictionary describing every field this report can
     * surface, sourced from the mod_aiproofreader tables it reads.
     *
     * @return array rows of [table, field, description]
     */
    public static function get_data_dictionary(): array {
        return [
            ['aiproofreader', 'id', 'Unique ID of the activity instance.'],
            ['aiproofreader', 'course', 'Course ID the activity belongs to.'],
            ['aiproofreader', 'name', 'Assignment name.'],
            ['aiproofreader', 'gradelevel', 'Target grade level (3-12) for AI feedback tone/lexile.'],
            ['aiproofreader', 'grade', 'Maximum points for the activity.'],
            ['aiproofreader_submission', 'id', 'Unique ID of the submission row (one per student per activity).'],
            ['aiproofreader_submission', 'aiproofreaderid', 'Links to the aiproofreader activity instance.'],
            ['aiproofreader_submission', 'userid', 'Student user ID.'],
            ['aiproofreader_submission', 'status', 'draft, feedbackpending, feedbackready, finalsubmitted, graded.'],
            ['aiproofreader_submission', 'initialtimesubmitted', 'Unix timestamp of the draft submission.'],
            ['aiproofreader_submission', 'finaltimesubmitted', 'Unix timestamp of the final submission.'],
            ['aiproofreader_submission', 'aifollowedscore', '1-5 AI-generated score of how well feedback was followed.'],
            [
                'aiproofreader_submission',
                'feedbackaimodel',
                'Label identifying the AI model/provider that generated the feedback ' .
                    '(site-configured, plus provider-reported detail when available).',
            ],
            [
                'aiproofreader_submission',
                'comparisonaimodel',
                'Label identifying the AI model/provider that generated the comparison ' .
                    '(site-configured, plus provider-reported detail when available).',
            ],
            ['aiproofreader_studentsurvey', 'q1overallfeedback', '1-5: overall feedback useful.'],
            ['aiproofreader_studentsurvey', 'q2specificfeedback', '1-5: assignment-specific feedback useful.'],
            ['aiproofreader_studentsurvey', 'q3usedfeedback', '1-5: used feedback to improve submission.'],
            ['aiproofreader_studentsurvey', 'q4categoryhelped', 'grammar, assignment, or both.'],
            ['aiproofreader_studentsurvey', 'q5confidence', '1-5: confidence in final vs. draft.'],
            ['aiproofreader_teachersurvey', 'q1overallfeedback', '1-5: overall feedback useful.'],
            ['aiproofreader_teachersurvey', 'q2specificfeedback', '1-5: assignment-specific feedback useful.'],
            ['aiproofreader_teachersurvey', 'q3usedfeedback', '1-5: did student use the feedback.'],
            ['aiproofreader_teachersurvey', 'q4feedbackfollowed', '1-5: was the feedback followed.'],
            ['aiproofreader_teachersurvey', 'q5aiscaffold', '1-5: did the AI help scaffold the student.'],
            ['aiproofreader_teachersurvey', 'q6aiaccuracy', '1-5: was AI feedback accurate for this assignment.'],
            ['aiproofreader_grade', 'grade', 'Points awarded by the teacher.'],
            ['aiproofreader_grade', 'graderid', 'Teacher user ID who graded the submission.'],
            ['aiproofreader_grade', 'timemodified', 'Unix timestamp the grade was last saved (used for reimbursement counts).'],
        ];
    }

    /**
     * Builds and runs the anonymized-export join query, selecting only the
     * columns needed for the caller's chosen fields (plus two internal-only
     * columns, __submissionid and __idnumber, always included so the caller
     * can compute the anonymous ID). Fields with an unrecognized key are
     * silently skipped rather than erroring, so a stale checkbox value from
     * an old page load can never break the export.
     *
     * Scoped to the same MS/HS enrolled students as every other tab
     * (see get_scope_userids()) - no opt-out roster filtering yet, that's
     * a separate feature still to be built.
     *
     * @param string[] $selectedkeys Catalog keys the caller wants included.
     * @return \moodle_recordset
     */
    public static function get_anonexport_recordset(array $selectedkeys): \moodle_recordset {
        global $DB;

        $catalog = export_field_catalog::get_catalog();
        $tablealias = [
            'submission'    => 's',
            'grade'         => 'g',
            'studentsurvey' => 'ss',
            'teachersurvey' => 'ts',
            'contacts'      => 'c',
            'user'          => 'u',
            'aiproofreader' => 'ap',
        ];

        $selects = ['s.id AS __submissionid', 'u.idnumber AS __idnumber'];
        $needcontacts = false;

        foreach ($selectedkeys as $key) {
            if (!isset($catalog[$key])) {
                continue;
            }
            $field = $catalog[$key];
            if ($field['table'] === 'computed') {
                // Anonid - not a SQL column, computed by the caller from __idnumber.
                continue;
            }
            $alias = $tablealias[$field['table']];
            $selects[] = "$alias.{$field['column']} AS $key";
            if ($field['table'] === 'contacts') {
                $needcontacts = true;
            }
        }

        $scopeuserids = self::get_scope_userids();
        if (empty($scopeuserids)) {
            // No HS/MS course configured - nothing is in scope. Return an
            // empty recordset rather than an unfiltered (unscoped) query.
            return $DB->get_recordset_sql('SELECT s.id AS __submissionid, u.idnumber AS __idnumber
                                              FROM {aiproofreader_submission} s
                                              JOIN {user} u ON u.id = s.userid
                                             WHERE 1 = 0');
        }

        [$insql, $inparams] = $DB->get_in_or_equal($scopeuserids, SQL_PARAMS_NAMED);

        $joins = 'JOIN {user} u ON u.id = s.userid
             LEFT JOIN {aiproofreader} ap ON ap.id = s.aiproofreaderid
             LEFT JOIN {aiproofreader_grade} g ON g.submissionid = s.id
             LEFT JOIN {aiproofreader_studentsurvey} ss ON ss.submissionid = s.id
             LEFT JOIN {aiproofreader_teachersurvey} ts ON ts.submissionid = s.id';

        if ($needcontacts && export_field_catalog::contacts_table_exists()) {
            // StudentNumber is stored as bigint in contacts but idnumber is
            // varchar in Moodle's user table - cast so the join works
            // regardless of driver-specific implicit conversion behaviour.
            $joins .= "\n             LEFT JOIN {contacts} c ON " . $DB->sql_cast_char2int('u.idnumber') . ' = c.StudentNumber';
        }

        $sql = 'SELECT ' . implode(",\n                   ", $selects) . "
                  FROM {aiproofreader_submission} s
                  $joins
                 WHERE s.userid $insql
                   AND u.idnumber NOT IN (SELECT studentnumber FROM {local_aiproofreaderreport_optout})
              ORDER BY s.id ASC";

        return $DB->get_recordset_sql($sql, $inparams);
    }

    /**
     * Replaces the entire opt-out roster with a new list of student
     * numbers. A full replace (not a merge) - each import represents the
     * current, complete roster from the district's ParentSquare process, so
     * a student who re-consents simply won't appear in the next upload.
     *
     * @param string[] $studentnumbers
     * @param int $importedby userid of the admin running the import.
     * @return int Number of rows saved.
     */
    public static function save_optout_roster(array $studentnumbers, int $importedby): int {
        global $DB;

        $studentnumbers = array_values(array_unique(array_filter(array_map('trim', $studentnumbers), function ($v) {
            return $v !== '';
        })));

        $DB->delete_records('local_aiproofreaderreport_optout');

        $now = time();
        $records = [];
        foreach ($studentnumbers as $studentnumber) {
            $records[] = (object) [
                'studentnumber' => $studentnumber,
                'timecreated'   => $now,
                'importedby'    => $importedby,
            ];
        }
        if (!empty($records)) {
            $DB->insert_records('local_aiproofreaderreport_optout', $records);
        }

        return count($records);
    }

    /**
     * Current opt-out roster size, for display next to the import button.
     *
     * @return int
     */
    public static function get_optout_roster_count(): int {
        global $DB;
        return $DB->count_records('local_aiproofreaderreport_optout');
    }
}
