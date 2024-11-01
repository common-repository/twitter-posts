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

//-----------------------------------------------------------------------------

if( !function_exists('twitterposts_plugin_basename') ) {
  function twitterposts_plugin_basename() {
    return plugin_basename(__FILE__);
  }
}

//-----------------------------------------------------------------------------

// get option uncached
function twitterposts_get_option_uncached( $identifier ) {
  global $wpdb;
  
  // get option directly from database
  $identifier = 'twitterposts_'.$identifier;
  $row = $wpdb->get_row( 
    "SELECT option_value FROM $wpdb->options WHERE option_name = '$identifier' LIMIT 1" );
  if( is_object($row) )
    return maybe_unserialize( $row->option_value );
  return false;
}

// update option uncached
function twitterposts_update_option_uncached( $identifier, $value, $addifneeded=false ) {
	global $wpdb;
  
  // write option into database
  $identifier_unchanged = $identifier;
  $value_unchanged = $value;
  $identifier = 'twitterposts_'.$identifier;
  $value = maybe_serialize( $value );
  if( $addifneeded && twitterposts_get_option_uncached($identifier_unchanged)===false ) {
    $wpdb->query( 
      $wpdb->prepare("INSERT INTO $wpdb->options (option_name, option_value, autoload) VALUES (%s, %s, %s)",$identifier,$value,'no') );
    return true;
  }
  $wpdb->query( 
    $wpdb->prepare("UPDATE $wpdb->options SET option_value = %s WHERE option_name = %s",$value,$identifier) );
  if( $wpdb->rows_affected>=1 )
    return true;
  return false;
}

//-----------------------------------------------------------------------------

// add post to done list
function twitterposts_done( &$post ) {
 
  // get done
  $done = twitterposts_get_option_uncached( 'done' );
  
  // add or update
  $done[ $post->ID ] = $post->post_date_gmt;
  
  // save
  twitterposts_update_option_uncached( 'done', $done );
  
  // update info
  $info = $oldinfo = twitterposts_get_option_uncached( 'info' );
  $info['done'] = count( $done );
  if( $info!=$oldinfo )
    twitterposts_update_option_uncached( 'info', $info );
}

// check if post is done
function twitterposts_done_check( &$post ) {
 
  // get done
  $done = twitterposts_get_option_uncached( 'done' );
  
  // check
  if( array_key_exists($post->ID,$done) ) {
    $ts_done = strtotime( $done[$post->ID].' GMT' );
    $ts_post = strtotime( $post->post_date_gmt.' GMT' );
    if( $ts_done>=$ts_post ) { 
      return true;
    }
  }
  return false;
}      
      
//-----------------------------------------------------------------------------

// get posts
function twitterposts_getposts( $query, $ts ) {

  // get posts
  $get_posts = new WP_Query;
  $posts = $get_posts->query( $query );

  // check error
  if( is_wp_error($posts) )
    return false;
  
  // remove old
  $posts_temp = array();
  foreach( $posts as $post ) {
    if( strtotime($post->post_date_gmt.' GMT')>$ts )
      $posts_temp[] = $post;
  }
  $posts = $posts_temp;
  
  // sort
  usort( $posts, 'twitterposts_getposts_sort' );
  
  // return
  return $posts;
}

// sort function
function twitterposts_getposts_sort( $a, $b ) {

  // get times
  $a = strtotime( $a->post_date_gmt.' GMT' );
  $b = strtotime( $b->post_date_gmt.' GMT' );

  // sort
  if( $a > $b )
    return 1;
  else if( $a < $b )
    return -1;
  else
    return 0;
}

//-----------------------------------------------------------------------------

// output admin page
function twitterposts_admin() {

  // output
  echo '<div class="wrap">';
    echo '<h2>Twitter Posts</h2>';
    echo '<div class="form-wrap">';
      echo '<h3>Query</h3>';
      echo '<div class="controlform" style="padding-left:12px;">';
        echo '<form method="post">';
          twitterposts_admin_config_query();
        echo '</form>';
      echo '</div>';
    echo '</div>';
    echo '<div class="form-wrap">';
      echo '<h3>Message</h3>';
      echo '<div class="controlform" style="padding-left:12px;">';
        echo '<form method="post">';
          twitterposts_admin_config_string();
        echo '</form>';
      echo '</div>';
    echo '</div>';
    echo '<div class="form-wrap">';
      echo '<h3>Trigger</h3>';
      echo '<div class="controlform" style="padding-left:12px;">';
        echo '<form method="post">';
          twitterposts_admin_config_trigger();
        echo '</form>';
      echo '</div>';
    echo '</div>';
    echo '<div class="form-wrap">';
      echo '<h3>Twitter</h3>';
      echo '<div class="controlform" style="padding-left:12px;">';
        echo '<form method="post">';
          twitterposts_admin_config_twitter_login();
        echo '</form>';
        echo '<br />';
        echo '<form method="post">';
          twitterposts_admin_config_twitter_timeframe();
        echo '</form>';
      echo '</div>';
    echo '</div>';
    echo '<div class="form-wrap">';
      echo '<h3>Actions</h3>';
      echo '<div class="controlform" style="padding-left:12px;">';
        echo '<form method="post">';
          twitterposts_admin_actions();
        echo '</form>';
      echo '</div>';
    echo '</div>';    
    echo '<div class="form-wrap">';
      echo '<h3>Info</h3>';
      echo '<div style="padding-left:12px;">';      
        twitterposts_admin_info();
      echo '</div>';
    echo '</div>';
  echo '</div>';
}

