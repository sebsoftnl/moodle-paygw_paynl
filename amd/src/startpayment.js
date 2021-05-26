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
 * Payment implementation for paynl system.
 *
 * @package    paygw_paynl
 * @module     paygw_paynl/startpayment
 * @copyright  2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';
import * as Repository from 'paygw_paynl/repository';
import Notification from 'core/notification';

/**
 * Detect selected payment method (if we have one).
 *
 * @returns {String|null}
 */
function getSelectedPaymentMethod() {
    let el = document.querySelector('input[name="method"][type="radio"]:checked');
    if (typeof el !== 'undefined' && el !== null) {
        return el.value;
    }
    return null;
}

/**
 * Create payment in the backend and redirect.
 *
 * @param {String} selector
 * @returns {void}
 */
export const startPayment = (selector) => {
    const doCreateTransaction = function(e) {
        e.preventDefault();
        let invokeElement = $(e.currentTarget);
        Repository.createPayment(
                invokeElement.data('component'),
                invokeElement.data('paymentarea'),
                invokeElement.data('itemid'),
                invokeElement.data('description'),
                getSelectedPaymentMethod(),
                null,
        ).then(function(result) {
            if (result.success) {
                window.location.href = result.redirecturl;
            } else {
                // hmmmm.
            }
        }).fail(Notification.exception);
    };
    $(selector).on('click', doCreateTransaction);
};
