<?php
require_once("model.php");
class MasterDetailModel extends Model{
	public $detail_table;
	public $detail_master_fkey;
	public $detail_fields;
	
	public $query_detail_insert;
	public $query_detail_insert_revs;
	
	function __construct($file, $pkey, array $child, $save_id, $parent_key, $table, array $fields, $detail_table, $detail_master_fkey, array $detail_fields){
		parent::__construct($file, $pkey, $child, $save_id, $parent_key, $table, $fields);
		$this->detail_table = $detail_table;
		$this->detail_master_fkey = $detail_master_fkey;
		$this->detail_fields = $detail_fields;
	}
}