// twitter config login
function twitterposts_admin_config_twitter_login() {

  // options
  $option = $oldoption = twitterposts_get_option_uncached( 'config' );

  // process
  if( isset($_POST['twitterposts-config-twitter-password']) ) {
    if( isset($_POST['twitterposts-config-twitter-user']) )
      $option['twitter']['user'] = strip_tags(stripslashes($_POST['twitterposts-config-twitter-user']));
    $option['twitter']['login'] =
      base64_encode( $option['twitter']['user']
      .':'
      .strip_tags(stripslashes($_POST['twitterposts-config-twitter-password'])) );
  }
  
  // update options if needed
  if( $option != $oldoption ) {
    twitterposts_update_option_uncached( 'config', $option );
  }
  
  // form
  echo 'Your Twitter Username'.' <br />'
  .'<input type="text" class="text" id="twitterposts-config-twitter-user" name="twitterposts-config-twitter-user" value="'.$option['twitter']['user'].'" />'
  .'<br />';
  echo '<br />';  
  echo 'Your Twitter Password'.' <br />'
  .'<input type="password" class="text" id="twitterposts-config-twitter-password" name="twitterposts-config-twitter-password" value="" />'
  .'<br />';
  echo '<br />';  
  echo '<input type="submit" value="Save">';
}

// twitter config login
function twitterposts_admin_config_twitter_timeframe() {

  // options
  $option = $oldoption = twitterposts_get_option_uncached( 'config' );
  
  // process
  if( isset($_POST['twitterposts-config-twitter-timeframe-count']) )
    $option['twitter']['timeframe']['count'] = (int)$_POST['twitterposts-config-twitter-timeframe-count'];
  
  // update options if needed
  if( $option != $oldoption ) {
    twitterposts_update_option_uncached( 'config', $option );
  }
  
  // form
  $hint_times = 'If you send too much updates to Twitter in a unknown time, new updates will be blocked for some time. '
               .'In case of being blocked, we still receive a success status from Twitter, and therefore we flag the post as done althaug it is not. '
               .'To prevent from getting in this not selfcorrecting state, it is strictly recommended to set a number of twitter updates that maximum will be send within a invented time period. '
               .'If there are more posts pending to twitter within this time period than allowed here, they will get done by the next trigger. '
               .'A tolerance of 15 seconds per Twitter Update is substracted from the timeframe to avoid idle triggers. Please take account of this, when setting the value. ';
  echo 'Maximum Twitter Updates per circa '.($option['twitter']['timeframe']['time']/60).' minutes '
  .'('.'<a href="#" onclick="javascript:alert(\''.$hint_times.'\');">?</a>'.')'
  .'<br />' 
  .'<input type="text" class="text" id="twitterposts-config-twitter-timeframe-count" name="twitterposts-config-twitter-timeframe-count" value="'.$option['twitter']['timeframe']['count'].'" />'
  .'<br />';
  echo '<br />';  
  echo '<input type="submit" value="Save">';
}

