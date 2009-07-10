<?php
/*
Plugin Name: Twitter Friends Widget
Plugin URI: http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget/
Description: Widget to display your Twitter Friends in the sidebar.
Version: 3.1
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

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//-------------------------- PLUGIN OPTIONS ------------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//THESE ARE THE VARIOUS OPTIONS STORED IN THE DATABASE
//pmc_TF_friends - stores number of friends
//pmc_TF_followers - stores number of followers
//pmc_TF_db - stores db version
//pmc_TF_user - twitter user name
//pmc_TF_password - twitter password
//pmc_TF_title - widget title
//pmc_TF_title_link - link for widget title
//pmc_TF_type - display friends or followers
//pmc_TF_limit - number of friends to display
//pmc_TF_image_size - display mini, normal or bigger profile image
//pmc_TF_show_rss - show a link to the users rss page?
//pmc_TF_cache - cache update value, in seconds
//pmc_TF_friends_time - timestamp for last friends update
//pmc_TF_followers_time - timestamp for last followers update
//pmc_TF_ID - users numeric Twitter ID
//pmc_TF_show_counts - display friends and followers counts
//pmc_TF_show_follow - display a button to automatically follow on Twitter

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//------------------------ MENU DISPLAY FUNCTIONS ------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

//function to add admin page
function pmcAdminPage_init() {
	add_action('admin_menu', 'pmcAdminPage');
	
	//check if the db needs to be updated
	pmcUpdateDBStruct();
}

//function to write admin page and sub pages
function pmcAdminPage() {
	add_menu_page('Twitter Friends', 'Twitter Friends', 8, __FILE__, 'pmcTFAdmin');
	add_submenu_page(__FILE__, 'Twitter Friends', 'Widget Settings', 8, 'twitter-friends-widget', 'pmcTFOptions');
	add_submenu_page(__FILE__, 'Twitter Friends', 'Change Twitter User', 8, 'twitter-friends-user', 'pmcTFChangeUser');
	add_submenu_page(__FILE__, 'Twitter Friends', 'Uninstall', 8, 'twitter-friends-uninstall', 'pmcTFUninstall');
}

//function to display admin page
function pmcTFAdmin() {
	//get options
	$pmcFriends = get_option('pmc_TF_friends');
	$pmcFollowers = get_option('pmc_TF_followers');
	$pmcUser = get_option('pmc_TF_user');
	$pmcType = get_option('pmc_TF_type');
	
	//check if the user has changed the current view
	if ($_POST['display-type']) {
		$pmcDisplayType = $_POST['display-type'];
	} else {
		//display friends by default
		$pmcDisplayType = 'friends';
	}
	
	//check if the user has deleted a friend
	if ($_POST['delete-friend']) {
		//use wpdb class
		global $wpdb;
		
		//set table name
		$pmcTable = $wpdb->prefix . 'twitterfriends';
		
		//get the id of the Twitter friend to be deleted
		$pmcDeleteID = $_POST['delete-friend'];
		
		//build sql to delete friend from table
		$pmcSQL = "DELETE FROM $pmcTable WHERE `id`=$pmcDeleteID";
		
		//execute query
		$pmcResult = $wpdb->get_results($pmcSQL);
		
		//get new friends
		pmcGetCounts();
		pmcGetFriends();
		
		//display success result
		echo '<div id="message" class="updated fade"><p>Successfully updated the database.</p></div>';
	}

	//page contents
	echo '<div class="wrap">';
	echo '<h2>Twitter Friends Widget</h2>';
	echo '<p>Welcome <strong>' . $pmcUser . '</strong> (<a href="?page=twitter-friends-user" title="Change Twitter User">Change Twitter User</a>)</p>';
	echo '<p>You currently have <strong>' . $pmcFriends . ' Friends</strong> and <strong>' . $pmcFollowers . ' Followers</strong>.</p>';
	echo '<p>Twitter Friends Widget is currently set to display your <strong>Twitter ' . ucfirst($pmcType) . ' </strong>. (<a href="?page=twitter-friends-widget" title="Change Widget Settings">Change Widget Settings</a>)</p>';
	echo '<form action="" method="post">';
	echo '<label for="display-type">Show: </label>';
	echo '<select id="display-type" name="display-type"><option value="friends">Friends</option><option value="followers">Followers</option></select>';
	echo '<input type="submit" value="Apply" class="button-secondary" />';
	echo '</form>';
	
	echo '<h3>Current ' . ucfirst($pmcDisplayType) . '</h3>';
	
	//display the output table based on user preference
	pmcTFDisplay($pmcDisplayType);
	
	//cloe div
	echo '</div>';
}

//function to display pre-WP 2.7 widget settings form
function pmcTFOptions() {
	//array to hold options for title link
	$pmcTitleLinkOpts = array(
		'none' => 'None',
		'page' => 'My Twitter Page',
		'rss' => 'My Twitter RSS'
	);
	
	//array to hold options for display
	$pmcDisplayOpts = array(
		'friends' => 'Friends',
		'followers' => 'Followers'
	); 
	
	//array to hold options for RSS link
	$pmcRSSOpts = array(
		'yes' => 'Yes',
		'no' => 'No'
	);
	
	//array to hold image sizes
	$pmcImageOpts = array(
		'mini' => 'Mini',
		'normal' => 'Normal',
		'bigger' => 'Large'
	);
	
	//array to hold options for showing friends/ followers counts
	//same as the RSS options
	$pmcCountsOpts = $pmcRSSOpts;
	
	//array to hold options for follow button
	//same as RSS options
	$pmcFollowOpts = $pmcRSSOpts;
		
	//build the form
	echo '<div class="wrap">';
	echo '<h2>Twitter Friends Widget Settings</h2>';
	echo '<p>Here is where you can change the settings for the Twitter Friends Widget.</p>';
	echo '<div style="display: block;">';
	echo '<form action="options.php" method="post">';
	
	echo '<fieldset style="display: block; float: left; border: 1px solid #aaa; background-color: #eee; padding: 10px; margin: 0 20px 40px 20px; width: 320px;"><legend style="padding: 0 5px; color: #666;">User Settings</legend>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_user">Twitter User Name:</label><input style="display:block; width: 300px; margin: 10px 0;" type="text" name="pmc_TF_user" id="pmc_TF_user" value="' . get_option('pmc_TF_user') . '" /></p>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_password">Twitter Password:</label><input style="display:block; width: 290px; margin: 10px 0;" type="password" name="pmc_TF_password" id="pmc_TF_password" value="' . get_option('pmc_TF_password') . '" /></p>';
	echo '</fieldset>';
	
	echo '<fieldset style="display: block; float: left; clear: left; border: 1px solid #aaa; background-color: #eee; padding: 10px; margin: 40px 20px; width: 320px;"><legend style="padding: 0 5px; color: #666;">Cache Settings</legend>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_cache">Cache Update Interval (in seconds):</label><input style="display:block; width: 300px; margin: 10px 0;" type="text" name="pmc_TF_cache" id="pmc_TF_cache" value="' . get_option('pmc_TF_cache') . '" /></p>';
	echo '</fieldset>';
	
	echo '<fieldset style="display: block; border: 1px solid #aaa; background-color: #eee; padding: 10px; margin: 40px 20px; width: 320px;"><legend style="padding: 0 5px; color: #666;">Output Settings</legend>';
	echo '<p><label for="pmc_TF_title" style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;">Widget Title:</label><input style="display:block; width: 300px; margin: 10px 0;" type="text" name="pmc_TF_title" id="pmc_TF_title" value="' . get_option('pmc_TF_title') . '" /></p>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_title_link">Title Link:</label><select style="display:block; width: 300px; margin: 10px 0;" name="pmc_TF_title_link" id="pmc_TF_title_link">';
	echo pmcWriteSelect($pmcTitleLinkOpts, get_option('pmc_TF_title_link'));
	echo '</select></p>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_type">Display Friends or Followers</label><select style="display:block; width: 300px; margin: 10px 0;" name="pmc_TF_type" id="pmc_TF_type">';
	echo pmcWriteSelect($pmcDisplayOpts, get_option('pmc_TF_type'));
	echo '</select></p>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_limit">Display Limit:</label><input style="display:block; width: 300px; margin: 10px 0;" type="text" name="pmc_TF_limit" id="pmc_TF_limit" value="' . get_option('pmc_TF_limit') . '" /></p>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_image_size">Profile Image Size:</label><select style="display:block; width: 300px; margin: 10px 0;" name="pmc_TF_image_size" id="pmc_TF_image_size">';
	echo pmcWriteSelect($pmcImageOpts, get_option('pmc_TF_image_size'));
	echo '</select></p>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px; 0" for="pmc_TF_show_rss">Show RSS Link?</label><select style="display:block; width: 300px; margin: 10px;" name="pmc_TF_show_rss" id="pmc_TF_show_rss">';
	echo pmcWriteSelect($pmcRSSOpts, get_option('pmc_TF_show_rss'));
	echo '</select></p>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px; 0" for="pmc_TF_show_counts">Show Friends &amp; Followers Counts?</label><select style="display:block; width: 300px; margin: 10px;" name="pmc_TF_show_counts" id="pmc_TF_show_counts">';
	echo pmcWriteSelect($pmcCountsOpts, get_option('pmc_TF_show_counts'));
	echo '</select></p>';
	echo '</fieldset>';
	
	echo '<p style="display: block; clear: both; margin: 20px;" ><input type="submit" value="Save settings" class="button-primary" /><input type="reset" value="Cancel" class="button-primary" /></p>';
	
	//check if the settings api is supported by looking for the register_setting function
	if (!function_exists('register_setting')) {
		//pre WP 2.7 functionality
		wp_nonce_field('update-options');
		echo '<input type="hidden" name="action" value="update" />';
		echo '<input type="hidden" name="page_options" value="pmc_TF_user,pmc_TF_password,pmc_TF_title,pmc_TF_title_link,pmc_TF_type,pmc_TF_limit,pmc_TF_image_size,pmc_TF_show_rss,pmc_TF_cache,pmc_TF_show_counts" />';
	} else {
		//WP 2.7+ functionality
		//also reqd for WPMU compatibility
		settings_fields('twitter-friends-widget');
	}
	
	echo '</form>';
	echo '</div>';
	echo '</div>';	
}

//function to create sections and fields for WP 2.7+ options form - required for WPMU 2.7 compatibility
function pmcTFOptions_init() {	
	
	//register the settings
	register_setting('twitter-friends-widget', 'pmc_TF_user');
	register_setting('twitter-friends-widget', 'pmc_TF_password');
	register_setting('twitter-friends-widget', 'pmc_TF_cache');
	register_setting('twitter-friends-widget', 'pmc_TF_title');
	register_setting('twitter-friends-widget', 'pmc_TF_title_link');
	register_setting('twitter-friends-widget', 'pmc_TF_type');
	register_setting('twitter-friends-widget', 'pmc_TF_limit');
	register_setting('twitter-friends-widget', 'pmc_TF_image_size');
	register_setting('twitter-friends-widget', 'pmc_TF_show_rss');
	register_setting('twitter-friends-widget', 'pmc_TF_show_counts');
}

//function to change Twitter User
function pmcTFChangeUser() {
	
	//check if the user has submitted the form
	if ($_POST['update_user']) {
		//security check
		check_admin_referer('change-tf-user');
		
		//use wpdb class
		global $wpdb;
		
		//set table name
		$pmcTable = $wpdb->prefix . 'twitterfriends';
		
		//check that the user has entered a username and password
		$pmcUser = $_POST['pmc_TF_user'];
		$pmcPass = $_POST['pmc_TF_password'];
		
		if ($pmcUser != '' and $pmcPass != '') {
			//update the username
			update_option('pmc_TF_user', $pmcUser);
			//update the password
			update_option('pmc_TF_password', $pmcPass);
			//delete current friends from the database
			$pmcSQL = "DELETE FROM $pmcTable WHERE `id` LIKE '%'";
			//run the query
			$pmcResult = $wpdb->get_results($pmcSQL);
			
			//get the friends for the new username
			pmcGetCounts();
			pmcGetFriends();
			pmcGetFollowers();
			
			//build message
			$pmcMessage = 'Your Twitter user name and password have been updated. The database has been updated with your the friends for your new user name.';
	} else {
		//check if the username or password have been left blank
		//start output message
		$pmcMessage = 'Unable to update your Twitter details. Please provide the following:  ';
		
		if ($pmcUser == '') {
			$pmcMessage .= ' Twitter user name. ';
		}
		
		if ($pmcPass == '') {
			$pmcMessage .= ' Twitter password. ';
		}
		
	}

	//display the message
	echo '<div class="updated fade"><p>' . $pmcMessage . '</p></div>';

	}
	
	//build the form
	echo '<div class="wrap">';
	echo '<h2>Change Twitter User</h2>';
	echo '<p>If you need to change your Twitter user name and password, you can do so here.</p>';
	echo '<form action="" method="post">';
	wp_nonce_field('change-tf-user');
	echo '<table class="form-table">';
	echo '<tr><td>';
	echo '<fieldset style="border: 1px solid #aaa; background-color: #eee; padding: 10px; margin 10px; width: 320px;"><legend style="padding: 0 5px; color: #666;">User Settings</legend>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_user">Twitter User Name:</label><input style="display:block; width: 300px; margin: 10px 0;" type="text" name="pmc_TF_user" id="pmc_TF_user"';
	if ($_POST['pmc_TF_user']) {
		echo ' value="' . $_POST['pmc_TF_user'] . '"';
	} else {
		echo ' value="' . get_option('pmc_TF_user') . '"';
	}
	echo ' /></p>';
	echo '<p><label style="display:block; width: 300px; margin: 10px 0; padding: 10px 0;" for="pmc_TF_password">Twitter Password:</label><input style="display:block; width: 290px; margin: 10px 0;" type="password" name="pmc_TF_password" id="pmc_TF_password"';
	if ($_POST['pmc_TF_password']) {
		echo ' value="' . $_POST['pmc_TF_password'] . '"';
	} else {
		echo ' value="' . get_option('pmc_TF_password') . '"';
	}
	echo ' /></p>';
	echo '</fieldset>';
	echo '</td></tr>';
	echo '<tr><td>';
	echo '<p><input type="submit" value="Save settings" class="button-primary" /><input type="reset" value="Cancel" class="button-primary" /></p>';
	echo '</td></tr></table>';
	echo '<input type="hidden" name="update_user" value="1" />';
	echo '</form>';
	echo '</div>';
	
}

//function to display uninstall page
function pmcTFUninstall() {
	//use wpdb class
	global $wpdb;
	$wpdb->show_errors();
	
	//set table name
	$pmcTable = $wpdb->prefix . 'twitterfriends';
	
	//check if the form has been submitted
	if ($_POST['uninstall']) {
		//security check
		check_admin_referer('tf-uninstall');
		
		//delete the various options from the database
		if (delete_option('pmc_TF_friends')) echo '<p>Deleted pmc_TF_friends</p>';
		if (delete_option('pmc_TF_followers')) echo '<p>Deleted pmc_TF_followers</p>';
		if (delete_option('pmc_TF_db')) echo '<p>Deleted pmc_TF_db</p>';
		if (delete_option('pmc_TF_user')) echo '<p>Deleted pmc_TF_user</p>';
		if (delete_option('pmc_TF_password')) echo '<p>Deleted pmc_TF_password</p>';
		if (delete_option('pmc_TF_title')) echo '<p>Deleted pmc_TF_title</p>';
		if (delete_option('pmc_TF_title_link')) echo '<p>Deleted pmc_TF_title_link</p>';
		if (delete_option('pmc_TF_type')) echo '<p>Deleted pmc_TF_type</p>';
		if (delete_option('pmc_TF_limit')) echo '<p>Deleted pmc_TF_limit</p>';
		if (delete_option('pmc_TF_image_size')) echo '<p>Deleted pmc_TF_image_size</p>';
		if (delete_option('pmc_TF_show_rss')) echo '<p>Deleted pmc_TF_show_rss</p>';
		if (delete_option('pmc_TF_cache')) echo '<p>Deleted pmc_TF_cache</p>';
		if (delete_option('pmc_TF_bgcolor')) echo '<p>Deleted pmc_TF_bgcolor';
		if (delete_option('pmc_TF_fgcolor')) echo '<p>Deleted pmc_TF_fgcolor';
		if (delete_option('pmc_TF_friends_time')) echo '<p>Deleted pmc_TF_friends_time</p>';
		if (delete_option('pmc_TF_followers_time')) echo '<p>Deleted pmc_TF_followers_time</p>';
		if (delete_option('pmc_TF_ID')) echo '<p>Deleted pmc_TF_ID</p>';
		if (delete_option('pmc_TF_show_follow')) echo '<p>Deleted pmc_TF_show_follow/p>';
		
		//drop the table from the table
		$pmcSQL = "DROP TABLE $pmcTable";
		//execute the query
		$pmcResult = $wpdb->query($pmcSQL);
		
		//display message
		if ($pmcResult) {
			echo '<div class="updated fade"><p>Successfully removed all settings from the database</p></div>';
		} else {
			echo '<div class="updated fade"><p>Unable to remove the settings from the database. Please try again.</p></div>';
		}
	}
			
	echo '<div class="wrap">';
	echo '<h2>Uninstall Twitter Friends Widget</h2>';
	echo '<p>If you are experiencing problems or simply want to remove the Twitter Friends Widget from your WordPress installation, then click the uninstall button.</p>';
	echo '<p><strong>IMPORTANT:</strong> Before uninstalling, please remove the Twitter Friends Widget from your <a href="./widgets.php" title="Widgets">sidebar</a>.</p>';
	echo '<p style="color: red; font-weight: bold;">Proceeding with the uninstall will remove all Twitter Friends settings, cache settings and cache content.</p>';
	echo '<p style="color: red; font-weight: bold;">Please backup your WordPress database before proceeding.</p>';
	echo '<p style="color: red; font-weight: bold;">Uninstallation Of The "Twitter Friends Widget" Settings Cannot Be Undone.</p>';
	echo '<form action="" method="post">';
	wp_nonce_field('tf-uninstall');
	echo '<input type="hidden" name="uninstall" value="1" /><input type="submit" class="button-primary" value="Uninstall Twitter Friends" /></form>';
	echo '</div>';
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//---------------------- TWITTER API FUNCTIONS ---------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

//function to get friends and followers count from Twitter
function pmcGetCounts() {
	//get options
	$pmcUser = get_option('pmc_TF_user');
	
	//define the Twitter API url
	$pmcURL = 'http://twitter.com/users/show/' . $pmcUser . '.xml';
	
	//set up the curl options
	$pmcCurl = curl_init();
	curl_setopt($pmcCurl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($pmcCurl, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($pmcCurl, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($pmcCurl, CURLOPT_URL, $pmcURL);
	
	//get the contents
	$pmcContent = curl_exec($pmcCurl);
	
	//search for the friends_count, followers count and user ID
	preg_match_all('/<friends_count>(.*)<\/friends_count>/', $pmcContent, $pmcFriendsCount);
	preg_match_all('/<followers_count>(.*)<\/followers_count>/', $pmcContent, $pmcFollowersCount);
	preg_match_all('/<user>\s*<id>(.*)<\/id>/', $pmcContent, $pmcUserID);
	
	//get the counts and ID
	$pmcNumFriends = strip_tags($pmcFriendsCount[0][0]);
	$pmcNumFollowers = strip_tags($pmcFollowersCount[0][0]);
	$pmcID = strip_tags($pmcUserID[0][0]);
	
	//update the options in the database
	update_option('pmc_TF_friends', $pmcNumFriends);
	update_option('pmc_TF_followers', $pmcNumFollowers);
	update_option('pmc_TF_ID', $pmcID);
	
}

//function to get friends from Twitter
function pmcGetFriends() {
	//get user name
	$pmcUser = get_option('pmc_TF_user');

	$pmcCount = get_option('pmc_TF_friends');
	
	//set the minimum number of pages to get
	$pmcNumPages = 0;
	
	//twitter returns a maximum of 100 friends at a time so we need to check how many pages we need to get to retrieve all friends
	while ($pmcCount > 0) {	
		$pmcNumPages++;
		$pmcCount = $pmcCount - 100;
	}
	
	//set up curl
	$pmcCurl = curl_init();
	curl_setopt($pmcCurl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($pmcCurl, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($pmcCurl, CURLOPT_CONNECTTIMEOUT, 2);
	
	//loop through the number of reqd pages and get the contents
	for ($i=1; $i<=$pmcNumPages; $i++) {
		//set the url to retrieve friends from Twitter dependent on the type to retrieve
			$pmcURL = 'http://twitter.com/statuses/friends/' . $pmcUser . '.xml?page=' . $i;
		
		//set the curl url
		curl_setopt($pmcCurl, CURLOPT_URL, $pmcURL);
		
		//get the contents
		$pmcFriendsList = curl_exec($pmcCurl);
		
		//search for the screen_name and profile_image_url, name and created_at
		preg_match_all('/<screen_name>(.*)<\/screen_name>/', $pmcFriendsList, $pmcScreenNames);
		preg_match_all('/<profile_image_url>(.*)<\/profile_image_url>/', $pmcFriendsList, $pmcProfileImages);
		preg_match_all('/<name>(.*)<\/name>/', $pmcFriendsList, $pmcRealNames);
		preg_match_all('/<\/friends_count>\s*<created_at>(.*)<\/created_at>/', $pmcFriendsList, $pmcCreatedAt);
				
		//store the screen_name and profile_image_url
		$pmcScreens = $pmcScreenNames[1];
		$pmcImages = $pmcProfileImages[1];
		$pmcNames = $pmcRealNames[1];
		$pmcDates = $pmcCreatedAt[1];
		
		//Twitter stores the created_at value in the following format: Day MMM DD HH:MM:SS ZONE YYYY - Mon Jan 01 00:00:00 +0000 1970
		//in order to convert this to a string we need to remove Day and ZONE and re-arrange string
		//array to hold the new date values
		$pmcCreated[] = '';
		
		//loop through each created_at
		foreach ($pmcDates as $pmcDate) {
			//split the string into constituent parts
			$pmcStrings = explode(" ", $pmcDate);
			//re-arrange the string according to our needs HH:MM:SS DD MMM YYYY
			$pmcNewDate = $pmcStrings[3] . ' ' . $pmcStrings[1] . ' ' . $pmcStrings[2] . ' ' . $pmcStrings[5];
			//convert the string to unix time
			$pmcTime = strtotime($pmcNewDate);
			//push the newly created date on to the end of the array
			array_push($pmcCreated, $pmcTime);
		}
		
		//call the function to update the database - pass the current loop index so we can tell if we're getting the first page
		pmcSaveFriends($pmcScreens, $pmcImages, $pmcNames, $pmcCreated, 'friends', $i);
	}
}

//function to get twitter followers
function pmcGetFollowers() {
	//get user name
	$pmcUser = get_option('pmc_TF_user');
	
	//get password
	$pmcPass = get_option('pmc_TF_password');

	$pmcCount = get_option('pmc_TF_followers');
	
	//set the minimum number of pages to get
	$pmcNumPages = 0;
	
	//twitter returns a maximum of 100 followers at a time so we need to check how many pages we need to get to retrieve all friends
	while ($pmcCount > 0) {	
		$pmcNumPages++;
		$pmcCount = $pmcCount - 100;
	}
	
	//set up curl
	$pmcCurl = curl_init();
	curl_setopt($pmcCurl, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($pmcCurl, CURLOPT_BINARYTRANSFER, 1);
	curl_setopt($pmcCurl, CURLOPT_CONNECTTIMEOUT, 2);
	curl_setopt($pmcCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
	curl_setopt($pmcCurl, CURLOPT_USERPWD, "$pmcUser:$pmcPass");
	
	//loop through the number of reqd pages and get the contents
	for ($i=1; $i<=$pmcNumPages; $i++) {
		//set the url to retrieve friends from Twitter dependent on the type to retrieve
			$pmcURL = 'http://twitter.com/statuses/followers.xml?page=' . $i;
			
		
		//set the curl url
		curl_setopt($pmcCurl, CURLOPT_URL, $pmcURL);
		
		//get the contents
		$pmcFriendsList = curl_exec($pmcCurl);
		
		//search for the screen_name and profile_image_url, name and created_at
		preg_match_all('/<screen_name>(.*)<\/screen_name>/', $pmcFriendsList, $pmcScreenNames);
		preg_match_all('/<profile_image_url>(.*)<\/profile_image_url>/', $pmcFriendsList, $pmcProfileImages);
		preg_match_all('/<name>(.*)<\/name>/', $pmcFriendsList, $pmcRealNames);
		preg_match_all('/<\/friends_count>\s*<created_at>(.*)<\/created_at>/', $pmcFriendsList, $pmcCreatedAt);
				
		//store the screen_name and profile_image_url
		$pmcScreens = $pmcScreenNames[1];
		$pmcImages = $pmcProfileImages[1];
		$pmcNames = $pmcRealNames[1];
		$pmcDates = $pmcCreatedAt[1];
		
		//Twitter stores the created_at value in the following format: Day MMM DD HH:MM:SS ZONE YYYY - Mon Jan 01 00:00:00 +0000 1970
		//in order to convert this to a string we need to remove Day and ZONE and re-arrange string
		//array to hold the new date values
		$pmcCreated[] = '';
		
		//loop through each created_at
		foreach ($pmcDates as $pmcDate) {
			//split the string into constituent parts
			$pmcStrings = explode(" ", $pmcDate);
			//re-arrange the string according to our needs HH:MM:SS DD MMM YYYY
			$pmcNewDate = $pmcStrings[3] . ' ' . $pmcStrings[1] . ' ' . $pmcStrings[2] . ' ' . $pmcStrings[5];
			//convert the string to unix time
			$pmcTime = strtotime($pmcNewDate);
			//push the newly created date on to the end of the array
			array_push($pmcCreated, $pmcTime);
		}
		
		//call the function to update the database - pass the current loop index so we can tell if we're getting the first page
		pmcSaveFriends($pmcScreens, $pmcImages, $pmcNames, $pmcCreated, 'followers', $i);
	}
}

//function to display the output in a table
function pmcTFDisplay($pmcDisplayType) {
	//use wpdb class
	global $wpdb;
	
	//set table name
	$pmcTable = $wpdb->prefix . 'twitterfriends';
	
	//sql to get the screen_names from db
	$pmcSQL = "SELECT * FROM $pmcTable WHERE `status` LIKE '$pmcDisplayType' ORDER BY `created_at` DESC";
	
	//run query
	$pmcFriends = $wpdb->get_results($pmcSQL);
	
	//containing div
	echo '<div style="display: block; width: 100%; margin: 20px auto; padding: 5px;">' . "\n";
	
	//display each friend in its own div
	foreach ($pmcFriends as $pmcFriend) {
		echo '<div class="pmcTFContainAdmin">' . "\n";
		echo '<div class="pmcTFBlockAdmin">' . "\n";
		echo '<img class="pmcTFImgAdmin" src="' . $pmcFriend->profile_image_url . '" alt="' . $pmcFriend->screen_name . '" title="' . $pmcFriend->name . '" />' . "\n";
		echo '</div>';
		echo '<div class="pmcTFNameAdmin">' . "\n";
		echo $pmcFriend->name . "\n";
		echo '</div>' . "\n";
		echo '<div class="pmcTFFormAdmin">' . "\n";
		echo '<form action="" method="post">' . "\n";
		echo '<input type="submit" value="Remove ' . ucfirst(rtrim($pmcDisplayType, 's')) . '" class="button-secondary" />' . "\n";
		echo '<input type="hidden" name="delete-friend" value ="' . $pmcFriend->id . '" />' . "\n";
		echo '</form>' . "\n";
		echo '</div>' . "\n";
		echo '</div>' . "\n";
	}
		echo '</div>';

}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//------------------------- DATABASE FUNCTIONS ---------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

//function to update the database structure
//new database uses the following columns: id (primary key), screen_name, profile_image_url, name, created_at, status
function pmcUpdateDBStruct() {
	//use $wpdb
	global $wpdb;
	
	//set table name
	$pmcTable = $wpdb->prefix . 'twitterfriends';
	
	//this db version
	$pmcThisDB = 2;
	
	//get the current db version
	$pmcOldDB = get_option('pmc_TF_db');
	
	//check if the we are using an older db version
	if ($pmcOldDB != $pmcThisDB) {	
		//sql to create the table
		$SQL = "CREATE TABLE " . $pmcTable . " (
		id mediumint(9) NOT NULL AUTO_INCREMENT,
		screen_name text NOT NULL,
		profile_image_url text NOT NULL,
		name text NOT NULL,
		created_at text NOT NULL,
		status text NOT NULL,
		UNIQUE KEY id (id)
		);";
		
		//use the WordPress dbDelta function to create the table
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($SQL);
		
		//update the database version
		update_option('pmc_TF_db', $pmcThisDB);
		
		//get current options from the database
		$pmcOptions = get_option('widget_pmcFriends');
		
		//check if the options are in the database
		//if they are get them
		if ($pmcOptions) {
			$pmcUser = $pmcOptions['pmc_TF_user'];
			$pmcPassword = $pmcOptions['pmc_TF_password'];
			$pmcTitle = $pmcOptions['pmc_TF_title'];
			$pmcTitleLink = $pmcOptions['pmc_TF_title_link'];
			$pmcLimit = $pmcOptions['pmc_TF_limit'];
			$pmcRSS = $pmcOptions['pmc_TF_show_rss'];
			$pmcCache = $pmcOptions['pmc_TF_cache'];
			$pmcType = $pmcOptions['pmc_TF_type'];
		} else {
			//otherwise set defaults settings
			$pmcUser = '';
			$pmcPassword = '';
			$pmcTitle = 'My Twitter Friends';
			$pmcTitleLink = 'none';
			$pmcLimit = '20';
			$pmcRSS = 'no';
			$pmcCache = '3600';
			$pmcType = 'friends';
		}
		
		//now we update the options we're keeping and deleting the ones we no longer need
		delete_option('widget_pmcFriends');
		update_option('pmc_TF_user', $pmcUser);
		update_option('pmc_TF_password', $pmcPass);
		update_option('pmc_TF_title', $pmcTitle);
		update_option('pmc_TF_title_link', $pmcTitleLink);
		update_option('pmc_TF_limit', $pmcLimit);
		update_option('pmc_TF_show_rss', $pmcRSS);
		update_option('pmc_TF_cache', $pmcCache);
		update_option('pmc_TF_type', $pmcType);
		
		//if the username exists, then we'll perform an update
		if ($pmcUser != '') {
			pmcGetCounts();
			pmcGetFriends();
			pmcGetFollowers();
		}
		
	}
}
	
//function to update the database
function pmcSaveFriends($pmcScreens, $pmcImages, $pmcNames, $pmcCreated, $pmcStatus, $pmcDelete) {
	//use WPDB class
	global $wpdb;
	
	//set table name
	$pmcTable = $wpdb->prefix . 'twitterfriends';
		
	//check if we're dealing with friends or followers
	//only delete all friends if we've just retrieved the first page of out Twitter friends/ followers
	if ($pmcStatus == 'friends' and $pmcDelete == 1) {
		
		//delete all current friends from the database
		$pmcSQL = "DELETE FROM $pmcTable WHERE `status`='friends'";
		//run query
		$pmcDeleteFriends = $wpdb->get_results($pmcSQL);
		
	} else if ($pmcStatus == 'followers' and $pmcDelete == 1) {
		
			//delete current followers
			$pmcSQL = "DELETE FROM $pmcTable WHERE `status`='followers'";
			//run query
			$pmcDeleteFollowers = $wpdb->get_results($pmcSQL);
	}
	
	
	//get the length of the arrays
	$pmcArrayLen = count($pmcScreens);
	
	//loop through the arrays
	for ($i=0; $i<$pmcArrayLen; $i++) {
		$pmcCurrScreen = $pmcScreens[$i];
		$pmcCurrImage = $pmcImages[$i];
		$pmcCurrName = $pmcNames[$i];
		$pmcCurrCreated = $pmcCreated[$i];
		
		//now insert the current list of friends/ followers
		$pmcInsertResult = $wpdb->insert(
				$pmcTable,
				array( 'id' => null, 'screen_name' => $pmcCurrScreen, 'profile_image_url' => $pmcCurrImage, 'name' => $pmcCurrName, 'created_at' => $pmcCurrCreated, 'status' => $pmcStatus)
			);
	}
}	

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//--------------------------- MISC. FUNCTIONS ----------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

//function to create drop down list
//takes 2 parameters - array of options and selected option
function pmcWriteSelect($pmcOptionArray, $pmcDefault) {
	
	//loop through the array of options
	foreach ($pmcOptionArray as $pmcOptKey => $pmcOptValue) {
		//check if the current option is the default
		if ($pmcOptKey == $pmcDefault) {
			$pmcSelect .= '<option value="' . $pmcOptKey . '" selected="selected">' . $pmcOptValue . '</option>';
		} else {
			$pmcSelect .= '<option value="' . $pmcOptKey . '">' . $pmcOptValue . '</option>';
		}
	}
	
	//return the html
	return $pmcSelect;
}

//function to check if the cache needs to be updated
function pmcCheckCache() {
	//get cache update interval
	$pmcCache = get_option('pmc_TF_cache');	
	
	//get last update timestamps
	$pmcFriendsTime = get_option('pmc_TF_friends_time');
	$pmcFollowersTime = get_option('pmc_TF_followers_time');
	
	//get the current time
	$pmcTime = time();
	
	//work out time since last update
	$pmcFriendsDiff = $pmcTime - $pmcFriendsTime;
	$pmcFollowersDiff = $pmcTime - $pmcFollowersTime;
	
	//check if the time difference is greater than the cache update interval
	//if it is update friends/ followers
	if ($pmcFriendsDiff > $pmcCache) {
		pmcGetCounts();
		pmcGetFriends();
		//update the update timestamp
		update_option('pmc_TF_friends_time', $pmcTime);
	}
	
	if ($pmcFollowersDiff > $pmcCache) {
		pmcGetCounts();
		pmcGetFollowers();
		//update the update timestamp
		update_option('pmc_TF_followers_time', $pmcTime);
	}
}

//function to call cache check in the footer
function pmcCallCheck() {
	add_action('wp_footer', 'pmcCheckCache');
}

//function to display friends using shortcode
function pmcShortcode($atts) {
		//use wpdb class
		global $wpdb;
		
		//set table name
		$pmcTable = $wpdb->prefix . 'twitterfriends';
		
		
		//extract shortcode arguments
		extract(shortcode_atts(array(
			'title' => 'My Twitter Friends',
			'limit' => 20,
			'type' => 'friends',
			'size' => 'mini'
			), $atts));
			
		//build SQL to get friends
		$pmcSQL = "SELECT * FROM $pmcTable WHERE `status`='$type' LIMIT $limit";
		
		//run query
		$pmcResult = $wpdb->get_results($pmcSQL);
		
		//containing div
		$pmcOut = '<div>';
		
		//display title
		$pmcOut .= '<h2>' . $title . '</h2>';
		
		//loop through the friends
		foreach ($pmcResult as $pmcFriend) {
			$pmcOut .= '<div style="display: inline-block; margin: 0;"><a rel="nofollow" href="http://twitter.com/' . $pmcFriend->screen_name . '" title="' . $pmcFriend->name . ' on Twitter">';
			
			//check what size image the user wants to display and display accordingly
			switch ($size) {
				case 'mini':
					$pmcImage = str_replace('_normal.', '_mini.', $pmcFriend->profile_image_url);
					$pmcOut .= '<img style="display: inline-block; float: left; width: 24px; height: 24px; padding: 3px; margin: 0;" src="' . $pmcImage . '" alt="' . $pmcFriend->screen_name . '" /></a></div>' . "\n";
					break;
				case 'normal':
					$pmcOut .= '<img style="display: inline-block; width: 48px; height: 48px; padding: 3px; margin: 0;" src="' . $pmcFriend->profile_image_url . '" alt="' . $pmcFriend->screen_name . '" /></a></div>' . "\n";
					break;
				case 'bigger':
					$pmcImage = str_replace('_normal.', '_bigger.', $pmcFriend->profile_image_url);
					$pmcOut .= '<img style="display: inline-block; width: 96px; height: 96px; padding: 3px; margin: 0;" src="' . $pmcImage . '" alt="' . $pmcFriend->screen_name . '" /></a></div>' . "\n";
					break;
			}
		}
		
		//close containing div
		$pmcOut .= '</div>';
		
		//echo the output
		return $pmcOut;
}

//function to add styles to blog header
function pmcWriteStyles() {
		echo '<!-- Styles for Twitter Friends Widget -->' . "\n";
		echo '<style type="text/css">' . "\n";
		echo '.pmcTFContainDiv {' . "\n";
		echo 'display: inline-block;' . "\n";
		echo 'display: -moz-inline-box;' . "\n";
		echo 'margin: 0;' . "\n";
		echo '}' . "\n";
		echo "\n";
		echo '.pmcTFImgMini {' . "\n";
		echo 'display: inline-block;' . "\n";
		echo 'display: -moz-inline-box;' . "\n";
		echo 'float: left;' . "\n";
		echo 'width: 24px;' . "\n";
		echo 'height: 24px;' . "\n";
		echo 'padding: 1px;' . "\n";
		echo 'margin: 0;' . "\n";
		echo 'overflow: hidden;' . "\n";
		echo '}' . "\n";
		echo "\n";
		echo '.pmcTFImgNorm {' . "\n";
		echo 'display: inline-block;' . "\n";
		echo 'display: -moz-inline-box;' . "\n";
		echo 'width: 48px;' . "\n";
		echo 'height: 48px;' . "\n";
		echo 'padding: 1px;' . "\n";
		echo 'margin: 0;' . "\n";
		echo 'overflow: hidden;' . "\n";
		echo '}' . "\n";
		echo "\n";
		echo '.pmcTFImgBig {' . "\n";
		echo 'display: inline-block;' . "\n";
		echo 'display: -moz-inline-box;' . "\n";
		echo 'width: 96px;' . "\n";
		echo 'height: 96px;' . "\n";
		echo 'padding: 1px;' . "\n";
		echo 'margin: 0;' . "\n";
		echo 'overflow: hidden;' . "\n";
		echo '}' . "\n";
		echo "\n";
		echo '.pmcTFRSS, .pmcTFCounts {' . "\n";
		echo 'display: block;' . "\n";
		echo 'margin: 10px 0;' . "\n";
		echo 'text-align: left;' . "\n";
		echo '}' . "\n";
		echo '* html .pmcTFContainDiv, .pmcTFImgMini, .pmcTFImgNorm, .pmcTFImgBig {' . "\n";
		echo 'display: inline;' . "\n";
		echo '}' . "\n";
		echo "\n";
		echo '* + html .pmcTFContainDiv, .pmcTFImgMini, .pmcTFImgNorm, .pmcTFImgBig {' . "\n";
		echo 'display: inline;' . "\n";
		echo '}' . "\n";
		echo '</style>' . "\n";
		
}

//function to write styles to Admin Pages header
function pmcWriteAdminStyles() {
	echo '<!-- Styles for Twitter Friends -->';
	echo '<style type="text/css">' . "\n";
	echo '.pmcTFContainAdmin { border: 1px solid #666; background-color: #aaa; padding: 0; margin: 5px; width: 150px; height: 95px; display: inline-block; float: left; }' . "\n";
	echo '.pmcTFBlockAdmin { display: block; margin: 5px 63px; }' . "\n";
	echo '.pmcTFImgAdmin { margin: 0 auto; padding: 0; width: 24px; height: 24px; }' . "\n";
	echo '.pmcTFNameAdmin { display: block; text-align: center; margin: 0; padding: 5px 0 0 0 ; background-color: #666; color: #eee; width: 100%; height: 20px; border-top: 1px solid #666; overflow: hidden; border-bottom: 1px solid #666; }' . "\n";
	echo '.pmcTFFormAdmin { text-align: center; background-color: #aaa; padding: 5px 0; }' . "\n";
	echo '* html .pmcTFContainAdmin { display: inline; }' . "\n";
	echo '* + html .pmcTFContainAdmin { display: inline; }' . "\n";
	echo '</style>' . "\n";
}

//function to add links on plugins page
function pmc_TF_add_links($links) { 
 // Add a link to this plugin's settings page
 $settings_link = '<a href="admin.php?page=twitter-friends-widget">Settings</a>';
 array_unshift($links, $settings_link); 
 return $links; 
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//-------------------------- WIDGET FUNCTIONS ----------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

//function to initiate the widget
function widget_TF_init() {
	//check that WP supports widgets
	if (!function_exists('register_sidebar')) {
		return;
	}
	
	//function to display Friends/Followers
	function pmcWidgetDisplay($pmcArgs) {
		//extract widget arguments
		extract($pmcArgs);
		
		//use wpdb class
		global $wpdb;
		
		//set table name
		$pmcTable = $wpdb->prefix . 'twitterfriends';
		
		//get options
		$pmcTitle = get_option('pmc_TF_title');
		$pmcTitleLink = get_option('pmc_TF_title_link');
		$pmcLimit = get_option('pmc_TF_limit');
		$pmcImageSize = get_option('pmc_TF_image_size');
		$pmcShowRSS = get_option('pmc_TF_show_rss');
		$pmcBG = get_option('pmc_TF_bgcolor');
		$pmcFG = get_option('pmc_TF_fgcolor');
		$pmcType = get_option('pmc_TF_type');
		$pmcID = get_option('pmc_TF_ID');
		$pmcUser = get_option('pmc_TF_user');
		$pmcShowCounts = get_option('pmc_TF_show_counts');
		$pmcFriendsCount = get_option('pmc_TF_friends');
		$pmcFollowersCount = get_option('pmc_TF_followers');
		$pmcShowFollow = get_option('pmc_TF_show_follow');
		
		//check if the user wants to display all users
		if ($pmcLimit == 0) {
			//sql to get count of friends
			$pmcLimitSQL = "SELECT COUNT(`id`) from $pmcTable WHERE `status`='$pmcType'";
			//get results
			$pmcLimit = $wpdb->get_var($pmcLimitSQL);
		}
			
			
		//build sql
		if ($pmcType == 'followers') {
			$pmcSQL = "SELECT * FROM $pmcTable WHERE `status`='followers' LIMIT 0, $pmcLimit";
		} else {
			$pmcSQL = "SELECT * FROM $pmcTable WHERE `status`='friends' LIMIT 0, $pmcLimit";
		}
		
		//run query
		$pmcResult = $wpdb->get_results($pmcSQL);
		
		//start building the output
		$pmcOut = $before_widget . $before_title;
		
		//check if the user wants the the title to link to their Twitter page or their Twitter RSS
		switch ($pmcTitleLink) {
			case 'none':
				$pmcOut .= $pmcTitle;
				break;
			case 'page':
				$pmcOut .= '<a rel="nofollow" href="http://twitter.com/'. $pmcUser . '" title="' . $pmcUser . ' on Twitter">' . $pmcTitle . '</a>';
				break;
			case 'rss':
				$pmcOut .= '<a rel="nofollow" href="http://twitter.com/statuses/user_timeline/' . trim($pmcID) . '.rss" title="Follow ' . $pmcUser . ' Twitter Updates via RSS">' . $pmcTitle . '</a>';
				break;
			default:
				$pmcOut .= $pmcTitle;
				break;
		}
		
		//finish title element
		$pmcOut .= $after_title;
		
		//loop through the results and build the output
		foreach ($pmcResult as $pmcFriend) {
			$pmcOut .= '<div class="pmcTFContainDiv"><a rel="nofollow" href="http://twitter.com/' . $pmcFriend->screen_name . '" title="' . $pmcFriend->name . ' on Twitter">';
			
			//check what size image the user wants to display and display accordingly
			switch ($pmcImageSize) {
				case 'mini':
					$pmcImage = str_replace('_normal.', '_mini.', $pmcFriend->profile_image_url);
					$pmcOut .= '<img class="pmcTFImgMini" src="' . $pmcImage . '" alt="' . $pmcFriend->screen_name . '" /></a></div>' . "\n";
					break;
				case 'normal':
					$pmcOut .= '<img class="pmcTFImgNorm" src="' . $pmcFriend->profile_image_url . '" alt="' . $pmcFriend->screen_name . '" /></a></div>' . "\n";
					break;
				case 'bigger':
					$pmcImage = str_replace('_normal.', '_bigger.', $pmcFriend->profile_image_url);
					$pmcOut .= '<img class="pmcTFImgBig" src="' . $pmcImage . '" alt="' . $pmcFriend->screen_name . '" /></a></div>' . "\n";
					break;
			}
		}

		//check if the user wants to display the rss link
		if ($pmcShowRSS == 'yes') {
			$pmcOut .= '<div class="pmcTFRSS"><a rel="nofollow" href="http://twitter.com/statuses/user_timeline/' . trim($pmcID) . '.rss" title="Follow ' . $pmcUser . ' Twitter Updates via RSS">' . $pmcUser . ' Twitter Updates via RSS</a></div>';
		}
		
		//check if the user wants to display friends followers counts
		if ($pmcShowCounts == 'yes') {
			$pmcOut .= '<div class="pmcTFCounts">Friends: ' . $pmcFriendsCount . ' Followers: '	. $pmcFollowersCount . '</div>';
		}
		
		//check if the user wants to display the follow button
		if ($pmcShowFollow == 'yes') {
			$pmcOut .= '<form action="http://twitter.com/friendships/create/' . $pmcUser . '.xml" method="post">';
			$pmcOut .= '<input type="submit" value="Follow Me on Twitter" />';
			$pmcOut .= '</form>';
		}
		//close widget display
		$pmcOut .= $after_widget;
		
		//echo the output
		echo $pmcOut;
	}
	
	//function to display widget control panel
	function pmcWidgetControl() {
		echo '<p>The settings for Twitter Friends can be changed from the <a href="admin.php?page=twitter-friends-widget" title="Twitter Friends Widget Settings">Twitter Friends Settings Page</a></p>';
	}
	
	//register the widget and control
	register_sidebar_widget('Twitter Friends', 'pmcWidgetDisplay');
	register_widget_control('Twitter Friends', 'pmcWidgetControl');
}

//------------------------------------------------------------------------------
//------------------------------------------------------------------------------
//-------------------------- WORDPRESS ACTIONS ---------------------------------
//------------------------------------------------------------------------------
//------------------------------------------------------------------------------

//initiate plugin
add_action('plugins_loaded', 'pmcAdminPage_init');
//write cache check function to footer
add_action('plugins_loaded', 'pmcCallCheck');
//initiate widget
add_action('widgets_init', 'widget_TF_init');
//add shortcode
add_shortcode('twitter-friends', 'pmcShortcode');
//write styles to header
add_action('wp_head', 'pmcWriteStyles');
//write styles to admin header
add_action('admin_head', 'pmcWriteAdminStyles');
//action for WP 2.7 settings api
add_action('admin_init', 'pmcTFOptions_init');
//add links for settings etc to plugins page
$plugin = plugin_basename(__FILE__); 
add_filter("plugin_action_links_$plugin", 'pmc_TF_add_links' );
?>