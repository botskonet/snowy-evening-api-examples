<?php

/**
 * Hi there! I'm an example PHP5 class for logging errors from php-based 
 * applications and websites to your account at Snowy-Evening.com.
 * 
 * If you don't have one yet, sign up for a new account at Snowy-Evening.com
 * and select a plan that enables the remote error logging feature.
 * 
 * Each project will have a unique API Key and Project ID. You will need
 * both of those pieces for this. They can be found on the Edit Project page.
 * 
 * Please note: if you're already using Aspen Framework (aspen-framework.org)
 * this functionality has been built in. Please see the configuration
 * documentation for help.
 * 
 * Email help@snowy-evening.com if you're having trouble.
 * 
 * Requires PHP 5.2.0. If using an older version, you can replace the 
 * 'json_encode' method below with any of the excellent json encoding
 * classes out there in the ether.
 * 
 * @author 		Michael Botsko
 * @copyright 	2011 Botsko.net, LLC
 * @license		You're free to use this code in any project. You can modify it
 * and redistribute it. We offer no warranty for this code.
 */

/**
 * It's recommended that you disable display_errors in your php.ini, and set your
 * error reporting to E_ALL. It's good practice to write code that doesn't
 * produce any errors, warnings, or notices and E_ALL will help you
 * determine how well you're doing.
 */
ini_set('display_errors', false);
error_reporting(E_ALL);


/**
 * Create a new instance of the error class and set the raise method as our
 * error handler. If you want, you can also assign this for the exception
 * handling.
 */
$error = new Snowy_error;
set_error_handler(array(&$error, 'raise'));

/**
 * PHP will automatically report errors using this class, but calling this 
 * yourself is easy:
 * 
 * $error->raise(512, 'Your application error message here.', __FILE__, __LINE__);
 * 
 */


/**
 * The class!
 */
class Snowy_error  {
  
	/**
	 * Enter your API key here.
	 */
	const SE_API_KEY = '$2a$08$2Mp4gNmdjfhgdfgb7kZJuZ.P4PQYf56qoHhhvUFCruVqT3p0Osju';
	
	/**
	 * Enter the project ID these errors should be reported to.
	 */
	const SE_PROJ_ID = 114;

	
	/**
	 * Raises a new error message
	 * @param integer $errNo
	 * @param string $errMsg
	 * @param string $file
	 * @param integer $line
	 * @return void
	 * @access public
	 */
	public function raise($errNo = false, $errMsg = 'An unidentified error occurred.', $file = false, $line = false) {

		if (!$errNo) { return; }
		while (ob_get_level()) {
			ob_end_clean();
		}

		$errType = array (
			1    => "PHP Error",
			2    => "PHP Warning",
			4    => "PHP Parse Error",
			8    => "PHP Notice",
			16   => "PHP Core Error",
			32   => "PHP Core Warning",
			64   => "PHP Compile Error",
			128  => "PHP Compile Warning",
			256  => "PHP User Error",
			512  => "PHP User Warning",
			1024 => "PHP User Notice",
			2048 => "Unknown",
			4096 => "Unknown",
			8192 => "Deprecated"
		);

		// Snowy accepts traces!
		$trace = array();
		if(function_exists('debug_backtrace')){
			$db = debug_backtrace();
			foreach($db as $file_t){
				if(isset($file_t['file'])){
					$trace[] = array('file'=>$file_t['file'],'line'=>$file_t['line'],'function'=>$file_t['function']);
				}
			}
		}

		// Build the full error report
		$error = array(
				'application'		=> '', // Fill this in to help determine which installation the error came from
				'version_complete'	=> '', // You can determine a complete version (version + build, dependency versions, etc)
				'version'			=> '', // Your application version
				'build'				=> '', // Current build number.
				'date'				=> date("Y-m-d H:i:s"),
				'gmdate'			=> gmdate("Y-m-d H:i:s"),
				'visitor_ip'		=> $this->getServerValue('REMOTE_ADDR'),
				'referrer_url'		=> $this->getServerValue('HTTP_REFERER'),
				'request_uri'		=> $this->getServerValue('REQUEST_URI'),
				'user_agent'		=> $this->getServerValue('HTTP_USER_AGENT'),
				'error_type'		=> $errType[$errNo],
				'error_message'		=> $errMsg,
				'error_no'			=> $errNo,
				'file'				=> $file,
				'line'				=> $line,
				'trace'				=> (empty($trace) ? false : $trace)
			);

			$params = array(
					'api_key'		=> self::SE_API_KEY,
					'project_id'	=> self::SE_PROJ_ID,
					'payload'		=> json_encode($error));

			// Use curl to post the json-encoded data
			$ch = curl_init();
			curl_setopt($ch,CURLOPT_URL,'https://snowy-evening.com/api/integration/error_log');
			curl_setopt($ch,CURLOPT_POST,count($error));
			curl_setopt($ch,CURLOPT_POSTFIELDS,http_build_query($params));
			curl_setopt($ch,CURLOPT_RETURNTRANSFER,true);
			$result = curl_exec($ch);
			curl_close($ch);
	}


	/**
	 * Simple way of gracefully handling values not present.
	 *
	 * @param string $key
	 * @param string $default
	 * @return string
	 */
	protected function getServerValue($key, $default = 'N/A'){
		return isset($_SERVER[$key]) ? $_SERVER[$key] : $default;
	}
}
?>