// query config
function twitterposts_admin_config_query() {

  // options
  $option = $oldoption = twitterposts_get_option_uncached( 'config' );
    
  // process
  if( isset($_POST['twitterposts-config-query-preview']) || isset($_POST['twitterposts-config-query-save']) ) {
    $query = strip_tags( stripslashes( $_POST['twitterposts-config-query'] ) ); 
    $query = str_replace( "\r", '', $query );
    $queryvars = explode( "\n", $query );
    $queryvars_trim = array();
    foreach( $queryvars as $queryvar ) {
      $queryvars_trim[] = trim( $queryvar );
    }
    $option['query'] = implode( '&', $queryvars_trim );
    if( isset($_POST['twitterposts-config-ts']) )
      $option['ts'] = $option['ts'] ? $option['ts'] : time();
    else
      $option['ts'] = 0;
  }
    
  // preview
  if( isset($_POST['twitterposts-config-query-preview']) ) {
  
    // get posts
    $posts = twitterposts_getposts( $option['query'], $option['ts'] );
    
    // output
    echo 'Preview'.' <br />';
    echo '<textarea rows="10" cols="55" wrap="off" readonly>';
    foreach( $posts as $post ) {
      // check if done
      $done = twitterposts_done_check( $post );
      // output
      echo $post->ID.' ('.($done?'DONE':'PENDING').'): '.get_the_title($post->ID)."\n";      
    }
    echo '</textarea>';
    echo '<br />';
    echo '<br />';    
  }
  
  // save
  if( isset($_POST['twitterposts-config-query-save']) ) {
    
    // update options if needed
    if( $option != $oldoption ) {
      twitterposts_update_option_uncached( 'config', $option );
    }
  
  }
  
  // form
  $hint_query = 'Here you can configure the WordPress QueryVars that are used to get the posts to twitter. \\n'
               .'Syntax: \\\'var=value\\\'. You can add multiple QueryVars separated by line breaks. '.'\\n'
               .'You can find a list of available QueryVars with supported values in the plugins readme file. '.'\\n'
               .'To save performance it is strictly recommended to limit your query to a number of latest posts that maximum will be new to twitter until the trigger is executed: '
               .'If you do not overwrite \\\'posts_per_page\\\' (the limit), your default number of posts per page is used. '.'\\n' 
               .'All matching posts will be resorted automatically, to twitter older posts before newer posts. ';  
  $hint_ts = 'This option prevents from sending old matching posts to twitter if plugin gets activated first time.\\n'
            .'To reset time: First deactivate all triggers, than uncheck this option, save, check option again, save and than reactivate your triggers. Or reset the whole state via the action button.';
  echo 'Overwrite QueryVars '
  .'('.'<a href="#" onclick="javascript:alert(\''.$hint_query.'\');javascript:window.open(\'http://codex.wordpress.org/Template_Tags/query_posts#Parameters\');">?</a>'.')'
  .'<br />'
  .'<textarea class="text" id="twitterposts-config-query" name="twitterposts-config-query" rows="3" cols="55">'.str_replace('&',"\n",$option['query']).'</textarea>'
  .'<br />'
  .'<input type="checkbox" id="twitterposts-config-ts" name="twitterposts-config-ts" '.($option['ts']?'checked="checked"':'').' /> Only posts newer than '.($option['ts']?gmdate('Y-m-d H:i:s',$option['ts']).' GMT':'now').' '
  .'('.'<a href="#" onclick="javascript:alert(\''.$hint_ts.'\');">?</a>'.')'
  .'<br />';
  echo '<br />';
  echo '<input type="submit" name="twitterposts-config-query-preview" id="twitterposts-config-query-preview" value="Preview">';
  echo '&nbsp;';  
  echo '<input type="submit" name="twitterposts-config-query-save" id="twitterposts-config-query-save" value="Save">';
}

// config string
function twitterposts_admin_config_string() {

  // options
  $option = $oldoption = twitterposts_get_option_uncached( 'config' );
          
  // save
  if( isset($_POST['twitterposts-config-string-save']) ) {
  
    // process
    if( isset($_POST['twitterposts-config-string']) )
      $option['string'] = strip_tags(stripslashes($_POST['twitterposts-config-string']));
      
    // update options if needed
    if( $option != $oldoption ) {
      twitterposts_update_option_uncached( 'config', $option );
    }

  }
  
  // form
  $hint_message = 'You may use the placeholders %url% (the posts tinyurl), %title% (the posts title) and %tags% (the tags if space is left) here. '.'\\n'
                 .'The title will be cutted automatically, if there is not enough space left in the message. Tags will only be added if there is space left in the message.';
  echo 'Twitter Message '
  .'('.'<a href="#" onclick="javascript:alert(\''.$hint_message.'\');">?</a>'.')'
  .'<br />'
  .'<textarea class="text" id="twitterposts-config-string" name="twitterposts-config-string" rows="3" cols="55">'.$option['string'].'</textarea>'
  .'<br />';
  echo '<br />';
  echo '<input type="submit" name="twitterposts-config-string-save" id="twitterposts-config-string-save" value="Save">';
}

