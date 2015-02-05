# wp-no-white-screen

This plugin helps troubleshoot issues with the infamous WordPress WSOD. Most errors can be caught with WP-DEBUG turned on using tools like X-Debug but some of the fatal errors don't come through either with WP-DEBUG or your PHP error handler. This is beacuse PHP lets WP "Handle" the error - the way WP handles an fatal error is to not display it [the reason is to increase security, since error messages often reveal information that can be used to leverage an attack on a site]

Plugin developers and Advanced users who troubleshoot complex issues will find this tool handy. No more messing with your PHP configuration or blind-testing to find out what's going on!


# Usage

 * Open up the `mu-plugins` folder in your WordPress Directory (or create one) usually located within `wp-content` folder in your `public_html` folder. Select the PHP file `no-white-screen.php` and place it into the `wp-content/mu-plugin` folder in your WordPress Directory. You will have all errors displayed or wrriten to log depnding on your settings in the next step. 

 * Open the `wp-config.php` file, which can be found in the WordPress folder and make these changes

 ```
    // Comment this out or change to true 
	// define('WP_DEBUG', false);

	define('WP_DEBUG', true);
	
	// If you want to write to log
	define('WP_DEBUG_LOG', true);

	// If you want to hide errors from users (Used mostly for live sites)
	define('WP_DEBUG_DISPLAY', false);

	// Just in case
	@ini_set('display_errors', 0);
 ```


Further Reading 

 * [The WordPress Codex](http://codex.wordpress.org/Debugging_in_WordPress)
 * [Other Cool Options](http://nacin.com/2010/04/23/5-ways-to-debug-wordpress/)
 * [Step by Step Instructions](http://fuelyourcoding.com/simple-debugging-with-wordpress/) 

**Tipp:**

If you still don't see any errors, then it's likely that there's something fundamentally wrong with WordPress. You should start debugging `/wp-settings.php` by adding breakpoints and identifying the last execution point(or line). 

**IMPORTANT** *Remember to remove this file again after debugging the error!*
Leaving it will cost your server valuable performance and can also expose error information to the public, which decreases your sites security
