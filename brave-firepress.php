<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              http://www.bravedigital.com
 * @since             1.0.0
 * @package           Brave_Firepress
 *
 * @wordpress-plugin
 * Plugin Name:       Firepress
 * Plugin URI:        http://www.bravedigital.com/firepress
 * Description:       Synchronises your posts with Firebase
 * Version:           1.0.0
 * Author:            Brave Digital
 * Author URI:        http://www.bravedigital.com
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       brave-firepress
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if (!is_admin()) //Only load the plugin when in the admin area.
{
	return;
}



	/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-brave-firepress-activator.php
 */
function activate_brave_firepress() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-brave-firepress-activator.php';
	Brave_Firepress_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-brave-firepress-deactivator.php
 */
function deactivate_brave_firepress() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-brave-firepress-deactivator.php';
	Brave_Firepress_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_brave_firepress' );
register_deactivation_hook( __FILE__, 'deactivate_brave_firepress' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-brave-firepress.php';


/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_brave_firepress() {

	$plugin = new Brave_Firepress();
	$plugin->run();

	$GLOBALS['brave_firepress'] = $plugin;

}
run_brave_firepress();


	function get_firepress()
	{
		/** @var Brave_Firepress $brave_firepress */
		global $brave_firepress;
		return $brave_firepress;
	}

//Exposed Global Functions which you can use in your themes and plugins to perform FirePress functions.
	function firepress_create_query($shallow = false)
	{
		/** @var Brave_Firepress $brave_firepress */
		global $brave_firepress;
		return $brave_firepress->create_query($shallow);
	}

	/**
	 * @param $location
	 * @param \Kreait\Firebase\Query $query
	 * @return bool
	 */
	function firepress_query($location, $query)
	{
		/** @var Brave_Firepress $brave_firepress */
		global $brave_firepress;

		if ($brave_firepress && $brave_firepress->isFirebaseSetup())
		{
			return $brave_firepress->query_database($location, $query);
		}
		else
		{
			return false;
		}
	}

	function firepress_get_smallest_from($location, $orderby = '', $numbertoget = 10, $shallow = false, $startat = false, $endat = false)
	{
		/** @var Brave_Firepress $brave_firepress */
		global $brave_firepress;

		if ($brave_firepress && $brave_firepress->isFirebaseSetup())
		{
			$query = $brave_firepress->create_query($shallow);
			if (empty($orderby))
			{
				$query->orderByKey();
			}
			else
			{
				$query->orderByChildKey($orderby);
			}

			if ($startat !== false)
			{
				$query->startAt($startat);
			}

			if ($endat !== false)
			{
				$query->endAt($endat);
			}

			$query->limitToFirst($numbertoget);

			return $brave_firepress->query_database($location, $query);
		}
		else
		{
			return false;
		}
	}

	function firepress_get_largest_from($location, $orderby = '', $numbertoget = 10, $shallow = false, $startat = false, $endat = false)
	{
		/** @var Brave_Firepress $brave_firepress */
		global $brave_firepress;

		if ($brave_firepress && $brave_firepress->isFirebaseSetup())
		{
			$query = $brave_firepress->create_query($shallow);
			if (empty($orderby))
			{
				$query->orderByKey();
			}
			else
			{
				$query->orderByChildKey($orderby);
			}

			if ($startat !== false)
			{
				$query->startAt($startat);
			}

			if ($endat !== false)
			{
				$query->endAt($endat);
			}

			$query->limitToLast($numbertoget);

			return $brave_firepress->query_database($location, $query);
		}
		else
		{
			return false;
		}
	}
