<?php

/*
Plugin Name: Movie2Blog (CinemaRx)
Plugin URI: http://www.cinemarx.ro/movie2blog/
Description: Inserts short movie info (actors, directors, short review, runtime) in your posts. Uses http://www.cinemarx.ro/ for serving data. (example: [rxmovie-XXX], where XXX is a CinemaRx movie id.)
Version: 0.16.1
Author: szz
Author URI: http://www.cinemarx.ro/
License: GPL
*/


define('L_CINEMARX_EMBED_VERSION', '0.16.1');
define('L_CINEMARX_EMBED_OPTIONS_TITLE', 'CinemaRx Movie2Blog Options');
define('L_CINEMARX_EMBED_WIDTH', 'Width:');
define('L_CINEMARX_EMBED_HEIGHT', 'Height:');
define('L_CINEMARX_EMBED_THEME', 'Theme/Style:');
define('L_CINEMARX_EMBED_BACKGROUND', 'Background color:');
define('L_CINEMARX_EMBED_FONT_FAMILY', 'Default font family:');
define('L_CINEMARX_EMBED_FONT_SIZE', 'Default font size:');
define('L_CINEMARX_EMBED_DESCRIPTION_LENGTH', 'Short plot description length:');
define('L_CINEMARX_EMBED_LANGUAGE', 'Language:');
define('L_CINEMARX_EMBED_UPDATE_BTN', 'Update options &raquo;');
define('L_CINEMARX_EMBED_RESETCACHE_BTN', 'Clear cache &raquo;');
define('L_CINEMARX_EMBED_MESSAGE_OPTIONS_UPDATED', 'Options updated.');
define('L_CINEMARX_EMBED_MESSAGE_CACHE_CLEARED', 'Cache cleared.');
define('L_CINEMARX_EMBED_INFO', 'Pentru instructiuni de folosire urmariti linkul de mai jos (For further instructions see):<br /><a target="_blank" href="http://www.cinemarx.ro/movie2blog/">http://www.cinemarx.ro/movie2blog/</a>');
global $table_prefix; // needed for WP 2.5 and above
define('CINEMARX_MOVIE2BLOG_TABLE', $table_prefix . 'cinemarx_movie2blog');
define('CINEMARX_MOVIE2BLOG_CACHE_HREF', 'http://www.cinemarx.ro/filme/embed/?rx_co&');
define('CINEMARX_CACHE_EXPIRE_TIME', 2*24*60*60/* two days */);

function wp_cinemarx_embed_install() {
	global $table_prefix, $wpdb, $user_level;

	# usually: wp_movie_ratings
	$table_name = CINEMARX_MOVIE2BLOG_TABLE;

	# only special users can install plugins
	if ($user_level < 8) { return; }

	# create movie ratings table
	if ($wpdb->get_var("SHOW TABLES LIKE '$table_name'") != $table_name) {

		$sql = "CREATE TABLE " . $table_name . " (
			cache_id char(32) CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL default '',
      cache_content text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL,
			cache_time int(11) unsigned NOT NULL default '0',
			PRIMARY KEY (cache_id)
		);";

		require_once(ABSPATH . 'wp-admin/upgrade-functions.php');
		dbDelta($sql);
	}
  // enable (uncomment) the following block if you have encoding problems (question marks)
  // disable & enable the plugin from plugin admin area
  /*
  else
	{
    $sql = "ALTER TABLE " . $table_name . " CHANGE `cache_content` `cache_content` text CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL";
    $wpdb->query($sql);
  }
  */


  add_option('cinemarx_embed_rx_width', '500px', '', 'yes');
  add_option('cinemarx_embed_rx_height', '180px', '', 'yes');
  add_option('cinemarx_embed_rx_theme', 'simple', '', 'yes');
  add_option('cinemarx_embed_rx_background', 'FFFFFF', '', 'yes');
  add_option('cinemarx_embed_rx_fontfamily', 'Arial,Verdana,Tahoma,sans-serif', '', 'yes');
  add_option('cinemarx_embed_rx_fontsize', '13px', '', 'yes');
  add_option('cinemarx_embed_rx_description_length', '200', '', 'yes');
  add_option('cinemarx_embed_rx_language', 'romana', '', 'yes');

  activate_cinemarx_embed_options();

}
# Hook for plugin installation
add_action('activate_' . dirname(plugin_basename(__FILE__)) . '/' . basename(plugin_basename(__FILE__)), 'wp_cinemarx_embed_install');


