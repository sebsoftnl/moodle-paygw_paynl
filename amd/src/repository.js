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
 * Repository for paynl system.
 *
 * @package    paygw_paynl
 * @module     paygw_paynl/repository
 * @copyright  2021 Ing. R.J. van Dongen <rogier@sebsoft.nl>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import Ajax from 'core/ajax';

/**
 * Returns the list of gateways that can process payments in the given currency.
 *
 * @param {string} component
 * @param {string} paymentArea
 * @param {number} itemId
 * @param {string} description
 * @param {number} paymentMethodId
 * @param {number} bankId
 * @returns {Promise<{shortname: string, name: string, description: String}[]>}
 */
export const createPayment = (component, paymentArea, itemId, description, paymentMethodId, bankId) => {
    let args = {
            component,
            paymentarea: paymentArea,
            itemid: itemId,
            description
        };
    if (paymentMethodId !== undefined) {
        args.paymentmethodid = paymentMethodId;
    }
    if (bankId !== undefined) {
        args.bankid = bankId;
    }
    const request = {
        methodname: 'paygw_paynl_create_payment',
        args: args
    };
    return Ajax.call([request])[0];
};
