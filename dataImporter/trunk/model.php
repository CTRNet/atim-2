<?php
class Model{
	public $file;
	public $pkey;
	public $child;
	public $save_id;
	public $parent_key;
	public $table;//table name
	public $fields;
	public $ask_parent			= null;
	
	public $parent_id			= null;
	public $file_handler		= null;
	public $query_insert		= null;
	public $query_insert_revs	= null;
	public $keys				= null;
	public $line				= null;
	public $values				= null;
	public $parent_key_value	= null;
	public $schema				= array();
	
	public $custom_data			= null;
	public $post_read_function	= null;//function that will be called after a line is read. The used Model object will be passed.
	public $post_write_function	= null;//function that will be called after an insert. The used Model + last_id will be passed
	
	/**
	 * A model configuration
	 * @param string $file The file path of a csv file or the index of a spreadsheet
	 * @param string $pkey The primary key of the sheet
	 * @param array $child The name of the childs of this model
	 * @param boolean $save_id Wheter or not to save the pkey/incremented id/model name association into the id_linking table
	 * @param string $parent_key The parent key to iterate on
	 * @param string $table The name of the database table to insert into
	 * @param array $fields The db fields/file fields association array
	 */
	function __construct($file, $pkey, array $child, $save_id, $parent_key, $table, array $fields){
		$this->file = $file;
		$this->pkey = $pkey;
		$this->child = $child;
		$this->save_id = $save_id;
		$this->parent_key = $parent_key;
		
		$this->table = $table;
		
		$this->updateFieldsValueDomain($fields);
		$this->fields = $fields;
	}
	
	protected function updateFieldsValueDomain(&$fields){
		foreach($fields as &$field){
			if(is_array($field)){
				$tmp = current($field);
				if(is_string($tmp)){
					//it's a domain name to fetch
					$field[key($field)] = getValueDomain($tmp);
				}
			}
		}
	}
}