// bloggers do not deactivate plugins before update, so:
function activate_cinemarx_embed_options()
{
  if ('16' != get_option('cinemarx_embed_options_activated'))
  {
    $default_options = array(
      'simple' => array(
        'width' => '500px',
        'height' => '180px',
        'background' => 'FFFFFF',
        'fontfamily' => 'Arial,Verdana,Tahoma,sans-serif',
        'fontsize' => '13px',
        'description' => '200',
      ),
      'advanced' => array(
        'width' => '500px',
        'height' => '180px',
        'background' => 'FFFFFF',
        'fontfamily' => 'Arial,Verdana,Tahoma,sans-serif',
        'fontsize' => '13px',
        'description' => '0',
      ),
      'advanced-description' => array(
        'width' => '500px',
        'height' => '180px',
        'background' => 'FFFFFF',
        'fontfamily' => 'Arial,Verdana,Tahoma,sans-serif',
        'fontsize' => '13px',
        'description' => '200',
      ),
      'vertical' => array(
        'width' => '180px',
        'height' => '500px',
        'background' => 'FFFFFF',
        'fontfamily' => 'Arial,Verdana,Tahoma,sans-serif',
        'fontsize' => '12px',
        'description' => '0',
      ),
      'mini' => array(
        'width' => '500px',
        'height' => '100px',
        'background' => 'FFFFFF',
        'fontfamily' => 'Arial,Verdana,Tahoma,sans-serif',
        'fontsize' => '11px',
        'description' => '0',
      ),
    );


    $cinemarx_embed_rx_theme = stripslashes(get_option('cinemarx_embed_rx_theme'));

    $cinemarx_embed_rx_width = stripslashes(get_option('cinemarx_embed_rx_width'));
    $cinemarx_embed_rx_height = stripslashes(get_option('cinemarx_embed_rx_height'));
    $cinemarx_embed_rx_background = stripslashes(get_option('cinemarx_embed_rx_background'));
    $cinemarx_embed_rx_fontfamily = stripslashes(get_option('cinemarx_embed_rx_fontfamily'));
    $cinemarx_embed_rx_fontsize = stripslashes(get_option('cinemarx_embed_rx_fontsize'));
    $cinemarx_embed_rx_description_length = stripslashes(get_option('cinemarx_embed_rx_description_length'));

    foreach($default_options as $theme => $options)
    {
      // selected theme?
      if ($theme == $cinemarx_embed_rx_theme)
      {
        update_option('cinemarx_embed_rx_width[' . $theme . ']', $cinemarx_embed_rx_width);
        update_option('cinemarx_embed_rx_height[' . $theme . ']', $cinemarx_embed_rx_height);
        update_option('cinemarx_embed_rx_background[' . $theme . ']', $cinemarx_embed_rx_background);
        update_option('cinemarx_embed_rx_fontfamily[' . $theme . ']', $cinemarx_embed_rx_fontfamily);
        update_option('cinemarx_embed_rx_fontsize[' . $theme . ']', $cinemarx_embed_rx_fontsize);
        update_option('cinemarx_embed_rx_description_length[' . $theme . ']', $cinemarx_embed_rx_description_length);
      }
      else
      {
        update_option('cinemarx_embed_rx_width[' . $theme . ']', $options['width']);
        update_option('cinemarx_embed_rx_height[' . $theme . ']', $options['height']);
        update_option('cinemarx_embed_rx_background[' . $theme . ']', $options['background']);
        update_option('cinemarx_embed_rx_fontfamily[' . $theme . ']', $options['fontfamily']);
        update_option('cinemarx_embed_rx_fontsize[' . $theme . ']', $options['fontsize']);
        update_option('cinemarx_embed_rx_description_length[' . $theme . ']', $options['description']);
      }
    }
    update_option('cinemarx_embed_options_activated', '16');
  }
}

#wp_head

function wp_cinemarx_embed_wp_head()
{
  if (isset($_REQUEST['rxreset']))
  {
    // do it only once
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE " . CINEMARX_MOVIE2BLOG_TABLE);
  }

  print '<link href="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-movie2blog/style.css?' . L_CINEMARX_EMBED_VERSION . '" rel="stylesheet" type="text/css" media="screen" /><!-- wp-movie2blog: v' . L_CINEMARX_EMBED_VERSION . ' -->';
}


