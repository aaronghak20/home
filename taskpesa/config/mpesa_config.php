<?php
/**
 * M-Pesa Daraja API configuration
 *
 * Per Section 3.5.7 of the project report, credentials are read
 * from environment variables and are never hard-coded in source
 * files. Set these in your XAMPP environment (or a local .env
 * loader of your choice) before testing withdrawals:
 *
 *   TASKPESA_MPESA_ENV                sandbox | production
 *   TASKPESA_MPESA_CONSUMER_KEY
 *   TASKPESA_MPESA_CONSUMER_SECRET
 *   TASKPESA_MPESA_SHORTCODE          (Business/Till/Paybill shortcode)
 *   TASKPESA_MPESA_INITIATOR_NAME
 *   TASKPESA_MPESA_INITIATOR_PASSWORD
 *   TASKPESA_MPESA_B2C_SECURITY_CRED  (encrypted initiator password)
 *   TASKPESA_MPESA_CALLBACK_BASE_URL  e.g. https://yourdomain.com
 */

declare(strict_types=1);

$mpesaEnv = getenv('TASKPESA_MPESA_ENV') ?: 'sandbox';

return [
    'env'              => $mpesaEnv,
    'base_url'         => $mpesaEnv === 'production'
        ? 'https://api.safaricom.co.ke'
        : 'https://sandbox.safaricom.co.ke',
    'consumer_key'     => getenv('TASKPESA_MPESA_CONSUMER_KEY') ?: '',
    'consumer_secret'  => getenv('TASKPESA_MPESA_CONSUMER_SECRET') ?: '',
    'shortcode'        => getenv('TASKPESA_MPESA_SHORTCODE') ?: '',
    'initiator_name'   => getenv('TASKPESA_MPESA_INITIATOR_NAME') ?: '',
    'security_cred'    => getenv('TASKPESA_MPESA_B2C_SECURITY_CRED') ?: '',
    'result_url'       => (getenv('TASKPESA_MPESA_CALLBACK_BASE_URL') ?: '') . '/mpesa_callback.php?type=result',
    'timeout_url'      => (getenv('TASKPESA_MPESA_CALLBACK_BASE_URL') ?: '') . '/mpesa_callback.php?type=timeout',
];
