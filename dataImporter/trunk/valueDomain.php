<?php
class ValueDomain{
	const ALLOW_BLANK = 1;
	const DONT_ALLOW_BLANK = 0;
	
	const CASE_SENSITIVE = 1;
	const CASE_INSENSITIVE = 0;
	
	private static $domain_cache = array();
	
	var $domain_name = null;
	var $case_sensitive = null;
	var $values = null;
	
	function __construct($domain_name, $allow_blank, $case_sensitive){
		$this->domain_name = $domain_name;
		$this->case_sensitive = $case_sensitive;
		
		$tmp = null;
		if(array_key_exists($domain_name, self::$domain_cache)){
			$tmp = array_merge(self::$domain_cache);
		}else{
			$tmp = getValueDomain($domain_name);
			self::$domain_cache = array_merge($tmp);
		}
		
		if($case_sensitive == self::CASE_INSENSITIVE){
			$this->values = array();
			foreach($tmp as $key => $val){
				$lower_key = strtolower($key);
				if(array_key_exists($lower_key, $this->values)){
					echo "WARNING: ignoring value [".$val."] in value domain [".$domain_name."] because the case sentivity setting makes it conflict with [".$this->values[$lower_key]."]\n";
				}else{
					$this->values[strtolower($key)] = $val;
				}
			}
		}else{
			$this->values = $tmp;
		}
		
		if($allow_blank){
			if(array_key_exists("", $this->values)){
				echo "WARNING: Blank was not added to value domain [".$domain_name."] because it already contains an association to it\n";
			}else{
				$this->values[""] = "";
			}
		}
	}
}