# main
function wp_cinemarx_embed($string) {


  global $wpdb;

  activate_cinemarx_embed_options();

  // theme
  $cinemarx_embed_rx_theme = stripslashes(get_option('cinemarx_embed_rx_theme'));
  // theme dependent settings
  $cinemarx_embed_rx_width = stripslashes(get_option('cinemarx_embed_rx_width[' . $cinemarx_embed_rx_theme . ']'));
  $cinemarx_embed_rx_height = stripslashes(get_option('cinemarx_embed_rx_height[' . $cinemarx_embed_rx_theme . ']'));
  $cinemarx_embed_rx_background = stripslashes(get_option('cinemarx_embed_rx_background[' . $cinemarx_embed_rx_theme . ']'));
  $cinemarx_embed_rx_fontfamily = stripslashes(get_option('cinemarx_embed_rx_fontfamily[' . $cinemarx_embed_rx_theme . ']'));
  $cinemarx_embed_rx_fontsize = stripslashes(get_option('cinemarx_embed_rx_fontsize[' . $cinemarx_embed_rx_theme . ']'));
  $cinemarx_embed_rx_description_length = stripslashes(get_option('cinemarx_embed_rx_description_length[' . $cinemarx_embed_rx_theme . ']'));
  // theme independent settings
  $cinemarx_embed_rx_language = stripslashes(get_option('cinemarx_embed_rx_language'));

  $search_for_patterns = array(
    '/\[rxmovie-(.*?)(-(.*?))?\]/i',
  );

  while (preg_match($search_for_patterns[0], $string, $matched))
  {
    $cache_data = NULL;
    $rx_data = NULL;

    $rx_url = 'rx_id=' . $matched[1] . '&rx_theme=' . $cinemarx_embed_rx_theme . '&rx_width=' . $cinemarx_embed_rx_width . '&rx_height=' . $cinemarx_embed_rx_height . '&rx_background=' . $cinemarx_embed_rx_background . '&rx_fontfamily=' . $cinemarx_embed_rx_fontfamily . '&rx_fontsize=' . $cinemarx_embed_rx_fontsize . '&rx_description_length=' . $cinemarx_embed_rx_description_length . '&rx_language=' . $cinemarx_embed_rx_language;

    $sql = "SELECT cache_content, cache_time FROM " . CINEMARX_MOVIE2BLOG_TABLE . " WHERE cache_id='" . md5($rx_url) . "' LIMIT 1";

    if (!($cache_data = $wpdb->get_row($sql, ARRAY_A)) || (time() > $cache_data['cache_time'] + CINEMARX_CACHE_EXPIRE_TIME))
    {
      $blog_url = get_option("siteurl");
      if ($rx_data = @file_get_contents(CINEMARX_MOVIE2BLOG_CACHE_HREF . $rx_url . '&rx_referrer=' . $blog_url))
      {
        if ($cache_data)
        {
          $sql = "UPDATE " . CINEMARX_MOVIE2BLOG_TABLE . " SET `cache_content`='" . $wpdb->escape($rx_data) . "', `cache_time`=" . time() . " WHERE `cache_id`='" . $wpdb->escape(md5($rx_url)) . "' LIMIT 1";
          $wpdb->query($sql);
        }
        else
        {
          $sql = "INSERT INTO " . CINEMARX_MOVIE2BLOG_TABLE . " (`cache_id`,`cache_content`,`cache_time`) VALUES ('" . $wpdb->escape(md5($rx_url)) . "', '" . $wpdb->escape($rx_data) . "', " . time() . ")";
          $wpdb->query($sql);
        }
        $cache_data = $rx_data;
      }
    }
    else
    {
      $cache_data = $cache_data['cache_content'];
    }

    if (NULL == $cache_data)
    {
      $replace_with_patterns = array(
        '<script type="text/javascript" src="http://www.cinemarx.ro/filme/embed/rxm.js"></script><script type="text/javascript">
          // <!--
          rx_id=' . $matched[1] . ';
    			rx_theme="' . $cinemarx_embed_rx_theme . '";
    			rx_width="' . $cinemarx_embed_rx_width . '";
    			rx_height="' . $cinemarx_embed_rx_height . '";
    			rx_background="' . $cinemarx_embed_rx_background . '";
    			rx_fontfamily="' . $cinemarx_embed_rx_fontfamily . '";
    			rx_fontsize="' . $cinemarx_embed_rx_fontsize . '";
    			rx_description_length="' . $cinemarx_embed_rx_description_length . '";
    			rx_language="' . $cinemarx_embed_rx_language . '";
    			rx_stylesheet_uri="' . get_bloginfo('wpurl') . '/wp-content/plugins/wp-movie2blog/style.css?' . L_CINEMARX_EMBED_VERSION . '";
    			rx_blog_stylesheet_uri="' . get_stylesheet_uri() . '";
    			rx_display();
    			// -->
    		</script>',
      );
    }
    else
    {
      $replace_with_patterns = array(
        $cache_data,
      );
    }

    foreach($search_for_patterns as $index => $pattern) {
        $string = str_replace($matched[0], $replace_with_patterns[$index], $string);
    }

  }


  return $string;
}
function wp_cinemarx_embed_rss($string) {

  $search_for_patterns = array(
    '/\[rxmovie-(.*?)(-(.*?))?\]/i',
  );
  $replace_with_patterns = array(
    '',
  );

  foreach($search_for_patterns as $index => $pattern) {
      $string = preg_replace($pattern, $replace_with_patterns[$index], $string);
  }

  return $string;
}

