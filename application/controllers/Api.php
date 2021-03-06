<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Api extends CI_Controller {
	function __construct(){
		parent::__construct();

        $this->load->helper('url');
        require "third_party/JWT/CreatorJWT.php";

        $this->JWT = new CreatorJwt();
    }

    public function login()
    {
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $postdata = file_get_contents("php://input");
        $request        = json_decode($postdata);
        $username       = $request->username;
        $password       = $request->password;

        $this->load->model("Customer_model");
        $result     = $this->Customer_model->customerLogin($username, $password);
        if($result == null){
            $response       = array(
                "status" => "error",
                "message" => "Failed to log in.",
                "user" => array()
            );

            echo 0;
        } else {
            $completeAddress = "";
            $completeAddress .= $result->address;

            if($result->number != NULL){
                $completeAddress	.= ' No. ' . $result->number;
            }
            
            if($result->block != NULL && $result->block != "000" && $result->block != ""){
                $completeAddress	.= ' Blok ' . $result->block;
            }
        
            if($result->rt != '000'){
                $completeAddress	.= ' RT ' . $result->rt;
            }
            
            if($result->rw != '000' && $result->rw != '000'){
                $completeAddress	.= ' /RW ' . $result->rw;
            }
            
            if($result->postal_code != NULL){
                $completeAddress	.= ', ' . $result->postal_code;
            }

            $completeAddress .= ", Kel. " . $result->kelurahan;
            $completeAddress .= ", Kec. " . $result->kecamatan;
            $response        = array(
                "status" => "success",
                "message" => "Successfully logged in.",
                "user" => array(
                    "name" => $result->name,
                    "address" => $completeAddress,
                    "pic" => $result->pic_name,
                    "city" => $result->city,
                    "phone_number" => $result->phone_number,
                    "uid" => $result->uid
                )
            );

            $tokenData = $response["user"];
            $token = $this->JWT->GenerateToken($tokenData);
            echo json_encode(array('Token'=>$token));
        }
    }

    public function getCustomerData()
    {
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $headers = apache_request_headers();
        $authorization = substr($headers['Authorization'], 7, 500);
        $data       = $this->JWT->DecodeToken($authorization);

        $result           = array();
        $result['user']   = $data;
        $result['overdue']  = 0;
        $result['due']      = 0;
        
        $this->load->model("Invoice_model");
        $statusArray                = $this->Invoice_model->getStatusByCustomerUID($data['uid']);
        foreach($statusArray as $status){
            if($status->due == 0){
                $result['due']      = $status->value;
            } else {
                $result['overdue']  = $status->value;
            }
        }

        $this->load->model("Internal_bank_account_model");
        $result['account']        = $this->Internal_bank_account_model->getShownItems();
        echo json_encode($result);
    }

    public function getCustomerInvoices($page = 1)
    {
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $headers = apache_request_headers();
        $authorization = substr($headers['Authorization'], 7, 500);
        $data       = $this->JWT->DecodeToken($authorization);
        $uid        = $data['uid'];
        $offset     = ($page - 1) * 10;

        $this->load->model("Invoice_model");
        $data           = $this->Invoice_model->getIncompletedTransactionByCustomerUID($uid, $offset);
        echo(json_encode($data));
    }

    public function getCompleteCustomerInvoices($page = 1)
    {
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Content-Type:application/json");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding");

        $headers = apache_request_headers();
        $authorization = substr($headers['Authorization'], 7, 500);
        $data       = $this->JWT->DecodeToken($authorization);
        $uid        = $data['uid'];
        $offset     = ($page - 1) * 10;

        $this->load->model("Invoice_model");
        $data['invoices']           = $this->Invoice_model->getCompleteTransactionByCustomerUID($uid, $offset);
        $data['records']            = $this->Invoice_model->countCompleteTransactionByCustomerUID($uid);
        echo(json_encode($data));
    }

    public function countCustomerInvoice(){
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $headers = apache_request_headers();
        $authorization = substr($headers['Authorization'], 7, 500);
        $data       = $this->JWT->DecodeToken($authorization);
        $uid        = $data['uid'];

        $this->load->model("Invoice_model");
        $data           = $this->Invoice_model->countIncompletedTransactionByCustomerUID($uid);
        echo $data;
    }

    public function getCustomerPayments($page = 1){
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $headers = apache_request_headers();
        $authorization = substr($headers['Authorization'], 7, 500);
        $data       = $this->JWT->DecodeToken($authorization);
        $uid        = $data['uid'];
        $offset     = ($page - 1) * 10;

        $this->load->model("Bank_model");
        $result['payments']       = $this->Bank_model->getCustomerPaymentsByCustomerUID($uid, $offset);
        $result['records']        = $this->Bank_model->countCustomerPaymentsByCustomerUID($uid);
        echo json_encode($result);
    }

    public function getInvoiceByName($name){
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $headers = apache_request_headers();
        $authorization = substr($headers['Authorization'], 7, 500);
        $data       = $this->JWT->DecodeToken($authorization);
        $uid        = $data['uid'];
        
        $this->load->model("Invoice_model");
        $result['general']     = $this->Invoice_model->getInvoiceByName($name, $uid);
        
        $this->load->model("Delivery_order_detail_model");
        $result['detail']       = $this->Delivery_order_detail_model->getByInvoiceId($result['general']->id);
        echo json_encode($result);
    }


	public function register()
	{
		header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $postdata = file_get_contents("php://input");
        $request    = json_decode($postdata);

		$this->load->model("Customer_model");
		$result			= $this->Customer_model->registerCustomer($request->username, $request->password);
		echo $result;
	}

	public function getCustomerSales()
	{
		header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');
        $postdata = file_get_contents("php://input");
		$customerUID         = $postdata;
		$this->load->model("Customer_sales_model");
		$result		= $this->Customer_sales_model->getByCustomerUID($customerUID);
		echo json_encode($result);
    }
    
    public function getCustomerSalesOrderHistory()
    {
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $currentMonth       = date('m');
        $currentYear        = date('Y');
        $currentDate        = mktime(0,0,0,$currentMonth, 1, $currentYear);
        $postdata = file_get_contents("php://input");
        $customerUID         = $postdata;
        $this->load->model("Sales_order_model");
        $data       = $this->Sales_order_model->getByCustomerUIDMonthly($customerUID);
        $result     = array();
        foreach($data as $datum){
            $year       = $datum->year;
            $month      = $datum->month;
            $value      = $datum->value;
            $sentValue  = $datum->sentValue;

            $difference = round(($currentDate - mktime(0,0,0,$month, 1, $year)) / (60 * 60 * 24 * 30));
            $result[$difference] = array(
                "value" => $value,
                "sent" => $sentValue
            );
            continue;
        }
        echo json_encode($result);
    }

    public function getSalesOrderHistory()
    {
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');
        
        $id             = $this->input->get('id');
        $this->load->model("Sales_order_model");
        $data           = $this->Sales_order_model->getByCustomerUid($id);
        echo json_encode($data);
    }

    public function getSalesOrderByName($name)
    {
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $this->load->model("Sales_order_model");
        $data           = $this->Sales_order_model->getByName($name);
        echo json_encode($data);
    }

    public function getCustomerSalesOrder($page = 1)
    {
        header('Access-Control-Allow-Origin: https://customer.dutasaptae.management');
        header("Access-Control-Allow-Methods: *");
        header("Access-Control-Allow-Headers: Content-Type, Content-Length, Accept-Encoding, Access-Control-Allow-Origin");
        header('Content-Type: application/json, charset=utf-8');

        $headers = apache_request_headers();
        $authorization = substr($headers['Authorization'], 7, 500);
        $data       = $this->JWT->DecodeToken($authorization);
        $uid        = $data['uid'];
        $offset     = ($page - 1) * 10;

        $this->load->model("Sales_order_model");
        $response['salesOrders']           = $this->Sales_order_model->getByCustomerUID($uid, $offset);
        $response['records']                = $this->Sales_order_model->countByCustomerUID($uid);
        echo(json_encode($response));
    }
}
?>