// config trigger
function twitterposts_admin_config_trigger() {

  // options
  $option = $oldoption = twitterposts_get_option_uncached( 'config' );

  // process
  if( isset($_POST['twitterposts-config-trigger-save']) )
    $option['trigger']['save'] = (bool)isset($_POST['twitterposts-config-trigger-save']);
  if( isset($_POST['twitterposts-config-trigger-publish']) )
    $option['trigger']['publish'] = (bool)isset($_POST['twitterposts-config-trigger-publish']);
  if( isset($_POST['twitterposts-config-trigger-intervall']) )
    $option['trigger']['intervall'] = (int)$_POST['twitterposts-config-trigger-intervall'];
  
  // update options if needed
  if( $option != $oldoption ) {
    twitterposts_update_option_uncached( 'config', $option );
  }
  
  // form
  echo 'Trigger TwitterUpdate after saving a Post'.' <br />'
  .'<input type="checkbox" id="twitterposts-config-trigger-save" name="twitterposts-config-trigger-save" '.($option['trigger']['save']?'checked="checked"':'').' /> Active'
  .'<br />';
  echo '<br />';
  echo 'Trigger TwitterUpdate after publishing or saving a published Post'.' <br />'
  .'<input type="checkbox" id="twitterposts-config-trigger-publish" name="twitterposts-config-trigger-publish" '.($option['trigger']['publish']?'checked="checked"':'').' /> Active'
  .'<br />';
  echo '<br />';
  echo 'Trigger TwitterUpdate via Intervall'.' <br />';
    echo '<select id="twitterposts-config-trigger-intervall" name="twitterposts-config-trigger-intervall">';
      $intervall = array(
            0 => 'never',
        86400 => 'circa every 24 hours',
        43200 => 'circa every 12 hours',
        21600 => 'circa every 6 hours',
        10800 => 'circa every 3 hours',
         3600 => 'circa every 1 hour'
      );
      $userintervall = true;
      foreach( $intervall as $intervall_value=>$intervall_text ) {
        $selected = ($intervall_value==$option['trigger']['intervall']);
        if( $selected )
          $userintervall = false;
        echo '<option value="'.$intervall_value.'" '.($selected?'selected':'').'>'.$intervall_text.'</option>';
      }
      if( $userintervall ) {
        echo '<option value="'.$option['trigger']['intervall'].'" selected>every '.$option['trigger']['intervall'].' seconds</option>';
      }
    echo '</select>';
    echo '<br />';
  echo '<br />';    
  echo '<input type="submit" value="Save">';
}

// output trigger now button
function twitterposts_admin_actions() {

  // test twitter
  echo 'Test Twitter Connection'.' <br />';     
  if( isset($_POST['twitterposts-action-testtwitter']) ) {
    twitterposts_twitter( 'Twitter Posts Test: Hello World!' );
    $info = twitterposts_get_option_uncached( 'info' );
    echo '<textarea rows="4" cols="55" wrap="off" readonly>';
      echo 'Last Twitter Status: '.$info['twitter'];    
    echo '</textarea>';
    echo '<br />';   
    echo '<br />';       
  }    
  echo '<input type="submit" name="twitterposts-action-testtwitter" id="twitterposts-action-testtwitter" value="Send Twitter Test!">'
      .'<br />';   
       
  echo '<br />';  
            
  // trigger now
  echo 'Trigger TwitterUpdate'.' <br />';     
  if( isset($_POST['twitterposts-action-triggernow']) ) {
    $oldinfo = twitterposts_get_option_uncached( 'info' );
    $olddone = twitterposts_get_option_uncached( 'done' );
    twitterposts_trigger();
    $info = twitterposts_get_option_uncached( 'info' );
    $done = twitterposts_get_option_uncached( 'done' );
    echo '<textarea rows="4" cols="55" wrap="off" readonly>';
      echo 'Trigger Result: '.$info['trigger'];
      echo "\n";
      echo 'Done Twitter Updates: '.(count($done)-count($olddone));    
      echo "\n";
      echo 'Last Twitter Status: '.$info['twitter'];
    echo '</textarea>';
    echo '<br />';   
    echo '<br />';       
  }    
  echo '<input type="submit" name="twitterposts-action-triggernow" id="twitterposts-action-triggernow" value="Trigger now!">'
      .'<br />';   
       
  echo '<br />';  
            
  // reset
  echo 'Reset list of done posts'.' <br />';     
  if( isset($_POST['twitterposts-action-reset']) ) {
    echo '<textarea rows="2" cols="55" wrap="off" readonly>';
      twitterposts_reset_times();
      twitterposts_reset_ts();
      twitterposts_reset_done();
      twitterposts_reset_info();
      twitterposts_reset_trigger();
      echo 'ResetState executed';
    echo '</textarea>';
    echo '<br />';   
    echo '<br />';       
  } 
  echo '<input type="submit" name="twitterposts-action-reset" id="twitterposts-action-reset" value="Reset State" '
      .'onclick="if( confirm(\'Are you sure you want to reset the whole state?\') ){ return true;}return false;">'     
      .'<br />';     
}

