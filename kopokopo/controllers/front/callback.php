<?php
class KopokopoCallbackModuleFrontController extends ModuleFrontController
{
    public function initContent()
    {
        parent::initContent();
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        
        $orderId = isset($_GET['order_id']) ? intval($_GET['order_id']) : null;
        $currencyId = isset($_GET['money_id']) ? intval($_GET['money_id']) : null;
        
        $cart = new Cart($orderId);
        
        if (Validate::isLoadedObject($cart)) {
            $customer = new Customer($cart->id_customer);
            
            if(Validate::isLoadedObject($customer)) {
                $secureKey = $customer->secure_key;
            }
        }
        /*if($_SERVER['REQUEST_METHOD'] === 'POST'){
            // Retrieve raw POST data (assuming Kopokopo sends JSON)
            $jsonData = Tools::file_get_contents('php://input');
        
            var_dump($jsonData);
            die;
            // Decode the JSON data into an associative array
            $data = json_decode($jsonData, true);
        
            // Validate the JSON data
            if (json_last_error() === JSON_ERROR_NONE && !empty($data)) {
                // Extract relevant information
                $orderId = isset($data['metadata']['customer_id']) ? (int)$data['metadata']['customer_id'] : 0;
                $status = isset($data['status']) ? $data['status'] : '';
        
                if ($orderId && $status) {
                    // Update the order status based on the payment result
                    $this->module->updateOrderStatus($orderId, $status);
        
                    // Respond with a success message
                    header('HTTP/1.1 200 OK');
                    echo json_encode(['status' => 'success']);
                    exit;
                } else {
                    // Log invalid data
                    PrestaShopLogger::addLog('Invalid callback data received: ' . $jsonData, 3);
                }
            } else {
                // Log JSON parsing error
                PrestaShopLogger::addLog('Failed to decode JSON data: ' . json_last_error_msg(), 3);
            }
        
            // Respond with an error if data is invalid
            header('HTTP/1.1 400 Bad Request');
            echo json_encode(['status' => 'error', 'message' => 'Invalid data']);
            exit;
        }*/
        
        // Get the raw POST data from the input stream
        $json = file_get_contents('php://input');

        // Decode the JSON data into a PHP array
        $data = json_decode($json, true);
        
        if (json_last_error() === JSON_ERROR_NONE) {
            // Log the JSON response
            $this->logResponse($json);
            $status = $data['data']['attributes']['status'];
            
            $paymentData = $data['data']['attributes']['event']['resource'];
            
            if($status == "Success") {
                $_SESSION['status'] = 'success';
                $_SESSION['msg'] = 'Payment was successfull!';
                
                $this->module->validateOrder(
                    (int) $orderId,
                    (int) Configuration::get('PS_OS_PAYMENT'),
                    (float) $paymentData['amount'],
                    $this->module->displayName,
                    null,
                    [],
                    (int) $currencyId,
                    false,
                    $secureKey
                );
                $this->storePaymentStatus($orderId, $status, $paymentData);
                // $message = "Payment was Successful!";
                // $data = ['status' => 'success', 'message' => 'Payment was successfull!'];
                // $response = json_encode($data);
                // // $this->showMessage($data);
                // $redirectUrl = $this->context->link->getModuleLink($this->module->name, 'paymentstatus', ['message' => $message], true);
                // $this->logResponse("URL: ". $redirectUrl);
                // $statusMessage = 'Your payment was successful!';
                // $statusType = 'success';
                //$_SESSION['status'] = $statusMessage;
                //Tools::setValue('status', $statusMessage);
            } else {
                $status = $_SESSION['status'] = 'danger';
                $msg = $_SESSION['msg'] = 'Payment was not successfull!';
                $this->logResponse('Error: Could not process payment: ' . $json);
                
                $this->storePaymentStatus($orderId, $status, $paymentData);
                //$this->module->passPaymentStatus($status, $msg);
                // $response = ['status' => 'danger', 'message' => 'Payment was not successfull!'];
                // $data = json_encode($response);
                // // $this->showMessage($data);
                // $message = "Payment Failed!";
                // $redirectUrl = $this->context->link->getModuleLink($this->module->name, 'paymentstatus', ['message' => $message], true);
                // $this->logResponse("URL: ". $redirectUrl);
                // header("Location: ".$redirectUrl);
                // $statusMessage = 'Payment failed..';
                // $statusType = 'danger';
                // $_SESSION['status'] = $statusMessage;
                
            }

            // Send a success response back to the API
            header('Content-Type: application/json');
            echo json_encode(['status' => 'success', 'message' => 'Payment received']);
            
        } else {
            // Log the error
            $this->logResponse('Invalid JSON received: ' . $json);

            // Send an error response
            header('Content-Type: application/json');
            echo json_encode(['status' => 'error', 'message' => 'Invalid JSON received']);
        }

        // Terminate the script
        die();
    }
    
    /**
     * Log the received JSON response to a file.
     *
     * @param string $logMessage
     */
    private function logResponse($logMessage)
    {
        // Define the log file path
        $logFile = _PS_MODULE_DIR_ . 'kopokopo/logs/callback_log.txt';

        // Ensure the logs directory exists
        if (!file_exists(dirname($logFile))) {
            mkdir(dirname($logFile), 0755, true);
        }

        // Create a timestamp
        $timestamp = date('Y-m-d H:i:s');

        // Format the log entry
        $logEntry = "[$timestamp] $logMessage" . PHP_EOL;

        // Append the log entry to the file
        file_put_contents($logFile, $logEntry, FILE_APPEND);
    }
    public function showMessage($data){
        $this->logResponse("Show Message Endpoint hit!");
        $this->logResponse($data);
        /*$c_data = json_decode($data,true);
        $this->logResponse($c_data);
        $message = $c_data['message'];
        $status = $c_data['status'];*/
        $status = "success";
        $message = "I'm done!";
        
        $this->context->smarty->assign(['message' => $message, 'status' => $status]);
        $this->setTemplate('module:kopokopo/views/templates/front/paymentstatus.tpl');
    }
    private function storePaymentStatus($orderId, $status, $paymentData)
    {
        $db = Db::getInstance();

        // Prepare the query
        $query = "INSERT INTO " . _DB_PREFIX_ . "kopokopo_payment_status (order_id, status, amount, response_data, date_add)
                  VALUES (" . (int)$orderId . ", '" . pSQL($status) . "', " . (float)$paymentData['amount'] . ", '" . pSQL(json_encode($paymentData)) . "', NOW())
                  ON DUPLICATE KEY UPDATE status = VALUES(status), amount = VALUES(amount), response_data = VALUES(response_data), date_add = NOW()";
        
        // Execute the query
        $db->execute($query);
    }
}
