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
 * Return page after payment
 *
 * File         return.php
 * Encoding     UTF-8
 *
 * @package     paygw_paynl
 *
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_paynl\paynl_helper;
use core\output\notification;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/thirdparty/paynl-sdk/autoload.php');

require_login();

$component = required_param('component', PARAM_ALPHANUMEXT);
$paymentarea = required_param('paymentarea', PARAM_ALPHANUMEXT);
$itemid = required_param('itemid', PARAM_INT);
// Params below added by Pay.
$orderid = required_param('orderId', PARAM_ALPHANUMEXT);
$orderstatusid = required_param('orderStatusId', PARAM_INT);

$context = context_system::instance(); // Because we "have no scope".
$PAGE->set_context($context);
$params = [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid
];
$PAGE->set_url('/payment/gateway/paynl/return.php', $params);
$PAGE->set_pagelayout('report');
$pagetitle = get_string('payment:returnpage', 'paygw_paynl');
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'paynl');
$paynlhelper = new paynl_helper($config->apitoken, $config->tokencode, $config->serviceid);

// Nevermind the GIVEN status, we will check the most recent remote transaction status.
// Bit odd; we have to juggle with transactionId vs orderId. This is PAY's fault.
try {
    // Fetch transaction info at pay.
    $externaltransaction = \Paynl\Transaction::get($orderid);
    // Let the helper hit the floor!
    // This will do the validation and all that fun stuff.
    $paynlhelper->synchronize_status($externaltransaction, null);

    if ($externaltransaction->isPaid()) {
        // Deliver course is cared for by the helper!
        $url = paynl_helper::determine_redirect_url($component, $paymentarea, $itemid);
        redirect($url, get_string('paymentsuccessful', 'paygw_paynl'), 0, 'success');

    } else if ($externaltransaction->isPending()) {
        // Display message.
        redirect(new moodle_url('/'), get_string('paymentpending', 'paygw_paynl'), 0, notification::NOTIFY_WARNING);
    } else if ($externaltransaction->isCanceled()) {
        // Back to main page with notification.
        redirect(new moodle_url('/'), get_string('paymentcancelled', 'paygw_paynl'), 0, notification::NOTIFY_WARNING);
    } else {
        // Back to main page with notification.
        redirect(new moodle_url('/'), get_string('cannotprocessstatus', 'paygw_paynl'), 0, notification::NOTIFY_WARNING);
    }

} catch (\dml_missing_record_exception $dme) {
    redirect(new moodle_url('/'), get_string('transactionrecordnotfound', 'paygw_paynl'), 0, notification::NOTIFY_ERROR);
} catch (moodle_exception $me) {
    redirect(new moodle_url('/'), $me->getMessage(), 0, notification::NOTIFY_ERROR);
} catch (\Exception $e) {
    echo $e->getMessage();
    redirect(new moodle_url('/'), get_string('unknownerror', 'paygw_paynl'), 0, notification::NOTIFY_ERROR);
}
