<?php
/**
 * Provide a admin area view for the plugin
 *
 * This file is used to markup the admin-facing aspects of the plugin.
 *
 * @link       http://www.bravedigital.com
 * @since      1.0.0
 *
 * @package    Brave_Firepress
 * @subpackage Brave_Firepress/admin/partials
 */

	/** @var Brave_Firepress_Admin $pluginadmin */
	$pluginadmin = $GLOBALS['firepress_admin']; //Set in the class-wordsync-admin.php file just before this file is included.

	// check if the user have submitted the settings
	// wordpress will add the "settings-updated" $_GET parameter to the url
	if (isset($_GET['settings-updated']))
	{
		//Clear the clean setup flag because we've changed settings and dont know yet if they work.
		$pluginadmin->getPlugin()->clear_setup_flag();
		// add settings saved message with the class of "updated"
		add_settings_error('firepress_messages', 'firepress_messages', __('Settings Saved', 'brave-firepress'), 'updated');
	}

?>
<div class="wrap">

	<div class="bravewrap">
		<div class="braveheader fullheader">
			<div class="logo"></div>
			<span class="maintitle"><?php echo get_admin_page_title(); ?> <small><?php echo $pluginadmin->getPlugin()->get_version(); ?></small></span>
			<div class="controls">
				<!-- <a class="submit-changes button button-primary" href="<?php echo $pluginadmin->getAdminUrl(); ?>"><?php _e('Back', 'wordsync'); ?></a> -->
			</div>
		</div>
		<div class="bravebody">
			<?php
				// show error/update messages
				settings_errors('firepress_messages');
			?>

			<?php
				if (!$pluginadmin->getPlugin()->isFirebaseSetup()): ?>
				<br/>
				<div class="stuffbox welcomebox">
					<div class="inside">
						<h3>Welcome to FirePress</h3>
						<h4>Setup:</h4>
						<ol>
							<li>Navigate to the <a href="https://console.firebase.google.com/project/_/settings/serviceaccounts/adminsdk">Service Accounts tab</a> in your Firebase project's settings page.</li>
							<li>Select your Firebase project. If you don't already have one, click the Create New Project button. If you already have an existing Google project associated with your app, click Import Google Project instead.</li>
							<li>Click the Generate New Private Key button at the bottom and download the .json file provided.</li>
							<li>Upload your .json credential files to the <code>wp-content/plugins/brave-firepress/accounts/</code> directory and enter it's filename (including the ".json") in the form below.</li>
						</ol>
					</div>
				</div>
			<?php endif; ?>

			<form action="options.php" method="post">
				<?php
					$option_group = $pluginadmin->getSlug();

					echo "<input type='hidden' name='option_page' value='" . esc_attr($option_group) . "' />";
					echo '<input type="hidden" name="action" value="update" />';
					wp_nonce_field("$option_group-options", "_wpnonce", false, true);

					//Get the referrer field but remove the settings=1 parameter off the url so that when the settings are saved, the user is returned to the main WordSync page.
					$ref = wp_referer_field(false);
					$ref = str_replace("&amp;settings=1", "", $ref);
					echo $ref;

					do_settings_sections($pluginadmin->getSettingsPage());
					// output save settings button
					submit_button(__('Save Settings', 'brave-firepress'));
				?>
			</form>


			<div class="">
				<h3>Sync Firebase with your data</h3>
				<p class="warning">This will DELETE your entire <code><?php echo get_option(Brave_Firepress::SETTING_DATABASE_BASEPATH, '/');?></code> key in FireBase and refresh it from your entire WordPress database. Please double check that the key is correct before proceeding. This could take a while.</p>
				<a href="#" class="button-primary button btn-resyncfirebase">Sync Firebase</a>
				<p class="sync-output"></p>
			</div>


		</div>

	</div>
</div>