// output info
function twitterposts_admin_info() {
  
  // get info
  $info = twitterposts_get_option_uncached( 'info' );
  
  // do output
  echo 'Done Twitter Updates: '
       .$info['done']
       .'<br />';
  echo 'Last Twitter Status: '
       .$info['twitter']
       .'<br />';
  echo 'Last Trigger Timestamp: '
       .$info['ts']
       .'<br />';
  echo 'Last Trigger Status: '
       .$info['trigger']
       .'<br />';
  echo 'Current Time: '
       .gmdate('Y-m-d H:i:s',time()).' GMT'
       .'<br />';
  echo '<br />';
  echo 'Official Plugin Website: '
       .'<a href="http://www.rene-ade.de/inhalte/wordpress-plugin-twitterposts.html" target="_blank">'
       .'http://www.rene-ade.de/inhalte/wordpress-plugin-twitterposts.html'
       .'</a> '
       .'(Informations, Updates, ...)'
       .'<br />';      
  echo 'Donations to the Author: '
       .'<a href="http://www.rene-ade.de/stichwoerter/spenden" target="_blank">'
       .'http://www.rene-ade.de/stichwoerter/spenden'
       .'</a> '
       .'(Amazon-Wishlist, Paypal, ...)'
       .'<br />';
  echo '<br />';
  echo 'Thank via Twitter: '
       .'<br />'
       .'<form action="http://twitter.com/home" target="_blank" method="get">'
       .'<textarea name="status" rows="3" cols="55">'
       .'@reneade: I\'m using your WordPress Plugin "Twitter Posts" ( http://www.rene-ade.de/inhalte/wordpress-plugin-twitterposts.html ) - Thank you!'
       .'</textarea>'
       .'<br />'
       .'<input type="submit" value="Send">'         
       .'</form> '
       .'<br />';         
}
  
// add page
function twitterposts_admin_add() {
  
  // add page
  add_submenu_page( 'options-general.php', 'Twitter Posts', 'Twitter Posts', 10/*admin only*/, 'twitterposts', 'twitterposts_admin' ); 
}

//-----------------------------------------------------------------------------

// get tiny url
function twitterposts_url( $url ) {
  
  // get tiny url
  $fp = @fopen( 'http://tinyurl.com/api-create.php?url='.$url, 'r' );
  if( $fp ) {
    $tinyurl = @fgets( $fp );
    if( $tinyurl && !empty($tinyurl) )
      $url = $tinyurl;
    @fclose( $fp );
  }
  
  // return
  return $url;
}

// get cut title
function twitterposts_title( $title, $length ) {

  // cut title if needed
  if( strlen($title)>$length ) {
    $cut = '...';
    $title = substr( $title, 0, $length-strlen($cut) );
    $title.= $cut;
  }

  // return
  return $title;
}

//-----------------------------------------------------------------------------

// update twitter status text
function twitterposts_twitter_send( $text, &$r_error_code, &$r_error_text ) {

  // config
  $config = twitterposts_get_option_uncached( 'config' );
	$host = 'twitter.com';
  $port = 80;
  $uri = '/statuses/update.xml';
	$param = 'status';
  $timeout = 10;
  $login = $config['twitter']['login'];
  
  // connect
  $r_error_code = null; $r_error_text = null;
	$fp = @fsockopen( $host, $port, $r_error_code, $r_error_text, $timeout );
  if( !$fp )
    return false;
    
  // send  
  $content = $param.'='.urlencode($text);
  @fwrite( $fp, "POST $uri HTTP/1.1\r\n" );  
  @fwrite( $fp, "Host: $host:$port\r\n" );
  @fwrite( $fp, "Authorization: Basic $login\r\n" );
  @fwrite( $fp, "User-Agent: wordpress-twitterposts\r\n" );
  @fwrite( $fp, "Content-type: application/x-www-form-urlencoded\r\n" );  
  @fwrite( $fp, "Content-length: ".strlen($content)."\r\n" );
  @fwrite( $fp, "Connection: close\r\n" );  
  @fwrite( $fp, "\r\n" );  
  @fwrite( $fp, $content );
  
  // receive
  $response = '';
  while( !@feof($fp) ) {
    $response.= @fread( $fp, 255 );
  }

  // close
  @fclose( $fp );
  
  // explode response
  $response = explode( "\r\n\r\n", trim($response) );
  $content = $response[1];  
  $header = explode( "\r\n", $response[0] );
  $headerarray = array();
  foreach( $header as $headerline ) {
    $seperator = ": ";
    $seperator_pos = strpos( $headerline, $seperator );
    $key = substr( $headerline, 0, $seperator_pos );
    $value = substr( $headerline, $seperator_pos+strlen($seperator) );
    $headerarray[ strtolower(trim($key)) ] = strtolower(trim($value));
  }
  $header = $headerarray;

  // return success
  if( $header['status']=='200 ok' )
    return true;
    
  // set error code
  $error = array();
  preg_match( '/(.*)<error>(.*)<\/error>(.*)/s', $content, $error );
  $r_error_code = $header['status'];
  $r_error_text = $error[2];
    
  // return error
  return false;
}

