<?php
/**
 * The file that defines the core plugin class
 *
 * A class definition that includes attributes and functions used across both the
 * public-facing side of the site and the admin area.
 *
 * @link       http://www.bravedigital.com
 * @since      1.0.0
 *
 * @package    Brave_Firepress
 * @subpackage Brave_Firepress/includes
 */

	use Kreait\Firebase\Configuration;
	use Kreait\Firebase\Firebase;

/**
 * The core plugin class.
 *
 * This is used to define internationalization, admin-specific hooks, and
 * public-facing site hooks.
 *
 * Also maintains the unique identifier of this plugin as well as the current
 * version of the plugin.
 *
 * @since      1.0.0
 * @package    Brave_Firepress
 * @subpackage Brave_Firepress/includes
 * @author     Brave Digital <plugins@braveagency.com>
 */
class Brave_Firepress {

	const SETTING_FIREBASE_URL = "bfp_firebase_url";
	const SETTING_FIREBASE_KEY = "bfp_firebase_key";
	const SETTING_DATABASE_BASEPATH = "bfp_firebase_db_basepath";
	const SETTING_POST_TYPES_TO_SAVE = "bfp_post_types_to_save";
	const SETTING_POST_KEY_FIELD = "bfp_post_key_field";
	const SETTING_FIELD_MAPPINGS = "bfp_field_mappings";
	const SETTING_ACF_OPTION = "bfp_acf_option";
	const SETTING_META_OPTION = "bfp_meta_option";
	const SETTING_TERMS_OPTION = "bfp_terms_option";
	const SETTING_EXCLUDED_POST_META_FIELDS = "bfp_excluded_post_meta_fields";
	const SETTING_EXCLUDE_TRASH = "bfp_exclude_trash";

	const OPTION_CONNECTED = "bfp_firepress_connected";

	const CUSTOM_FIELD_POST_FIREBASE_KEY = '_bfp_firebasekey';

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Brave_Firepress_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/** @var Brave_Firepress_Admin */
	protected $admin;

	protected $isSetup = false;
	protected $firebaseurl;
	protected $firebasekey;
	protected $basepath;
	protected $keyfield;
	protected $excludetrash;

	/** @var \Kreait\Firebase\Reference */
	protected $basereference;

	/** @var Firebase */
	protected $firebase = null;
	protected $posttypestosave = array();

	/** @var KLogger\Logger $logger */
	protected $logger;

	protected $adminnotice_error = '';
	protected $adminnotice_success = '';

