<?php
//-----------------------------------------------------------------------------
/*
Plugin Name: Twitter Posts
Version: 1.0.2
Plugin URI: http://www.rene-ade.de/inhalte/wordpress-plugin-twitterposts.html
Description: This wordpress plugin automatically twitters all posts matching a user defined query...
Author: Ren&eacute; Ade
Author URI: http://www.rene-ade.de
*/
//-----------------------------------------------------------------------------
?>
<?php
  function twitterposts_plugin_basename() {
    return plugin_basename(__FILE__);
  }
  include 'twitter-posts/twitter-posts.php';
?>