// update twitter status text and set info
function twitterposts_twitter( $text ) {

  // get config
  $config = twitterposts_get_option_uncached( 'config' );
  $tolerance = ( 15/*maxtimediffperpost=timeout+runtime*/*$config['twitter']['timeframe']['count'] ); 

  // check if allowed
  $times = $oldtimes = twitterposts_get_option_uncached( 'times' );
  $time_min = time();  
  $times_clean = array();
  foreach( $times as $time ) {
    if( $time>=((time()+$tolerance)-$config['twitter']['timeframe']['time']) ) { 
      $times_clean[] = $time; // keep
      if( $time<$time_min )
        $time_min = $time;
    }
  }   
  // if not allowed
  if( count($times_clean)>=$config['twitter']['timeframe']['count'] ) { 
    // update clean times
    if( $times_clean!=$oldtimes )
      twitterposts_update_option_uncached( 'times', $times_clean );
    // update info
    $time_next = ( $time_min + $config['twitter']['timeframe']['time'] );
    $info = $oldinfo = twitterposts_get_option_uncached( 'info' );
    $info['twitter'] = 'DEFERRED UNTIL '.gmdate('Y-m-d H:i:s',$time_next).' GMT';
    if( $info!=$oldinfo )
      twitterposts_update_option_uncached( 'info', $info );         
    return null;
  }
  $times_clean[] = time(); // add time
  if( $times_clean!=$oldtimes )
    twitterposts_update_option_uncached( 'times', $times_clean );

  // send
  $error_code = null; $error_text = null;
  $retval = twitterposts_twitter_send( $text, $error_code, $error_text );
  
  // update info
  $info = $oldinfo = twitterposts_get_option_uncached( 'info' );
  if( $retval ) {
    $info['twitter'] = 'SUCCESS';
  }
  else {
    $info['twitter'] = 'ERROR'.' '.$error_code;
    if( !empty($error_text) )
      $info['twitter'].= ', '.$error_text;
  }
  if( $info!=$oldinfo )
    twitterposts_update_option_uncached( 'info', $info );
    
  // return
  return $retval;
}

// twitter post
function twitterposts_twitterpost( &$post ) {

  // get format string
  $config = twitterposts_get_option_uncached( 'config' );
  $string = $config['string']; 

  // get tiny url
  $url = twitterposts_url( get_permalink($post->ID) );  

  // get left space for title 
  $free = $string;
  $free = str_replace( '%url%', $url, $free );
  $free = str_replace( '%tags%', '', $free );
  $free = str_replace( '%title%', '', $free );
  $free = 140 - strlen( $free );
  
  // get cut title
  $title = twitterposts_title( get_the_title($post->ID), $free ); 
  
  // replace vars
  $string = str_replace( '%url%', $url, $string );  
  $string = str_replace( '%title%', $title, $string );
  
  // get tags
  $tags = wp_get_post_tags( $post->ID );
  
  // add tags if there is space
  $first = true;
  foreach( $tags as $tag ) {
    $tag = $tag->name;
  
    // get free space
    $free = str_replace( '%tags%', '', $string );
    $free = 140 - strlen( $free );
  
    // get tag string
    $tag = '#'.$tag;
    if( !$first )
      $tag = ' '.$tag;

    // replace if enough space
    if( strlen($tag)<=$free ) {
      $string = str_replace( '%tags%', $tag.'%tags%', $string );
      $first = false;
    }
  }
  // remove placeholder
  $string = str_replace( '%tags%', '', $string );
  
  // twitter string  
  return twitterposts_twitter( $string );
}

//-----------------------------------------------------------------------------

