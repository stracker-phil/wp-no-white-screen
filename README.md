# wp-no-white-screen
Got a white-screen-of-death in WordPress? Use this file to display the error!

# Setup
1. Save this file as `wp-contents/mu-plugins/no-white-screen.php`
2. In wp-config.php set `WP_DEBUG` to true

If you still don't see any errors, then there's something fundamentally wrong
and you should start debugging wp-settings.php by adding some debug output
at various points to find the last working line.

*Remember to remove this file again after debugging the error!*