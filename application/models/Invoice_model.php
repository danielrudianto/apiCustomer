<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Invoice_model extends CI_Model {
	private $table_invoice = 'invoice';
		
		public $id;
		public $name;
		public $value;
		public $date;
		public $information;
		public $is_confirm;
		public $is_done;
		public $taxInvoice;
		public $lastBillingDate;
		public $nextBillingDate;
		public $opponent_id;
		public $customer_id;
		public $discount;
		public $delivery;
		
		public function __construct()
		{
			parent::__construct();
		}
		public function getStatusByCustomerUID($customerUID){
			$today			= date("Y-m-d");
			$query			= $this->db->query("
				SELECT SUM(invoice.value + invoice.delivery - invoice.discount - COALESCE(receivableTable.value, 0)) AS value, IF(ADDDATE(invoice.date, INTERVAL a.payment DAY) < '$today', '1', '0') AS due
				FROM invoice
				JOIN code_delivery_order ON invoice.id = code_delivery_order.invoice_id
				JOIN (
					SELECT DISTINCT(delivery_order.code_delivery_order_id) AS id, code_sales_order.payment
					FROM delivery_order
					JOIN sales_order ON delivery_order.sales_order_id = sales_order.id
					JOIN code_sales_order ON sales_order.code_sales_order_id = code_sales_order.id
					JOIN customer ON code_sales_order.customer_id = customer.id
					WHERE customer.uid = '$customerUID'
				) AS a
				ON code_delivery_order.id = a.id
				LEFT JOIN (
					SELECT SUM(receivable.value) AS value, invoice_id
					FROM receivable
					GROUP BY receivable.invoice_id
				) AS receivableTable
				ON invoice.id = receivableTable.invoice_id
				WHERE invoice.is_confirm = 1
				AND invoice.is_done = 0
				GROUP BY due
				ORDER BY due ASC
			");
	
			$result			= $query->result();
			return $result;
		}
	
		public function getInvoiceByName($name, $customerUID)
		{
			$query		= $this->db->query("
				SELECT invoice.*, a.payment,COALESCE(a.name, 'none') AS seller
				FROM invoice
				JOIN code_delivery_order ON invoice.id = code_delivery_order.invoice_id
				JOIN (
					SELECT DISTINCT(delivery_order.code_delivery_order_id) AS id, code_sales_order.payment, sellerTable.name
					FROM delivery_order
					JOIN sales_order ON delivery_order.sales_order_id = sales_order.id
					JOIN code_sales_order ON sales_order.code_sales_order_id = code_sales_order.id
					JOIN customer ON code_sales_order.customer_id = customer.id
					LEFT JOIN (
						SELECT users.name, users.id
						FROM users
					) as sellerTable
					ON code_sales_order.seller = sellerTable.id
					WHERE customer.uid = '$customerUID'
				) AS a
				ON code_delivery_order.id = a.id
				WHERE invoice.name = '$name'
			");
	
			$result			= $query->row();
			return $result;
		}
	
		public function getIncompletedTransactionByCustomerUID($customerUID, $offset = 0, $limit = 10)
		{
			$today			= date("Y-m-d");
			$query		= $this->db->query("
				SELECT invoice.*, COALESCE(receivableTable.value,0) as paid, IF(ADDDATE(invoice.date, INTERVAL deliveryOrderTable.payment DAY) < '$today', '1', '0') AS due, ADDDATE(invoice.date, INTERVAL deliveryOrderTable.payment DAY) AS dueDate
				FROM invoice
				LEFT JOIN (
					SELECT SUM(value) as value, invoice_id FROM receivable
					GROUP BY invoice_id
				) AS receivableTable
				ON receivableTable.invoice_id = invoice.id
				JOIN (
					SELECT DISTINCT(code_delivery_order.invoice_id) as id, code_sales_order.payment
					FROM code_delivery_order
					JOIN delivery_order ON delivery_order.code_delivery_order_id = code_delivery_order.id
					JOIN sales_order ON delivery_order.sales_order_id = sales_order.id
					JOIN code_sales_order ON sales_order.code_sales_order_id = code_sales_order.id
					JOIN customer ON customer.id = code_sales_order.customer_id
					WHERE customer.uid = '$customerUID'
					UNION (
						SELECT invoice.id , customer.term_of_payment AS payment
						FROM invoice
						JOIN customer ON invoice.customer_id = customer.id
						WHERE invoice.is_done = '0'
						AND invoice.is_confirm = '1'
						AND customer.uid = '$customerUID'						
					)
				) AS deliveryOrderTable
				ON invoice.id = deliveryOrderTable.id
				WHERE invoice.is_done = '0'
				AND invoice.is_confirm = '1'
				ORDER BY invoice.date ASC
				LIMIT $limit OFFSET $offset
			");
	
			$result	= $query->result();
			return $result;
		}
	
		public function getCompleteTransactionByCustomerUID($customerUID, $offset = 0, $limit = 10)
		{
			$query		= $this->db->query("
				SELECT invoice.*, COALESCE(receivableTable.value,0) as paid
				FROM invoice
				LEFT JOIN (
					SELECT SUM(value) as value, invoice_id FROM receivable
					GROUP BY invoice_id
				) AS receivableTable
				ON receivableTable.invoice_id = invoice.id
				WHERE invoice.id IN (
					SELECT DISTINCT(code_delivery_order.invoice_id) as id
					FROM code_delivery_order
					JOIN delivery_order ON delivery_order.code_delivery_order_id = code_delivery_order.id
					JOIN sales_order ON delivery_order.sales_order_id = sales_order.id
					JOIN code_sales_order ON sales_order.code_sales_order_id = code_sales_order.id
					JOIN customer ON customer.id = code_sales_order.customer_id
					WHERE customer.uid = '$customerUID'
					UNION (
						SELECT invoice.id 
						FROM invoice
						JOIN customer ON invoice.customer_id = customer.id
						WHERE invoice.is_done = '0'
						AND invoice.is_confirm = '1'
						AND customer.uid = '$customerUID'						
					)
				)
				AND invoice.is_confirm = '1'
				ORDER BY invoice.date ASC
				LIMIT $limit OFFSET $offset
			");
	
			$result	= $query->result();
			return $result;
		}
	
		public function countIncompletedTransactionByCustomerUID($customerUID){
			$query		= $this->db->query("
				SELECT invoice.id
				FROM invoice
				LEFT JOIN (
					SELECT SUM(value) as value, invoice_id FROM receivable
					GROUP BY invoice_id
				) AS receivableTable
				ON receivableTable.invoice_id = invoice.id
				WHERE invoice.id IN (
					SELECT DISTINCT(code_delivery_order.invoice_id) as id
					FROM code_delivery_order
					JOIN delivery_order ON delivery_order.code_delivery_order_id = code_delivery_order.id
					JOIN sales_order ON delivery_order.sales_order_id = sales_order.id
					JOIN code_sales_order ON sales_order.code_sales_order_id = code_sales_order.id
					JOIN customer ON customer.id = code_sales_order.customer_id
					WHERE customer.uid = '$customerUID'
					UNION (
						SELECT invoice.id 
						FROM invoice
						JOIN customer ON invoice.customer_id = customer.id
						WHERE invoice.is_done = '0'
						AND invoice.is_confirm = '1'
						AND customer.uid = '$customerUID'						
					)
				)
				AND invoice.is_done = '0'
				AND invoice.is_confirm = '1'
			");
	
			$result	= $query->num_rows();
			return $result;
		}
	
		public function countCompleteTransactionByCustomerUID($customerUID){
			$query		= $this->db->query("
				SELECT invoice.id
				FROM invoice
				LEFT JOIN (
					SELECT SUM(value) as value, invoice_id FROM receivable
					GROUP BY invoice_id
				) AS receivableTable
				ON receivableTable.invoice_id = invoice.id
				WHERE invoice.id IN (
					SELECT DISTINCT(code_delivery_order.invoice_id) as id
					FROM code_delivery_order
					JOIN delivery_order ON delivery_order.code_delivery_order_id = code_delivery_order.id
					JOIN sales_order ON delivery_order.sales_order_id = sales_order.id
					JOIN code_sales_order ON sales_order.code_sales_order_id = code_sales_order.id
					JOIN customer ON customer.id = code_sales_order.customer_id
					WHERE customer.uid = '$customerUID'
					UNION (
						SELECT invoice.id 
						FROM invoice
						JOIN customer ON invoice.customer_id = customer.id
						WHERE invoice.is_done = '0'
						AND invoice.is_confirm = '1'
						AND customer.uid = '$customerUID'						
					)
				)
				AND invoice.is_confirm = '1'
			");
	
			$result	= $query->num_rows();
			return $result;
		}
	}
?>