// check for new posts
function twitterposts_trigger() {
 
  // set trigger time stamp
  $trigger = $oldtrigger = twitterposts_get_option_uncached( 'trigger' );      
  $trigger['ts'] = time();
  if( $trigger!=$oldtrigger )
    twitterposts_update_option_uncached( 'trigger', $trigger );
 
  // set info
  $info = $oldinfo = twitterposts_get_option_uncached( 'info' );      
  $info['ts'] = gmdate('Y-m-d H:i:s',time()).' GMT'; 
  $info['trigger'] = 'INCOMPLETE'; // until complete
  if( $info != $oldinfo )
    twitterposts_update_option_uncached( 'info', $info );
    
  // get config
  $config = twitterposts_get_option_uncached( 'config' );
  
  // get posts
  $posts = twitterposts_getposts( $config['query'], $config['ts'] );
  if( !is_array($posts) ) {
    // set info
    $info = $oldinfo = twitterposts_get_option_uncached( 'info' );      
    $info['trigger'] = 'FAILURE';
    if( $info != $oldinfo )
      twitterposts_update_option_uncached( 'info', $info );
    return false;
  }
  
  // update twitter
  foreach( $posts as $post ) {
  
    // check if undone
    if( !twitterposts_done_check($post) ) {
    
      // no interruption
      $ignore_user_abort = ignore_user_abort( true );
      $max_execution_time = ini_set( 'max_execution_time', 0 );       
      
      // the lock
      $lock = array( microtime(), rand() );
      $lock_ok = true;            
      if( $lock_ok ) {
        // check lock      
        if( twitterposts_get_option_uncached('lock')!='READY' )
          $lock_ok = false; // lock exists
      }
      if( $lock_ok ) {
        // set lock
        if( !twitterposts_update_option_uncached('lock',$lock) )
          $lock_ok = false;
      }
      if( $lock_ok ) {      
        // recheck lock
        sleep( 1 );
        if( $lock!=twitterposts_get_option_uncached('lock') )
          $lock_ok = false; // newer request got lock
      }
            
      // critical section
      $twitter = null;
      if( $lock_ok ) {    
        // recheck if undone
        if( !twitterposts_done_check($post) ) {        
          // twitter it
          $twitter = twitterposts_twitterpost( $post );
          if( $twitter ) {
            // set done
            twitterposts_done( $post );
          }
        }
      }

      if( $lock_ok ) {          
        // re check lock
        if( $lock!=twitterposts_get_option_uncached('lock') )
          $lock_ok = false; // newer request got lock
      }
      if( $lock_ok ) {          
        // remove lock
        if( !twitterposts_update_option_uncached('lock','READY') )
          $lock_ok = false;
      }
      
      // reset no interruption
      if( $max_execution_time!==false ) {
        ini_set( 'max_execution_time', $max_execution_time );
        set_time_limit( $max_execution_time );
      }
      ignore_user_abort( $ignore_user_abort );
      
      // on error return
      if( !$twitter ) {
        // set info
        $info = $oldinfo = twitterposts_get_option_uncached( 'info' );      
        if( $twitter===false )
          $info['trigger'] = 'FAILURE';
        if( $twitter===null )
          $info['trigger'] = 'INCOMPLETE';
        else
          $info['trigger'] = 'UNKNOWN';
        if( $info != $oldinfo )
          twitterposts_update_option_uncached( 'info', $info );
        // return
        return $twitter;
      }
    }
  }
  
  // set info
  $info = $oldinfo = twitterposts_get_option_uncached( 'info' );      
  $info['trigger'] = 'SUCCESS';
  if( $info != $oldinfo )
    twitterposts_update_option_uncached( 'info', $info );
          
  // return true
  return true;
}

//-----------------------------------------------------------------------------

// hourly event
function twitterposts_event_hourly() {

  // get config
  $config = twitterposts_get_option_uncached( 'config' );      
  
  // check if intervall is set
  if( $config['trigger']['intervall']<=0 )
    return;
  
  // get trigger
  $trigger = twitterposts_get_option_uncached( 'trigger' );      
  
  // check if we have to run trigger
  $tolerance = ( (60*60) / 2 );
  if( time()+$tolerance >= ($trigger['ts']+$config['trigger']['intervall']) ) 
    twitterposts_trigger();
}

// on save
function twitterposts_event_save( $id, $post ) {

  // get config
  $config = twitterposts_get_option_uncached( 'config' );      
  
  // check if trigger 
  $trigger = false;
  if( $config['trigger']['save'] ) {
    $trigger = true;
  }
  if( $config['trigger']['publish'] ) {
    if( $post->post_status == 'publish' )
      $trigger = true;    
  }

  // trigger
  if( $trigger )
    twitterposts_trigger();
}

