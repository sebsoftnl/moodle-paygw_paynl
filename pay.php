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
 * Redirects to the paynl checkout for payment
 *
 * @package    paygw_paynl
 * @copyright  2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core_payment\helper;
use paygw_paynl\paynl_helper;

require_once(__DIR__ . '/../../../config.php');
require_once(__DIR__ . '/thirdparty/paynl-sdk/autoload.php');

require_login();
$component = required_param('component', PARAM_COMPONENT);
$paymentarea = required_param('paymentarea', PARAM_AREA);
$itemid = required_param('itemid', PARAM_INT);
$description = required_param('description', PARAM_TEXT);

$context = context_system::instance(); // Because we "have no scope".
$PAGE->set_context($context);
$params = [
    'component' => $component,
    'paymentarea' => $paymentarea,
    'itemid' => $itemid,
    'description' => $description
];
$PAGE->set_url('/payment/gateway/paynl/pay.php', $params);
$PAGE->set_pagelayout('report');
$pagetitle = $description;
$PAGE->set_title($pagetitle);
$PAGE->set_heading($pagetitle);

$config = (object) helper::get_gateway_configuration($component, $paymentarea, $itemid, 'paynl');
$payable = helper::get_payable($component, $paymentarea, $itemid);
$surcharge = helper::get_gateway_surcharge('paynl');

$paynlhelper = new paynl_helper($config->apitoken, $config->tokencode, $config->serviceid);
$imagebase = paynl_helper::get_image_base();

$PAGE->requires->js_call_amd('paygw_paynl/startpayment', 'startPayment', ['[data-action="paynl-startpayment"]']);

echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('selectpaymentmethod', 'paygw_paynl'), 2);
echo '<div>' . get_string('selectpaymentmethod_help', 'paygw_paynl') . '</div>';

$paymentmethods = $paynlhelper->get_payment_methods();
if (count($paymentmethods) === 0) {
    echo \html_writer::div(get_string('err:nopaymentmethods', 'paygw_paynl'), 'alert alert-warning');
} else {
    $wcontext = (object)[
        'imagebase' => $imagebase,
        'methods' => array_values($paymentmethods)
    ];
    echo $OUTPUT->render_from_template('paygw_paynl/paynl_select_method', $wcontext);
    echo $OUTPUT->render_from_template('paygw_paynl/paynl_startpayment', (object)$params);
}
echo $OUTPUT->footer();
