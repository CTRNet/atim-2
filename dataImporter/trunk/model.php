<?php
class Model{
	public $file;
	public $csv_pkey;
	public $child;
	public $save_id;
	public $parent_csv_key;
	public $parent_sql_key;
	public $table;//table name
	public $fields;
	
	public $parent_id			= null;
	public $file_handler		= null;
	public $query_insert		= null;
	public $query_insert_revs	= null;
	public $keys				= null;
	public $line				= null;
	public $values				= null;
	public $schema				= array();
	public $parent_model		= null;
	public $first_read			= true;
	public $last_id				= null;
	public $csv_key_value		= null;
	public $parent_csv_key_value= null;
	
	public $custom_data			= null;
	public $post_read_function	= null;//function that will be called after a line is read. The used Model object will be passed. Returns true on a valid line, false otherwise.
	public $post_write_function	= null;//function that will be called after an insert. The used Model will be passed.
	public $insert_condition_function = null;//function that will be called on loop iteration to see if the line needs to be inserted now or not.
	
	/**
	 * A model configuration
	 * @param string $file The file path of a csv file or the index of a spreadsheet
	 * @param string $csv_pkey The primary key of the sheet
	 * @param array $child The name of the childs of this model
	 * @param boolean $save_id Wheter or not to save the pkey/incremented id/model name association into the id_linking table
	 * @param string $parent_key The parent key to iterate on
	 * @param string $table The name of the database table to insert into
	 * @param array $fields The db fields/file fields association array
	 */
	function __construct($file, $csv_pkey, array $child, $save_id, $parent_sql_key, $parent_csv_key, $table, array $fields){
		$this->file = $file;
		$this->csv_pkey = $csv_pkey;
		$this->child = $child;
		$this->save_id = $save_id;
		$this->parent_sql_key = $parent_sql_key;
		$this->parent_csv_key = $parent_csv_key;
		
		$this->table = $table;
		
		$this->fields = $fields;
	}
}