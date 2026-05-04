<?php
// Salesforce CRM Configuration
// Copy this file to salesforce.php and fill in your actual credentials
// Get these from: Salesforce Setup > App Manager > Your Connected App

define('SF_CLIENT_ID',     'your_salesforce_consumer_key');
define('SF_CLIENT_SECRET', 'your_salesforce_consumer_secret');
define('SF_USERNAME',      'your_salesforce_username@example.com');
define('SF_PASSWORD',      'your_sf_passwordYourSecurityToken'); // password + security token combined
define('SF_LOGIN_URL',     'https://login.salesforce.com');
define('SF_API_VERSION',   'v59.0');

/**
 * Get Salesforce Access Token using Username-Password OAuth flow
 */
function sf_get_access_token() {
    $url = SF_LOGIN_URL . '/services/oauth2/token';

    $postData = 'grant_type=password'
        . '&client_id='     . rawurlencode(SF_CLIENT_ID)
        . '&client_secret=' . rawurlencode(SF_CLIENT_SECRET)
        . '&username='      . rawurlencode(SF_USERNAME)
        . '&password='      . rawurlencode(SF_PASSWORD);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST,           true);
    curl_setopt($ch, CURLOPT_POSTFIELDS,     $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,     ['Content-Type: application/x-www-form-urlencoded']);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode !== 200) {
        error_log('Salesforce auth failed: ' . $response);
        return false;
    }

    $data = json_decode($response, true);
    return [
        'access_token' => $data['access_token'],
        'instance_url' => $data['instance_url'],
    ];
}

/**
 * Make a Salesforce REST API call
 */
function sf_api_call($method, $endpoint, $body = []) {
    $auth = sf_get_access_token();
    if (!$auth) return false;

    $url = $auth['instance_url'] . $endpoint;
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Authorization: Bearer ' . $auth['access_token'],
        'Content-Type: application/json',
    ]);

    if ($method === 'POST') {
        curl_setopt($ch, CURLOPT_POST,       true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
    } elseif ($method === 'PATCH') {
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'PATCH');
        curl_setopt($ch, CURLOPT_POSTFIELDS,    json_encode($body));
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['status' => $httpCode, 'response' => json_decode($response, true)];
}

/**
 * Create a Lead in Salesforce when a new user registers
 */
function sf_create_lead($firstName, $lastName, $email, $phone = '', $company = 'Self') {
    return sf_api_call('POST', '/services/data/' . SF_API_VERSION . '/sobjects/Lead/', [
        'FirstName'  => $firstName,
        'LastName'   => $lastName,
        'Email'      => $email,
        'Phone'      => $phone,
        'Company'    => $company,
        'LeadSource' => 'Web',
        'Status'     => 'New',
    ]);
}

/**
 * Create a Case in Salesforce when a user submits an inquiry
 */
function sf_create_case($subject, $description, $email) {
    return sf_api_call('POST', '/services/data/' . SF_API_VERSION . '/sobjects/Case/', [
        'Subject'       => $subject,
        'Description'   => $description,
        'SuppliedEmail' => $email,
        'Origin'        => 'Web',
        'Status'        => 'New',
        'Priority'      => 'Medium',
    ]);
}
