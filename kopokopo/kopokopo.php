<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

use PrestaShop\PrestaShop\Core\Payment\PaymentOption;


class Kopokopo extends PaymentModule
{
    public function __construct()
    {
        $this->name = 'kopokopo';
        $this->tab = 'payments_gateways';
        $this->version = '1.0.0';
        $this->author = 'samuelsimiyu';
        $this->need_instance = 0;
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('Kopokopo Mpesa Payment');
        $this->description = $this->l('Accept payments via Kopokopo K2 API for Mpesa STK Push.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    public function install()
    {
        return parent::install() &&
            $this->registerHook('paymentOptions') &&
            $this->registerHook('paymentReturn') &&
            $this->registerHook('footer') &&
            Configuration::updateValue('KOPOKOPO_TILL_NUMBER', '') &&
            Configuration::updateValue('KOPOKOPO_CLIENT_ID', '') &&
            Configuration::updateValue('KOPOKOPO_CLIENT_SECRET', '') &&
            Configuration::updateValue('KOPOKOPO_API_KEY', '') &&
            Configuration::updateValue('KOPOKOPO_ACCESS_TOKEN', '') &&
            Configuration::updateValue('KOPOKOPO_TOKEN_EXPIRY', '');
    }

    public function uninstall()
    {
        return parent::uninstall() &&
            Configuration::deleteByName('KOPOKOPO_TILL_NUMBER') &&
            Configuration::deleteByName('KOPOKOPO_CLIENT_ID') &&
            Configuration::deleteByName('KOPOKOPO_CLIENT_SECRET') &&
            Configuration::deleteByName('KOPOKOPO_API_KEY') &&
            Configuration::deleteByName('KOPOKOPO_ACCESS_TOKEN') &&
            Configuration::deleteByName('KOPOKOPO_TOKEN_EXPIRY');
    }

    public function getContent()
    {
        if (Tools::isSubmit('submit_kopokopo')) {
            Configuration::updateValue('KOPOKOPO_TILL_NUMBER', Tools::getValue('KOPOKOPO_TILL_NUMBER'));
            Configuration::updateValue('KOPOKOPO_CLIENT_ID', Tools::getValue('KOPOKOPO_CLIENT_ID'));
            Configuration::updateValue('KOPOKOPO_CLIENT_SECRET', Tools::getValue('KOPOKOPO_CLIENT_SECRET'));
            Configuration::updateValue('KOPOKOPO_API_KEY', Tools::getValue('KOPOKOPO_API_KEY'));
            $this->context->smarty->assign('confirmation', $this->l('Settings updated.'));
        }

        $this->context->smarty->assign([
            'till_number' => Configuration::get('KOPOKOPO_TILL_NUMBER'),
            'client_id' => Configuration::get('KOPOKOPO_CLIENT_ID'),
            'client_secret' => Configuration::get('KOPOKOPO_CLIENT_SECRET'),
            'api_key' => Configuration::get('KOPOKOPO_API_KEY'),
        ]);

        return $this->display(__FILE__, 'views/templates/admin/configure.tpl');
    }

    public function hookFooter($params)
    {
        if ($this->context->controller->php_self === 'order') {
            $this->context->controller->addJS($this->_path . 'views/js/payment_interceptor.js');
        }
    }

    public function hookPaymentOptions()
    {
        if (!$this->active) {
            return [];
        }

        $payment_option = new PaymentOption();
        $payment_option->setCallToActionText($this->l('Pay with Mpesa'))
            ->setAction($this->context->link->getModuleLink($this->name, 'payment', [], true));
            //->setAdditionalInformation($this->context->smarty->fetch('module:kopokopo/views/templates/front/payment_execution.tpl'));

        return [$payment_option];
    }

    public function hookPaymentReturn($params)
    {
        if (!$this->active) {
            return;
        }

        $this->context->smarty->assign([
            'status' => Tools::getValue('status', 'failed'),
        ]);

        return $this->display(__FILE__, 'views/templates/front/payment_execution.tpl');
    }

public function initiatePayment($amount, $currency, $phoneNumber, $email, $firstName, $lastName, $orderId, $currencyId)
{
    $apiUrl = 'https://api.kopokopo.com/api/v1/incoming_payments';
    $accessToken = $this->getAccessToken();
    $tillNumber = Configuration::get('KOPOKOPO_TILL_NUMBER');
    
    $callbackUrl = $this->context->link->getModuleLink($this->name, 'callback', ['order_id' => (int)$orderId, 'money_id' => (int)$currencyId], true);

    if (strpos($phoneNumber, '254') !== 0) {
        $phoneNumber = '+254' . ltrim($phoneNumber, '0'); // Prepend +254 and remove any leading 0
    }

    $data = [
        'payment_channel' => 'M-PESA STK Push',
        'till_number' => $tillNumber,
        'subscriber' => [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'phone_number' => $phoneNumber,
            'email' => $email,
        ],
        'amount' => [
            'currency' => $currency,
            'value' => $amount
        ],
        'metadata' => [
            'customer_id' => $orderId,
            'reference' => $orderId,
            'notes' => 'Payment for order ' . $orderId,
        ],
        '_links' => [
            'callback_url' => $callbackUrl,
        ]
    ];

    try {
        // Initialize cURL session
        $ch = curl_init($apiUrl);

        // Set cURL options
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); // Return the response as a string
        curl_setopt($ch, CURLOPT_POST, true); // Send the request as a POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data)); // Attach the data in JSON format
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $accessToken,
        ]);
        curl_setopt($ch, CURLOPT_HEADER, true); // Include headers in the output

        // Execute the cURL request
        $response = curl_exec($ch);
        
        // Get HTTP status code
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        
        // Extract headers
        $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
        $header = substr($response, 0, $headerSize);
        $body = substr($response, $headerSize);

        // Close the cURL session
        curl_close($ch);

        // Handle the case where the body is null but headers might contain important information
        if ($httpCode == 201 && empty($body)) {
            // Parse the headers into an array
            $headers = [];
            foreach (explode("\r\n", $header) as $line) {
                $parts = explode(':', $line, 2);
                if (count($parts) == 2) {
                    $headers[trim($parts[0])] = trim($parts[1]);
                }
            }

            // Example: Check for a specific header like 'Location'
            if (isset($headers['Location'])) {
                return 'Payment initiated successfully. Location: ' . $headers['Location'];
            }

            // Optionally return all headers if you want to inspect them
            //return 'Payment initiated successfully. Headers: ' . print_r($headers, true);
            return true;
        }

        // Handle HTTP errors
        if ($httpCode != 201) {
            return 'Failed to initiate payment. HTTP Code: ' . $httpCode . ' - cURL Error: ' . $curlError;
        }

        // Decode JSON response if body is not null
        $responseArray = json_decode($body, true);

        // Check if the response contains the payment URL
        if (isset($responseArray['status']) && $responseArray['status'] === 'success') {
            return $responseArray['data']['paymentUrl'];
        }

        return false;

    } catch (Exception $e) {
        // Handle any exceptions
        return 'Exception: ' . $e->getMessage();
    }
}

    

    private function getAccessToken()
    {
        // Retrieve stored token and its expiry time
        $storedToken = Configuration::get('KOPOKOPO_ACCESS_TOKEN');
        $tokenExpiry = Configuration::get('KOPOKOPO_TOKEN_EXPIRY');

        // Check if the token exists and is still valid
        if ($storedToken && $tokenExpiry && strtotime($tokenExpiry) > time()) {
            return $storedToken;
        }

        // Define API URL and credentials
        $apiUrl = 'https://api.kopokopo.com/oauth/token';
        $clientId = Configuration::get('KOPOKOPO_CLIENT_ID');
        $clientSecret = Configuration::get('KOPOKOPO_CLIENT_SECRET');
        $grantType = 'client_credentials';

        // Prepare POST fields
        $postFields = [
            'client_id' => $clientId,
            'client_secret' => $clientSecret,
            'grant_type' => $grantType,
        ];

        // Initialize cURL session
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($postFields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/x-www-form-urlencoded',
        ]);

        // Execute cURL request
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        // Check for HTTP errors
        if ($httpCode != 200) {
            return 'Failed to obtain access token. HTTP Code: ' . $httpCode . ' - cURL Error: ' . $curlError;
        }

        // Decode JSON response
        $responseArray = json_decode($response, true);

        // Check if the response contains the access token
        if (isset($responseArray['access_token'])) {
            $token = $responseArray['access_token'];
            $expiresIn = $responseArray['expires_in'];
            $expiryDate = date('Y-m-d H:i:s', time() + $expiresIn);

            // Store the new token and its expiry time
            Configuration::updateValue('KOPOKOPO_ACCESS_TOKEN', $token);
            Configuration::updateValue('KOPOKOPO_TOKEN_EXPIRY', $expiryDate);

            return $token;
        }

        // Return error if access token is not found in the response
        return 'Access token not found in response.';
    }

    public function handleCallback($orderId, $status)
    {
        $order = new Order($orderId);
        if ($status === 'success') {
            $order->setCurrentState(Configuration::get('PS_OS_PAYMENT'));
        } else {
            $order->setCurrentState(Configuration::get('PS_OS_ERROR'));
        }
    }
    public function updateOrderStatus($orderId, $status)
    {
        $order = new Order($orderId);
    
        if (!Validate::isLoadedObject($order)) {
            return 'Order not found.';
        }
    
        // Map Kopokopo status to PrestaShop order states
        $statusMapping = [
            'success' => Configuration::get('PS_OS_PAYMENT'), // Paid
            'failed' => Configuration::get('PS_OS_ERROR'),   // Payment error
            // Add other status mappings if needed
        ];
    
        if (isset($statusMapping[$status])) {
            $order->setCurrentState($statusMapping[$status]);
            return 'Order status updated.';
        }
    
        return 'Unknown status provided.';
    }
    
    public function hookModuleRoutes($params)
    {
        return [
            'module-kopokopo-callback' => [
                'controller' => 'callback',
                'rule'       => 'module/kopokopo/callback',
                'keywords'   => [
                    'order_id' => ['regexp' => '[0-9]+', 'param' => 'order_id'],
                ],
                'params'     => [
                    'fc' => 'module',
                    'module' => 'kopokopo',
                ],
            ],
        ];
    }
    
    public function passPaymentStatus($status, $msg){
        $this->context->cookie->status = $status;
        $this->context->cookie->msg = $msg;
        
        $data = ['status' => $status, 'msg' => $msg];
        
        return $data;
    }
}
