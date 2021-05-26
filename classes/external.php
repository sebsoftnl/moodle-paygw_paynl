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
 * This class contains a list of webservice functions related to the PayNL payment gateway.
 *
 * File         external.php
 * Encoding     UTF-8
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

declare(strict_types=1);

namespace paygw_paynl;

use external_api;
use external_function_parameters;
use external_value;
use external_single_structure;
use external_multiple_structure;

use core_payment\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/externallib.php');
require_once($CFG->dirroot . '/payment/gateway/paynl/thirdparty/paynl-sdk/autoload.php');

/**
 * This class contains a list of webservice functions related to the PayNL payment gateway.
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class external extends external_api {

    /**
     * Perform what needs to be done when a transaction is reported to be complete.
     * This function does not take cost as a parameter as we cannot rely on any provided value.
     *
     * @param string $component Name of the component that the itemid belongs to
     * @param string $paymentarea
     * @param int $itemid An internal identifier that is used by the component
     * @param string $description
     * @param int $paymentmethodid Payment method id
     * @param int $bankid bank identifier|reserved for future use
     * @return array
     */
    public static function create_payment(string $component, string $paymentarea, int $itemid,
            string $description, int $paymentmethodid = null, int $bankid = null): array {
        global $USER, $DB;

        $params = self::validate_parameters(self::create_payment_parameters(), [
            'component' => $component,
            'paymentarea' => $paymentarea,
            'itemid' => $itemid,
            'description' => $description,
            'paymentmethodid' => $paymentmethodid,
            'bankid' => $bankid,
        ]);

        $config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'paynl');
        $payable = helper::get_payable($component, $paymentarea, $itemid);
        $currency = $payable->get_currency();
        $surcharge = helper::get_gateway_surcharge('paynl');
        $amount = helper::get_rounded_cost($payable->get_amount(), $currency, $surcharge);
        $paynl = new paynl_helper($config->apitoken, $config->tokencode, $config->serviceid);

        try {
            $testmode = ($config->environment === 'sandbox');
            $result = $paynl->create_payment($currency, $params['description'], $amount,
                    $params['component'], $params['paymentarea'], $params['itemid'],
                    $params['paymentmethodid'], $params['bankid'], $testmode);

            // Please DO store some intermediates, because we need those.
            $txid = $result->getTransactionId();
            $pmref = $result->getPaymentReference();
            $redirecturl = $result->getRedirectUrl();

            $time = time();
            $record = (object)[
                'userid' => $USER->id,
                'component' => $component,
                'paymentarea' => $paymentarea,
                'itemid' => $itemid,
                'transactionid' => $txid,
                'paymentreference' => $pmref,
                'status' => 'INIT',
                'statuscode' => 0,
                'testmode' => $testmode ? 1 : 0,
                'timecreated' => $time,
                'timemodified' => $time
            ];
            $DB->insert_record('paygw_paynl', $record);

            $success = true;
        } catch (\Exception $e) {
            debugging('Exception while trying to process payment: ' . $e->getMessage(), DEBUG_DEVELOPER);
            $success = false;
            $message = get_string('internalerror', 'paygw_paynl') . $e->getMessage();
            $redirecturl = null;
        }

        return [
            'success' => $success,
            'message' => $message,
            'redirecturl' => $redirecturl,
        ];
    }

    /**
     * Returns description of method parameters.
     *
     * @return external_function_parameters
     */
    public static function create_payment_parameters() {
        return new external_function_parameters([
            'component' => new external_value(PARAM_COMPONENT, 'The component name'),
            'paymentarea' => new external_value(PARAM_AREA, 'Payment area in the component'),
            'itemid' => new external_value(PARAM_INT, 'The item id in the context of the component area'),
            'description' => new external_value(PARAM_TEXT, 'Payment description'),
            'paymentmethodid' => new external_value(PARAM_INT, 'Payment method ID', VALUE_DEFAULT, null, NULL_ALLOWED),
            'bankid' => new external_value(PARAM_INT, 'Bank ID (reserved for future use)', VALUE_DEFAULT, null, NULL_ALLOWED),
        ]);
    }

    /**
     * Returns description of method result value.
     *
     * @return external_function_parameters
     */
    public static function create_payment_returns() {
        return new external_function_parameters([
            'success' => new external_value(PARAM_BOOL, 'Whether everything was successful or not.'),
            'message' => new external_value(PARAM_RAW,
                    'Message (usually the error message). Unused or not available if everything went well',
                    VALUE_OPTIONAL),
            'redirecturl' => new external_value(PARAM_RAW, 'Message (usually the error message).', VALUE_OPTIONAL),
        ]);
    }

}
