<?php
    class Delivery_order_detail_model extends CI_Model {
        private $table_delivery_order = 'delivery_order';
            
        public $id;
        public $sales_order_id;
        public $code_delivery_order_id;
        public $quantity;
        
        public $item_id;
        public $customer_id;
        
        public $reference;
        public $name;
        public $do_name;
        public $so_name;
        public $customer_name;
        public $address;
        public $city;
        public $date;
        
        public function __construct()
        {
            parent::__construct();
        }

        public function getByInvoiceId($invoiceId)
		{
			$this->db->select('delivery_order.*, item.name, item.reference, item.id as item_id, price_list.price_list, sales_order.discount, sales_order.quantity as ordered, sales_order.sent');
			$this->db->from('delivery_order');
			$this->db->join('sales_order', 'delivery_order.sales_order_id = sales_order.id');
			$this->db->join('price_list', 'price_list.id = sales_order.price_list_id');
			$this->db->join('item', 'price_list.item_id = item.id');
			$this->db->join('code_delivery_order', 'delivery_order.code_delivery_order_id = code_delivery_order.id');
			$this->db->where('code_delivery_order.invoice_id', $invoiceId);
			
			$query		= $this->db->get();
			$result		= $query->result();

			return $result;
		}
    }
?>