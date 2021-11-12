<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Internal_bank_account_model extends CI_Model {
	private $table_account = 'internal_bank_account';
		
		public $id;
		public $name;
		public $number;
		public $bank;
		public $branch;
		
		public function __construct()
		{
			parent::__construct();
		}

		public function getShownItems(){
			$this->db->where("is_shown", 1);
			$this->db->order_by('name', 'asc');
			$this->db->order_by('number', 'asc');

			$query		= $this->db->get($this->table_account);
			$result		= $query->result();
			return $result;
		}
}