//-----------------------------------------------------------------------------

// add admin actions
function twitterposts_actionlinks( $action_links, $plugin_file ) {
  if( $plugin_file!=twitterposts_plugin_basename() ) // only for this plugin
    return $action_links;

  // add links
  $action_links[] = '<a href="options-general.php?page=twitterposts">'.__('Configure').'</a>';
  $action_links[] = '<a href="http://www.rene-ade.de/stichwoerter/spenden" target="_blank">'.__('Donate').'</a>';

  // return with added links
  return $action_links;
}

//-----------------------------------------------------------------------------

// reset done
function twitterposts_reset_done() {

  // empty array 
  twitterposts_update_option_uncached( 'done', array(), true ); 
}

// reset info
function twitterposts_reset_info() {
  
  // reset info
  $info = array();
  $info['ts'] = 'NEVER';
  $info['trigger'] = 'NONEXISTENT';
  $info['done'] = 0;
  $info['twitter']  = 'NONEXISTENT';
  twitterposts_update_option_uncached( 'info', $info, true );
}

// reset ts
function twitterposts_reset_ts() {

  // reset ts
  $config = $oldconfig = twitterposts_get_option_uncached( 'config' );
  if( $config['ts'] )
    $config['ts'] = time();
  if( $config!=$oldconfig )
    twitterposts_update_option_uncached( 'config', $config );
}

// reset times
function twitterposts_reset_times() {

  // empty array 
  twitterposts_update_option_uncached( 'times', array(), true ); 
}

// reset trigger
function twitterposts_reset_trigger() {

  // value 0 for never
  $trigger = array();
  $trigger['ts'] = 0;
  twitterposts_update_option_uncached( 'trigger', $trigger, true );
}

// activation
function twitterposts_activation() {
  
  // init config
  if( !is_array(twitterposts_get_option_uncached('config')) ) {
    $config = array();
    $config['ts'] = time();
    $config['query'] = 'post_type=post&post_status=publish&orderby=date&order=DESC&posts_per_page=25';
    $config['string'] = 'Published a blog post: "%title%" %url%';
    $config['twitter']['user'] = null;
    $config['twitter']['login'] = null;
    $config['twitter']['timeframe']['time'] = 60*60;
    $config['twitter']['timeframe']['count'] = 10;    
    $config['trigger']['save'] = false;
    $config['trigger']['publish'] = true;
    $config['trigger']['intervall'] = false;    
    twitterposts_update_option_uncached( 'config', $config, true );
  }
  
  // init done
  if( !is_array(twitterposts_get_option_uncached('done')) ) {
    twitterposts_reset_done();
  }
  
  // init info
  if( !is_array(twitterposts_get_option_uncached('info')) ) {
    twitterposts_reset_info();
  }
  
  // init times
  if( !is_array(twitterposts_get_option_uncached('times')) ) {
    twitterposts_reset_times();
  }
  
  // init trigger
  if( !is_array(twitterposts_get_option_uncached('trigger')) ) {
    twitterposts_reset_trigger();
  }
  
  // activate lock
  twitterposts_update_option_uncached( 'lock', 'READY', true );
  sleep( 1 );
  twitterposts_update_option_uncached( 'lock', 'READY', true ); 
  
  // add schedule
  wp_schedule_event( time(), 'hourly', 'twitterposts_event_hourly' );
}

// deactivation
function twitterposts_deactivation() {
  
  // clear schedule
  wp_clear_scheduled_hook( 'twitterposts_event_hourly' );
  
  // deactivate lock
  twitterposts_update_option_uncached( 'lock', 'DEACTIVATED' );
  sleep( 1 );
  twitterposts_update_option_uncached( 'lock', 'DEACTIVATED' ); 
}

//-----------------------------------------------------------------------------

// actions
add_action( 'admin_menu', 'twitterposts_admin_add', 10/*order*/, 0/*params*/ );
add_action( 'activate_'.twitterposts_plugin_basename(), 'twitterposts_activation', 10/*order*/, 0/*params*/ );
add_action( 'deactivate_'.twitterposts_plugin_basename(), 'twitterposts_deactivation', 10/*order*/, 0/*params*/ );
add_action( 'twitterposts_event_hourly', 'twitterposts_event_hourly', 10/*order*/, 0/*params*/ );
add_action( 'save_post', 'twitterposts_event_save', 10/*order*/, 2/*params*/ );

// filters
add_filter( 'plugin_action_links', 'twitterposts_actionlinks', 10/*order*/, 2/*params*/ ); 

//-----------------------------------------------------------------------------

?>