<?php
// Stop direct call
if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class AVH_TBB_Core
{

	/**
	 * Version of AVH Themed By Browser
	 *
	 * @var string
	 */
	var $version;

	/**
	 * Comments used in HTML do identify the plugin
	 *
	 * @var string
	 */
	var $comment_begin;
	var $comment_end;

	/**
	 * Paths and URI's of the WordPress information, 'home', 'siteurl', 'plugin_url', 'plugin_dir'
	 *
	 * @var array
	 */
	var $info;

	/**
	 * Options set for the plugin
	 *
	 * @var array
	 */
	var $options;
	var $data;

	/**
	 * Default options for the plugin
	 *
	 * @var array
	 */
	var $default_general_options;
	var $default_options;
	var $default_browser_data;
	var $default_data;

	/**
	 * Name of the options field in the WordPress database options table.
	 *
	 * @var string
	 */
	var $db_options_name_core;
	var $db_options_name_data;
	var $avh_browscap;

	/**
	 * PHP5 constructor
	 *
	 */
	function __construct ()
	{
		$this->version = "0.2";
		$this->comment_begin = '<!-- AVH Themed By Browser version ' . $this->version . ' Begin -->';
		$this->comment_end = '<!-- AVH Themed By Browser version ' . $this->version . ' End -->';

		/**
		 * Default options - General Purpose
		 *
		 */
		$this->default_general_options = array ('version' => $this->version );

		/**
		 * Default Options - All as stored in the DB
		 *
		 */
		$this->default_options = array ('general' => $this->default_general_options );

		$this->default_browser_data = array ('default' => get_current_theme() );
		$this->default_data = array ('browser' => $this->default_browser_data );

		$this->db_options_name_core = 'avhtbb_core';
		$this->db_options_name_data = 'avhtbb_data';

		// Determine installation path & url
		$path = str_replace( '\\', '/', dirname( __FILE__ ) );
		$path = substr( $path, strpos( $path, 'plugins' ) + 8, strlen( $path ) );
		$path = substr( $path, 0, strlen( $path ) - 4 );

		$info['siteurl'] = get_option( 'siteurl' );
		if ( $this->isMuPlugin() ) {
			$info['plugin_url'] = WPMU_PLUGIN_URL;
			$info['plugin_dir'] = WPMU_PLUGIN_DIR;

			if ( $path != 'mu-plugins' ) {
				$info['plugin_url'] .= '/' . $path;
				$info['plugin_dir'] .= '/' . $path;
			}
		} else {
			$info['plugin_url'] = WP_PLUGIN_URL;
			$info['plugin_dir'] = WP_PLUGIN_DIR;

			if ( $path != 'plugins' ) {
				$info['plugin_url'] .= '/' . $path;
				$info['plugin_dir'] .= '/' . $path;
			}
		}

		// Set class property for info
		$this->info = array ('home' => get_option( 'home' ), 'siteurl' => $info['siteurl'], 'plugin_url' => $info['plugin_url'], 'plugin_dir' => $info['plugin_dir'], 'graphics_url' => $info['plugin_url'] . '/images', 'wordpress_version' => $this->getWordpressVersion() );

		// Set up browscap configuration.
		$this->avh_browscap = & AVH_TBB_Singleton::getInstance( 'AVH_TBB_Browscap', array ('db' => $this->info['plugin_dir'] . '/inc/lite_php_browscap.ini', 'return_array' => true ) );

		$this->handleOptions();
		$this->handleData();

		return;
	}

	/**
	 * PHP4 constructor - Initialize the Core
	 *
	 * @return
	 */
	function AVH_TBB_Core ()
	{
		$this->__construct();
	}

	/**
	 * Test if local installation is mu-plugin or a classic plugin
	 *
	 * @return boolean
	 */
	function isMuPlugin ()
	{
		if ( strpos( dirname( __FILE__ ), 'mu-plugins' ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Sets the class property "options" to the options stored in the DB and if they do not exists set them to the default options
	 * Checks if upgrades are necessary based on the version number
	 *
	 */
	function handleOptions ()
	{
		// Get options from WP options
		$options = $this->loadOptions();

		if ( false === $options ) { // New Installation
			$this->resetToDefaultOptions();
		} else {
			$this->setOptions( $options );
		}

	} // End handleOptions()

	/**
	 * Sets the class property "data" to the data stored in the DB and if they do not exists set them to the default options
	 * Checks if upgrades are necessary based on the version number
	 *
	 */
	function handleData ()
	{

		// Get options from WP options
		$data_from_table = $this->loadData();
		if (false === $data_from_table) { // New Installation
			$this->resetToDefaultData();
		} else {
			$this->data = $data_from_table;
		}
	}

	/**
	 * Get the base directory of a directory structure
	 *
	 * @param string $directory
	 * @return string
	 *
	 */
	function getBaseDirectory ( $directory )
	{
		//get public directory structure eg "/top/second/third"
		$public_directory = dirname( $directory );
		//place each directory into array
		$directory_array = explode( '/', $public_directory );
		//get highest or top level in array of directory strings
		$public_base = max( $directory_array );

		return $public_base;
	}

	/**
	 * Returns the wordpress version
	 * Note: 2.7.x will return 2.7
	 *
	 * @return float
	 *
	 */
	function getWordpressVersion ()
	{
		// Include WordPress version
		require (ABSPATH . WPINC . '/version.php');
		$version = ( float ) $wp_version;
		return $version;
	}

	/**********************
	 *                    *
	 * Browscap functions *
	 *                    *
	 *********************/

	/**
	 * Get all the Parents from the Browscap
	 *
	 * @return array
	 */
	function getBrowscapParents ()
	{
		return ($this->avh_browscap->getParents());
	}

		/**
	 * Get all the browser info of the current visitor
	 *
	 * @return array
	 */

	function getBrowscapBrowser ()
	{
		return ($this->avh_browscap->getBrowser());

	}

	/*****************************
	 *                           *
	 * Data manipulation methods *
	 *                           *
	 *****************************/

	/******************************
	 *                            *
	 * Methods for variable: data *
	 *                            *
	 *****************************/

	/**
	 * @param array $data
	 */
	function setData ( $data )
	{
		$this->data = $data;
	}

	/**
	 * @return array
	 */
	function getData ()
	{
		return ($this->data);
	}

	/**
	 * Save all current data to the DB
	 * @param array $data
	 *
	 */
	function saveData ( $data )
	{
		update_option( $this->db_options_name_data, $data );
		wp_cache_flush(); // Delete cache
		$this->setData( $data );
	}

	/**
	 * Retrieve the data from the DB
	 *
	 * @return array
	 */
	function loadData ()
	{
		return (get_option( $this->db_options_name_data ));
	}

	/**
	 * Get the value of a data element. If there is no value return false
	 *
	 * @param string $option
	 * @param string $key
	 * @return mixed
	 * @since 0.1
	 */
	function getDataElement ( $option, $key )
	{
		if ( $this->data[$option][$key] ) {
			$return = $this->data[$option][$key];
		} else {
			$return = false;
		}
		return ($return);
	}

	/**
	 * Reset to default data and save in DB
	 *
	 */
	function resetToDefaultData ()
	{
		$this->data = $this->default_data;
		$this->saveData( $this->default_data );
	}

	/*********************************
	 *                               *
	 * Methods for variable: options *
	 *                               *
	 ********************************/

	/**
	 * @param array $data
	 */
	function setOptions ( $options )
	{
		$this->options = $options;
	}

	/**
	 * return array
	 */
	function getOptions ()
	{
		return ($this->options);
	}

	/**
	 * Save all current data and set the data
	 *
	 */
	function saveOptions ( $options )
	{
		update_option( $this->db_options_name_core, $options );
		wp_cache_flush(); // Delete cache
		$this->setOptions( $options );
	}

	function loadOptions ()
	{
		return (get_option( $this->db_options_name_core ));
	}

	/**
	 * Get the value for an option element. If there's no option is set on the Admin page, return the default value.
	 *
	 * @param string $key
	 * @param string $option
	 * @return mixed
	 */
	function getOptionElement ( $option, $key )
	{
		if ( $this->options[$option][$key] ) {
			$return = $this->options[$option][$key]; // From Admin Page
		} else {
			$return = $this->default_options[$option][$key]; // Default
		}
		return ($return);
	}

	/**
	 * Reset to default options and save in DB
	 *
	 */
	function resetToDefaultOptions ()
	{
		$this->options = $this->default_options;
		$this->saveOptions( $this->default_options );
	}

} //End Class
?>