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
 * Exchange script. This is where status updates for transaction end up when called _from_ PayNL.
 *
 * File         xchange.php
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

// No login check is expected since this is a signup script.
// @codingStandardsIgnoreLine
require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/thirdparty/paynl-sdk/autoload.php');

try {
    $component = required_param('component', PARAM_COMPONENT);
    $paymentarea = required_param('paymentarea', PARAM_AREA);
    $itemid = required_param('itemid', PARAM_INT);

    $config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'paynl');
    $paynlhelper = new paynl_helper($config->apitoken, $config->tokencode, $config->serviceid);

    $transaction = \Paynl\Transaction::getForExchange();
    // Let the helper hit the floor!
    // This will do the validation and all that fun stuff.
    $paynlhelper->synchronize_status($transaction, null);

    if ($transaction->isPaid()) {
        // Deliver course handled by paynl_helper.
        echo "TRUE|payment processed; order delivered.";
        exit;
    } else if ($transaction->isPending()) {
        echo "TRUE|payment status updated.";
        exit;
    } else if ($transaction->isCanceled()) {
        echo "TRUE|payment status updated.";
        exit;
    } else {
        echo "false|payment status updated but system cannot process " . $transaction->getStatus()->getStateName();
    }

} catch (\dml_missing_record_exception $dme) {
    echo "true|payment not processed: cannot locate internal transaction record.";
    exit;
} catch (moodle_exception $me) {
    echo "false|moodle-error:" . $me->getMessage();
    exit;
} catch (\Exception $e) {
    echo "false|generic-error:" . $e->getMessage();
    exit;
}
