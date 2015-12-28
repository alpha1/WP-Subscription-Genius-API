<?php
class wp_subscription_genius_api {
	const ENDPOINT = "https://api.subscriptiongenius.com/";
	protected $api_version = "2";
	protected $api_key = "";
	protected $api_password = "";
	protected $prevent_future = false;
	public	  $errors = array();
	public	  $headers = array();
	protected $cache = false; //
	protected $customfields = null;
	//TODO get subscribers with X issues left

	function __construct( $api_key = false, $api_password = false ){
		if($api_key !== false){
			$this->set_api_key( $api_key );
		}
		if($api_password !== false){
			$this->set_api_password( $api_password );
		}
	}

	function get_api_url($resource = ""){;
		return self::ENDPOINT . $this->api_version .'/'. $resource .'/';
	}

	function setup_auth_header(){
		$this->headers = array( 'Authorization' => 'Basic ' . base64_encode($this->get_api_key() .":". $this->get_api_password()) );
	}

	function get_headers(){
		return $this->headers;
	}

	public function set_api_key( $key ){
		$this->api_key = $key;
	}

	public function get_api_key(){
		return $this->api_key;
	}

	public function set_api_password( $password ){
		$this->api_password = $password;
	}

	public function get_api_password(){
		return $this->api_password;
	}

	function set_cache( $cache_length = 3600){
		//valid options are int or bool. True should be assumed to be 60
		if(is_int($cache_length) || is_bool($cache_length)){
			$this->cache = $cache_length;
			return true;
		} else {
			return false;
		}
	}
	
	public static function maybe_is_subscription_genius_id( $value ){
		if( (!empty( $value ) || !is_null( $value ) || ( false !== $value ) ) && ( intval( $value ) == $value ) ){
			return true;
		} else {	
			return false;
		}
	}

	public function maybe_is_subscriber_id( $subscriber_id = ""){
		if( self::maybe_is_subscription_genius_id( $subscriber_id ) ){
			return $subscriber_id;
		} else {
			return false;
		}
	}

	public function send_get_request( $resource = "", array $data = array() ){
		$this->setup_auth_header();
		$data_out = array(
		'method'		=> 'GET',
		'compress'		=> true,
		'decompress'	=> true,
		'headers'		=> $this->get_headers(),
		);
		$url = add_query_arg( $data, $this->get_api_url( $resource ) );
		//TODO: validate url?
		if($url !== false){
			$result = wp_safe_remote_get( $url, $data_out );
			$json = wp_remote_retrieve_body( $result );
			unset($this->headers);
			return array(
				'code'	=> wp_remote_retrieve_response_code( $result ),
				'data'	=> json_decode( $json, true ),
				'json'	=> $json
			);
		} else {
			unset($this->headers);
			//what to do if the url is false
		}
	}

	public function send_post_request( $resource = "", array $data = array(), $query = array() ){
		$this->setup_auth_header();
		$data_out = array(
		'method'		=> 'POST',
		'compress'		=> true,
		'decompress'	=> true,
		'headers'		=> $this->get_headers(),
		'body' 			=> http_build_query( $data,'', '&' ),
		);
		$url = add_query_arg( $query, $this->get_api_url( $resource ) );
		//$url = ( $data, $this->get_api_url( $resource ) );
		//TODO: validate url?
		if($url !== false){
			$result = wp_safe_remote_post( $url, $data_out );
			$json = wp_remote_retrieve_body( $result );
			unset($this->headers);
			return array(
				'code'	=> wp_remote_retrieve_response_code( $result ),
				'data'	=> json_decode( $json, true ),
				'json'	=> $json
			);
		} else {
			unset($this->headers);
			//what to do if the url is false
		}
	}
	
	public function send_put_request( $resource ="", array $data = array() ){
		$this->setup_auth_header();
		$data_out = array(
		'method'		=> 'PUT',
		'compress'		=> true,
		'decompress'	=> true,
		'headers'		=> $this->get_headers(),
		'body' 			=> http_build_query( $data,'', '&' ),
		);
		$url = $this->get_api_url($resource);
		//TODO: validate url?
		if($url !== false){
			$result = wp_safe_remote_request( $url, $data_out );
			$json = wp_remote_retrieve_body( $result );
			unset($this->headers);
			//TODO Check for wp_error
			return array(
				'code'	=> wp_remote_retrieve_response_code( $result ),
				'data'	=> json_decode( $json, true ),
				'json'	=> $json
			);
		} else {
			unset($this->headers);
			//what to do if the url is false
		}
	}

	public function send_delete_request( $resource = "" ){
		$this->setup_auth_header();
		$data_out = array(
		'method'		=> 'DELETE',
		'compress'		=> true,
		'decompress'	=> true,
		'headers'		=> $this->get_headers(),
		//'body' 			=> http_build_query( $data,'', '&' ),
		);
		$url = $this->get_api_url($resource);
		if($url !== false){
			$result = wp_safe_remote_request( $url, $data_out );
			$json = wp_remote_retrieve_body( $result );
			return array(
				'code'	=> wp_remote_retrieve_response_code( $result ),
				'data'	=> json_decode( $json, true ),
				'json'	=> $json
			);
		} else {
			unset($this->headers);
		}
	}
	
	function report_error( $title = "", $message = "", $retry = false, $prevent_future = false, $severity = 'normal'){
		/*
		severity:		array	allowed values: low, normal, high, fatal
		title:			string	any
		message:		string	any
		retry:			bool 	//tell wordpress cron to retry this whole stack in a few minutes
		prevent_future:	bool 	//prevents future api calls on this page, because they depend on this call's success	 
		*/
		$this->latest_error = array();
		
		$this->latest_error[$severity][] = array(
			'title'				=> $title,
			'message'			=> $message,
			'retry'				=> (bool) $retry,
			'prevent_future'	=> (bool) $prevent_future,
		);
		$this->errors[$severity][] = array(
			'title'				=> $title,
			'message'			=> $message,
			'retry'				=> (bool) $retry,
			'prevent_future'	=> (bool) $prevent_future,
		);
		if($prevent_future === true){
			$this->prevent_future = true;
		}
	}
?>
