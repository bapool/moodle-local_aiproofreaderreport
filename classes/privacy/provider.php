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

namespace local_aiproofreaderreport\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;

/**
 * Privacy provider for local_aiproofreaderreport.
 *
 * The only personal data this plugin stores itself is the grant teacher
 * roster (teacher user ids, and who uploaded the roster), held in the system
 * context. Report data is read from mod_aiproofreader, which declares its own.
 * The opt-out roster holds district student numbers, not Moodle user ids.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider {

    /** @var string Grant roster table. */
    private const TABLE = 'local_aiproofreaderreport_grantteacher';

    /**
     * Describe the personal data this plugin stores or passes on.
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(self::TABLE, [
            'userid' => 'privacy:metadata:grantteacher:userid',
            'timecreated' => 'privacy:metadata:grantteacher:timecreated',
            'importedby' => 'privacy:metadata:grantteacher:importedby',
        ], 'privacy:metadata:grantteacher');

        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:core_message');

        return $collection;
    }

    /**
     * Contexts holding data for a user: the system context, if they are on
     * the roster or uploaded it.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        if ($DB->record_exists_select(self::TABLE, 'userid = :userid OR importedby = :importedby',
                ['userid' => $userid, 'importedby' => $userid])) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * Users with data in the given context.
     *
     * @param userlist $userlist
     */
    public static function get_users_in_context(userlist $userlist) {
        if ($userlist->get_context()->contextlevel != CONTEXT_SYSTEM) {
            return;
        }
        $userlist->add_from_sql('userid', 'SELECT userid FROM {' . self::TABLE . '}', []);
        $userlist->add_from_sql('importedby', 'SELECT importedby FROM {' . self::TABLE . '} WHERE importedby > 0', []);
    }

    /**
     * Export a user's roster entry and the roster entries they uploaded.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        $userid = $contextlist->get_user()->id;
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            $data = (object) ['onroster' => transform::yesno(false)];
            $own = $DB->get_record(self::TABLE, ['userid' => $userid]);
            if ($own) {
                $data->onroster = transform::yesno(true);
                $data->timeadded = transform::datetime($own->timecreated);
            }
            $data->entriesuploaded = $DB->count_records(self::TABLE, ['importedby' => $userid]);

            writer::with_context($context)->export_data([
                get_string('pluginname', 'local_aiproofreaderreport'),
                get_string('privacy:grantroster', 'local_aiproofreaderreport'),
            ], $data);
        }
    }

    /**
     * Delete all roster data in a context.
     *
     * @param \context $context
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;

        if ($context->contextlevel == CONTEXT_SYSTEM) {
            $DB->delete_records(self::TABLE);
        }
    }

    /**
     * Delete one user's data: remove them from the roster and blank them out
     * as the uploader of other teachers' entries.
     *
     * @param approved_contextlist $contextlist
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        foreach ($contextlist->get_contexts() as $context) {
            if ($context->contextlevel == CONTEXT_SYSTEM) {
                self::delete_users([$contextlist->get_user()->id]);
            }
        }
    }

    /**
     * Delete data for several users in a context.
     *
     * @param approved_userlist $userlist
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        if ($userlist->get_context()->contextlevel == CONTEXT_SYSTEM) {
            self::delete_users($userlist->get_userids());
        }
    }

    /**
     * Shared delete logic for the two delete methods above.
     *
     * @param int[] $userids
     */
    private static function delete_users(array $userids): void {
        global $DB;

        if (empty($userids)) {
            return;
        }
        [$insql, $params] = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select(self::TABLE, "userid $insql", $params);
        $DB->set_field_select(self::TABLE, 'importedby', 0, "importedby $insql", $params);
    }
}
