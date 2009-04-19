<?php
/*
Plugin Name: Twitter Friends Widget
Plugin URI: http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget/
Description: Widget to display your Twitter Friends in the sidebar.
Version: 2.7
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
		
		//store settings
		$pmcUser = $pmcOptions['pmc_TF_user'];
		$pmcPass = $pmcOptions['pmc_TF_password'];
		$pmcDisplay = $pmcOptions['pmc_TF_type'];
		
		//we'll use curl to get the list
		$pmcCurl = curl_init();
		
		//check if the user wants to display friends or followers
		//friends is the default
		if ($pmcDisplay == 'followers') {
			//Twitter API to return followers - requires authentication
			$pmcURL = 'http://twitter.com/statuses/followers.xml';
			
			//set the appropriate curl options
			curl_setopt($pmcCurl, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
			curl_setopt($pmcCurl, CURLOPT_USERPWD, "$pmcUser:$pmcPass");
			
		} else {
			//Twitter API to return friends - does not require authentication
			$pmcURL = 'http://twitter.com/statuses/friends/' . $pmcUser . '.xml';
			
		}
		
		//set basic curl options
		curl_setopt($pmcCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($pmcCurl, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($pmcCurl, CURLOPT_URL, $pmcURL);
		curl_setopt($pmcCurl, CURLOPT_CONNECTTIMEOUT, 2);

		//get the list from Twitter
		$pmcTFlist = curl_exec($pmcCurl);
		
		//close curl connection
		curl_close($pmcCurl);
		
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
	
	//function to get the number of the users followers and friends
	function pmcGetFriendsCount() {
		//get the users options
		$pmcOptions = get_option('widget_pmcFriends');
		$pmcUser = $pmcOptions['pmc_TF_user'];
		
		//url to get user info from Twitter
		$pmcURL = "http://twitter.com/users/show/$pmcUser.xml";
		
		//use curl to get the info
		$pmcCurl = curl_init();
		
		//set the curl options
		curl_setopt($pmcCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($pmcCurl, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($pmcCurl, CURLOPT_URL, $pmcURL);
		curl_setopt($pmcCurl, CURLOPT_CONNECTTIMEOUT, 2);
		
		//get the info
		$pmcUserInfo = curl_exec($pmcCurl);
		
		//close curl connection
		curl_close($pmcCurl);
		
		//search for the number of followers
		preg_match('/<followers_count>(.*)<\/followers_count>/', $pmcUserInfo, $pmcFollowersCount);
		
		//search for the number of friends
		preg_match('/<friends_count>(.*)<\/friends_count>/', $pmcUserInfo, $pmcFriendsCount);
		
		//store the two counts
		update_option('pmc_TF_followers_count', $pmcFolllowersCount[0]);
		update_option('pmc_TF_friends_count', $pmcFriendsCount[0]);
		
	}
		
		
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
			pmcRetrieveTwitterID();
			pmcGetFriendsCount();
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
		$pmcTFLimit = $pmcOptions['pmc_TF_limit'];
		$pmcTFShowRSS = $pmcOptions['pmc_TF_show_rss'];
		$pmcTFTitleLink = $pmcOptions['pmc_TF_title_link'];
		$pmcTFID = get_option('pmc_TF_ID');
		
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
		$SQL = "SELECT * FROM `" . $pmcTableName . "`";
		
		//run the query and return an associative array
		$pmcResults = $wpdb->get_results($SQL);
		
		//get the length of the returned array
		$pmcResultsLen = count($pmcResults);
		
		//check if the user has set a limit.
		//if the limit is set to 0, show all
		if ($pmcTFLimit == 0) {
			$pmcTFLimit = $pmcResultsLen;
		}
		
		//start building the widget output
		echo $before_widget . $before_title;
		
		//write the link as selected by the user
		switch ($pmcTFTitleLink) {
			case 'none':
				echo $pmcTitle;
				break;
			case 'page':
				echo '<a rel="nofollow" href="http://twitter.com/' . $pmcTFUser . '" title="My Twitter Home Timeline">' . $pmcTitle . '</a>';
				break;
			case 'rss':
				echo '<a rel="nofollow" href="https://twitter.com/statuses/user_timeline/' . $pmcTFID . '.rss" title="Subscribe to my Twitter Feed">';
				echo '<img style="margin: 0 10px 0 0; border: 0; text-decoration: none;" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/twitter-friends-widget/rss.png" title="Subscribe to my Twitter RSS" alt="RSS: " />' . $pmcTitle . '</a>';
				break;
			default:
				echo $pmcTitle;
				break;
		}
		
		//close the widget title
		echo $after_title;
		
		//counter to check if a row should be closed
		$i = 1;
		
		//flag to check if a new row should be opened
		$pmcNewRow = TRUE;
		
		//start the HTML table
		$pmcHTML = '<table class="pmcTFTable">';
		
		//iterate through the arrays and build the HTML table
		foreach ($pmcResults as $pmcFriend) {
			
			//check if we need to open a new row
			if ($pmcNewRow) {
				$pmcHTML .= '<tr class="pmcTFTR">' . "\n";
				$pmcNewRow = FALSE;
			}
			
			//build the links to user sites using profile image
			$pmcHTML .= '<td class="pmcTFTD"><a rel="nofollow" href="http://twitter.com/' . $pmcFriend->screen_name . '" title="' . $pmcFriend->screen_name . '"><img class="pmcTFimg" src="' . $pmcFriend->profile_image_url . '" alt="' . $pmcFriend->screen_name . '" /></a></td>' . "\n";
			//check if we have reached the end of a row
			if ($i % $pmcTFRows == 0 ) {
				$pmcHTML .= '</tr>' . "\n";
				//set the flag to open a new row
				$pmcNewRow = TRUE;
				
			} //close if
			
			//increment the counter
			$i++;
			
		} //close for
		
		//close the HTML table
		$pmcHTML .= '</tr>' . "\n" . '</table>' . "\n";
		
		//check if the user wants to display the RSS link and add the link to the rss feed
		if ($pmcTFShowRSS) {
			$pmcHTML .= '<p><a rel="nofollow" href="https://twitter.com/statuses/user_timeline/' . $pmcTFID . '.rss" title="Subscribe to my Twitter Feed">';
			$pmcHTML .= '<img style="margin: 0 10px 0 0; border: 0; text-decoration: none;" src="' . get_bloginfo('wpurl') . '/wp-content/plugins/twitter-friends-widget/rss.png" title="Subscribe to my Twitter RSS" alt="RSS: " /></a>';
			$pmcHTML .= '<a rel="nofollow" href="https://twitter.com/statuses/user_timeline/' . $pmcTFID . '.rss" title="Subscribe to my Twitter Feed">';
			$pmcHTML .= 'Subscribe to my Twitter RSS</a></p>';
		}
		
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
		//get the Twitter username from database
		$pmcTwitterOptions = get_option('widget_pmcFriends');
		$pmcTwitterUser = $pmcTwitterOptions['pmc_TF_user'];

		//use curl to make connection
		$pmcCurl = curl_init();
	
		//set the url to the Twitter API
		$pmcTwitterAPI = 'http://twitter.com/users/show/' . $pmcTwitterUser . '.xml';
			
		//set curl options
		curl_setopt($pmcCurl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($pmcCurl, CURLOPT_URL, $pmcTwitterAPI);
		curl_setopt($pmcCurl, CURLOPT_CONNECTTIMEOUT, 2);

		//connect with curl
		$pmcTwitterData = curl_exec($pmcCurl);
			
		//close the connection
		curl_close($pmcCurl);
			
		//get the twitter id
		preg_match ('/<id>(.*)<\/id>/', $pmcTwitterData, $matches);	
	
		//remove the <id></id> HTML tags from the returned key
		$pmcTrimID = strip_tags($matches[0]);
			
		//update the setting to the database
		update_option('pmc_TF_ID', $pmcTrimID);
				
	}//close pmcRetrieveTwitterID
	
	//function to write the style info to the header
	function pmcTFStyles() {
		//get styles options
		$pmcStyles = get_option('widget_pmcFriends');
		$pmcBGcolor = $pmcStyles['pmc_TF_bgcolor'];
		$pmcFGcolor = $pmcStyles['pmc_TF_fgcolor'];
		$pmcTFTable = $pmcStyles['pmc_TF_table'];
		
		echo '<!-- CSS style for Twitter Friends widget -->' . "\n";
		echo '<style type="text/css">' . "\n";	
		echo 'table.pmcTFTable {' . "\n". $pmcTFTable . "\n" . '}' . "\n";
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
			//remove anything that shouldn't be there
			$newoptions['pmc_TF_title'] = strip_tags(stripslashes($_POST['pmc_TF_title']));
			$newoptions['pmc_TF_user'] = strip_tags(stripslashes($_POST['pmc_TF_user']));
			$newoptions['pmc_TF_rows'] = strip_tags(stripslashes($_POST['pmc_TF_rows']));
			$newoptions['pmc_TF_limit'] = strip_tags(stripslashes($_POST['pmc_TF_limit']));
			$newoptions['pmc_TF_bgcolor'] = strip_tags(stripslashes($_POST['pmc_TF_bgcolor']));
			$newoptions['pmc_TF_fgcolor'] = strip_tags(stripslashes($_POST['pmc_TF_fgcolor']));
			$newoptions['pmc_TF_cache'] = strip_tags(stripslashes($_POST['pmc_TF_cache']));
			$newoptions['pmc_TF_show_rss'] = strip_tags(stripslashes($_POST['pmc_TF_show_rss']));
			$newoptions['pmc_TF_title_link'] = strip_tags(stripslashes($_POST['pmc_TF_title_link']));
			$newoptions['pmc_TF_table'] = strip_tags(stripslashes($_POST['pmc_TF_table']));
			$newoptions['pmc_TF_type'] = strip_tags(stripslashes($_POST['pmc_TF_type']));
			$newoptions['pmc_TF_password'] = strip_tags(stripslashes($_POST['pmc_TF_password']));
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
		if (!$options['pmc_TF_limit'] and $options['pmc_TF_limit'] != 0) $options['pmc_TF_limit'] = 20;
		if (!$options['pmc_TF_bgcolor']) $options['pmc_TF_bgcolor'] = "#FFFFFF";
		if (!$options['pmc_TF_fgcolor']) $options['pmc_TF_fgcolor'] = "#000000";
		if (!$options['pmc_TF_cache']) $options['pmc_TF_cache'] = 3600;
		if (!$options['pmc_TF_title_link']) $options['pmc_TF_title_link'] = 'none';
		if (!$options['pmc_TF_table']) $options['pmc_TF_table'] = "width: 120px; padding: 0; margin: 20px 0; border: 0; border-collapse: collapse; border-spacing: 0;";
		if (!$options['pmc_TF_type']) $options['pmc_TF_type'] = 'friends';

		
		//get the options already saved in the database, encoding any HTML
		$pmcTFTitle = htmlspecialchars($options['pmc_TF_title'], ENT_QUOTES);
		$pmcTFUser = htmlspecialchars($options['pmc_TF_user'], ENT_QUOTES);
		$pmcTFRows = htmlspecialchars($options['pmc_TF_rows'], ENT_QUOTES);
		$pmcTFLimit = htmlspecialchars($options['pmc_TF_limit'], ENT_QUOTES);
		$pmcBGcolor = htmlspecialchars($options['pmc_TF_bgcolor'], ENT_QUOTES);
		$pmcFGcolor = htmlspecialchars($options['pmc_TF_fgcolor'], ENT_QUOTES);
		$pmcCacheUpdate = htmlspecialchars($options['pmc_TF_cache'], ENT_QUOTES);
		$pmcShowRSS = htmlspecialchars($options['pmc_TF_show_rss'], ENT_QUOTES);
		$pmcUpdateUser = htmlspecialchars($options['pmc_TF_update_user'], ENT_QUOTES);
		$pmcTFTitleLink = htmlspecialchars($options['pmc_TF_title_link'], ENT_QUOTES);
		$pmcTFTable = htmlspecialchars($options['pmc_TF_table'], ENT_QUOTES);
		$pmcTFDisplay = htmlspecialchars($options['pmc_TF_type'], ENT_QUOTES);
		$pmcTFPass = htmlspecialchars($options['pmc_TF_password'], ENT_QUOTES);
		
		//code to automatically enable checkbox if user has enabled setting
		if ($pmcShowRSS) {
			$pmcShowRSS = ' checked="yes" ';
		} else {
			$pmcShowRSS = '';
		}
		
		//build the control panel
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_title">' . __('Title:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_title" name="pmc_TF_title" type="text" value="'.$pmcTFTitle.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_title_link">' . __('Title Link:', 'widgets');
		echo pmcSelectLink($pmcTFTitleLink);
		echo '</label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_user">' . __('Your Twitter Name:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_user" name="pmc_TF_user" type="text" value="'.$pmcTFUser.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_password">' . __('Your Twitter Password:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_password" name="pmc_TF_password" type="password" value="'.$pmcTFPass.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_type">' . __('Display Friends or Followers?:', 'widgets');
		echo pmcSelectDisplay($pmcTFDisplay);
		echo '</label></p>';
		echo '<p style="color: red; font-weight: bold;">If you change the "Display Friends or Followers" setting, please do a <a href="./admin.php?page=twitter-friends-delete" title="Delete Friends">delete all</a> followed by a <a href="./admin.php?page=twitter-friends-update" title="Manual Update">manual update</a></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_rows">' . __('Images per Row:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_rows" name="pmc_TF_rows" type="text" value="'.$pmcTFRows.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_limit">' . __('Display Limit (0 for Display all):', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_limit" name="pmc_TF_limit" type="text" value="'.$pmcTFLimit.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_cache">' . __('Cache Update Interval: (in seconds)', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_cache" name="pmc_TF_cache" type="text" value="'.$pmcCacheUpdate.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_show_rss">' . __('Show RSS Link?', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_bgcolor" name="pmc_TF_show_rss" type="checkbox"'.$pmcShowRSS.' /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_bgcolor">' . __('Background Colour:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_bgcolor" name="pmc_TF_bgcolor" type="text" value="'.$pmcBGcolor.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_fgcolor">' . __('Text Colour:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_fgcolor" name="pmc_TF_fgcolor" type="text" value="'.$pmcFGcolor.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_table">' . __('Table Style:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_table" name="pmc_TF_table" type="text" value="'.$pmcTFTable.'" /></label></p>';
		echo '<input type="hidden" id="pmc_friends_widget_submit" name="pmc_friends_widget_submit" value="1" />';
		
	} //close pmcFriends_control()
	
	//function to write drop down list in control panel and automatically select currently stored value
	function pmcSelectLink($pmcCurrOpt) {
		//start building the select tag
		$pmcSelect = '<select style="display: block; width: 300px; text-align: left;" id="pmc_TF_title_link" name="pmc_TF_title_link">';
		
		//build the rest of the select tag based on current setting
		switch ($pmcCurrOpt) {
			case 'none':
				$pmcSelect .= '<option value="none" selected="selected">None</option><option value="page">My Twitter Page</option><option value="rss">My Twitter RSS</option>';
				break;
			case 'page':
				$pmcSelect .= '<option value="none">None</option><option value="page" selected="selected">My Twitter Page</option><option value="rss">My Twitter RSS</option>';
				break;
			case 'rss':
				$pmcSelect .=  '<option value="none">None</option><option value="page">My Twitter Page</option><option value="rss"  selected="selected">My Twitter RSS</option>';
				break;
			default:
				$pmcSelect .= '<option value="none">None</option><option value="page">My Twitter Page</option><option value="rss">My Twitter RSS</option>';
		}
		
		//close the select tag
		$pmcSelect .= '</select>';
		
		//return the completed tag
		return $pmcSelect;
			
	} //close pmcSelectLink
	
	//function to write the HTML for the "Display" drop down
	function pmcSelectDisplay($pmcOpt) {
		//start building the drop down
		$pmcSelect = '<select style="display: block; width: 300px; text-align: left;" id="pmc_TF_type" name="pmc_TF_type">';
		
		//build the list based on current option
		switch ($pmcOpt) {
			case 'friends':
				$pmcSelect .= '<option value="friends" selected="selected">Friends</option><option value="followers">Followers</option>';
				break;
			case 'followers':
				$pmcSelect .= '<option value="friends">Friends</option><option value="followers" selected="selected">Followers</option>';
				break;
			default:
				$pmcSelect .= '<option value="friends" selected="selected">Friends</option><option value="followers">Followers</option>';
		}
		
		//close select
		$pmcSelect .= '</select>';
		
		//return the completed tag
		return $pmcSelect;
		
	} //close pmcSelectDisplay
	
	//register widget and widget control
	register_sidebar_widget('Twitter Friends', 'pmcDisplayFriends');
	register_widget_control('Twitter Friends', 'pmcFriends_control', 300, 300);
	
} //close widget_pmcTwitterFriends_init

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
	echo '<p>';
	pmcGetFriendsCount();
	echo '</p>';
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
	
	//get the contents of the post variable
	$pmcDelete = $_POST;
	
	//set the table name
	$pmcTableName = $wpdb->prefix . 'twitterfriends';
		
	//check if the user has clicked a delete button
	if ($pmcDelete) {
		//the returned array contains the id of the user that we want to delete
		$pmcDeleteID = array_keys($pmcDelete);
		
		//check if the user wants to delete all 
		if ($pmcDeleteID[0] == 'delete-all') {
			//SQL to delete all entries
			$SQL = "DELETE FROM $pmcTableName";
		} else {
			//delete a single entry
			$SQL = "DELETE FROM $pmcTableName WHERE `id` LIKE $pmcDeleteID[0]";
		} //close if

	} //close if
		
	//run the query
	$pmcResult = $wpdb->query($SQL);
	
	echo '<div class="wrap">';
	echo '<h2>Twitter Friends In The Database</h2>';
	echo '<h3>Delete All Friends</h3>';
	echo '<p>Use this option if you want to remove all your Twitter Friends from the database.</p>';
	echo '<p style="color: red; font-weight: bold;">Please back up your WordPress database before proceeding.</p>';
	echo '<p><form name="delete-all-form" action="" method="post"><input type="submit" value="Delete All" class="button-primary" /><input type="hidden" name="delete-all" value="1" /></form></p>';
	echo '<p>&nbsp;</p>';

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
			$pmcNewID = pmcRetrieveTwitterID();
					
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
		echo '<div id="message" class="updated fade"><p>All widget settings have been <strong>removed</strong>. Thank you for using Twitter Friends Widget. You can now safely <a href="./plugins.php" title="Plugins Page">deactivate</a> Twitter Friends Widget</p></div>';

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
	
	//remove the friends count
	delete_option('pmc_TF_friends_count');
	
	//remove the followers count
	delete_option('pmc_TF_followers_count');
	
} //close pmcUninstallSettings()

//function to display friends using shortcode
function pmcShortcode($atts) {
	
	//use wpdb
	global $wpdb;
	
	//table name
	$pmcTable = $wpdb->prefix . 'twitterfriends';
	
	//set the default shortcode attributes
	extract(shortcode_atts(array(
			'friends_per_row' => 20,
			'num_friends' => 100,
			), $atts));	
	
	//build the SQL to get friends list from db
	$SQL = "SELECT * FROM `" . $pmcTable . "` LIMIT " . $num_friends;
	
	//query db
	$pmcResult = $wpdb->get_results($SQL);
	
	//start building the output table
	$pmcOutput = '<table class="pmcTFTable">';
	
	//we'll use a flag to check if a new row should be opened
	$pmcFlag = TRUE;
	
	//we'll use a counter to check if the table row should be closed
	$i = 1;
	
	//loop through the results
	foreach ($pmcResult as $pmcFriend) {
		
		//check if we need to open a new row
		if ($pmcFlag) {
			//add the html to open the row
			$pmcOutput .= '<tr class="pmcTFTR">';
			//set the open row flag to false
			$pmcFlag = FALSE;
		}
		
		//build the link to the friends twitter page using their profile image
		$pmcOutput .= '<td class="pmcTFTD"><a rel="nofollow" href="http://twitter.com/' . $pmcFriend->screen_name . '" title="' . $pmcFriend->screen_name . '"><img style="height: 24px; width: 24px;" src="' . $pmcFriend->profile_image_url . '" title="' . $pmcFriend->screen_name . '" alt="' . $pmcFriend->screen_name . '" /></a></td>';
		
		//check if we need to close the row
		if ($i % $friends_per_row == 0) {
			
			$pmcOutput .= '</tr>';
			
			//if we close the row, we'll need to open a new row afterwards
			//if this is the last friend in the list, then the code to write the html for a new row won't be called.
			$pmcFlag = TRUE;
		}
		
		//increment the counter for the next friend
		$i++;
	}

	//close table
	$pmcOutput .= '</table>';
	
	//return the finished table to the shortcode macro handler
	return $pmcOutput;
	
} //close pmcShortcode

//make sure that the plugin is not loaded until after the widgets are
add_action("plugins_loaded", "pmcTFAdvanced_init");
//action to have WordPress load the widget
add_action('widgets_init', 'widget_pmcFriends_init');
//action to add styles to the header
add_action('wp_head', 'pmcTFStyles');
//action to allow shortcode
add_shortcode('twitter-friends', 'pmcShortcode');
?>
