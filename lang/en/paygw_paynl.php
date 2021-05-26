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
 * Strings for component 'paygw_paynl', language 'en'
 *
 * File         paygw_paynl.php
 * Encoding     UTF-8
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pluginname'] = 'PAY.';
$string['pluginname_desc'] = 'The Pay plugin allows you to receive payments via Pay.';
$string['gatewayname'] = 'PAY.';
$string['gatewaydescription'] = 'PAY. is an authorised payment gateway provider for processing credit card transactions.';
$string['privacy:metadata'] = 'The PAY. plugin does not store any personal data.';
$string['environment'] = 'Gateway mode';
$string['environment_help'] = 'Use this field to distinquish between sandbox testing and production mode. Please do note that sandbox testing mode does not perform actual real payments.';
$string['live'] = 'Production';
$string['sandbox'] = 'Sandbox/testing';
$string['apitoken'] = 'Secret';
$string['apitoken_help'] = 'The API token that PAY. generated for your application.';
$string['tokencode'] = 'Token code';
$string['tokencode_help'] = 'The token code that PAY. generated for your application (starts with "AT-").';
$string['serviceid'] = 'Service ID';
$string['serviceid_help'] = 'The service ID that PAY. generated for your application (starts with "SL-").';
$string['internalerror'] = 'An internal error has occurred. Please contact the system administrator.';
$string['unknownerror'] = 'An unknown error has occurred. Please contact the system administrator.';
$string['redirect-notify'] = 'Please note that starting a payment redirects you to an external payment page.';
$string['selectpaymentmethod'] = 'Select payment method';
$string['selectpaymentmethod_help'] = 'You can make a selection for the payment method here if you wish to do so.<br/>
If you don\'t select one, don\'t worry! In that case you will be able to select the payment method after you have been redirected to PAY.';
$string['startpayment'] = 'Start payment';
$string['err:validatetransaction:component'] = 'Transaction invalid: component mismatch';
$string['err:validatetransaction:paymentarea'] = 'Transaction invalid: paymentarea mismatch';
$string['err:validatetransaction:itemid'] = 'Transaction invalid: itemid mismatch';
$string['err:validatetransaction:userid'] = 'Transaction invalid: user mismatch';
$string['paymentalreadypaid'] = 'Payment already performed';
$string['paymentsuccessful'] = 'Your payment was successful';
$string['paymentcancelled'] = 'Your payment was cancelled';
$string['paymentpending'] = 'Your payment is pending. We will process the payment status later';
$string['cannotprocessstatus'] = 'Your payment has a status we cannot (yet) process. Please contact system administrator';
$string['transactionrecordnotfound'] = 'Reference to this payment cannot be found in our system.';
$string['payment:returnpage'] = 'Processing payment status.';
$string['task:processopenorders'] = 'Process open orders.';

$string['privacy:metadata:paygw_paynl'] = 'The PAY. payment plugin stores external transactionid\'s and payment references for the Moodle user needed to identity and synchronize payments.';
$string['privacy:metadata:paygw_paynl:userid'] = 'User ID';
$string['privacy:metadata:paygw_mollie:paymentid'] = 'Payment ID (internal)';
$string['privacy:metadata:paygw_paynl:component'] = 'Payment component';
$string['privacy:metadata:paygw_paynl:paymentarea'] = 'Payment area';
$string['privacy:metadata:paygw_paynl:itemid'] = 'Payment item ID';
$string['privacy:metadata:paygw_paynl:transactionid'] = 'Pay Transaction ID (external)';
$string['privacy:metadata:paygw_paynl:paymentreference'] = 'Pay Payment reference (external)';
$string['privacy:metadata:paygw_paynl:status'] = 'Order status name';
$string['privacy:metadata:paygw_paynl:statuscode'] = 'Order status code';
$string['privacy:metadata:paygw_paynl:testmode'] = 'Whether or not payment was done in test/sandbox mode';
$string['privacy:metadata:paygw_paynl:timecreated'] = 'Time the order record was created';
$string['privacy:metadata:paygw_paynl:timemodified'] = 'Time the order record was last updated';
