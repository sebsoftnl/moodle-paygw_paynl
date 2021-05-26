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
 * Task to attempt to process lingering transactions.
 *
 * File         processopenorders.php
 * Encoding     UTF-8
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_paynl\task;

use core_payment\helper;
use paygw_paynl\paynl_helper;

defined('MOODLE_INTERNAL') || die();

/**
 * Task to attempt to process lingering transactions.
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class processopenorders extends \core\task\scheduled_task {

    /**
     * Return the localised name for this task
     *
     * @return string task name
     */
    public function get_name() {
        return get_string('task:processopenorders', 'paygw_paynl');
    }

    /**
     * Executes the task
     *
     * Process all pending orders.
     * Used by our plugin task in case we missed return / exchange callbacks from PAYNL.
     *
     * @return void
     */
    public function execute() {
        global $DB;
        mtrace('Processing pending orders for PAYNL in case we missed exchange requests.');

        // We process these 10 at a time in reverse order. So the "newest" first.
        $statuses = array(-80 /*CANCEL-EXPIRE*/, -90 /*CANCEL*/, 100 /*PAID*/);
        list($notinsql, $params) = $DB->get_in_or_equal($statuses, SQL_PARAMS_NAMED, 'pstate', false, 0);
        $sql = "SELECT * FROM {paygw_paynl}
            WHERE statuscode {$notinsql}
            AND timemodified <= :lastmodified
            AND timemodified >= :lastmodified2
            ORDER BY timemodified DESC
            ";
        $params['lastmodified'] = $this->get_last_run_time() - DAYSECS; // One day behind.
        $params['lastmodified2'] = $this->get_last_run_time() - (7 * DAYSECS); // Cut off: don't attempt if more than 7 days old.

        $results = $DB->get_records_sql($sql, $params, 0, 10);
        foreach ($results as $transactionrecord) {
            try {
                // The scope of the transaction determines WHICH pay config to use.
                $paynlhelper = new paynl_helper('', '', '');
                // Let the helper hit the floor!
                // This will do the validation and all that fun stuff.
                $paynlhelper->synchronize_status(null, $transactionrecord);
                // Do a "fitty mu" powernap.
                usleep(50000);
            } catch (\Exception $e) {
                // Don't do a damn thing.
                mtrace("Error during execution: " . $e->getMessage());
            }
        }
    }

}