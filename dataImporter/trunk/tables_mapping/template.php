<?php
// The goal here is to
// -create your configured model where the following items are defined
// --your pkey
// --childs of this model (if any)
// --association between some db field and some xls fields 
// 
// -optionally you can 
// --set some post read/write functions
// --attach custom data to your model (that could be usefull for post read/write function)

$pkey = "template_id";
$child = array("template_child1", "template_child2");
$fields = array(
	//db field => xls field
	"title" => "foo", //the xls field "foo" will go into the db "title" field  
	"first_name" => "",//no xls field, this will be ignored (not even part of the insert query)
	"middle_name" => "@jean",//@ means that the following string is used. No look up is made. "jean" will be inserted.
	"last_name" => "#my value",//This will be inserted but the custom value for the key "my value" must be defined in the post read function 
	"date_of_birth" => "Date of Birth Date",
	"dob_date_accuracy" => "Date of Birth Accuracy",
	"marital_status" => array("married" => new ValueDomain("marital_status", ValueDomain::ALLOW_BLANK, ValueDomain::CASE_INSENSITIVE)),//will read the xls married column and validates if it matches a value in the marital_status value_domain. If not -> warning. Tolerates blank values. Case insensitive.
	"language_preferred" => array("language" => array("fr" => "french", "en" => "english"))//will read the xls language column and use it as a key into this array. The matching value will be inserted. If there is no match a warning is thrown.
);

//see the Model class definition for more info
$model = new Model(0, $pkey, $child, true, NULL, NULL, 'participants', $fields);

//we can then attach post read/write functions
$model->post_read_function = 'postTemplateRead';
$model->post_write_function = 'postTemplateWrite';
$model->custom_data = array(
	"date_fields" => array(
		$fields["date_of_birth"] => $fields["dob_date_accuracy"]
	) 
);

//adding this model to the config
Config::$models['template'] = $model;

//defining the post read function
/**
 * Do some stuff
 * @param Model $m The model that was just read
 */
function postTemplateRead(Model $m){
	//do some stuff
	//if you need the db connection, you can use Config::$db_connection to get it
	excelDateFix($m);//will fix the dates read in an exel file. Uses the custom_data array. See the function definition for more info.
	$m->values['my value'] = "...";//custom value definition
}

//defining the post write function
//this function will be called right after the insert query. You have all query date + the auto increment id
/**
 * Do some stuff
 * @param Model $m The model that was just writter in db
 * @param unknown_type $template_id The auto increment id generated by the insert
 */
function postTemplateWrite(Model $m, $template_id){
	//do some stuff
	//if you need the db connection, you can use Config::$db_connection to get it
}
