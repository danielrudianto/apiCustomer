<?php
    defined('BASEPATH') OR exit('No direct script access allowed');

    class Sales_order_model extends CI_Model {
        private $table_sales_order = 'code_sales_order';
        
        public $id;
        public $customer_id;
        public $name;
        public $date;
        public $seller;
        public $taxing;
        public $is_confirm;
        public $guid;
        public $invoicing_method;
        public $created_by;
        public $note;
        public $payment;
        
        public $customer_name;
        public $customer_address;
        public $customer_city;
        
        public function getByName($name)
        {
            $this->db->where('name', $name);
            $query			= $this->db->get($this->table_sales_order);
            $result			= $query->row();

            if($result != null){
                $response		= array();
                $query			= $this->db->query("
                    SELECT item.name, item.reference, sales_order.quantity, sales_order.sent, sales_order.discount, price_list.price_list
                    FROM sales_order
                    JOIN price_list ON sales_order.price_list_id = price_list.id
                    JOIN item ON price_list.item_id = item.id
                    WHERE sales_order.code_sales_order_id = '$result->id'
                ");

                $items			= $query->result();
                $response		= (array) $result;
                $response['items']	= (array) $items;

                return $response;
            } else {
                return null;
            }
        }

        public function getByCustomerUID($customerUID, $offset = 0){
            $query          = $this->db->query("
                SELECT code_sales_order.name, code_sales_order.date
                FROM code_sales_order
                JOIN customer ON code_sales_order.customer_id = customer.id
                WHERE customer.uid = '$customerUID'
                AND is_confirm = 1
                ORDER BY code_sales_order.date DESC
                LIMIT 10 OFFSET $offset
            ");

            $result         = $query->result();
            return $result;
        }

        public function countByCustomerUID($customerUID){
            $query          = $this->db->query("
                SELECT code_sales_order.name, code_sales_order.date
                FROM code_sales_order
                JOIN customer ON code_sales_order.customer_id = customer.id
                WHERE customer.uid = '$customerUID'
                AND is_confirm = 1
            ");

            $result         = $query->num_rows();
            return $result;
        }
    }
?>