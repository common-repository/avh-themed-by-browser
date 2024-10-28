<?php
// Stop direct call
if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

class AVH_TBB_Admin
{
	var $core;

	/**
	 * Message management
	 *
	 */
	var $message = '';
	var $status = '';

	function __construct ()
	{
		$this->core = & AVH_TBB_Singleton::getInstance('AVH_TBB_Core');

		// Admin URL and Pagination
		$this->core->admin_base_url = $this->core->info['siteurl'] . '/wp-admin/admin.php?page=';
		if ( isset( $_GET['pagination'] ) ) {
			$this->core->actual_page = ( int ) $_GET['pagination'];
		}

		// Admin menu
		add_action( 'admin_menu', array (&$this, 'actionAdminMenu' ) );

		// CSS Helper
		add_action( 'admin_print_styles', array (&$this, 'actionInjectCSS' ) );

		// Enqueue jQuery only on certain pages
		$avhpages = array ('avhtbb_options' );

		if ( in_array( $_GET['page'], $avhpages ) ) {
			wp_enqueue_script( 'jquery-ui-tabs' );
		}

		return;
	}

	/**
	 * PHP4 Constructor - Intialize Admin
	 *
	 * @return
	 */
	function AVH_TBB_Admin ()
	{
		$this->__construct();
	}

	/**
	 * Add the Options to the Management and Options page repectively
	 *
	 * @WordPress Action admin_menu
	 *
	 */
	function actionAdminMenu ()
	{
		$folder = $this->core->getBaseDirectory( plugin_basename( $this->core->info['plugin_dir'] ) );
		add_menu_page( __( 'AVH Themed By Browser' ), __( 'AVH Themed By Browser' ), 10, $folder, array (&$this, 'handleMenu' ) );
		add_submenu_page( $folder, __( 'Overview' ), __( 'Overview' ), 10, $folder, array (&$this, 'handleMenu' ) );
		add_submenu_page( $folder, __( 'General Options' ), __( 'General Options' ), 10, 'avh-tbb-options', array (&$this, 'handleMenu' ) );
		add_submenu_page( $folder, __( 'Browser-Theme correlations' ), __( 'Browser-Theme correlations' ), 10, 'avh-tbb-browser', array (&$this, 'handleMenu' ) );
		add_filter( 'plugin_action_links_avh-themed-by-browser/avh-themed-by-browser.php', array (&$this, 'filterPluginActions' ), 10, 2 );
	}

	/**
	 * Enqueue CSS
	 *
	 * @WordPress Action admin_print_styles
	 * @since 3.0
	 *
	 */
	function actionInjectCSS ()
	{
		wp_enqueue_style( 'avhtbbadmin', $this->core->info['plugin_url'] . '/inc/avh-tbb.admin.css', array (), $this->core->version, 'screen' );
	}

	/**
	 * Adds Settings next to the plugin actions
	 *
	 * @WordPress Filter plugin_action_links_avh-themed-by-browser/avh-themed-by-browser.php
	 *
	 */
	function filterPluginActions ( $links, $file )
	{
		static $this_plugin;

		if ( ! $this_plugin )
			$this_plugin = $this->core->getBaseDirectory( plugin_basename( $this->core->info['plugin_dir'] ) );
		if ( $file )
			$file = $this->core->getBaseDirectory( $file );
		if ( $file == $this_plugin ) {
			$settings_link = '<a href="admin.php?page=avh-themed-by-browser">' . __( 'Settings', 'avhtbb' ) . '</a>';
			array_unshift( $links, $settings_link ); // before other links
		//$links = array_merge ( array (	$settings_link ), $links ); // before other links
		}
		return $links;

	}

	function handleMenu ()
	{
		switch ( $_GET['page'] ) {
			case 'avh-tbb-options' :
				$this->handleGeneralOptions();
				break;
			case 'avh-tbb-browser' :
				$this->handleCorrelation();
				break;
			case 'avh-themed-by-browser' :
			default :
				$this->handleOverview();

		}
		$this->printAdminFooter();
	}

