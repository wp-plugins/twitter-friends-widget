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
1. Your Twitter Name - required.
1. Friends per Row - how many friends you want displayed in each row. Default is 5. 
1. Display Limit - how many friends you want to display. By default this is set to 20. Friends are listed in the order of the most recently added to your Twitter profile. Set this to 0 to display all.
1. Background Colour - the background colour of the table holding the Twitter Profile Images. Default is white (#FFFFFF). Use Hex values or the standard HTML named colours.
1. Text Colour - as the background colour above, but affects the text displayed. Default is black (#000000). Once again Hex or named colours can be used.

== Installation ==

1. Extract "twitter-friends-widget.zip".
1. Upload the "twitter-friends-widget" folder to your WordPress plugins directory.
1. Activate the plugin from the WordPress plugins Admin Panel.
1. Update the options, from the Widgets Panel under the Appearance/ Design Panel. (Depends on your version of WordPress.)

== Frequently Asked Questions ==

= The profile images are not being displayed =
Twitter stores profile images using Amazon's S3 online storage. The plugin hotlinks to the images on the Amazon. If the images are not being retrieved, then there might be an issue with your ISP's access to the Amazon cloud. Give it a minute and try again.

= I have a million followers on Twitter and it takes for ever for my sidebar to load. =
Of course it will. That's why there's an option within the plugin to limit the number of friends that are displayed. Use it.

= The table that displays the users doesn't blend in with my blog theme, how do I change it? =
In the Widget settings panel, you can specify a background colour and a text colour. Use either hex values (e.g. #FFFFFF is white, or the standard HTML colours: red, green, blue etc.)

= How do I re-enable caching? =
Due to a large number of bug reports regarding the caching function, I have temporarily disabled it in this version of the plugin. If you wish, you can re-enable it manually, though it will require editing of the plugin files.

Firstly you will need to edit the twitter-friends-widget.php file. Look for the following at line 71:

//		$pmcTFconn->dir = dirname(__FILE__)."/cache/";

Delete the comment marks and then scroll to line 74:

if (!$pmcTFconn->fetch($pmcURL, "0", "friends.xml")) {

Change the "0" to "daily", including the quotation marks. (You can set this TTL to any time you wish, the caching period is measured in seconds.)

Once caching has been enabled in the plugin, you will need to create a folder called "cache" in the "twitter-friends-widget" folder. This folder must be writable.

= I have enabled caching, but I keep getting an error message. Why? =
If you receive the following error after manually enabling caching, then you should double check that the "cache" folder is writable:

Warning: Missing argument 1 for http::getFromUrl(), called in ".../wp-content/plugins/twitter-friends-widget/class_http.php" on line 88 and defined in ".../public_html/wp-content/plugins/twitter-friends-widget/class_http.php"

== Feedback ==
Feedback and requests for new features are welcome. Just leave a comment on the plugin homepage. (http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget)

== Changelog ==

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

== To Do ==
* Add option to specify large or mini styles
* Add check to ensure that user name is entered.
* Work out caching bugs
