### Brave Firepress ###

License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

## Description ##

Brave Firepress synchronises all your wordpress posts with a Firebase real-time database.
After setting up the link between WordPress and Firebase, you can choose which posts to write across, the root path for all WordPress posts (defaults to '/wp/') 
and which fields of the posts should be copied across to Firebase and how they should appear in the Firebase database.
Post meta, terms and taxonomies are supported and can either be saved into a 'terms'/'meta' key for each post, or merged into the main key for that post.
Advanced Custom Fields are also natively supported and can either be saved to a 'fields' key for each post, or merged into the main key for that post.

A list of field mappings is given to allow you to customise how your WordPress post data shows up in Firebase, and which fields to include or exclude.


## Installation ##

 * Install the plugin as per usual, through the WordPress plugin repository or by uploading the zip file manually to your site and activate it.
 * Once activated, you can find the plugin under *Settings -> Firepress*
 * Go to your Firebase Console and navigate to the [Service Accounts tab](https://console.firebase.google.com/project/_/settings/serviceaccounts/adminsdk) in your Firebase project's settings page.
 * Select your Firebase project. If you don't already have one, click the Create New Project button. If you already have an existing Google project associated with your app, click Import Google Project instead.
 * Click the Generate New Private Key button at the bottom and download the .json file provided.
 * Upload your .json credential files to the wp-content/plugins/brave-firepress/accounts/ directory.
 * Go to the plugin's settings page and:
   * Enter your Firebase install's URL - you can find it on your Real Time Database's page inside the Firebase console.
   * Select your .json credential file from the dropdown provided after you've copied it into the plugin's /accounts/ directory.
   * Select post types which you'd like to synchronise to your Firebae database.
   * Select how you would like WordPress to synchronise your post types into your Firebase database.
   * Save your settings. If all goes well, you should see a message informing you that your Firebase and WordPress installs are successfully connected together.