	/**
	 * Shows the form for the general options
	 *
	 */
	function handleGeneralOptions ()
	{

		if ( isset( $_POST['updateoption'] ) ) {
			check_admin_referer( 'avh_tbb_options' );
			$data = $this->core->getData();
			$data['browser']['default'] = $_POST['theme'];
			$this->core->saveData( $data );
			$this->message = 'Default theme updated to: '.$_POST['theme'];
		}
		$this->displayMessage();

		$defaulttheme = $this->core->data['browser']['default'];

		echo '<div class="wrap">';
		echo '<h2>' . __( 'General Options', 'avhtbb' ) . '</h2>';

		echo '<h3>' . __( 'Default theme' ) . '</h3>';
		echo '<form name="avhtbb-browsers" id="avhtbb-browsers" method="POST" accept-charset="utf-8" >';
		wp_nonce_field( 'avh_tbb_options' );

		echo '<p>' . __( 'Default Theme', 'avhtbb' ) . ': ';
		echo '<label for="theme">';
		echo '<select name="theme" id="theme">';
		echo $this->handleDropdownTheme( $defaulttheme );
		echo '</select></label></p>';

		echo '<div class="submit"><input type="submit" class="button-primary" name= "updateoption" value="' . __( 'Update', 'avhtbb' ) . '"/></div>';

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Shows the form for settings and removing Browser-Theme Correlation
	 *
	 */
	function handleCorrelation ()
	{
		$data = $this->core->getData();
		if ( isset( $_POST['updateoption'] ) ) {
			check_admin_referer( 'avh_tbb_browsers' );

			// Delete all correlations and add the checked ones
			$browserdefault = $data['browser']['default'];
			$data['browser'] = null;
			$data['browser']['default'] = $browserdefault;
			if ( isset( $_POST['correlation'] ) ) {
				foreach ( $_POST['correlation'] as $browser => $theme ) {
					foreach ( array_keys( $theme ) as $key ) {
						$data['browser'][$browser] = $key;
					}
				}
			}
			if ( ! ('avhtbb_no_browser' == $_POST['browser'] || 'avhtbb_no_theme' == $_POST['theme']) ) {
				$data['browser'][$_POST['browser']] = $_POST['theme'];
			}
			$this->core->saveData( $data );
		}

		echo '<div class="wrap">';
		echo '<h2>' . __( 'Browser/Theme Correlation', 'avhtbb' ) . '</h2>';

		echo '<form name="avhtbb-browsers" id="avhtbb-browsers" method="POST" accept-charset="utf-8" >';
		wp_nonce_field( 'avh_tbb_browsers' );

		echo '<table class="form-table">';
		echo '<tr>';
		echo '<th>';
		_e( 'Current Correlations', 'avhtbb' );
		echo '<br/>';
		echo '<small>' . __( 'Unchecking the correlation will delete it.', 'avhtbb' ) . '</small>';
		echo '</th>';
		echo '<td>';
		echo '<div style="width: 100%; height: 100%">';
		echo '<ul id="avhtbb_correlation_list">';
		if ( count( $data['browser'] ) > 1 ) {
			foreach ( $data['browser'] as $browser => $theme ) {
				if ( 'default' == $browser ) {
					continue;
				}
				echo '<li id="' . str_replace( ' ', '_', $browser ) . '"	class="avhtbb_correlation_browser current">';
				echo '<input type="checkbox" id="cb_' . str_replace( ' ', '_', $browser ) . '" name="correlation[' . $browser . '][' . $theme . ']" checked="checked" />';
				echo __( 'Browser' ) . ': ' . $browser . '<br />';
				echo __( 'Theme' ) . ': ' . $theme;
				echo '</li>';
			}
		} else {
			_e( 'No correlations set', 'avhtbb' );
		}
		echo '</ul>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';

		echo '<tr>';
		echo '<th>';
		_e( 'Set Correlation', 'avhtbb' );
		echo '<br/>';
		echo '<small>' . __( 'Select the browser and theme to set a correlation.', 'avhtbb' ) . '</small>';
		echo '</th>';
		echo '<td>';
		echo '<div style="width: 100%; height: 100%">';
		echo '<p>' . __( 'Browser', 'avhtbb' ) . ': ';
		echo '<label for="browser">';
		echo '<select name="browser" id="browser">';
		echo '<option value="avhtbb_no_browser">' . __( 'Select browser' ) . '</option>';
		echo $this->handleDropdownBrowser();
		echo '</select></label><br />';

		echo __( 'Theme', 'avhtbb' ) . ': ';
		echo '<label for="theme">';
		echo '<select name="theme" id="theme">';
		echo '<option value="avhtbb_no_theme">' . __( 'Select theme' ) . '</option>';
		echo $this->handleDropdownTheme();
		echo '</select></label></p>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
		echo '<div class="submit"><input type="submit" class="button-primary" name= "updateoption" value="' . __( 'Update', 'avhtbb' ) . '"/></div>';

		echo '</form>';
		echo '</div>';
	}

	/**
	 * Show the overview of settings
	 *
	 */
	function handleOverview ()
	{
		$data = $this->core->getData();
		echo '<div class="wrap">';
		echo '<h2>' . __( 'Overview', 'avhtbb' ) . '</h2>';

		echo '<h3>' . __( 'Default theme' ) . '</h3>';
		echo '<p>' .__('The current default theme','avhtbb').': '. $data['browser']['default'];

		echo '<h3>'.__( 'Current Correlations', 'avhtbb' ).'</h3>';
		echo '<table>';
		echo '<tr>';
		echo '<td>';
		echo '<div style="width: 100%; height: 100%">';
		echo '<ul id="avhtbb_correlation_list">';
		if ( count( $data['browser'] ) > 1 ) {
			foreach ( $data['browser'] as $browser => $theme ) {
				if ( 'default' == $browser ) {
					continue;
				}
				echo '<li id="' . str_replace( ' ', '_', $browser ) . '"	class="avhtbb_correlation_browser current">';
				echo __( 'Browser' ) . ': ' . $browser . '<br />';
				echo __( 'Theme' ) . ': ' . $theme;
				echo '</li>';
			}
		} else {
			_e( 'No correlations set', 'avhtbb' );
		}
		echo '</ul>';
		echo '</div>';
		echo '</td>';
		echo '</tr>';
		echo '</table>';
	}

	function handleDropdownBrowser ()
	{
		$parents = $this->core->getBrowscapParents();

		foreach ( array_keys( $parents ) as $browser ) {
			$r .= '<option value="' . $browser . '">' . $browser . '</option>';
		}
		return ($r);
	}

	function handleDropdownTheme ( $selected = false )
	{
		$wp_themes = get_themes();
		uksort( $wp_themes, 'strnatcasecmp' );
		foreach ( $wp_themes as $theme ) {
			if ( $selected == $theme['Name'] ) // Make default first in list
				$p = '<option selected="selected" value="' . $theme['Name'] . '">' . $theme['Name'] . '</option>';
			else
				$r .= '<option value="' . $theme['Name'] . '">' . $theme['Name'] . '</option>';
		}
		return ($p . $r);
	}

	/**
	 * Add initial avh-amazon options in DB
	 *
	 */
	function installPlugin ()
	{
		// Get options from WP options
		$options_from_table = $this->core->loadOptions();
		if ( false === $options_from_table ) {
			$this->core->resetToDefaultOptions();
		}
		// Get data from WP options
		$data_from_table = $this->core->loadData();
		if ( false === $data_from_table ) {
			$this->core->resetToDefaultData();
		}

	}

	############## WP Options ##############
	/**
	 * Update an option value  -- note that this will NOT save the options.
	 *
	 * @param array $optkeys
	 * @param string $optval
	 */
	function setOption ( $optkeys, $optval )
	{
		$key1 = $optkeys[0];
		$key2 = $optkeys[1];
		$this->core->options[$key1][$key2] = $optval;
	}

	############## Admin WP Helper ##############
	/**
	 * Display plugin Copyright
	 *
	 */
	function printAdminFooter ()
	{
		echo '<p class="footer_avhtbb">';
		printf( __( '&copy; Copyright 2009 <a href="http://blog.avirtualhome.com/" title="My Thoughts">Peter van der Does</a> | AVH Themed By Browser Version %s', 'avhamazon' ), $this->core->version );
		echo '</p>';
	}

	/**
	 * Display WP alert
	 *
	 */
	function displayMessage ()
	{
		if ( $this->message != '' ) {
			$message = $this->message;
			$status = $this->status;
			$this->message = $this->status = ''; // Reset
		}

		if ( $message ) {
			$status = ($status != '') ? $status : 'updated';
			echo '<div id="message"	class="' . $status . ' fade">';
			echo '<p><strong>' . $message . '</strong></p></div>';
		}
	}
}
?>