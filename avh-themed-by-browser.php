<?php
/*
Plugin Name: AVH Themed By Browser
Plugin URI: http://blog.avirtualhome.com/wordpress-plugins
Description: This plugin gives you the ability to dynamically change your blog's theme based on the browser the visitor is using.
Version: 0.2
Author: Peter van der Does
Author URI: http://blog.avirtualhome.com/

Copyright 2008  Peter van der Does  (email : peter@avirtualhome.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA  02110-1301  USA
*/

// Check version.
global $wp_version;

if ( ( float) $wp_version >= 2.8 ) {
	require(dirname(__FILE__).'/2.8/avh-tbb.client.php');
} else {
	$message='<div class="updated fade"><p><strong>'.__('AVH Latest can\'t work with this WordPress version !', 'avhlatest').'</strong></p></div>';
	add_action('admin_notices', create_function( '', "echo '$message';" ) );

}