<?php
/*
Plugin Name: Shubham's Registration Process
Plugin URI: https://shubbudishu.github.io/registration-process/
Description: The plugin meant for the registration process of a person with WordPress
Version: 2.2.4
Author: Shubham Sonkar
Author URI: https://shubbudishu.github.io/registration-process/
Text Domain: registration-process
*/

defined( 'ABSPATH' ) || exit;

require_once( ABSPATH . 'wp-admin/includes/plugin.php' );
$plugin_data = get_plugin_data( __FILE__ );

define( 'um_url', plugin_dir_url( __FILE__ ) );
define( 'um_path', plugin_dir_path( __FILE__ ) );
define( 'um_plugin', plugin_basename( __FILE__ ) );
define( 'ultimatemember_version', $plugin_data['Version'] );
define( 'ultimatemember_plugin_name', $plugin_data['Name'] );

require_once 'includes/class-functions.php';
require_once 'includes/class-init.php';
