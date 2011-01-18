<?php
class Model{
	public $file;
	public $pkey;
	public $child;
	public $save_id;
	public $parent_key;
	public $table;//table name
	public $fields;
	public $additional_queries	= null;
	public $ask_parent			= null;
	
	public $parent_id			= null;
	public $file_handler		= null;
	public $query_insert		= null;
	public $query_insert_revs	= null;
	public $keys				= null;
	public $line				= null;
	public $values				= null;
	public $parent_key_value	= null;
	public $csv_reference		= null;
	
	public $custom_data			= null;
	public $post_read_function	= null;
	
	function __construct($file, $pkey, array $child, $save_id, $parent_key, $table, array $fields){
		$this->file = $file;
		$this->pkey = $pkey;
		$this->child = $child;
		$this->save_id = $save_id;
		$this->parent_key = $parent_key;
		
		$this->table = $table;
		$this->fields = $fields;
	}
}