	/**
	 * Define the core functionality of the plugin.
	 *
	 * Set the plugin name and the plugin version that can be used throughout the plugin.
	 * Load the dependencies, define the locale, and set the hooks for the admin area and
	 * the public-facing side of the site.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {

		$this->plugin_name = 'brave-firepress';
		$this->version = '1.0.0';

		$this->load_dependencies();
		$this->loader = new Brave_Firepress_Loader();

		//Initalise Log file:
		$uploaddir = wp_upload_dir();
		$logdir = $uploaddir['basedir'].DIRECTORY_SEPARATOR.'firepress-logs';
		if (!file_exists($logdir))
		{
			wp_mkdir_p($logdir);
		}
		$this->logger = new KLogger\Logger($logdir);

		$this->set_locale();

		$this->admin = new Brave_Firepress_Admin($this);

		$this->loader->add_action('init', $this, 'after_loaded');
		$this->loader->add_action('wp_insert_post', $this, 'send_post_to_firebase', 100, 3);
		$this->loader->add_action('before_delete_post', $this, 'delete_post_from_firebase', 10, 1);
		$this->loader->add_action('admin_notices', $this, 'admin_notices');
	}

	public function log($message, $context = array())
	{
		if (!is_array($context)) $context = array($context);
		return $this->logger->log(Psr\Log\LogLevel::INFO, $message, $context);
	}

	public function logWarning($message, $context = array())
	{
		if (!is_array($context)) $context = array($context);
		return $this->logger->log(Psr\Log\LogLevel::WARNING, $message, $context);
	}

	public function logError($message, $context = array())
	{
		if (!is_array($context)) $context = array($context);

		return $this->logger->log(Psr\Log\LogLevel::ERROR, $message, $context);
	}

	public function setAdminNoticeError($msg)
	{
		set_transient('firepress_adminnotice_error', $msg, 1 * HOUR_IN_SECONDS);
		$this->adminnotice_error = $msg;
	}

	public function setAdminNoticeSuccess($msg)
	{
		set_transient('firepress_adminnotice_success', $msg, 1 * HOUR_IN_SECONDS);
		$this->adminnotice_success = $msg;
	}

	/**
	 * If Firebase is set up and can write a db entry, then this setup flag is set to true.
	 * @return bool
	 */
	public function isFirebaseSetup()
	{
		return $this->isSetup;
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * Include the following files that make up the plugin:
	 *
	 * - Brave_Firepress_Loader. Orchestrates the hooks of the plugin.
	 * - Brave_Firepress_i18n. Defines internationalization functionality.
	 * - Brave_Firepress_Admin. Defines all hooks for the admin area.
	 * - Brave_Firepress_Public. Defines all hooks for the public side of the site.
	 *
	 * Create an instance of the loader which will be used to register the hooks
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies()
	{

		$basepath = plugin_dir_path(dirname( __FILE__ ));

		//Plugin Framework
		require_once $basepath . 'includes/class-brave-firepress-loader.php';

		//Logging
		if (!interface_exists("\\Psr\\Log\\LoggerInterface"))
		{
			require_once $basepath . 'includes/logging/LoggerInterface.php';
		}

		if (!class_exists("\\Psr\\Log\\AbstractLogger"))
		{
			require_once $basepath . 'includes/logging/AbstractLogger.php';
		}

		if (!class_exists("\\Psr\\Log\\LogLevel"))
		{
			require_once $basepath . 'includes/logging/LogLevel.php';
		}

		if (!class_exists("\\KLogger\\Logger"))
		{
			require_once $basepath . 'includes/logging/logger.php';
		}

		//Admin Area
		require_once $basepath . 'admin/class-brave-firepress-admin.php';

		//Firebase SDK
		require_once $basepath . 'includes/firebase-php/vendor/autoload.php';




	}

	/**
	 * Define the locale for this plugin for internationalization.
	 *
	 * Uses the Brave_Firepress_i18n class in order to set the domain and to register the hook
	 * with WordPress.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function set_locale()
	{
		$this->loader->add_action( 'plugins_loaded', $this, 'load_plugin_textdomain' );
	}

	public function load_plugin_textdomain()
	{

		load_plugin_textdomain(
			'brave-firepress',
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);

	}

	/**
	 * Clears the setup flag and erases the
	 */
	public function clear_setup_flag()
	{
		$this->isSetup = false;
		update_option(Brave_Firepress::OPTION_CONNECTED, 'no');
	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	public function after_loaded()
	{
		$this->isSetup     = false;
		$posttypestosave   = get_option(Brave_Firepress::SETTING_POST_TYPES_TO_SAVE);
		$this->firebasekey = get_option(Brave_Firepress::SETTING_FIREBASE_KEY);
		$this->firebaseurl = get_option(Brave_Firepress::SETTING_FIREBASE_URL);
		$this->basepath    = trailingslashit(get_option(Brave_Firepress::SETTING_DATABASE_BASEPATH));
		$this->keyfield    = 'ID'; //get_option(Brave_Firepress::SETTING_POST_KEY_FIELD);
		$this->excludetrash = get_option(Brave_Firepress::SETTING_EXCLUDE_TRASH);


		if (!is_array($posttypestosave))
		{
			$posttypestosave = array();
		}
		$this->posttypestosave = $posttypestosave;

		if (count($posttypestosave) > 0)
		{
			if (!empty($this->firebaseurl) && !empty($this->firebasekey))
			{
				$configfilename = $this->get_key_path($this->firebasekey);

				if (file_exists($configfilename))
				{

					try
					{


					$config = new Configuration();
					$config->setAuthConfigFile($configfilename);

					$this->firebase = new Firebase($this->firebaseurl, $config);

					//$this->basereference = $this->firebase->getReference($this->basepath);


					if (get_option(Brave_Firepress::OPTION_CONNECTED) !== 'yes')
					{
						//Write a value to the firepress database to tell if we are connected or not.
						$res = $this->firebase->set(date("F j Y, g:i a"), $this->get_database_path('firepress', 'last_connect'));

						if ($res > 300)
						{
							$this->isSetup = false;
							$this->setAdminNoticeError('Unable to connect to Firebase! HTTP code '.$res. ' returned.');
						}
						else
						{
							$this->firebase->set(site_url(), $this->get_database_path('firepress', 'connected_from'));

							$this->isSetup = true;
							$this->setAdminNoticeSuccess('Your Wordpress site is now connected to Firebase! Any posts you create, update or delete will be sent to your Firebase database.');
							update_option(Brave_Firepress::OPTION_CONNECTED, 'yes');
						}
					}
					else
					{
						//We know this firepress setting works.
						$this->isSetup = true;
					}

					}
					catch (Exception $e)
					{
						$this->setAdminNoticeError('An error occured while trying to connect to Firebase: <br/>'.$e->getMessage());

						$this->isSetup = false;
						update_option(Brave_Firepress::OPTION_CONNECTED, 'no');
					}

				}
				else
				{
					$this->setAdminNoticeError('An error occured while trying to connect to Firebase: <br/>'.'Couldnt find your credentials file. Please make sure it\'s in the <span class="code">accounts</span> directory.');

				}
			}
			else
			{
				$this->setAdminNoticeError('Please enter your Firebase url to start using Firepress.');
			}
		}
		else
		{
			$this->setAdminNoticeError('In order to start using Firepress, please choose at least one post type to save to Firebase.');
		}

	}

	public function addPostTypeToSave($posttype)
	{
		$posttypestosave = get_option(Brave_Firepress::SETTING_POST_TYPES_TO_SAVE);

		foreach ($posttypestosave as $pt)
		{
			if ($posttype === $pt) {
				return;
			}
		}

		$posttypestosave[] = $posttype;

		update_option(Brave_Firepress::SETTING_POST_TYPES_TO_SAVE, $posttypestosave);
		$this->posttypestosave = $posttypestosave;
	}

	public function removePostTypeToSave($posttype)
	{
		$posttypestosave = get_option(Brave_Firepress::SETTING_POST_TYPES_TO_SAVE);

		$newposttypes = array();
		foreach ($posttypestosave as $pt)
		{
			if ($posttype !== $pt)
			{
				$newposttypes[] = $pt;
			}
		}

		update_option(Brave_Firepress::SETTING_POST_TYPES_TO_SAVE, $newposttypes);
		$this->posttypestosave = $newposttypes;
	}


	public function get_database_path($posttype, $key)
	{
		return trailingslashit($this->basepath). $posttype . '/'. $key;
	}


	public function create_query($shallow = false)
	{
		$query = new \Kreait\Firebase\Query($shallow);

		return $query;
	}

	public function delete_key($posttype, $key)
	{
		$path = $this->get_database_path($posttype, $key);

		return ($this->firebase->delete($path) == 200);
	}

	/**
	 * Writes data to the Firebase database location specified
	 * WARNING: NO LOCATION CHECKING IS PERFORMED, YOU CAN EASILY WIPE YOUR DATABASE WITH THIS FUNCTION.
	 *
	 * @param $location
	 * @param $data
	 * @return array|bool|int
	 */
	public function set_key($location, $data)
	{
		if (!$this->isFirebaseSetup())
		{
			return false;
		}
		else
		{
			return $this->firebase->set($data, $location);
		}
	}

	/**
	 * Writes data to the Firebase database location specified, but wont overwrite child keys that arent part of your payload.
	 * WARNING: NO LOCATION CHECKING IS PERFORMED, YOU CAN EASILY WIPE YOUR DATABASE WITH THIS FUNCTION.
	 *
	 * @param $location
	 * @param $data
	 * @return array|bool|int
	 */
	public function update_key($location, $data)
	{
		if (!$this->isFirebaseSetup())
		{
			return false;
		}
		else
		{
			return $this->firebase->update($data, $location);
		}
	}


	/**
	 * @param $location
	 * @param \Kreait\Firebase\Query $query
	 * @return bool
	 */
	public function query_database($location, $query)
	{
		if (!$this->isSetup) return false;

		$result = $this->firebase->query($location, $query);

		return $result;
	}

	/**
	 * @param $postid
	 * @param bool $createifdoesntexist
	 * @return string
	 */
	public function get_post_firebasekey($postid, $createifdoesntexist = true)
	{
		if ($postid == 0)
		{
			$this->log("get_post_firebasekey: Post id is 0. Exiting.");
			return '';
		}

		$this->log("get_post_firebasekey: Getting for post id ".$postid);
		$key = get_post_meta($postid, "_bfp_firebasekey", true);
		$this->log("get_post_firebasekey: Key is ".$key);

		if ($createifdoesntexist && empty($key))
		{
			$this->log("Key doesnt exist! So I'm creating a new one.");
			$key = $this->create_post_firebasekey($postid);
		}

		return $key;
	}

	public function create_post_firebasekey($postid)
	{

		$keyfield = $this->keyfield;

		$post = get_post($postid, ARRAY_A);

		if (!$post) return '';

		$posttype = $post['post_type'];

		$key = (isset($post[$keyfield]) ? $post[$keyfield] : '');

		/*

		Removed this logic. All keys will be henceforce identified by their wordpress post IDs or the overwritten .

		//Get the keyfield for this post. This is usually the option set up in the FirePress settings page, but can be overwritten by the bfp_post_keyfield filter.
		$keyfield = apply_filters("bfp_post_keyfield", $keyfield, $postid, $posttype);



		//If the key field is post_name then do post_name specific logic:
		if ($keyfield == "post_name")
		{
			//If the firebase db indexes posts by their slugs, then remove any potential "__trashed" suffixes that Wordpress adds when a post is trashed so that we can reference the correct key in the Firebase DB.
			$key = preg_replace("/__trashed$/", "", $key);
		}
		*/

		$key = apply_filters("bfp_post_key", $key, $postid, $posttype);

		update_post_meta($postid, Brave_Firepress::CUSTOM_FIELD_POST_FIREBASE_KEY, $key);
		//$this->log("create_post_firebasekey: Adding key ".$key." to post id ".$postid);
		//$this->log("create_post_firebasekey: Read back key is = ".$this->get_post_firebasekey($postid, false));

		return $key;
	}


	/**
	 * Converts a post id to a small object which contains the post's ID and Slug so that the reference can be useful outside of Wordpress.
	 * @param $postid
	 * @return string|array
	 */
	protected function convert_postid_to_firebasepostid($postid)
	{
		if ($postid == 0)
		{
			return '';
		}

		if (is_object($postid) && $postid instanceof WP_Post)
		{
			return $this->convert_postid_to_firebasepostid($postid->ID);
		}

		if (is_array($postid))
		{
			$arr = array();
			foreach ($postid as $key=>$pid)
			{
				$arr[$key] = $this->convert_postid_to_firebasepostid($pid);
			}
			return $arr;
		}

		return $this->get_post_firebasekey($postid, true); //array('id'=>$postid, 'slug'=> $postid > 0 ? get_post_field('post_name', $postid, 'raw') : false, 'title'=> get_the_title($postid));
	}

	/**
	 * Expands a term object into a small array containing useful info about the term.
	 * TODO: At a later stage this should be removed and FirePress should gather a list of taxonomies and store their terms in a list, rather than repeating them over and over inside each object.
	 *
	 * @param $term - Can be a single term ID or an array of term IDs.
	 * @param $taxonomy
	 * @return array - Returns an associative object that represents the term - or an array of associative objects if an array was passed.
	 */
	protected function convert_term_to_firebaseterm($term, $taxonomy)
	{

		if (is_array($term))
		{
			$arr = array();
			foreach ($term as $key=>$pid)
			{
				$arr[$key] = $this->convert_term_to_firebaseterm($pid, $taxonomy);
			}
			return $arr;
		}

		if (!is_object($term) && is_numeric($term))
		{
			$term = get_term($term, $taxonomy, OBJECT);
		}

		return array("id"=>$term->term_id, "name"=>$term->name, "slug"=>$term->slug, "tax"=>$taxonomy);
	}

	/**
	 * Main function which saves / updates the Firebase database with your new information.
	 *
	 * @param int $postid
	 * @param WP_Post $post
	 * @param bool $isupdate
	 * @return array|int|string
	 */
	public function send_post_to_firebase($postid, $post, $isupdate)
	{

		//# Check Suitability:

		if (wp_is_post_revision($postid)) return false;
		if (wp_is_post_autosave($postid)) return false;

		if (!apply_filters("bfp_should_save_post", true, $postid, $post, $isupdate))
		{
			$this->log("Aborted saving post ". $postid." to Firebase because of a filter running on bfp_should_save_post.");
			return false;
		}

		/** @var WPDB $wpdb */
		global $wpdb;

		//Initalise $thisdata by converting the WP_Post object into an associative array.
		$thisdata = (array) $post;




		if (in_array($thisdata['post_status'],  array('draft','auto-draft', 'pending', 'inherit'))) return false; //This post is not ready for human consuption. Ignore this until the post is published.

		//Only proceed if this post type is in the list of post types to save.
		if (!in_array($thisdata['post_type'], $this->posttypestosave)) return false;

		if (!$this->isSetup)
		{
			$this->setAdminNoticeError('Unable to save this post to Firebase. Firepress is not setup or your credentials are not correct.');
			return false;
		}


		//Gather all the field names from the FirePress settings:

		$posttype = $thisdata['post_type'];

		$metaoption = get_option(Brave_Firepress::SETTING_META_OPTION, 'key');
		$acfoption = get_option(Brave_Firepress::SETTING_ACF_OPTION, 'key');
		$termsoption = get_option(Brave_Firepress::SETTING_TERMS_OPTION, 'key');

		$map = get_option(Brave_Firepress::SETTING_FIELD_MAPPINGS, array());


		//# Format and clean up the data for insertion into Firebase:

		if ($thisdata['post_status'] == 'trash')
		{
			//Trashed posts have their post_name renamed with __trashed on the end, causing FirePress to make a duplicate object in the Firebase DB.
			//Undo this change in the data so that the object is referenced correctly. This is also done inside get_post_firebasekey.
			$thisdata['post_name'] = preg_replace("/__trashed$/", "", $thisdata['post_name']);
		}



		$thisdata['post_author'] = intval($thisdata['post_author']);
		$thisdata['post_parent'] = intval($thisdata['post_parent']);

		//Get author username
		$userdata = get_userdata($thisdata['post_author']);
		$thisdata['post_author_username'] = $userdata->user_login;

		//Convert a post id into something more usable outside of Wordpress.
		$thisdata['post_parent'] = $this->convert_postid_to_firebasepostid($thisdata['post_parent']);




		//# Featured Image handling

		if (post_type_supports($posttype, 'thumbnail'))
		{
			//Save out all image sizes present in wordpress with their urls:
			$sizes = get_intermediate_image_sizes();
			$images = array();
			foreach ($sizes as $size)
			{
				$img = get_the_post_thumbnail_url($postid, $size);
				$images[$size] = $img;
			}
			$thisdata['image'] = $images;
		}


		//Remap fields according to field mappings:
		//WARNING If any fields are NOT specified in the map array in class-brave-firepress-admin.php:121, then they will get deleted here!

		if (count($map) > 0)
		{
			$newthisdata = array();
			foreach ($map as $old=>$new)
			{
				if (!empty($new) && isset($thisdata[$old]))
				{
					$newthisdata[$new] = $thisdata[$old];
				}
			}
			$thisdata = $newthisdata;
		}



		//# Meta Data Storing:

		$metadata = array();
		if ($metaoption != 'off' || ($acfoption != 'off' && is_plugin_active('advanced-custom-fields/acf.php')))
		{

			$excludedmetafields = explode(PHP_EOL, get_option(Brave_Firepress::SETTING_EXCLUDED_POST_META_FIELDS, ''));

			$excludedmetafields = array_merge(array(
				'_edit_lock',
				'_edit_last',
				'_wp_trash_meta_time',
				'_wp_trash_meta_status',
				'_wp_desired_post_slug',
				'_wp_old_slug',
				'_encloseme',
				'_thumbnail_id',
			), $excludedmetafields);

			$meta = $this->sqlQuery($wpdb->postmeta, array('post_id'=>$postid), array('meta_key'=>$excludedmetafields));

			//Store this post's meta data
			foreach ($meta as $row)
			{
				//Skip hidden keys - those that start with _.
				if (strpos($row['meta_key'], "_") === 0) continue;
				$metadata[$row['meta_key']] = maybe_unserialize($row['meta_value']);
			}
			unset($meta);
		}


		if ($acfoption != 'off' && is_plugin_active('advanced-custom-fields/acf.php'))
		{
			//ACF Support:
			$fields = get_field_objects($postid, false);

			//$this->log("ACF Fields: ", $fields);
			if ($fields !== false)
			{
				//Remove ACF fields from the 'metadata' section and add each field to the 'fields' section.

				$fielddata = array();
				foreach ($fields as $fieldname => $field)
				{
					if (isset($metadata[$fieldname])) unset($metadata[$fieldname]);
					if (isset($metadata['_'.$fieldname])) unset($metadata['_'.$fieldname]);

					//Scan through each field and convert it's data if necessary, based on the type of field presented.
					switch ($field['type'])
					{

						case 'post_object':
							$fielddata[$fieldname] = $this->convert_postid_to_firebasepostid($field['value']);
							break;

						case 'taxonomy':
							$fielddata[$fieldname] = $this->convert_term_to_firebaseterm($field['value'], $field['taxonomy']);
							break;

						case 'file':
							$fielddata[$fieldname] = acf_format_value($field['value'], $postid, $field);

							break;

						case 'image':
							$fielddata[$fieldname] = acf_format_value($field['value'], $postid, $field);

							break;


						default:
							$fielddata[$fieldname] = $field['value'];
					}
				}



				if ($acfoption == 'merge')
				{
					//Merge the meta data directly into the key:
					$thisdata = array_merge($thisdata, $fielddata);
				}
				else
				{
					//Create a subkey:
					$thisdata[$map['fields']] = $fielddata;
				}
			}

		}

		if ($metaoption != 'off')
		{
			if ($metaoption == 'merge')
			{
				//Merge the meta data directly into the key:
				$thisdata = array_merge($thisdata, $metadata);
			}
			else
			{
				//Create a subkey:
				$thisdata[$map['meta']] = $metadata;
			}
		}



		if ($termsoption != 'off')
		{
			//Store this post's taxonomy terms

			$taxs = get_object_taxonomies($posttype);
			$termsdata = array();

			foreach ($taxs as $tax)
			{
				$terms = wp_get_post_terms($postid, $tax);
				//$this->log("Post id ".$postid." has ".count($terms)." terms in the ".$tax." taxonomy.");
				if (count($terms) > 0)
				{
					$termsdata[$tax] = array();

					foreach ($terms as $term)
					{
						$termsdata[$tax][] = $this->convert_term_to_firebaseterm($term, $tax);
					}
				}

			}

			if ($termsoption == 'merge')
			{
				//Merge the meta data directly into the key:
				$thisdata = array_merge($thisdata, $termsdata);
			}
			else
			{
				//Create a subkey:
				$thisdata[$map['terms']] = $termsdata;
			}
		}



		//Get the FireBase key under which we are going to store this post in Firebase:

		$key = $this->create_post_firebasekey($postid);

		//Key cannot be empty.
		if (empty($key))
		{
			$this->logError('Error trying to save post '.$postid. ' to Firebase: Key field was empty or not found!', $thisdata);
			$this->setAdminNoticeError('Error trying to save post '.$postid. ' to Firebase: Key field was empty or not found!');
			return false;
		}

		//# Actual saving to FireBase:

		$thispath = $this->get_database_path($posttype, $key);

			try
			{

				//$existing = $this->firebase->exists($thispath);
				//$this->log("Retrieving any existing data at ".$thispath, $existing);

				if ($isupdate)
				{
					$this->log("Updating firebase. Updating key ".$thispath);
					$res = $this->firebase->update($thisdata, $thispath);
				}
				else
				{
					$this->log("Updating firebase. Setting key ".$thispath);
					$res = $this->firebase->set($thisdata, $thispath);
				}

				if ($res < 300)
				{
					$this->setAdminNoticeSuccess('Post saved to Firebase.');
				}
				else
				{
					$this->logError('Error trying to save post '.$postid. ' to Firebase: HTTP Status '.$res.' returned.');

					$this->setAdminNoticeError('Error trying to save post '.$postid. ' to Firebase: HTTP Status '.$res.' returned.');
				}
			}
			catch (Exception $e)
			{
				$this->logError('Error trying to save post '.$postid. ' to Firebase: '. $e->getMessage());

				$this->setAdminNoticeError('Error trying to save post '.$postid. ' to Firebase: '. $e->getMessage());
			}

		return true;
	}

	public function delete_post_from_firebase($postid)
	{
		$this->log("delete_post: Got request to delete post ".$postid. ".");

		$posttype = get_post_field('post_type', $postid, 'raw');

		if (in_array($posttype, $this->posttypestosave))
		{
			$this->log("delete_post: Got request to delete post ".$postid. " which is type ".$posttype);


			if (!$this->isSetup)
			{
				//$this->setAdminNoticeError('Error trying to remove post: FirePress is not setup!');
				$this->log("Firepress isnt setup. Unable to delete post ".$postid." from Firebase.");
				return;
			}

			if (!apply_filters("bfp_should_delete_post", true, $postid, $posttype))
			{
				$this->log("Aborted deleting post ". $postid." from Firebase because of a filter running on bfp_should_delete_post.");
				return;
			}

			$this->log("delete_post: About to get key for post ".$postid);
			//Get the FireBase key under which we are going to store this post in Firebase:
			$key = $this->get_post_firebasekey($postid, false);

			if (!empty($key))
			{

				try
				{
					$res = $this->firebase->delete($this->get_database_path($posttype, $key));

					if ($res < 300)
					{
						$this->setAdminNoticeSuccess('Post has been removed from Firebase.');

						do_action("bfp_post_deleted", $postid, $key);
					}
					else
					{
						$this->setAdminNoticeError('Error trying to remove post '.$postid. ' from Firebase: HTTP Status '.$res.' returned.');
					}
				}
				catch (Exception $e)
				{
					$this->setAdminNoticeError('Error trying to remove post '.$postid. ' from Firebase: '. $e->getMessage());
				}
			}
			else
			{
				$this->setAdminNoticeError('Error trying to remove post '.$postid. ' from Firebase: Key field was empty or not found!');
			}
		}

	}

	public function makeResult($success, $msg)
	{
		return array("success"=>$success, "msg"=>$msg);
	}


	public function resync()
	{
		if (!$this->isFirebaseSetup())
		{
			return $this->makeResult(false, "FirePress is not set up correctly!");
		}

		$posts = get_posts(array(
			'posts_per_page' => -1,
			'post_status' => array('publish', 'trash', 'private', 'future'),
			'post_type' => $this->posttypestosave
		));

		$this->firebase->delete(trailingslashit($this->basepath));

		$cnt = 0;
		foreach ($posts as $post)
		{

			$res = $this->send_post_to_firebase($post->ID, $post, false);

			if (!$res)
			{
				return $this->makeResult(false, "Sync did not complete. Synced ".$cnt . " of ".count($posts). " posts.");
			}

			$cnt++;
		}

		return $this->makeResult(true, "Sync Successful! ".count($posts). " posts synced.");
	}


	/**
	 * Runs a query on the WPDB
	 *
	 * @param $table
	 * @param array $conditions
	 * @param array $exclusions
	 * @return array|mixed|null|object
	 */
	public function sqlQuery($table, $conditions = array(), $exclusions = array())
	{
		/** @var WPDB $wpdb */
		global $wpdb;

		$sql = 'SELECT * FROM '.$table;

		$where = array();
		foreach ($conditions as $key=>$val)
		{
			if (!is_numeric($val)) $val = '"'.$val.'"';
			$where[] = $key . ' = '.$val;
		}


		foreach ($exclusions as $key=>$exc)
		{
			if (!is_array($exc) || count($exc) == 0) continue;

			$exc = array_map(function($item) { return is_numeric($item) ? esc_sql($item) : '"'.esc_sql($item).'"'; }, $exc);
			$where[] = $key . ' NOT IN ('.implode(',', $exc).')';
		}

		if (count($where) > 0)
		{
			$sql .= ' WHERE '. implode(' AND ', $where);
		}

		//$this->wpsync->log("Executing SQL: ".$sql);
		$result = $wpdb->get_results($sql, ARRAY_A);

		return $result;

	}


	function admin_notices()
	{
		$this->adminnotice_error = get_transient('firepress_adminnotice_error');
		$this->adminnotice_success = get_transient('firepress_adminnotice_success');

		if (!empty($this->adminnotice_error))
		{
			echo '<div class="notice notice-error"><p>'.$this->adminnotice_error.'</p></div>';
			delete_transient('firepress_adminnotice_error');
		}

		if (!empty($this->adminnotice_success))
		{
			echo '<div class="notice notice-success is-dismissible"><p>'.$this->adminnotice_success.'</p></div>';
			delete_transient('firepress_adminnotice_success');
		}

		$this->adminnotice_error = '';
		$this->adminnotice_success = '';
	}



	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Brave_Firepress_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

	public function get_key_path( $keyfile )
	{
		$path = trailingslashit(plugin_dir_path(dirname(__FILE__)) . DIRECTORY_SEPARATOR. 'accounts') . $keyfile;

		if (empty($keyfile)) return $path;

		if (strtolower(substr($path, -5, 5)) != '.json') $path .= '.json';
		return $path;
	}

}
