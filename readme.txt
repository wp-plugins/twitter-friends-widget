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

= How do I enable caching? =
Caching is now enable by default. The plugin creates a table in the WordPress database to store the screen names and profile image url's of your friends. The cache is updated on a hourly basis by WP-Cron.

= Can I disable caching? =
At the moment, no. I may add an option to disable caching, but I don't see any reason to. Getting the friends list from Twitter each time the page is loaded is slow, and if for any reason Twitter is down, your friends list won't be displayed. By using the cache, the plugin will have something to display. It might not reflect the latest followers you've added, but it will show something.

= How often does the cache update? =
The cache uses wp_cron to update itself automatically, and this is set to run an update on an hourly basis. WP-Cron only runs when your website has been visited, so it may take slightly longer than an hour to update.

= Can I change the update frequency? =
Not at the moment. I will be adding an option to the plugin settings to change the frequency.

= The RSS icon does not appear beside the subscribe link =
The path to the RSS icon is hard-coded as follows:

[your WordPress URL]/wp-contents/plugins/twitter-friends-widget/rss.png

If you have modified your WordPress install so that your plugins are not stored in the default plugins directory, then the icon will not be found. Edit line 241 of twitter-friends-widget.php to reflect your current plugins directory and it should work again.

== Feedback ==
Feedback and requests for new features are welcome. Just leave a comment on the plugin homepage. (http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget)

== Changelog ==

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

== To Do ==
* Add option to specify large or mini styles
* Add check to ensure that user name is entered.
