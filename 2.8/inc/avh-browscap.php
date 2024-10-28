<?php
// Stop direct call
if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class AVH_TBB_Browscap
{
	var $browscapIni;
	var $browscapPath;
	var $db;
	var $user_agent;
	var $return_array;
	var $cache = false;

	function __construct ($args)
	{
		$this->browscapIni = NULL;
		$this->browscapPath = '';
		$this->user_agent = null;
		$this->return_array = false;
		$this->db = '';
		if (is_array($args)) {
			foreach($args as $key => $value)
        	{
            	$this->$key = $value;  // Works, but ugly
        	}
		}

		$this->handleIniFile();
	}

	function AVH_TBB_Browscap ()
	{
		$this->__construct();
	}

	/**
	 * Read the Ini File and create array
	 *
	 */
	function handleIniFile ()
	{
		if ( (! isset( $this->browscapIni )) ) {
			$this->browscapIni = parse_ini_file( $this->db, true ); //Get php_browscap.ini on http://browsers.garykeith.com/downloads.asp
			$this->browscapPath = $this->db;
			uksort( $this->browscapIni, array (&$this, '_sortBrowscap' ) );
			$this->browscapIni = array_map( array (&$this, '_lowerBrowscap' ), $this->browscapIni );
		}
	}

	function _sortBrowscap ( $a, $b )
	{
		$sa = strlen( $a );
		$sb = strlen( $b );
		if ( $sa > $sb )
			return - 1;
		elseif ( $sa < $sb )
			return 1;
		else
			return strcasecmp( $a, $b );
	}

	function _lowerBrowscap ( $r )
	{
		return array_change_key_case( $r, CASE_LOWER );
	}

	function getBrowser ()
	{
		if ( ($this->user_agent == null) && isset( $_SERVER['HTTP_USER_AGENT'] ) )
			$this->user_agent = $_SERVER['HTTP_USER_AGENT'];

		$cap = null;
		foreach ( $this->browscapIni as $key => $value ) {
			if ( ($key != '*') && (! array_key_exists( 'parent', $value )) )
				continue;
			$keyEreg = '^' . str_replace( array ('\\', '.', '?', '*', '^', '$', '[', ']', '|', '(', ')', '+', '{', '}', '%' ), array ('\\\\', '\\.', '.', '.*', '\\^', '\\$', '\\[', '\\]', '\\|', '\\(', '\\)', '\\+', '\\{', '\\}', '\\%' ), $key ) . '$';
			if ( preg_match( '%' . $keyEreg . '%i', $this->user_agent ) ) {
				$cap = array ('browser_name_regex' => strtolower( $keyEreg ), 'browser_name_pattern' => $key ) + $value;
				$maxDeep = 8;
				while ( array_key_exists( 'parent', $value ) && array_key_exists( $parent = $value['parent'], $this->browscapIni ) && (-- $maxDeep > 0) )
					$cap += ($value = $this->browscapIni[$parent]);
				break;
			}
		}
		return $this->return_array ? $cap : ( object ) $cap;
	}

	function getParents ()
	{
		$parents = array ();
		foreach ( $this->browscapIni as $key => $value ) {
			if ( $value['parent'] == 'DefaultProperties' ) {
				$parents[$key] = true;
			}
		}
		uksort($parents,'strnatcasecmp');
		return ($parents);
	}
}
?>