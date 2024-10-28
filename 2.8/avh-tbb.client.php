<?php
// Stop direct call
if ( preg_match( '#' . basename( __FILE__ ) . '#', $_SERVER['PHP_SELF'] ) ) {
	die( 'You are not allowed to call this page directly.' );
}

/**
 * Singleton Class
 */
class AVH_TBB_Singleton
{
	function &getInstance ( $class, $arg1 = null )
	{
		static $instances = array (); // array of instance names
		if ( array_key_exists( $class, $instances ) ) {
			$instance = & $instances[$class];
		} else {
			if (!class_exists($class)) {
                switch ($class) {
                	case 'AVH_TBB_Core':
                		require_once (dirname( __FILE__ ) . '/inc/avh-tbb.core.php');
                		break;
                	case 'AVH_TBB_Browscap':
                		require_once (dirname( __FILE__ ) . '/inc/avh-browscap.php');
                		break;
                }
			}
			$instances[$class] = new $class( $arg1 );
			$instance = & $instances[$class];
		}
		return $instance;
	} // getInstance
} // singleton

/**
 * Initialize the plugin
 *
 */
function avhtbb_init ()
{
	// Admin
	if ( is_admin() ) {
		require (dirname( __FILE__ ) . '/inc/avh-tbb.admin.php');
		$avhadmin = & new AVH_TBB_Admin( );
		// Installation
		register_activation_hook( __FILE__, array (&$avhadmin, 'installPlugin' ) );
	}
	if (!is_admin()) {
		add_filter( 'template', 'avhtbb_ChangeTemplate', 10 );
	}

} // End avhamazon_init()


add_action( 'plugins_loaded', 'avhtbb_init' );

function avhtbb_ChangeTemplate ( $name )
{
	$core = & AVH_TBB_Singleton::getInstance('AVH_TBB_Core');

	static $theme_name;

	if ( $theme_name === null ) {
		$browser = $core->getBrowscapBrowser();
		$theme_name=$core->getDataElement('browser',$browser['parent']);
		if ($theme_name) {
			$theme = get_theme($theme_name);
			switch_theme( $theme['Template'], $theme['Stylesheet'] );
		} else {
			$theme_name=$core->getDataElement('browser','default');
			$theme = get_theme($theme_name);
			switch_theme( $theme['Template'], $theme['Stylesheet'] );
		}
		$theme_name =$theme['Template'];
	}
	return $theme_name;
}
?>