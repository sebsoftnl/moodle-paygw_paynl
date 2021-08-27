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
 * Privacy Subsystem implementation for paygw_paynl.
 *
 * File         provider.php
 * Encoding     UTF-8
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_paynl\privacy;

defined('MOODLE_INTERNAL') || die;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\transform;
use core_privacy\local\request\writer;
use core_privacy\local\request\userlist;
use core_privacy\local\request\approved_userlist;
use core_payment\privacy\paygw_provider;

/**
 * Privacy Subsystem implementation for paygw_paynl.
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements \core_privacy\local\metadata\provider,
        \core_privacy\local\request\plugin\provider,
        \core_privacy\local\request\core_userlist_provider,
        paygw_provider {

    /**
     * Get the language string identifier with the component's language
     * file to explain why this plugin stores no data.
     *
     * @return  string
     */
    public static function get_reason() : string {
        return 'privacy:metadata';
    }

    /**
     * Provides a collection of stored metadata about a user
     *
     * @param collection $collection
     * @return collection
     */
    public static function get_metadata(\core_privacy\local\metadata\collection $collection): collection {
        $collection->add_database_table(
                'paygw_paynl',
                [
                        'userid' => 'privacy:metadata:paygw_paynl:userid',
                        'paymentid' => 'privacy:metadata:paygw_paynl:paymentid',
                        'component' => 'privacy:metadata:paygw_paynl:component',
                        'paymentarea' => 'privacy:metadata:paygw_paynl:paymentarea',
                        'itemid' => 'privacy:metadata:paygw_paynl:itemid',
                        'transactionid' => 'privacy:metadata:paygw_paynl:transactionid',
                        'paymentreference' => 'privacy:metadata:paygw_paynl:paymentreference',
                        'status' => 'privacy:metadata:paygw_paynl:status',
                        'statuscode' => 'privacy:metadata:paygw_paynl:statuscode',
                        'testmode' => 'privacy:metadata:paygw_paynl:testmode',
                        'timecreated' => 'privacy:metadata:paygw_paynl:timecreated',
                        'timemodified' => 'privacy:metadata:paygw_paynl:timemodified'
                ],
                'privacy:metadata:paygw_paynl'
        );
        return $collection;
    }

    /**
     * Get the lists of contexts that contain user information for the specified user.
     *
     * @param int $userid
     * @return contextlist
     */
    public static function get_contexts_for_userid(int $userid): contextlist {
        $contextlist = new contextlist();
        // I know we should really provide the proper contexts.
        // This can be so messy, we just return system context. Payments should really be system context anyway.
        $contextlist->add_system_context();
        return $contextlist;
    }

    /**
     * Export all user data for the specified user.
     *
     * @param approved_contextlist $contextlist
     */
    public static function export_user_data(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a system context.
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }

            // Add contextual data for given user.
            $alldata = [$context->id => []];
            $alluserdata = $DB->get_recordset_sql(
                    "SELECT * FROM {paygw_paynl} WHERE userid = :userid",
                    array('userid' => $user->id)
            );
            foreach ($alluserdata as $userdata) {
                $alldata[$context->id][] = (object) [
                    'userid' => $userdata->userid,
                    'component' => $userdata->component,
                    'paymentarea' => $userdata->paymentarea,
                    'itemid' => $userdata->itemid,
                    'transactionid' => $userdata->transactionid,
                    'paymentreference' => $userdata->paymentreference,
                    'status' => $userdata->status,
                    'statuscode' => $userdata->statuscode,
                    'testmode' => transform::yesno($userdata->testmode),
                    'timecreated' => transform::datetime($userdata->timecreated),
                    'timemodified' => transform::datetime($userdata->timemodified)
                ];
            }
            $alluserdata->close();

            // The data is organised in: {?}/transactiondata.json.
            array_walk($alldata, function($transactions, $contextid) {
                $context = \context::instance_by_id($contextid);
                writer::with_context($context)->export_related_data(
                    ['paygw_paynl'],
                    'paygw_paynl_transactiondata',
                    (object)['transactions' => $transactions]
                );
            });
        }
    }
    /**
     * Delete all use data which matches the specified context.
     *
     * @param context $context The module context.
     */
    public static function delete_data_for_all_users_in_context(\context $context) {
        global $DB;
        if ($context->contextlevel != CONTEXT_SYSTEM) {
            return;
        }

        // Delete everything.
        $DB->delete_records('paygw_paynl', null);
    }

    /**
     * Delete all user data for the specified user, in the specified contexts.
     *
     * @param approved_contextlist $contextlist The approved contexts and user information to delete information for.
     */
    public static function delete_data_for_user(approved_contextlist $contextlist) {
        global $DB;

        if (empty($contextlist->count())) {
            return;
        }

        $user = $contextlist->get_user();
        foreach ($contextlist->get_contexts() as $context) {
            // Check that the context is a system context.
            if ($context->contextlevel != CONTEXT_SYSTEM) {
                continue;
            }
            $DB->delete_records_select('paygw_paynl', 'userid = :userid', ['userid' => $user->id]);
        }
    }
    /**
     * Get the list of users who have data within a context.
     *
     * @param userlist $userlist The userlist containing the list of users who have data in this context/plugin combination.
     */
    public static function get_users_in_context(userlist $userlist) {
        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }
        $sql = 'SELECT DISTINCT userid FROM {paygw_paynl}';
        $userlist->add_from_sql('userid', $sql, []);
    }

    /**
     * Delete multiple users within a single context.
     *
     * @param  approved_userlist $userlist The approved context and user information to delete information for.
     */
    public static function delete_data_for_users(approved_userlist $userlist) {
        global $DB;

        $context = $userlist->get_context();
        if (!$context instanceof \context_system) {
            return;
        }

        $userids = $userlist->get_userids();
        list($usersql, $userparams) = $DB->get_in_or_equal($userids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('paygw_paynl', 'userid '.$usersql, $userparams);
    }

    /**
     * Delete all user data related to the given payments.
     *
     * @param string $paymentsql SQL query that selects payment.id field for the payments
     * @param array $paymentparams Array of parameters for $paymentsql
     */
    public static function delete_data_for_payment_sql(string $paymentsql, array $paymentparams) {
        global $DB;
        $DB->delete_records_select('paygw_paynl', "paymentid IN ({$paymentsql})", $paymentparams);
    }

    /**
     * Export all user data for the specified payment record, and the given context.
     *
     * @param \context $context Context
     * @param array $subcontext The location within the current context that the payment data belongs
     * @param \stdClass $payment The payment record
     */
    public static function export_payment_data(\context $context, array $subcontext, \stdClass $payment) {
        global $DB;

        $subcontext[] = get_string('gatewayname', 'paygw_paynl');
        $record = $DB->get_record('paygw_paynl', ['paymentid' => $payment->id]);

        $data = (object) [
            'transactionid' => $record->transactionid,
            'paymentreference' => $record->paymentreference,
            'status' => $record->status,
            'statuscode' => $record->statuscode,
            'testmode' => $record->testmode,
        ];
        writer::with_context($context)->export_data(
            $subcontext,
            $data
        );
    }

}
