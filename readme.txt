=== Twitter Friends Widget ===
Contributors: paulmac
Tags: Twitter, widget, friends, following
Requires at least: 2.5
Tested up to: 2.7.1
Stable tag: trunk

Displays your Twitter friends with their profile images in the sidebar.

== Description ==

Twitter Friends is a WordPress plugin that displays your Twitter friends in your sidebar in the same way that they appear on your Twitter homepage.

The widget allows you to specify the following:

1. Widget Title - default is "My Twitter Followers".
1. Title Link - Whether the Widget Title should link to your Twitter Home Page, Twitter RSS or none, (default).
1. Your Twitter Name - required.
1. Friends per Row - how many friends you want displayed in each row. Default is 5. 
1. Display Limit - how many friends you want to display. By default this is set to 20. Friends are listed in the order of the most recently added to your Twitter profile. Set this to 0 to display all.
1. Cache Update Interval - how long between updates to the cache. Specified in seconds. Default is 3600 or one hour.
1. Show RSS Link - chose whether to show a link to your Twitter RSS feed or not.
1. Background Colour - the background colour of the table holding the Twitter Profile Images. Default is white (#FFFFFF). Use Hex values or the standard HTML named colours.
1. Text Colour - as the background colour above, but affects the text displayed. Default is black (#000000). Once again Hex or named colours can be used.
1. Table Style - set the CSS to style the table used to display the friends list.

The Advanced Settings Plugin allows you to perform the following actions:

1. Delete Friends -Delete Twitter friends from the cache. This does not delete the friend from your Twitter Profile. Useful if you want to refresh the profile image or screen name of a small number of Twitter Friends.
1. Manual Update - Performs a manual update of your Twitter Friends list. Useful if you have added a number of Twitter Friends and don't want to wait for the cache to update automatically.
1. Change Twitter User - Use this option if you have changed your Twitter Username. Automatically updates the widget with details of your new username and rebuilds your Twitter Friends list.
1. Uninstall - Removes all database settings and deletes the cache. Deactivate the Twitter Friends Widget before performing the uninstallation. Useful if you are experiencing problems with the widget.

== Installation ==

1. Extract "twitter-friends-widget.zip".
1. Upload the "twitter-friends-widget" folder to your WordPress plugins directory.
1. Activate the "Twitter Friends Widget" from the WordPress plugins Admin Panel.
1. If required, activate the "Twitter Friends Widget - Advanced Settings" plugin from the WordPress plugins Admin Panel.
1. Update the options, from the Widgets Panel under the Appearance/ Design Panel. (Depends on your version of WordPress.)
1. Access the "Twitter Friends Widget - Advanced Settings" from the Main Menu.

== Frequently Asked Questions ==

= The profile images are not being displayed =
Twitter stores profile images using Amazon's S3 online storage. The plugin hotlinks to the images on the Amazon. If the Twitter User changes their profile image it may take some time for the cache to update with the latest profile image. If the problem continues, perform a Manual Update from the "Twitter Friends Widget - Advanced Settings".

= I have a million followers on Twitter and it takes for ever for my sidebar to load. =
Of course it will. That's why there's an option within the plugin to limit the number of friends that are displayed. Use it.

= The table that displays the users doesn't blend in with my blog theme, how do I change it? =
In the Widget settings panel, you can specify background colour, text colour and CSS style for the containing table. Use either hex values (e.g. #FFFFFF is white), or the standard HTML colours, (red, green, blue etc.).

= How do I enable caching? =
Caching is now enable by default. The plugin creates a table in the WordPress database to store the screen names and profile image url's of your friends. The cache is updated automatically, dependent on the time specified in the widget options. The cache can now be manually updated using the "Manual Update" function in "Twitter Friends Widget - Advanced Settings".

= Can I disable caching? =
At the moment, no. I may add an option to disable caching, but I don't see any reason to. Getting the friends list from Twitter each time the page is loaded is slow, and if for any reason Twitter is down, your friends list won't be displayed. By using the cache, the plugin will have something to display. It might not reflect the latest followers you've added, but it will show something.

= How often does the cache update? =
The default setting is 3600 seconds, (one hour), but this can be changed in the widget options. I would reccomend that you don't set this to 0 if you have a lot of Twitter friends, as the page load time will increase drastically. The cache can now be updated manually using the "Twitter Friends Widget - Advanced Settings".

= Why doesn't my page update exactly after x seconds? =
The plugin will only check if it needs to be updated when the page displaying the plugin is loaded. So if you set the update interval to 3600 seonds, it will update the cache the next time the page is loaded after that hour has passed.

= The RSS icon does not appear beside the subscribe link =
The path to the RSS icon is hard-coded as follows:

[your WordPress URL]/wp-contents/plugins/twitter-friends-widget/rss.png

If you have modified your WordPress install so that your plugins are not stored in the default plugins directory, then the icon will not be found. Edit lines 245 and 273 of twitter-friends-widget.php to reflect your current plugins directory and it should work again.

== Feedback ==
Feedback and requests for new features are welcome. Just leave a comment on the plugin homepage. (http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget) or via Twitter to @whatithink

== Changelog ==

= Version 2.51 =
* Bug Fix: Fatal Error triggered if Advanced Settings loaded before widget.

= Version 2.5 =
* Added "Twitter Friends Widget - Advanced Settings" plugin.
* Added Delete Friends option.
* Added Manual Update option.
* Added Change User option.
* Added Uninstall option.
* Updated widget code to alter how the Twitter ID is retrieved from Twitter Username.
* Fixed minor issue with profile images being incorrectly displayed in sidebar.

= Version 2.4 =
* Added option to change table style.

= Version 2.3 =
* Added option to enable/ disable "Subscribe to My Twitter Feed" link.
* Added option to add link to Widget Title

= Version 2.2 =
* Fixed a bug where ther limit specified in the widget settings was not being applied.

= Version 2.1 =
* Removed wp-cron based caching as this was unreliable.
* Added my own simple scheduler for updating the cache.

= Version 2.0 =
* Complete re-write of the plugin
* Added caching via a new table added to the WordPress database
* Added cache updating via wp-cron
* Added option that permanantly stores Twitter ID, Twitter API only needs to be called once to get the ID
* Added RSS icon to subscribe link

= Version 1.21 =
* Fixed bug with background and text colour styles not being applied correctly

= Version 1.2 =
* Due to a large number of issues with the caching function, caching has been removed. See FAQ for more details.
* Added link to users Twitter RSS
* Added option to change background and text colours.

= Version 1.13 =
* Added blank http_friends.xml to cache so that cache folder will be included with plugin .zip.
* Updated installation instructions to reflect that cache must be writable.
* Updated readme with error message regarding unwritable cache folder.

= Version 1.12 =
Fixed issue with profile images not being displayed correctly.

= Version 1.11 =
Fixed bug with local caching

= Version 1.1 =
* Changed from using "normal" size images to "mini" size profile images
* Added styles to header

== Coming Soon ==
* Option to specify mini, normal or bigger profile images.
* Shortcode to allow display of Twitter Friends from within posts/ pages.
* Option to use Twitter API to block users when deleted from cache.
* Local caching of profile images.