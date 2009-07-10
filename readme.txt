=== Twitter Friends Widget ===
Contributors: paulmac, dremation
Donate link: https://www.paypal.com/cgi-bin/webscr?cmd=_donations&business=LPT7UT9QE2G42&lc=IE&item_name=Paul%20McCarthy&amount=1%2e00&currency_code=EUR&currency_code=EUR&bn=PP%2dDonationsBF%3abtn_donate_SM%2egif%3aNonHosted
Tags: Twitter, widget, friends, following
Requires at least: 2.5
Tested up to: 2.7.*
Stable tag: trunk

Displays your Twitter friends with their profile images in the sidebar.

== Description ==

Twitter Friends is a WordPress plugin that displays your Twitter friends/ followers in your sidebar in the same way that they appear on your Twitter homepage.

== Installation ==

1. Extract "twitter-friends-widget.zip".
1. Upload the "twitter-friends-widget" folder to your WordPress plugins directory.
1. Activate the "Twitter Friends Widget" from the WordPress plugins Admin Panel.
1. Update the options, from the Widgets Settings Page under Twitter Friends on the Main Menu.

= Using Twitter Friends Shortcode =

Twitter Friends now includes a WordPress shortcode that allows you to display your friends list in a post/ page. To use the shortcode, create a new post/ page and enter the  Twitter Friends shortcode. The shortcode uses the following syntax:

[twitter-friends title="My Twitter Friends" limit="20" type="friends" size="mini"]

The shortcode takes four attributes:
1. title: The title displayed by the shortcode output. The default is "My Twitter Friends".
1. limit: The number of Twitter Friends to display. Default is 20.
1. type: Display "friends" or "followers". Default is "friends".
1. size: Image size to display - "mini", "normal" or "bigger". Default is "mini".

== Frequently Asked Questions ==

= The profile images are not being displayed =
Twitter stores profile images using Amazon's S3 online storage. The plugin hotlinks to the images on the Amazon. If the Twitter User changes their profile image it may take some time for the cache to update with the latest profile image.

= I have a million followers on Twitter and it takes for ever for my sidebar to load. =
Of course it will. That's why there's an option within the plugin to limit the number of friends that are displayed. Use it.

= Why doesn't the cache update exactly after x seconds? =
The plugin will only check if it needs to be updated when the page displaying the plugin is loaded. So if you set the update interval to 3600 seonds, it will update the cache the next time the page is loaded after that hour has passed.

= How do I delete Friends/ Followers from the plugin? =
The ability to delete friends has been integrated into the Twitter Friends menu option. The page now displays your friends and followers along with a delete button.

== Settings ==

Widget Settings.

1. User Settings:
* Twitter User Name: Your Twitter login. Required.
* Twitter Password: Your Twitter Password. Required if you want to display your Twitter Followers instead of Twitter Friends.
1. Cache Settings:
* Cache Update Interval: How long between updates to the cache. Specified in seconds. Default is 3600 or one hour.
1. Output Settings:
* Widget Title: Default is "My Twitter Friends".
* Title Link: Whether the Widget Title should link to your Twitter Home Page, Twitter RSS or none, (default).
* Display Friends or Followers: Choose whether you want the widget to display your Twitter Friends (people you follow), or Twitter Followers (people who follow you).
* Display Limit: How many friends you want to display. By default this is set to 20. Friends are displayed in the order that they joined Twitter. Set this to 0 to display all.
* Profile Image Size: Size of the profile image to display. Options are "mini", "normal" and "bigger"
* Show RSS Link: Chose whether to show a link to your Twitter RSS feed or not.
* Show Friends & Followers Counts: Show how many Twitter Friends and Twitter Followers you have.

== Feedback ==
Feedback and requests for new features are welcome. Just leave a comment on the plugin homepage. (http://www.paulmc.org/whatithink/wordpress/plugins/twitter-friends-widget) or via Twitter to @whatithink

== Changelog ==

= 3.10 =
* Fixed bug that prevented Followeres from being shown 

= 3.02 =
* Added WordPress Mu compatibility. (Fixed issue with WPMU users not being able to save settings).
* Minor update to CSS used to display images. 
* Added Settings link to Plugins page. (Appears beside Deactivate and Edit links)

= 3.01 =
* Updated CSS for better compatibility with IE.

= 3.0 =
* Complete rewrite of plugin.
* Images displayed using DIV's instead of TABLE.
* Moved Widget Settings to Main Menu.
* Redesign of Twitter Friends back-end.
* Delete Options integrated with main Twitter Friends menu.
* Plugin updates both Friends and Followers regardless of which is being displayed.
* Cache update code moved to blog footer - speeds up display of sidebar.
* New database structure.
* Updated code to retrieve all friends/ followers from Twitter.
* Removed options to specify background and text colours - no longer required since display table was removed.
* Updated shortcode to allow more options.
* Added option to display Friends and Followers Counts.


= 2.7 =
* Added shortcode.
* Added rel="nofollow" to Twitter Profile/ RSS links.
* Cleaned up some database code.
* Bug Fix: Updated code for genarating output table to remove an XHTML validation error.

= 2.6 =
* Added option to specify whether Friends or Followers are displayed.
* Updated code to use curl instead of pmc_http_class to connect to Twitter.
* Added "Delete All" option in Advanced Settings.
* Bug Fix: Error in code used to display RSS link in Widget Title.
* Bug Fix: Display limit not being displayed correctly in Widget Control Panel.
* Bug Fix: Twitter ID not updated correctly.

= 2.5.1 =
* Incorporated patch from David Jack Wange Olrik to rename class_http.php and generic http() class to avoid naming conflicts.

= 2.5 =
* Added "Twitter Friends Widget - Advanced Settings" plugin.
* Added Delete Friends option.
* Added Manual Update option.
* Added Change User option.
* Added Uninstall option.
* Updated widget code to alter how the Twitter ID is retrieved from Twitter Username.
* Fixed minor issue with profile images being incorrectly displayed in sidebar.

= 2.4 =
* Added option to change table style.

= 2.3 =
* Added option to enable/ disable "Subscribe to My Twitter Feed" link.
* Added option to add link to Widget Title

= 2.2 =
* Fixed a bug where ther limit specified in the widget settings was not being applied.

= 2.1 =
* Removed wp-cron based caching as this was unreliable.
* Added my own simple scheduler for updating the cache.

= 2.0 =
* Complete re-write of the plugin
* Added caching via a new table added to the WordPress database
* Added cache updating via wp-cron
* Added option that permanantly stores Twitter ID, Twitter API only needs to be called once to get the ID
* Added RSS icon to subscribe link

= 1.21 =
* Fixed bug with background and text colour styles not being applied correctly

= 1.2 =
* Due to a large number of issues with the caching function, caching has been removed. See FAQ for more details.
* Added link to users Twitter RSS
* Added option to change background and text colours.

= 1.13 =
* Added blank http_friends.xml to cache so that cache folder will be included with plugin .zip.
* Updated installation instructions to reflect that cache must be writable.
* Updated readme with error message regarding unwritable cache folder.

= 1.12 =
Fixed issue with profile images not being displayed correctly.

= 1.11 =
Fixed bug with local caching

= 1.1 =
* Changed from using "normal" size images to "mini" size profile images
* Added styles to header
