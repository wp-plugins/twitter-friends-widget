<?php
/*
Plugin Name: Twitter Friends Widget
Plugin URI: http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget/
Description: Widget to display your Twitter Friends in the sidebar
Version: 2.1beta
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

	
//wrap widget functions in an init
function widget_pmcFriends_init() {
	//check that WP can use widgets
	if (!function_exists('register_sidebar_widget')) {
		return;
	} //close if
	
	//function to get Twitter Friends using Twitter API
	function pmcGetFriends() {
		//use wpdb class
		global $wpdb;
		
		//set table name
		$pmcTableName = $wpdb->prefix . 'twitterfriends';
		
		//check that the table exists
		if ($wpdb->get_var("show tables like '" . $pmcTableName . "'") != $pmcTableName) {
			pmcAddTable();
		}
		
		//get the widget settings
		$pmcOptions = get_option('widget_pmcFriends');
		
		//store username
		$pmcUser = $pmcOptions['pmc_TF_user'];
		
		//create the url for the Twitter API
		$pmcURL = 'http://twitter.com/statuses/friends/' . $pmcUser . '.xml';
		
		//use class_http to retrieve the friends list
		//list is in XML format - see Twitter API for more details
		require_once(dirname(__FILE__).'/class_http.php');
		
		//create the connection
		$pmcTFconn = new http();
		
		//fetch the url
		if (!$pmcTFconn->fetch($pmcURL, "0", "friends")) {
			echo '<h3>Error</h3>';
			echo '<p>There was an error retrieving your friends list</p>';
			echo $pmcTFconn->log;
			exit();
		} //close if
		
		//store the friends list
		$pmcTFlist = $pmcTFconn->body;
		
		//search for the screen_name
		preg_match_all('/<screen_name>(.*)<\/screen_name>/', $pmcTFlist, $pmcScreen);
		
		//search for the profile_image_url
		preg_match_all('/<profile_image_url>(.*)<\/profile_image_url>/', $pmcTFlist, $pmcImage);
		
		//the array returned by preg_match_all is a 2-dimensional area, we only need the 1st
		//store the names in a new array and trim the XML tags while we're at it.
		$pmcFriends[] = "";
		$pmcImageURL[] ="";
		
		//loop through both arrays, and strip the XML tags
		//store the results in an array
		foreach ($pmcScreen[0] as $pmcName) {
			$pmcTrimName = strip_tags($pmcName);
			array_push($pmcFriends, $pmcTrimName);
		} //close foreach
		
		
		foreach ($pmcImage[0] as $pmcPic) {
			$pmcTrimPic = strip_tags($pmcPic);
			
			//twitter returns a link to the "normal" profile images, change the links to the "mini" version
			$pmcTrimPic = str_replace("_normal.", "_mini.", $pmcTrimPic);
						
			array_push($pmcImageURL, $pmcTrimPic);
		} //close foreach
		
		
		//get the length of the friends array
		$pmcFriendsLen = count($pmcFriends);
		
		//we now loop through the array of screen_names and check if it's in the database
		for ($i=1; $i<$pmcFriendsLen; $i++) {
			//store the screen_name
			$pmcFriendsName = $pmcFriends[$i];
			
			//store the profile_image_url
			$pmcFriendsImage = $pmcImageURL[$i];
			
			//create sql statement to check if the screen_name is already in the database
			$sqlNameSelect = "SELECT `screen_name` FROM `" . $pmcTableName . "` WHERE `screen_name` like '" . $pmcFriendsName . "'";
			//create sql statement to check if the profile_image_url is in the database
			$sqlImageSelect = "SELECT `profile_image_url` FROM `" . $pmcTableName . "` WHERE `screen_name` like '" . $pmcFriendsName . "'"; 

			//run the queries
			$pmcNameResult = $wpdb->get_var($sqlNameSelect);
			$pmcImageResult = $wpdb->get_var($sqlImageSelect);
			
			//check the result
			if ($pmcNameResult == '') {
				//insert the new friend if it doesn't already exist
				$sqlInsert = "INSERT INTO " . $pmcTableName . " VALUES ('','" . $pmcFriendsName . "','" . $pmcFriendsImage . "')";
				
				//run the query
				$pmcInsertResult = $wpdb->query($sqlInsert);
				
			}
			
			//if the name does exist, we'll check if the profile_image_url has been updated
			if ($pmcFriendsImage != $pmcImageResult) {
				//update the friends details
				$sqlUpdate = "UPDATE " . $pmcTableName . " SET `profile_image_url`='" . $pmcFriendsImage . "' WHERE `screen_name`='" . $pmcFriendsName . "'";

				//run the query
				$pmcUpdateResult = $wpdb->query($sqlUpdate);
			}
		}
			
	} //close pmcGetFriends
	
	
	//function to add the table to the database
	function pmcAddTable() {
		//use WordPress db class
		global $wpdb;
		
		//set table name
		$pmcTableName = $wpdb->prefix . 'twitterfriends';
		
		//check to see if the table already exists
		if ($wpdb->get_var("show tables like '" . $pmcTableName . "'") != $pmcTableName) {
			
			//build the sql to create the table
			$SQL = "CREATE TABLE " . $pmcTableName . " (
				id mediumint(9) NOT NULL AUTO_INCREMENT,
				screen_name text NOT NULL,
				profile_image_url text NOT NULL,
				UNIQUE KEY id (id)
				);";
				
			//use the WordPress dbDelta function to create the table
			require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
			dbDelta($SQL);
		} //close if
	} //close pmcAddTable
	
	//function to display friends in a HTML table
	function pmcDisplayFriends($pmcArgs) {
		//check if the cache needs to be updated
		$pmcUpdateReqd = pmcCheckTime();
		
		if ($pmcUpdateReqd) {
			//if an update is required, call the function to update the db
			pmcGetFriends();
		}
		//extract the Widget display settings
		extract($pmcArgs);
		
		//use wpdb class
		global $wpdb;
		
		//set table name
		$pmcTableName = $wpdb->prefix . 'twitterfriends';
		
		//get the widget options
		$pmcOptions = get_option('widget_pmcFriends');
		$pmcTitle = $pmcOptions['pmc_TF_title'];
		$pmcTFUser = $pmcOptions['pmc_TF_user'];
		$pmcTFRows = (int) $pmcOptions['pmc_TF_rows'];
		$pmcTFLimit = (int) $pmcOptions['pmc_TF_limit'];
		
		//check that the user has set a value for the rows to be used
		if ($pmcTFRows == 0) {
			//if not use the default of 5
			$pmcTFRows = 5;
		}
		
		//check that the database table exists
		if ($wpdb->get_var("show tables like '" . $pmcTableName . "'") != $pmcTableName) {
			//if not, add it
			pmcAddTable();
			//get the friends list
			pmcGetFriends();
		}
		
		//create sql to get screen names from  database
		$SQL = "SELECT `screen_name`, `profile_image_url` FROM `" . $pmcTableName . "`";
		
		//run the query and return an associative array
		$pmcResults = $wpdb->get_results($SQL, ARRAY_N);
		
		//get the length of the returned array
		$pmcResultsLen = count($pmcResults);
		
		//start building the widget output
		echo $before_widget . $before_title . $pmcTitle . $after_title;
		
		//start the HTML table
		$pmcHTML = '<table class="pmcTFTable"><tr class="pmcTFTR">';
		
		//iterate through the arrays and build the HTML table
		for ($i=1; $i<$pmcResultsLen; $i++) {
			$pmcHTML .= '<td class="pmcTFTD"><a href="http://twitter.com/' . $pmcResults[$i][0] . '" title="' . $pmcResults[$i][0] . '"><img class="pmcTFimg" src="' . $pmcResults[$i][1] . '" alt="' . $pmcResults[$i][0] . '" /></a></td>' . "\n";
			//check if we have reached the end of a row
			if ($i % $pmcTFRows == 0) {
				$pmcHTML .= '</tr><tr class="pmcTFTR">' . "\n";
			} //close if
		} //close for
		
		//close the HTML table
		$pmcHTML .= '</tr>' . "\n" . '</table>' . "\n";
		
		//add the link to the rss feed
		$pmcHTML .= '<p><a href="https://twitter.com/statuses/user_timeline/' . pmcRetrieveTwitterID() . '.rss" title="Subscribe to my Twitter Feed">';
		$pmcHTML .= '<img style="margin: 0 10px 0 0; border: 0; text-decoration: none;" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/twitter-friends-widget/rss.png" title="Subscribe to my Twitter RSS" alt="RSS: " /></a>';
		$pmcHTML .= '<a href="https://twitter.com/statuses/user_timeline/' . pmcRetrieveTwitterID() . '.rss" title="Subscribe to my Twitter Feed">';
		$pmcHTML .= 'Subscribe to my Twitter RSS</a></p>';
		
		//display the HTML
		echo $pmcHTML;
		
		//close the widget
		echo $after_widget;
	} //close pmcDisplayFriends
	
	//function to check if the update period has passed or not
	function pmcCheckTime() {
		//get the last time widget was update
		$pmcLastUpdate = get_option('pmc_last_update');
		
		//get the user specified update interval
		$pmcOptions = get_option('widget_pmcFriends');
		$pmcUserInterval = (int) $pmcOptions['pmc_TF_cache'];
		
		//get the current time
		$pmcNow = time();
		
		//check that the last update time exists
		if (!$pmcLastUpdate) {
		
			//if it doesn't exist, add it
			add_option('pmc_last_update', $pmcNow);
			//return false if no update is required
			return FALSE;		
		} else {
		
			//calculate the time difference
			$pmcDiff = $pmcNow - $pmcLastUpdate;
			
			//check if the difference is greater than the specified interval
			if ($pmcDiff > $pmcUserInterval) {
				//if it is return true
				return TRUE;
			} //close inner if
		}//close outer if
		
	} //close pmcCheckTime
	
	//function to get the users twitter id from their username
	function pmcRetrieveTwitterID() {
		//check if we have stored the ID already
		//get the Twitter username from database
		$pmcTwitterOptions = get_option('widget_pmcFriends');
		$pmcTwitterUser = $pmcTwitterOptions['pmc_TF_user'];
		$pmcTwitterID = get_option('pmc_TF_ID');
		
		if (!$pmcTwitterID) {

			//require class_http.php
			require_once(dirname(__FILE__).'/class_http.php');
	
			//create a new connection
			$pmcTwitterConn = new http();
	
			//set the url to the Twitter API
			$pmcTwitterAPI = 'http://twitter.com/users/show/' . $pmcTwitterUser . '.xml';
	
			//make sure that we can connect, if not display an error message
			if (!$pmcTwitterConn->fetch($pmcTwitterAPI, "0", "twitter")) {
				echo "<h2>There is a problem with the http request!</h2>";
  				echo $pmcTwitterConn->log;
		  		exit();
			} //close if
	
			//if we have connected, then get the data.
			//as this is xml data, we are lookig for the ID key and it's value.
			$pmcTwitterData=$pmcTwitterConn->body;
			preg_match ('/<id>(.*)<\/id>/', $pmcTwitterData, $matches);	
	
			//remove the <id></id> HTML tags from the returned key
			$pmcTrimID = strip_tags($matches[0]);
			
			//add the setting to the database
			add_option('pmc_TF_ID', $pmcTrimID);
		
			//set the return variable
			$pmcTwitterID = $pmcTrimID;
		} //close if
	
		//return the Twitter ID
		return $pmcTwitterID;
		
	}//close pmcRetrieveTwitterID
	
	//function to write the style info to the header
	function pmcTFStyles() {
		//get styles options
		$pmcStyles = get_option('widget_pmcFriends');
		$pmcBGcolor = $pmcStyles['pmc_TF_bgcolor'];
		$pmcFGcolor = $pmcStyles['pmc_TF_fgcolor'];
		
		echo '<!-- CSS style for Twitter Friends widget -->' . "\n";
		echo '<style type="text/css">' . "\n";
		echo 'table.pmcTFTable {' . "\n" . 'width: 120px; padding: 0; margin: 20px 0; border: 0; border-collapse: collapse; border-spacing: 0; background-color: ' . $pmcBGcolor . ' !important; color: ' . $pmcFGcolor . ' !important;' . "\n" . '}' . "\n";
		echo 'tr.pmcTFTR {' . "\n" . 'margin: 0; padding: 0; border-collapse: collapse;' . "\n" . '}' . "\n";
		echo 'td.pmcTFTD {' . "\n" . 'max-width: 24px; max-height: 24px; border: 0; padding: 0; margin: 0; border-collapse: collapse; overflow: hidden; background-color: ' . $pmcBGcolor . '!important; color: ' . $pmcFGcolor . '!important;' . "\n" . '}' . "\n";
		echo 'img.pmcTFimg {' . "\n" . 'border: 0; padding: 0; margin: 0; height: 24px; width: 24px;' . "\n" . '}' . "\n";
		echo '</style>' . "\n";
	}
	
	//function to display widget control
	function pmcFriends_control() {
		//get the options from the WordPress database
		$options = $newoptions = get_option('widget_pmcFriends');
		
		//check if the settings have been saved
		if ($_POST['pmc_friends_widget_submit']) {
			//remove anything that sholdn't be there
			$newoptions['pmc_TF_title'] = strip_tags(stripslashes($_POST['pmc_TF_title']));
			$newoptions['pmc_TF_user'] = strip_tags(stripslashes($_POST['pmc_TF_user']));
			$newoptions['pmc_TF_rows'] = strip_tags(stripslashes($_POST['pmc_TF_rows']));
			$newoptions['pmc_TF_limit'] = strip_tags(stripslashes($_POST['pmc_TF_limit']));
			$newoptions['pmc_TF_bgcolor'] = strip_tags(stripslashes($_POST['pmc_TF_bgcolor']));
			$newoptions['pmc_TF_fgcolor'] = strip_tags(stripslashes($_POST['pmc_TF_fgcolor']));
			$newoptions['pmc_TF_cache'] = strip_tags(stripslashes($_POST['pmc_TF_cache']));
			
		} //close if
		
		//check if there has been an update
		if ($options != $newoptions) {
			//if there has been a change, save the changes in the WordPress database
			$options = $newoptions;
			//check that the user has entered a user name
			if ($options['pmc_TF_user'] == "") {
				echo '<h1>You must enter a username</h1>';
			} else {
				update_option('widget_pmcFriends', $options);
			} //close if
		} // close if
		
		//set the default options
		if (!$options['pmc_TF_title']) $options['pmc_TF_title'] = "My Twitter Friends";
		if (!$options['pmc_TF_user']) $options['pmc_TF_user'] = "";
		if (!$options['pmc_TF_rows']) $options['pmc_TF_rows'] = 5;
		if (!$options['pmc_TF_limit'] and $options['pmc_TF_limit'] !=0) $options['pmc_TF_limit'] = 20;
		if (!$options['pmc_TF_bgcolor']) $options['pmc_TF_bgcolor'] = "#FFFFFF";
		if (!$options['pmc_TF_fgcolor']) $options['pmc_TF_fgcolor'] = "#000000";
		if (!$options['pmc_TF_cache']) $options['pmc_TF_cache'] = 3600;

		
		//get the options already saved in the database, encoding any HTML
		$pmcTFTitle = htmlspecialchars($options['pmc_TF_title'], ENT_QUOTES);
		$pmcTFUser = htmlspecialchars($options['pmc_TF_user'], ENT_QUOTES);
		$pmcTFRows = htmlspecialchars($options['pmc_TF_rows'], ENT_QUOTES);
		$pmcTFLimit = htmlspecialchars($options['pmc_TF_limit'], ENT_QUOTES);
		$pmcBGcolor = htmlspecialchars($options['pmc_TF_bgcolor'], ENT_QUOTES);
		$pmcFGcolor = htmlspecialchars($options['pmc_TF_fgcolor'], ENT_QUOTES);
		$pmcCacheUpdate = htmlspecialchars($options['pmc_TF_cache'], ENT_QUOTES);
		
		//build the control panel
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_title">' . __('Title:') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_title" name="pmc_TF_title" type="text" value="'.$pmcTFTitle.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_user">' . __('Your Twitter Name:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_user" name="pmc_TF_user" type="text" value="'.$pmcTFUser.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_rows">' . __('Friends per Row:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_rows" name="pmc_TF_rows" type="text" value="'.$pmcTFRows.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_limit">' . __('Display Limit (0 for Display all):', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_limit" name="pmc_TF_limit" type="text" value="'.$pmcTFLimit.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_cache">' . __('Cache Update Interval: (in seconds)', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_cache" name="pmc_TF_cache" type="text" value="'.$pmcCacheUpdate.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_bgcolor">' . __('Background Colour:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_bgcolor" name="pmc_TF_bgcolor" type="text" value="'.$pmcBGcolor.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_fgcolor">' . __('Text Colour:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_fgcolor" name="pmc_TF_fgcolor" type="text" value="'.$pmcFGcolor.'" /></label></p>';
		echo '<input type="hidden" id="pmc_friends_widget_submit" name="pmc_friends_widget_submit" value="1" />';
		
	} //close pmcFriends_control()
		
	//register widget and widget control
	register_sidebar_widget('Twitter Friends', 'pmcDisplayFriends');
	register_widget_control('Twitter Friends', 'pmcFriends_control', 300, 300);
	
} //close widget_pmcTwitterFriends_init

//action to have WordPress load the widget
add_action('widgets_init', 'widget_pmcFriends_init');
//action to add styles to the header
add_action('wp_head', 'pmcTFStyles');
?>