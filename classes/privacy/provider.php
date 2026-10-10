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
 * This plugin stores two rosters in the system context: the grant teacher
 * roster (teacher user ids) and the opt-out roster (district student
 * numbers of students whose parents opted out of research exports, matched
 * to Moodle users by their ID number). Both record who uploaded them. The
 * report and its exports read submission data from mod_aiproofreader, which
 * declares that data itself. Each exporter's saved Anonymized Export field
 * list is a user preference.
 *
 * @package    local_aiproofreaderreport
 * @copyright  2026 Brian Pool
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \core_privacy\local\request\core_userlist_provider,
    \core_privacy\local\request\plugin\provider,
    \core_privacy\local\request\user_preference_provider {
    /** @var string Grant roster table. */
    private const TABLE = 'local_aiproofreaderreport_grantteacher';

    /** @var string Opt-out roster table. */
    private const OPTOUT = 'local_aiproofreaderreport_optout';

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

        $collection->add_database_table(self::OPTOUT, [
            'studentnumber' => 'privacy:metadata:optout:studentnumber',
            'timecreated' => 'privacy:metadata:optout:timecreated',
            'importedby' => 'privacy:metadata:optout:importedby',
        ], 'privacy:metadata:optout');

        $collection->add_subsystem_link('core_message', [], 'privacy:metadata:core_message');

        $collection->add_user_preference(
            \local_aiproofreaderreport\export_field_catalog::PREF_SAVEDFIELDS,
            'privacy:metadata:preference:exportfields'
        );

        return $collection;
    }

    /**
     * Contexts holding data for a user: the system context, if they are on
     * either roster or uploaded one.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        global $DB;

        $contextlist = new contextlist();
        $exists = $DB->record_exists_select(
            self::TABLE,
            'userid = :userid OR importedby = :importedby',
            ['userid' => $userid, 'importedby' => $userid]
        ) || $DB->record_exists(self::OPTOUT, ['importedby' => $userid]);

        if (!$exists) {
            $studentnumber = self::get_studentnumber($userid);
            $exists = $studentnumber !== '' && $DB->record_exists(self::OPTOUT, ['studentnumber' => $studentnumber]);
        }
        if ($exists) {
            $contextlist->add_system_context();
        }
        return $contextlist;
    }

    /**
     * A user's district student number (their Moodle ID number), or '' if none.
     *
     * @param int $userid
     * @return string
     */
    private static function get_studentnumber(int $userid): string {
        global $DB;
        return trim((string) $DB->get_field('user', 'idnumber', ['id' => $userid]));
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
        $userlist->add_from_sql('importedby', 'SELECT importedby FROM {' . self::OPTOUT . '} WHERE importedby > 0', []);
        $userlist->add_from_sql(
            'id',
            "SELECT u.id
               FROM {user} u
               JOIN {" . self::OPTOUT . "} o ON o.studentnumber = u.idnumber
              WHERE u.deleted = 0 AND u.idnumber <> ''",
            []
        );
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

            $optout = (object) ['optedout' => transform::yesno(false)];
            $studentnumber = self::get_studentnumber($userid);
            $own = false;
            if ($studentnumber !== '') {
                $own = $DB->get_record(self::OPTOUT, ['studentnumber' => $studentnumber], '*', IGNORE_MULTIPLE);
            }
            if ($own) {
                $optout->optedout = transform::yesno(true);
                $optout->studentnumber = $own->studentnumber;
                $optout->timeadded = transform::datetime($own->timecreated);
            }
            $optout->entriesuploaded = $DB->count_records(self::OPTOUT, ['importedby' => $userid]);

            writer::with_context($context)->export_data([
                get_string('pluginname', 'local_aiproofreaderreport'),
                get_string('privacy:optoutroster', 'local_aiproofreaderreport'),
            ], $optout);
        }
    }

    /**
     * Export the user's saved Anonymized Export field selection.
     *
     * @param int $userid
     */
    public static function export_user_preferences(int $userid) {
        $name = \local_aiproofreaderreport\export_field_catalog::PREF_SAVEDFIELDS;
        $value = get_user_preferences($name, null, $userid);
        if ($value !== null) {
            writer::export_user_preference(
                'local_aiproofreaderreport',
                $name,
                $value,
                get_string('privacy:metadata:preference:exportfields', 'local_aiproofreaderreport')
            );
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
            $DB->delete_records(self::OPTOUT);
        }
    }

    /**
     * Delete one user's data: remove them from both rosters and blank them
     * out as the uploader of other entries.
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
        $DB->set_field_select(self::OPTOUT, 'importedby', 0, "importedby $insql", $params);

        $studentnumbers = array_filter(array_map('trim', $DB->get_fieldset_select('user', 'idnumber', "id $insql", $params)));
        if (!empty($studentnumbers)) {
            [$sinsql, $sparams] = $DB->get_in_or_equal(array_values($studentnumbers), SQL_PARAMS_NAMED, 'sn');
            $DB->delete_records_select(self::OPTOUT, "studentnumber $sinsql", $sparams);
        }
    }
}
