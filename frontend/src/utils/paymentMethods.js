/**
 * Payment Method Constants & Utilities
 * 
 * Contains payment method definitions and helper functions
 * for displaying payment methods in Arabic
 */

export const PAYMENT_METHODS = {
  CASH: 'CASH',
  CHEQUE: 'CHEQUE',
  VODAFONE_CASH: 'VODAFONE_CASH',
  INSTAPAY: 'INSTAPAY',
  BANK_ACCOUNT: 'BANK_ACCOUNT',
};

export const PAYMENT_METHOD_LABELS = {
  CASH: 'نقدي',
  CHEQUE: 'شيك',
  VODAFONE_CASH: 'فودافون كاش',
  INSTAPAY: 'إنستاباي',
  BANK_ACCOUNT: 'حساب بنكي',
};

/**
 * Get Arabic label for payment method
 * @param {string} method - Payment method code
 * @returns {string} Arabic label
 */
export const getPaymentMethodLabel = (method) => {
  if (!method) return 'غير محدد';
  
  const upperMethod = method.toUpperCase();
  return PAYMENT_METHOD_LABELS[upperMethod] || method;
};

/**
 * Get all payment method options for select/dropdown
 * @returns {Array} Array of {value, label} objects
 */
export const getPaymentMethodOptions = () => {
  return Object.keys(PAYMENT_METHODS).map(key => ({
    value: PAYMENT_METHODS[key],
    label: PAYMENT_METHOD_LABELS[key]
  }));
};

/**
 * Check if payment method requires cheque fields
 * @param {string} method - Payment method
 * @returns {boolean}
 */
export const requiresChequeFields = (method) => {
  return method === PAYMENT_METHODS.CHEQUE;
};

/**
 * Check if payment method requires mobile number
 * @param {string} method - Payment method
 * @returns {boolean}
 */
export const requiresMobileNumber = (method) => {
  return method === PAYMENT_METHODS.VODAFONE_CASH;
};

/**
 * Check if payment method requires InstaPay fields
 * @param {string} method - Payment method
 * @returns {boolean}
 */
export const requiresInstaPayFields = (method) => {
  return method === PAYMENT_METHODS.INSTAPAY;
};

/**
 * Check if payment method requires bank account fields
 * @param {string} method - Payment method
 * @returns {boolean}
 */
export const requiresBankAccountFields = (method) => {
  return method === PAYMENT_METHODS.BANK_ACCOUNT;
};

export default {
  PAYMENT_METHODS,
  PAYMENT_METHOD_LABELS,
  getPaymentMethodLabel,
  getPaymentMethodOptions,
  requiresChequeFields,
  requiresMobileNumber,
  requiresInstaPayFields,
  requiresBankAccountFields,
};
