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
 * Contains class for PayNL payment gateway.
 *
 * File         gateway.php
 * Encoding     UTF-8
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace paygw_paynl;

defined('MOODLE_INTERNAL') || die();

/**
 * The gateway class for PayNL payment gateway.
 *
 * @package     paygw_paynl
 *
 * @author      Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @copyright   2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class gateway extends \core_payment\gateway {

    /**
     * The full list of currencies supported by PayNL regardless of account origin country.
     *
     * @return string[]
     */
    public static function get_supported_currencies(): array {
        // Only certain currencies are supported based on the users account, but below are all the currencies that the plugin
        // can support as they are given in cents.
        return [
                'USD', 'AED', 'ALL', 'AMD', 'ANG', 'AUD', 'AWG', 'AZN', 'BAM', 'BBD', 'BDT', 'BGN', 'BIF', 'BMD', 'BND', 'BSD',
                'BWP', 'BZD', 'CAD', 'CDF', 'CHF', 'CNY', 'DKK', 'DOP', 'DZD', 'EGP', 'ETB', 'EUR', 'FJD', 'GBP', 'GEL', 'GIP',
                'GMD', 'GYD', 'HKD', 'HRK', 'HTG', 'IDR', 'ILS', 'ISK', 'JMD', 'JPY', 'KES', 'KGS', 'KHR', 'KMF', 'KRW', 'KYD',
                'KZT', 'LBP', 'LKR', 'LRD', 'LSL', 'MAD', 'MDL', 'MGA', 'MKD', 'MMK', 'MNT', 'MOP', 'MRO', 'MVR', 'MWK', 'MXN',
                'MYR', 'MZN', 'NAD', 'NGN', 'NOK', 'NPR', 'NZD', 'PGK', 'PHP', 'PKR', 'PLN', 'QAR', 'RON', 'RSD', 'RUB', 'RWF',
                'SAR', 'SBD', 'SCR', 'SEK', 'SGD', 'SLL', 'SOS', 'SZL', 'THB', 'TJS', 'TOP', 'TRY', 'TTD', 'TWD', 'TZS', 'UAH',
                'UGX', 'UZS', 'VND', 'VUV', 'WST', 'XAF', 'XCD', 'YER', 'ZAR'
        ];
    }

    /**
     * Configuration form for the gateway instance
     *
     * Use $form->get_mform() to access the \MoodleQuickForm instance
     *
     * @param \core_payment\form\account_gateway $form
     */
    public static function add_configuration_to_gateway_form(\core_payment\form\account_gateway $form): void {
        $mform = $form->get_mform();

        $mform->addElement('text', 'tokencode', get_string('tokencode', 'paygw_paynl'));
        $mform->setType('tokencode', PARAM_TEXT);
        $mform->addHelpButton('tokencode', 'tokencode', 'paygw_paynl');

        $mform->addElement('text', 'apitoken', get_string('apitoken', 'paygw_paynl'));
        $mform->setType('apitoken', PARAM_TEXT);
        $mform->addHelpButton('apitoken', 'apitoken', 'paygw_paynl');

        $mform->addElement('text', 'serviceid', get_string('serviceid', 'paygw_paynl'));
        $mform->setType('serviceid', PARAM_TEXT);
        $mform->addHelpButton('serviceid', 'serviceid', 'paygw_paynl');

        $options = [
            'live' => get_string('live', 'paygw_paynl'),
            'sandbox'  => get_string('sandbox', 'paygw_paynl'),
        ];
        $mform->addElement('select', 'environment', get_string('environment', 'paygw_paynl'), $options);
        $mform->addHelpButton('environment', 'environment', 'paygw_paynl');
    }

    /**
     * Validates the gateway configuration form.
     *
     * @param \core_payment\form\account_gateway $form
     * @param \stdClass $data
     * @param array $files
     * @param array $errors form errors (passed by reference)
     */
    public static function validate_gateway_form(\core_payment\form\account_gateway $form,
            \stdClass $data, array $files, array &$errors): void {
        if ($data->enabled &&
                (empty($data->tokencode) || empty($data->apitoken) || empty($data->serviceid))) {
            $errors['enabled'] = get_string('gatewaycannotbeenabled', 'payment');
        }
        if (empty($data->tokencode)) {
            $errors['tokencode'] = get_string('required');
        }
        if (empty($data->apitoken)) {
            $errors['apitoken'] = get_string('required');
        }
        if (empty($data->serviceid)) {
            $errors['serviceid'] = get_string('required');
        }
    }

}
