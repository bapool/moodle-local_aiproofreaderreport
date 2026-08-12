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

namespace local_aiproofreaderreport\task;

defined('MOODLE_INTERNAL') || die();

/**
 * Weekly cleanup of stale mod_aiproofreader data: submissions left behind
 * by activities that no longer exist, and submissions belonging to deleted
 * user accounts. This is a safety net - deleting an activity or a course
 * the normal way already cleans up after itself - for data that got
 * orphaned some other way (direct DB work, a user later being deleted,
 * partial failures, etc.) so the database doesn't slowly fill with rows
 * nothing can ever reference again.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class cleanup_stale_data extends \core\task\scheduled_task {
    /**
     * Returns the task's name, shown in Site administration > Server > Scheduled tasks.
     *
     * @return string
     */
    public function get_name(): string {
        return get_string('cleanupstaledata', 'local_aiproofreaderreport');
    }

    /**
     * Runs the cleanup.
     */
    public function execute() {
        global $DB;

        if (!$DB->get_manager()->table_exists('aiproofreader')) {
            // mod_aiproofreader isn't installed (shouldn't happen given the
            // plugin dependency, but this task must never fatal error).
            return;
        }

        $orphaned = $this->delete_submissions_for_missing_instances();
        $deleteduser = $this->delete_submissions_for_deleted_users();

        mtrace('local_aiproofreaderreport: removed ' . $orphaned
            . ' submission(s) for deleted activities and ' . $deleteduser
            . ' submission(s) for deleted user accounts.');
    }

    /**
     * Deletes submissions (and their survey/grade/file data) that point at
     * an aiproofreader instance which no longer exists.
     *
     * @return int Number of submissions removed
     */
    protected function delete_submissions_for_missing_instances(): int {
        global $DB;

        $sql = "SELECT s.id
                  FROM {aiproofreader_submission} s
             LEFT JOIN {aiproofreader} ap ON ap.id = s.aiproofreaderid
                 WHERE ap.id IS NULL";
        $submissionids = array_keys($DB->get_records_sql($sql));

        return $this->delete_submissions($submissionids);
    }

    /**
     * Deletes submissions (and their survey/grade/file data) belonging to
     * a user account that has since been deleted.
     *
     * @return int Number of submissions removed
     */
    protected function delete_submissions_for_deleted_users(): int {
        global $DB;

        $sql = "SELECT s.id
                  FROM {aiproofreader_submission} s
                  JOIN {user} u ON u.id = s.userid
                 WHERE u.deleted = 1";
        $submissionids = array_keys($DB->get_records_sql($sql));

        return $this->delete_submissions($submissionids);
    }

    /**
     * Shared deletion routine: removes survey, grade, file, and submission
     * data for a given list of submission IDs.
     *
     * @param int[] $submissionids
     * @return int Number of submissions removed
     */
    protected function delete_submissions(array $submissionids): int {
        global $DB;

        if (empty($submissionids)) {
            return 0;
        }

        // Batch to keep the IN() clause reasonable on very large districts.
        $chunks = array_chunk($submissionids, 500);
        $fs = get_file_storage();

        foreach ($chunks as $chunk) {
            [$insql, $inparams] = $DB->get_in_or_equal($chunk);

            $DB->delete_records_select('aiproofreader_studentsurvey', "submissionid $insql", $inparams);
            $DB->delete_records_select('aiproofreader_teachersurvey', "submissionid $insql", $inparams);
            $DB->delete_records_select('aiproofreader_grade', "submissionid $insql", $inparams);

            // Best-effort file cleanup: the module context may already be
            // gone (normal course-module deletion purges it automatically),
            // so a missing context here is expected and not an error.
            foreach ($chunk as $submissionid) {
                try {
                    $submission = $DB->get_record('aiproofreader_submission', ['id' => $submissionid]);
                    if (!$submission) {
                        continue;
                    }
                    $cm = get_coursemodule_from_instance('aiproofreader', $submission->aiproofreaderid);
                    if ($cm) {
                        $context = \context_module::instance($cm->id, IGNORE_MISSING);
                        if ($context) {
                            $fs->delete_area_files($context->id, 'mod_aiproofreader', 'draftsubmission', $submissionid);
                            $fs->delete_area_files($context->id, 'mod_aiproofreader', 'finalsubmission', $submissionid);
                        }
                    }
                } catch (\Throwable $e) {
                    // Never let one bad row abort the whole cleanup run.
                    debugging('local_aiproofreaderreport: file cleanup skipped for submission '
                        . $submissionid . ': ' . $e->getMessage(), DEBUG_DEVELOPER);
                }
            }

            $DB->delete_records_select('aiproofreader_submission', "id $insql", $inparams);
        }

        return count($submissionids);
    }
}
