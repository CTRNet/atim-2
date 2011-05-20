<?php
require_once("model.php");
class MasterDetailModel extends Model{
	public $detail_table;
	public $detail_master_fkey;
	public $detail_fields;
	
	public $query_detail_insert			= null;
	public $query_detail_insert_revs	= null;
	public $detail_schema 				= array();
	
	/**
	 * Create a master/detail model configuration
	 * @param string $file The file path of a csv file or the index of a spreadsheet
	 * @param string $pkey The primary key of the sheet
	 * @param array $child The name of the childs of this model
	 * @param boolean $save_id Wheter or not to save the pkey/incremented id/model name association into the id_linking table
	 * @param string $parent_key The parent key to iterate on
	 * @param string $table The name of the database master table to insert into
	 * @param array $fields The db fields/file fields association array for the master model
	 * @param string $detail_table The detail table name
	 * @param string $detail_master_fkey The db field name of the detail table that is used as a key to reach the master
	 * @param array $detail_fields The db fields/file fields association array for the detail model
	 */
	function __construct($file, $pkey, array $child, $save_id, $parent_key, $table, array $fields, $detail_table, $detail_master_fkey, array $detail_fields){
		parent::__construct($file, $pkey, $child, $save_id, $parent_key, $table, $fields);
		$this->detail_table = $detail_table;
		$this->detail_master_fkey = $detail_master_fkey;
		
		$this->detail_fields = $detail_fields;
	}
}