add_filter('the_content', 'wp_cinemarx_embed', 9/*smaller than default priority 10 - in case of combination with a lightbox plugin*/);
add_filter('the_content_rss', 'wp_cinemarx_embed_rss', 8/*smaller than default priority 10 - in case of combination with a lightbox plugin*/);




# ADMIN

function wp_cinemarx_embed_options_page()
{
  activate_cinemarx_embed_options();

  if (isset($_POST['rx_submit']))
  {
    // theme modified?
    $cinemarx_embed_rx_theme = get_option('cinemarx_embed_rx_theme');
    if ($cinemarx_embed_rx_theme != $_POST['rx_theme'])
    {
      update_option('cinemarx_embed_rx_theme', $_POST['rx_theme']);
    }
    else
    {
      // theme dependent settings
      update_option('cinemarx_embed_rx_width[' . $cinemarx_embed_rx_theme . ']', $_POST['rx_width']);
      update_option('cinemarx_embed_rx_height[' . $cinemarx_embed_rx_theme . ']', $_POST['rx_height']);
      update_option('cinemarx_embed_rx_background[' . $cinemarx_embed_rx_theme . ']', $_POST['rx_background']);
      update_option('cinemarx_embed_rx_fontfamily[' . $cinemarx_embed_rx_theme . ']', $_POST['rx_fontfamily']);
      update_option('cinemarx_embed_rx_fontsize[' . $cinemarx_embed_rx_theme . ']', $_POST['rx_fontsize']);
      update_option('cinemarx_embed_rx_description_length[' . $cinemarx_embed_rx_theme . ']', $_POST['rx_description_length']);
    }
    // theme independent settings
    update_option('cinemarx_embed_rx_language', $_POST['rx_language']);

    print '<div id="message" class="updated fade"><p>' . L_CINEMARX_EMBED_MESSAGE_OPTIONS_UPDATED . '</p></div>';
  }
  
  if (isset($_POST['rx_resetcache']))
  {
    global $wpdb;
    $wpdb->query("TRUNCATE TABLE " . CINEMARX_MOVIE2BLOG_TABLE);
    print '<div id="message" class="updated fade"><p>' . L_CINEMARX_EMBED_MESSAGE_CACHE_CLEARED . '</p></div>';
  }

  // settings
  $cinemarx_embed_rx_theme = get_option('cinemarx_embed_rx_theme');
  // theme dependent settings
  $cinemarx_embed_rx_width = get_option('cinemarx_embed_rx_width[' . $cinemarx_embed_rx_theme . ']');
  $cinemarx_embed_rx_height = get_option('cinemarx_embed_rx_height[' . $cinemarx_embed_rx_theme . ']');
  $cinemarx_embed_rx_background = get_option('cinemarx_embed_rx_background[' . $cinemarx_embed_rx_theme . ']');
  $cinemarx_embed_rx_fontfamily = get_option('cinemarx_embed_rx_fontfamily[' . $cinemarx_embed_rx_theme . ']');
  $cinemarx_embed_rx_fontsize = get_option('cinemarx_embed_rx_fontsize[' . $cinemarx_embed_rx_theme . ']');
  $cinemarx_embed_rx_description_length = get_option('cinemarx_embed_rx_description_length[' . $cinemarx_embed_rx_theme . ']');
  // theme independent settings
  $cinemarx_embed_rx_language = get_option('cinemarx_embed_rx_language');

?>
<div class="wrap">
<h2><?php print L_CINEMARX_EMBED_OPTIONS_TITLE; ?></h2>

<form method="post" enctype="multipart/form-data" id="cinemarx_embed_options_form">

<table class="optiontable">

<tr valign="top">
  <th scope="row"><label><?php print L_CINEMARX_EMBED_THEME; ?></label></th>
  <td><select name="rx_theme" id="rx_theme" class="text" onchange="document.getElementById('rx_submit').click();"><?php
    $rx_themes = array(
      'simple' => 'simple',
      'advanced-description' => 'advanced (with plot description)',
      'advanced' => 'advanced (without plot description)',
      'vertical' => 'vertical',
      'mini' => 'mini',
    );
    foreach ($rx_themes as $theme_index => $theme)
    {
      print '<option value="' . $theme_index . '"' . ((0 == strcasecmp($cinemarx_embed_rx_theme, $theme_index))? ' selected="selected"' : '') . '>' . $theme . '</option>';
    }
  ?></select></td>
</tr>

<tr valign="top">
  <th scope="row"><label><?php print L_CINEMARX_EMBED_WIDTH; ?></label></th>
  <td><input type="text" name="rx_width" id="rx_width" class="text" size="6" value="<?php print stripslashes($cinemarx_embed_rx_width); ?>" /></td>
</tr>

<tr valign="top">
  <th scope="row"><label><?php print L_CINEMARX_EMBED_HEIGHT; ?></label></th>
  <td><input type="text" name="rx_height" id="rx_height" class="text" size="6" value="<?php print stripslashes($cinemarx_embed_rx_height); ?>" /></td>
</tr>

<tr valign="top">
  <th scope="row"><label><?php print L_CINEMARX_EMBED_LANGUAGE; ?></label></th>
  <td><select name="rx_language" id="rx_language" class="text"><?php
    $rx_languages = array('romana', 'english', 'magyar');
    foreach ($rx_languages as $language)
    {
      print '<option value="' . $language . '"' . ((0 == strcasecmp($cinemarx_embed_rx_language, $language))? ' selected="selected"' : '') . '>' . $language . '</option>';
    }
  ?></select></td>
</tr>

<tr valign="top">
  <th scope="row"><label><?php print L_CINEMARX_EMBED_BACKGROUND; ?></label></th>
  <td><input type="text" name="rx_background" id="rx_background" class="text" size="30" value="<?php print stripslashes($cinemarx_embed_rx_background); ?>" /></td>
</tr>

<tr valign="top">
  <th scope="row"><label><?php print L_CINEMARX_EMBED_FONT_FAMILY; ?></label></th>
  <td><input type="text" name="rx_fontfamily" id="rx_fontfamily" class="text" size="30" value="<?php print stripslashes($cinemarx_embed_rx_fontfamily); ?>" /></td>
</tr>

<tr valign="top">
  <th scope="row"><label><?php print L_CINEMARX_EMBED_FONT_SIZE; ?></label></th>
  <td><input type="text" name="rx_fontsize" id="rx_fontsize" class="text" size="30" value="<?php print stripslashes($cinemarx_embed_rx_fontsize); ?>" /></td>
</tr>

<tr valign="top">
  <th scope="row"><label><?php print L_CINEMARX_EMBED_DESCRIPTION_LENGTH; ?></label></th>
  <td><input type="text" name="rx_description_length" id="rx_description_length" class="text" size="6" value="<?php print stripslashes($cinemarx_embed_rx_description_length); ?>" /><br />(Note: plot descriptions are available only in romanian. Enter <strong>0</strong> to disable description.)</td>
</tr>

</table>

<p class="submit"><input type="submit" id="rx_submit" name="rx_submit" value="<?php print L_CINEMARX_EMBED_UPDATE_BTN; ?>" /></p>
<p class="submit"><input type="submit" id="rx_resetcache" name="rx_resetcache" value="<?php print L_CINEMARX_EMBED_RESETCACHE_BTN; ?>" /></p>

<p><?php print L_CINEMARX_EMBED_INFO; ?></p>

</form>

</div>

<?php
}

# Add 'Movie2Blog' page to Wordpress' Options menu
function wp_cinemarx_embed_add_options_page() {
    if (function_exists('add_options_page')) {
		  add_options_page('CinemaRx_Options', 'Movie2Blog', 8, basename(__FILE__), 'wp_cinemarx_embed_options_page');
    }
}

# Add actions for admin panel
add_action('admin_menu', 'wp_cinemarx_embed_add_options_page');

# Add to header
add_action('wp_head', 'wp_cinemarx_embed_wp_head');


?>
