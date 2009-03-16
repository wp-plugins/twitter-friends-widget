<?php
/*
Plugin Name: Twitter Friends Widget
Plugin URI: http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget/
Description: Widget to display your Twitter Friends in the sidebar
Version: 1.2
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

//function called when widget is initiated
function widget_pmcFriends_init() {
	
	//check that we can use widgets
	if (!function_exists('register_sidebar_widget')) {
		return;
	}

	//function to get the friends list
	function pmcGetFriends($pmcArgs) {
		//check the cache file permissions
		
		//get the default WordPress widget settings
		extract($pmcArgs);
	
		//get this widget's settings
		$pmcOptions = get_option('widget_pmcFriends');
		$pmcTFTitle = $pmcOptions['pmc_TF_title'];
		$pmcTFUser = $pmcOptions['pmc_TF_user'];
		$pmcTFRows = (int) $pmcOptions['pmc_TF_rows'];
		$pmcTFLimit = (int) $pmcOptions['pmc_TF_limit'];
		
		//check that the user has not set 0, if they have we'll use the default.
		if ($pmcTFRows == 0) {
			$pmcTFRows = 5;
		}
			
	
		//build the URL to retrieve the friends list from Twitter
		$pmcURL = 'http://twitter.com/statuses/friends/' . $pmcTFUser . '.xml';
		
		//we'll use Troy Wolf's http_class to connect to Twitter
		require_once(dirname(__FILE__).'/class_http.php');
		
		
		//set up the connection to the Twitter API
		$pmcTFconn = new http();
		
		//start the widget display
		echo $before_widget . $before_title . $pmcTFTitle . $after_title;
		
		//to re-enable caching un-comment the line below and change the TTL the the if statement below from "0" to "daily"
		//don't forget to make the cache folder writable
//		$pmcTFconn->dir = dirname(__FILE__)."/cache/";
	
		//lets go get the friends list (If you want to re-enable caching, change "0" to "daily")
		if (!$pmcTFconn->fetch($pmcURL, "0", "friends.xml")) {
			echo '<h2>There was a problem getting your friends list</h2>';
			echo '<p>Unable to retrieve your friends list from Twitter. Please try again later.</p>';
			echo '<p>For more information regarding possible problems, please see the error log below.</p>';
			echo $pmcTFconn->log;
			exit();
		} // close if
		
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
		
		//loop through the array, and strip the XML tags
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
		

		//if the user has specified a limit of 0, we'll display all the friends, otherwise apply the limit
		if ($pmcTFLimit == 0) {
			//get the length of the array - i.e. all friends
			$pmcCount = count($pmcFriends);
		} else {
			$pmcCount = $pmcTFLimit;
		} //close if
		
		//build the table
		$pmcTable = "\n" . '<table class="pmcTFTable">' . "\n" . '<tr>'  . "\n";

		//we'll create the table with the number of rows specified by the user, default is 5
		for ($i = 1; $i < $pmcCount; $i++) {
			$pmcTable = $pmcTable . '<td class="pmcTFTD"><a style="color: #000;" href="http://twitter.com/' . $pmcFriends[$i] . '" title="' . $pmcFriends[$i] . '"><img class="pmcTFimg" src="' . $pmcImageURL[$i] . '" alt="' . $pmcFriends[$i] . '" /></a></td>' . "\n";
			if ($i % $pmcTFRows == 0) {
				$pmcTable = $pmcTable . '</tr><tr>' . "\n";
			} //close if
		} //close for

		$pmcTable = $pmcTable . '</tr>' . "\n" . '</table>' . "\n";

		//display link to RSS feed
		$pmcTable .= '<p><a href="https://twitter.com/statuses/user_timeline/' . pmcRetrieveTwitterID() . '.rss" title="Subscribe to my Twitter Feed">Subscribe to my Twitter RSS</a></p>';

		//display the table
		echo $pmcTable;
		
		//close the widget
		echo $after_widget;
		
	} //close pmcGetFriends

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

		
		//get the options already saved in the database, encoding any HTML
		$pmcTFTitle = htmlspecialchars($options['pmc_TF_title'], ENT_QUOTES);
		$pmcTFUser = htmlspecialchars($options['pmc_TF_user'], ENT_QUOTES);
		$pmcTFRows = htmlspecialchars($options['pmc_TF_rows'], ENT_QUOTES);
		$pmcTFLimit = htmlspecialchars($options['pmc_TF_limit'], ENT_QUOTES);
		$pmcBGcolor = htmlspecialchars($options['pmc_TF_bgcolor'], ENT_QUOTES);
		$pmcFGcolor = htmlspecialchars($options['pmc_TF_fgcolor'], ENT_QUOTES);
		
		//build the control panel
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_title">' . __('Title:') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_title" name="pmc_TF_title" type="text" value="'.$pmcTFTitle.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_user">' . __('Your Twitter Name:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_user" name="pmc_TF_user" type="text" value="'.$pmcTFUser.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_rows">' . __('Friends per Row:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_rows" name="pmc_TF_rows" type="text" value="'.$pmcTFRows.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_limit">' . __('Display Limit (0 for Display all):', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_limit" name="pmc_TF_limit" type="text" value="'.$pmcTFLimit.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_bgcolor">' . __('Background Colour:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_bgcolor" name="pmc_TF_bgcolor" type="text" value="'.$pmcBGcolor.'" /></label></p>';
		echo '<p style="margin: 20px auto;"><label style="display: block; width:300px; text-align: left;" for="pmc_TF_fgcolor">' . __('Text Colour:', 'widgets') . ' <input style="display: block; width: 300px; text-align: left;" id="pmc_TF_fgcolor" name="pmc_TF_fgcolor" type="text" value="'.$pmcFGcolor.'" /></label></p>';
		echo '<input type="hidden" id="pmc_friends_widget_submit" name="pmc_friends_widget_submit" value="1" />';
		
	} //close pmcFriends_control()
	
	//function to write the style info to the header
	function pmcTFStyles() {
		//get styles options
		$pmcStyles = get_option('widget_pmcFriends');
		$pmcBGcolor = $pmcStyles['pmc_TF_bgcolor'];
		$pmcFGcolor = $pmcStyles['pmc_TF_fgcolor'];
		
		echo '<!-- CSS style for Twitter Friends widget -->' . "\n";
		echo '<style type="text/css">' . "\n";
		echo 'table.pmcTFTable {' . "\n" . 'width: 120px; padding: 0; margin: 0; border: 0; border-collapse: collapse; background-color: ' . $pmcBGcolor . ' !important; color: ' . $pmcFGcolor . ' !important;' . "\n" . '}' . "\n";
		echo 'td.pmcTFTD {' . "\n" . 'max-width: 24px; max-height: 24px; border: 0; padding: 0; margin: 0; border-collapse: collapse; overflow: hidden; background-color: ' . $pmcBGcolor . '!important; color: ' . $pmcFGcolor . '!important;' . "\n" . '}' . "\n";
		echo 'img.pmcTFimg {' . "\n" . 'border: 0; padding: 0; margin: 0; height: 24px; width: 24px;' . "\n" . '}' . "\n";
		echo '</style>' . "\n";
	}
	
	//function to get the users twitter id from their username
	function pmcRetrieveTwitterID() {
		//require class_http.php
		require_once(dirname(__FILE__).'/class_http.php');
	
		//create a new connection
		$pmcTwitterConn = new http();
	
		//get the Twitter username from post variable
		$pmcTwitterOptions = get_option('widget_pmcFriends');
		$pmcTwitterUser = $pmcTwitterOptions['pmc_TF_user'];
	
		//set the url to the Twitter API
		$pmcTwitterAPI = 'http://twitter.com/users/show/' . $pmcTwitterUser . '.xml';
	
		//make sure that we can connect, if not display an error message
		if (!$pmcTwitterConn->fetch($pmcTwitterAPI, "0", "twitter")) {
			echo "<h2>There is a problem with the http request!</h2>";
  			echo $pmcTwitterConn->log;
	  		exit();
		}
	
		//if we have connected, then get the data.
		//as this is xml data, we are lookig for the ID key and it's value.
		$pmcTwitterData=$pmcTwitterConn->body;
		preg_match ('/<id>(.*)<\/id>/', $pmcTwitterData, $matches);	
	
		//remove the <id></id> HTML tags from the returned key
		$pmcTrimID = strip_tags($matches[0]);
	
		//return the Twitter ID
		return $pmcTrimID;
		
	}
	//register the widget and widget control
	register_sidebar_widget('Twitter Friends', 'pmcGetFriends');
	register_widget_control('Twitter Friends', 'pmcFriends_control', 300, 300);

} //close widget_pmcFriends_init

//have WordPress load the widget
add_action('widgets_init', 'widget_pmcFriends_init');

//add styles to the header
add_action('wp_head', 'pmcTFStyles');
?>