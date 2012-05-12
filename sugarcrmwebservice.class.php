<?php
/* 
credits for the class
http://kovshenin.com/2010/05/lead-generation-forms-with-wordpress-and-sugarcrm-2287/
*/
class SugarCRMWebServices {

	var $username;
	var $password;
	var $session;

	var $soap;
	
	function __construct()
	//function SugarCRMWebServices()
	{
	  $options = get_option('wp_sugar_options');
		$this->username = $options['wp_sugar_user'];
		$this->password = $options['wp_sugar_pass'];
		$this->soap = new nusoapclient($options['wp_sugar_url']);
	}

	function login()
	{
		$result = $this->soap->call(
		  'login', 
		  array(
		    'user_auth' => array(
		      'user_name' => $this->username,
		      'password' => md5($this->password),
		      'version' => '.01'
		      ),
		    'application_name' => 'WP-Soap'
		    )
		  );
		$this->session = $result['id'];
		return $result['error']['description'];
	}
	
	function create_lead($data)
	{
		$name_value_list = array();
		foreach($data as $key => $value)
			array_push($name_value_list, array('name' => $key, 'value' => $value));

		$result = $this->soap->call('set_entry', array(
			'session' => $this->session,
			'module_name' => 'Leads',
			'name_value_list' => $name_value_list,
		));
		return $result;
	}
		
	function lead_email_exists( $email )
	{
		$result = $this->soap->call('get_entry_list', array(
			'session' 				=> $this->session,
			'module_name' 		=> 'Leads',
			'query'						=> "leads.id in (SELECT eabr.bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (ea.id = eabr.email_address_id) WHERE eabr.deleted=0 and ea.email_address LIKE '" . $email . "%')",
		));
		return ($result["result_count"]) ? '1' : '0' ;
	}
	
	function account_email_exists( $email )
	{
		$result = $this->soap->call('get_entry_list', array(
			'session' 				=> $this->session,
			'module_name' 		=> 'Accounts',
			'query'						=> "accounts.id in (SELECT eabr.bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (ea.id = eabr.email_address_id) WHERE eabr.deleted=0 and ea.email_address LIKE '" . $email . "%')",
		));
		return ($result["result_count"]) ? true : false ;
	}
	
	function getLeadByEmail( $email )
	{
		$result = $this->soap->call('get_entry_list', array(
			'session' 				=> $this->session,
			'module_name' 		=> 'Leads',
			'query'						=> "leads.id in (SELECT eabr.bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (ea.id = eabr.email_address_id) WHERE eabr.deleted=0 and ea.email_address LIKE '" . $email . "%')",
		));
		if($result["result_count"] > 0){
			foreach($result['entry_list'] as $record){
        return (object)$this->returnRecordArray($record['name_value_list']);  // return an object 
	    }
		}				
	}
	
	function getAccountByEmail( $email )
	{
		$result = $this->soap->call('get_entry_list', array(
			'session' 				=> $this->session,
			'module_name' 		=> 'Accounts',
			'query'						=> "accounts.id in (SELECT eabr.bean_id FROM email_addr_bean_rel eabr JOIN email_addresses ea ON (ea.id = eabr.email_address_id) WHERE eabr.deleted=0 and ea.email_address LIKE '" . $email . "%')",
		));
			
		if($result["result_count"] > 0){
			foreach($result['entry_list'] as $record){
	      return (object)$this->returnRecordArray($record['name_value_list']);  // return an object
	    }
		}				
	}
	
	private function returnRecordArray($array){
	    $a=array();
	    while(list($n,$v)=each($array)){
	        $a[$v['name']]=$v['value'];
	    }
	    return $a;
	} 
	
	function updateAccountData($id, $data)
	{
		$newdata = array_merge($data, array('id' => $id));
		$this->createAccount($newdata);
	}
}