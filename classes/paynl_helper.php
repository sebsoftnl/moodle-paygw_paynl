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
 * Various helper methods for interacting with the PayNL API
 *
 * File         paynl_helper.php
 * Encoding     UTF-8
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_paynl;

use DateTime;
use moodle_url;
use moodle_exception;
use stdClass;
use core_payment\helper;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/payment/gateway/paynl/thirdparty/paynl-sdk/autoload.php');

/**
 * The helper class for PayNL payment gateway.
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class paynl_helper {

    /**
     * Get image base location.
     *
     * Please note this is for images that USED to be provided by PayNL itself.
     * However, for some reason they thought it was a great idea to have customers
     * host the images themselves.
     * Very poor decision indeed (why not use a CDN for example??).
     *
     * @return string
     */
    public static function get_image_base() {
        global $CFG;
        return $CFG->wwwroot . '/payment/gateway/paynl/thirdparty/images';
    }

    /**
     * Initialise the PayNL API client.
     *
     * @param string $apikey
     * @param string $tokencode
     * @param string $serviceid
     */
    public function __construct(string $apikey, string $tokencode, string $serviceid) {
        // Provide PayNL API with the details.
        \Paynl\Config::setApiToken($apikey);
        \Paynl\Config::setServiceId($serviceid);
        \Paynl\Config::setTokenCode($tokencode);
    }

    /**
     * List payment methods.
     *
     * @return array
     */
    public function get_payment_methods() {
        return \Paynl\Paymentmethods::getList();
    }

    /**
     * Create/start a transaction
     *
     * @param string $currency
     * @param string $description
     * @param float $cost
     * @param string $component
     * @param string $paymentarea
     * @param int $itemid
     * @param int $paymentmethodid
     * @param int $bankid
     * @param bool $testmode
     * @return mixed
     * @throws \Exception
     */
    public static function create_payment(string $currency, string $description, float $cost, string $component,
            string $paymentarea, int $itemid, int $paymentmethodid = null, int $bankid = null, bool $testmode = false) {
        global $CFG, $USER;
        try {
            $params = [
                'component' => $component,
                'paymentarea' => $paymentarea,
                'itemid' => $itemid
            ];
            $redirecturl = new moodle_url($CFG->wwwroot . '/payment/gateway/paynl/return.php', $params);
            $exchangeurl = new moodle_url($CFG->wwwroot . '/payment/gateway/paynl/xchange.php', $params);

            $enduser = array();
            $enduser['language'] = $USER->lang;
            $enduser['lastName'] = $USER->lastname;
            $enduser['initials'] = $USER->firstname;
            if (!empty($USER->phone1)) {
                $enduser['phoneNumber'] = $USER->phone1;
            } else if (!empty($USER->phone2)) {
                $enduser['phoneNumber'] = $USER->phone2;
            }
            $address = array();
            if (!empty($USER->address)) {
                $address['streetName'] = $USER->address;
            }
            if (!empty($USER->city)) {
                $address['city'] = $USER->city;
            }
            if (!empty($USER->COUNTRY)) {
                $address['countryCode'] = $USER->country;
            }
            if (!empty($address)) {
                $enduser['address'] = $address;
            }
            $enduser['emailAddress'] = $USER->email;

            $orderproducts = [];
            $orderproducts[] = [
                'productId' => "{$component}-{$paymentarea}-{$itemid}",
                'description' => $description,
                'price' => $cost,
                'quantity' => 1,
            ];

            $options = [
                'amount' => $cost,
                'currency' => $currency,
                'returnUrl' => $redirecturl->out(false),
                'exchangeUrl' => $exchangeurl->out(false),
                'description' => $description,
                'testmode' => $testmode ? 1 : 0,
                'ipaddress' => getremoteaddr(),
                'invoiceDate' => new DateTime(),
                'deliveryDate' => new DateTime(),
                'tool' => 'moodle/paygw_paynl-v'.get_config('paygw_paynl', 'version'),
                'extra1' => "{$component}|{$paymentarea}|{$itemid}|{$USER->id}",
                'enduser' => $enduser,
            ];
            if (!empty($address)) {
                $options['address'] = $address;
                $options['invoiceAddress'] = $address;
            }
            $options['browserData'] = array(
                'browser_name_regex' => '^mozilla/5\.0 (windows; .; windows nt 5\.1; .*rv:.*) gecko/.* firefox/0\.9.*$',
                'browser_name_pattern' => 'Mozilla/5.0 (Windows; ?; Windows NT 5.1; *rv:*) Gecko/* Firefox/0.9*',
                'parent' => 'Firefox 0.9',
                'platform' => 'WinXP',
                'browser' => 'Firefox',
                'version' => 0.9,
                'majorver' => 0,
                'minorver' => 9,
                'cssversion' => 2,
                'frames' => 1,
                'iframes' => 1,
                'tables' => 1,
                'cookies' => 1,
            );

            if (!empty($paymentmethodid)) {
                $options['paymentMethod'] = $paymentmethodid;
            }
            if (!empty($bankid)) {
                $options['bank'] = $bankid;
            }

            $result = \Paynl\Transaction::start($options);
            return $result;
        } catch (\Exception $e) {
            throw $e;
        }

    }

    /**
     * Try to synchronize the status for a payment based on the internal transactionrecord,
     * the PayNL Transaction info, or both.
     *
     * This method performs validation on whether the information from the external
     * source and internal transaction records match.
     *
     * @param \Paynl\Result\Transaction\Transaction $transaction
     * @param stdClass $transactionrecord
     * @return \Paynl\Result\Transaction\Transaction the PayNL transaction info.
     * @throws moodle_exception
     */
    public function synchronize_status(\Paynl\Result\Transaction\Transaction $transaction = null,
            stdClass $transactionrecord = null) {
        global $DB;
        if ($transaction === null && $transactionrecord === null) {
            throw new moodle_exception('Provide either the PayNL transaction, the internal record or both.');
        }

        if ($transaction === null) {
            // For SAFETY; we shall ALWAYS update API config.
            // This is basically an "unfortunate must".
            $config = (object) helper::get_gateway_configuration($transactionrecord->component,
                $transactionrecord->paymentarea, $transactionrecord->itemid, 'paynl');
            \Paynl\Config::setApiToken($config->apitoken);
            \Paynl\Config::setServiceId($config->serviceid);
            \Paynl\Config::setTokenCode($config->tokencode);

            $transaction = \Paynl\Transaction::get($transactionrecord->transactionid);
        }
        if ($transactionrecord === null) {
            $transactionrecord = $DB->get_record('paygw_paynl',
                    ['transactionid' => $transaction->getStatus()->getOrderId()], '*', MUST_EXIST);
        }

        list($ccomponent, $cpaymentarea, $citemid, $cuserid) = explode('|', $transaction->getExtra1());
        // Ok, validate.
        if ($transactionrecord->component !== $ccomponent) {
            throw new moodle_exception('err:validatetransaction:component', 'paygw_paynl');
        }
        if ($transactionrecord->paymentarea !== $cpaymentarea) {
            throw new moodle_exception('err:validatetransaction:paymentarea', 'paygw_paynl');
        }
        if ($transactionrecord->itemid !== $citemid) {
            throw new moodle_exception('err:validatetransaction:itemid', 'paygw_paynl');
        }
        if ($transactionrecord->userid !== $cuserid) {
            throw new moodle_exception('err:validatetransaction:userid', 'paygw_paynl');
        }

        // Do we need to do anything?
        $transactionstatus = $transaction->getStatus();
        if ($transactionrecord->statuscode == $transactionstatus->getState()) {
            // Status has not changed so break!
            return $transaction;
        }

        // Update state.
        $transactionrecord->status = $transactionstatus->getStateName();
        $transactionrecord->statuscode = $transactionstatus->getState();
        $transactionrecord->timemodified = time();
        $DB->update_record('paygw_paynl', $transactionrecord);

        // Now finally, perform actual order delivery if paid.
        if ($transaction->isPaid()) {
            // Deliver course.
            $payable = helper::get_payable($transactionrecord->component,
                    $transactionrecord->paymentarea, $transactionrecord->itemid);
            $cost = helper::get_rounded_cost($payable->get_amount(), $payable->get_currency(),
                    helper::get_gateway_surcharge('paynl'));
            $paymentid = helper::save_payment($payable->get_account_id(),
                    $transactionrecord->component, $transactionrecord->paymentarea,
                    $transactionrecord->itemid, $transactionrecord->userid,
                    $cost, $payable->get_currency(), 'paynl');
            helper::deliver_order($transactionrecord->component, $transactionrecord->paymentarea,
                    $transactionrecord->itemid, $paymentid, $transactionrecord->userid);

            // Set payment ID!
            $transactionrecord->paymentid = $paymentid;
            $transactionrecord->timemodified = time();
            $DB->update_record('paygw_paynl', $transactionrecord);
        }

        return $transaction;
    }

    /**
     * Determine the redirect URL.
     *
     * @param string $component
     * @param string $paymentarea
     * @param string $itemid
     * @return moodle_url
     */
    public static function determine_redirect_url($component, $paymentarea, $itemid) {
        global $CFG;
        // Find redirection.
        $url = new moodle_url('/');
        // Method only exists in 3.11+.
        if (method_exists('\core_payment\helper', 'get_success_url')) {
            $url = helper::get_success_url($component, $paymentarea, $itemid);
        } else if ($component == 'enrol_fee' && $paymentarea == 'fee') {
            require_once($CFG->dirroot . '/course/lib.php');
            $courseid = static::db()->get_field('enrol', 'courseid', ['enrol' => 'fee', 'id' => $itemid]);
            if (!empty($courseid)) {
                $url = course_get_url($courseid);
            }
        }
        return $url;
    }

}
