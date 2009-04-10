<?php
/*
Plugin Name: Twitter Friends Widget - Advanced Settings
Plugin URI: http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget/
Description: Advanced edit, update and uninstall options for Twitter Friends Widget
Version: 2.51
Author: Paul McCarthy
Author URI: http://www.paulmc.org/whatithink
*/

/*  Copyright 2009  Paul McCarthy  (email : paul@paulmc.org)

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

//in order to work, we require the widget functions
require_once("twitter-friends-widget.php");

//function called when the plugin is loaded
function pmcTFAdvanced_init() {
	//add the admin page
	add_action('admin_menu', 'pmcTFAdminMenu');
}

	
//function to add the Twitter Friends Advanced Menu to the settings menu
function pmcTFAdminMenu() {
	add_menu_page('Twitter Friends', 'Twitter Friends', 8, __FILE__, 'pmcTFOptions');
	add_submenu_page(__FILE__, 'Twitter Friends', 'Delete Friends', 8, 'twitter-friends-delete', 'pmcTFEdit');
	add_submenu_page(__FILE__, 'Twitter Friends', 'Manual Update', 8, 'twitter-friends-update', 'pmcTFManualUpdate');
	add_submenu_page(__FILE__, 'Twitter Friends', 'Change Twitter User', 8, 'twitter-friends-user', 'pmcTFChangeUser');
	add_submenu_page(__FILE__, 'Twitter Friends', 'Uninstall', 8, 'twitter-friends-uninstall', 'pmcTFUninstall');
		
} //close pmcTFAdminMenu()
	
//function to display the main Advanced Settings Page
function pmcTFOptions() {
	echo '<div class="wrap">';
	echo '<h2>Twitter Friends Advanced Settings</h2>';
	echo '<p>This page contains advanced settings for the Twitter Friends Plugin. To change the display and output settings for the Twitter Friends Widget, please click <a href="./widgets.php" title="Widgets">here</a>.</p>';
	echo '<h3>Delete Friends</h3>';
	echo '<p>Delete Twitter friends from the cache. This does not delete the friend from your Twitter Profile. Useful if you want to refresh the profile image or screen name of a small number of Twitter Friends.</p>';
	echo '<h3>Manual Update</h3>';
	echo '<p>Performs a manual update of your Twitter Friends list. Useful if you have added a number of Twitter Friends and don\'t want to wait for the cache to update automatically.</p>';
	echo '<h3>Change Twitter User</h3>';
	echo '<p>Use this option if you have changed your Twitter Username. Automatically updates the widget with details of your new username and rebuilds your Twitter Friends list.</p>';
	echo '<h3>Uninstall</h3>';
	echo '<p>Removes all database settings and deletes the cache. Deactivate the Twitter Friends Widget before performing the uninstallation. Useful if you are experiencing problems with the widget.</p>';
	echo '<h2>Thanks</h2>';
	echo '<p>Thanks to everyone who downloaded and used Twitter Friends Widget, to everyone who left a comment, feedback and bug reports on the <a href="http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget" title="Twitter Friends Widget Homepage">plugin homepage</a>, especially <a href="http://www.lifeofjackass.com" title="Thanks DJ!">DJ</a> who has provided some great ideas, feedback and assistance.</p>';
	echo '<p>Thanks for your patience,<br/>';
	echo '<a href="http://www.paulmc.org/whatithink" title="Paul McCarthy">Paul McCarthy</a></p>';
	echo '</div>';
} //close pmcTFOptions
	
//function to display the Edit Friends Page
function pmcTFEdit() {
	//use wpdb
	global $wpdb;
	
	//set the table name
	$pmcTableName = $wpdb->prefix . 'twitterfriends';
	
	//get the contents of the post variable
	$pmcDelete = $_POST;
	
	//check if the user has clicked a delete button
	if ($pmcDelete) {
		//the returned array contains the id of the user that we want to delete
		$pmcDeleteID = array_keys($pmcDelete);
		
		//build the SQL
		$SQL = "DELETE FROM $pmcTableName WHERE `id` LIKE $pmcDeleteID[0]";
		
		//run the query
		$pmcResult = $wpdb->query($SQL);
		
	} //close if
	
	echo '<div class="wrap">';
	echo '<h2>Twitter Friends In The Database</h2>';

	//call the function to output the list of twitter friends from the database
	pmcDisplayFriendsTable();
		
	echo '</div>';
		
} //close pmcTFEdit
	
//function to display Manual Update page under Advanced Settings
function pmcTFManualUpdate() {
	//see if the user has clicked the update button
	$pmcCheckUpdate = $_POST['update-now'];

	//if the user has clicked the button, we'll run the pmcGetFriends function
	if ($pmcCheckUpdate) {
		pmcGetFriends();
		//we'll echo an update message to the screen
		echo '<div id="message" class="updated fade"><p>Your Twitter friends list has been <strong>updated</strong>.</p></div>';
	}
		
	//display the page info and update form
	echo '<div class="wrap">';
	echo '<h2>Manual Update</h2>';
	echo '<p>If you prefer not to wait for the widget cache to automatically upate, you can perform a manual update by clicking the update button.</p>';
	echo '<form name="manul-update" action="" method="post"><input type="hidden" name="update-now" value="1" /><input type="submit" class="button-primary" value="Update Now" /></form>';
	echo '</div>';
		
} //close pmcTFManualUpdate

//function to display the Twitter User Page
function pmcTFChangeUser() {
	//see if the user has updated their username
	$pmcChangeUser = $_POST['change-user'];
	
	//get the current username from the database
	$pmcOptions = get_option('widget_pmcFriends');
	$pmcUserName = $pmcOptions['pmc_TF_user'];

	if ($pmcChangeUser) {
		//use wpdb
		global $wpdb;
	
		//set the table name
		$pmcTableName = $wpdb->prefix . 'twitterfriends';
	
		//check that the username is different
		if ($pmcChangeUser != $pmcUserName) {
		
			//we'll update the username in the database
			$pmcOptions['pmc_TF_user'] = strip_tags(stripslashes($pmcChangeUser));
			//update the options
			update_option('widget_pmcFriends', $pmcOptions);

			//we also need to update the Twitter ID for RSS feeds etc
			$pmcNewID = pmcRetrieveTwitterID($pmcUserName);
		
			//update the Twitter User ID
			update_option('pmc_TF_ID', $pmcNewID);
					
			//remove the current list of friends from the database
			$SQL = "DROP TABLE " . $pmcTableName;
		
			//run the query
			$pmcResult = $wpdb->query($SQL);
			
			//we now get the new list of friends
			pmcGetFriends();
			
			//display a message
			echo '<div id="message" class="updated fade"><p>Your new username has been saved and your list of friends has been updated.</p></div>';
			
		} else {
			//if the username is blank or the same then display a message
			echo '<div id="message" class="updated fade"><p>Your new username is the same as your current one. <strong>No changes were made to the database.</strong></p></div>';
		} //close inner if
	} //close outer if
	
	//get the new user name
	$pmcNewOptions = get_option('widget_pmcFriends');
	$pmcNewUser = $pmcNewOptions['pmc_TF_user'];
	
	//get the current Twitter ID
	$pmcCurrID = get_option('pmc_TF_ID');
	
	echo '<div class="wrap">';
	echo '<h2>Change Twitter User</h2>';
	echo '<p>If you want to change your Twitter username, then you can do so by filling in your new name below:</p>';
	echo '<p style="color: red; font-weight: bold;">Changing your Twitter username involves making changes to the database. Please backup your database before continuing.</p>';
	echo '<p style="color: red; font-weight: bold;">Updating your username will also update your Twitter Friends list. This process may take several moments depending on the number of Twitter Friends you have.</p>';
	echo '<p style="color: red; font-weight: bold;">Please do not change from this page until the process has completed.</p>';
	echo '<p>Your current username is: <strong>' . $pmcNewUser . '</strong></p>';
	echo '<p>Your current Twitter ID is: <strong>' . $pmcCurrID . '</strong></p>';
	echo '<form name="change-user-form" action="" method="post"><p><label for="change-user">Your New Twitter Username:</label></p><div class="input-text-wrap"><p><input type="text" id="change-user" name="change-user" /></p></div><p><input type="submit" class="button-primary" value="Change Username" /></p></form>';
	echo '</div>';
	
} //close pmcTFChangeUser	

//function to display the Uninstall page under Advanced Settings
function pmcTFUninstall() {
	//see if the user has clicked the uninstall button
	$pmcUninstall = $_POST['uninstall'];
		
	//if the user has clicked the uninstall button, run the uninstall function
	if ($pmcUninstall) {
		pmcUninstallSettings();
			
		//display the confirmation message
		echo '<div id="message" class="updated fade"><p>All widget settings have been <strong>removed</strong>. Thank you for using the Twitter Friends Widget.</p></div>';

	} //close if
		
	echo '<div class="wrap">';
	echo '<h2>Uninstall Twitter Friends Widget</h2>';
	echo '<p>If you are experiencing problems or simply want to remove the Twitter Friends Widget from your WordPress installation, then click the uninstall button.</p>';
	echo '<p><strong>IMPORTANT:</strong> Before uninstalling, please remove the Twitter Friends Widget from your <a href="./widgets.php" title="Widgets">sidebar</a> and deactivate the Twitter Friends Plugin from the <a href="./plugins.php" title="Plugins">Plugins</a> page.</p>';
	echo '<p style="color: red; font-weight: bold;">Proceeding with the uninstall will remove all Twitter Friends settings, cache settings and cache content.</p>';
	echo '<p style="color: red; font-weight: bold;">Please backup your WordPress database before proceeding.</p>';
	echo '<p style="color: red; font-weight: bold;">Uninstallation Of The "Twitter Friends Widget" Settings Cannot Be Undone.</p>';
	echo '<form name="uninstall-form" action="" method="post"><input type="hidden" name="uninstall" value="1" /><input type="submit" class="button-primary" value="Uninstall Twitter Friends" /></form>';
	echo '</div>';
		
} //close pmcTFUninstall
	
//function to display table of Twitter Friends in the Advanced Settings page.
function pmcDisplayFriendsTable() {
	//we'll be using the WordPress database
	global $wpdb;
		
	//set the table name
	$pmcTableName = $wpdb->prefix . 'twitterfriends';
		
	//check that the table exists
	if ($wpdb->get_var("show tables like '" . $pmcTableName . "'") != $pmcTableName) {
		//if it doesn't exist, show a message
		echo '<p>There are no Twitter Friends in the database. You either have not yet <a href="./widgets.php" title="Configure the Twitter Friends Widget">configured</a> the Twitter Friends widget, or you have uninstalled the plugin settings.</p>';
	} else {
		//if the table does exist we can get the list of Twitter Friends
			
		//set up the sql
		$pmcSQL = "SELECT * FROM `" . $pmcTableName . "`";
			
		//run the sql query
		$pmcResult = $wpdb->get_results($pmcSQL);
			
		//check that we have a result that we can work with
		if ($pmcResult) {

			//if we do we start building the table
			//table header
			echo '<table class="widefat" cellspacing="0">' . "\n";
			echo '<thead>' . "\n";
			echo '<tr>' . "\n";
			echo '<th scope="col" class="manage-column">ID</th>' . "\n";
			echo '<th scope="col" class="manage-column">Profile Image</th>' . "\n";
			echo '<th scope="col" class="manage-column">Screen Name</th>' . "\n";
			echo '<th scope="col" class="manage-column">Delete</th>' . "\n";
			echo '</tr>' . "\n";
			echo '</thead>' . "\n";
				
			//start the table body
			echo '<tbody>' . "\n";
							
			//loop through the results
			foreach ($pmcResult as $pmcFriend) {

				//build the table using the profile image and the screen name
				echo '<tr>' . "\n";
				echo '<td>' . $pmcFriend->id . '</td>' . "\n";
				echo '<td><img style="width: 24px; height: 24px;" src="' . $pmcFriend->profile_image_url . '" title="' . $pmcFriend->screen_name . '" /></td>' . "\n";						echo '<td><a href="http://twitter.com/' . $pmcFriend->screen_name . '" title="' . $pmcFriend->screen_name . '">' . $pmcFriend->screen_name . '</a></td>' . "\n";
				echo '<td><form name="delete-' . $pmcFriend->id . '" action="" method="post"><input type="hidden" name="' . $pmcFriend->id . '" value="1" /><input type="submit" class="button-secondary" value="Delete" /></form></td>' . "\n";
						
			} //close foreach
				
			//close the table
			echo '</table>' . "\n";
		} else {
			//if we are unable to display the Twitter Friends
			echo '<p>Unable to display your Twitter Friends at this time. Please refresh the page and try again.</p>';
		} //close inner if
					
	} //close outer if
					
} //close pmcDisplayFriendsTable

//function to uninstall all widget settings
function pmcUninstallSettings() {
	//use the wpdb
	global $wpdb;
	
	//set the table name
	$pmcTableName = $wpdb->prefix . 'twitterfriends';
	
	//before we try to remove the table, we'd better check that it exists
	//check to see if the table already exists
	if ($wpdb->get_var("show tables like '" . $pmcTableName . "'") == $pmcTableName) {
		
		//build the SQL to drop the table
		$SQL = 'DROP TABLE ' . $pmcTableName;
		
		//run the query
		$pmcResult = $wpdb->query($SQL);
	
	}
	
	//we now need to remove all settings from the database
	//remove the users Twitter ID
	delete_option('pmc_TF_ID');
	
	//remove the widget_settings
	delete_option('widget_pmcFriends');
	
	//remove the last cache update time
	delete_option('pmc_last_update');
	
} //close pmcUninstallSettings()

//make sure that the plugin is not loaded until after the widgets are
add_action("plugins_loaded", "pmcTFAdvanced_init");
?>