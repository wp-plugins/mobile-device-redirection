<?php
/*
Plugin Name: Mobile Device Redirection
Plugin URI: http://github.com/funkjedi/mobile-device-redirection
Version: 0.1
Author: Tim Robertson
Description: This plugin integrates the mobile device detection from <a href="http://mobileesp.com">mobileesp.com</a> allowing you to redirect mobile devices to a specific url.
*/

require_once WP_PLUGIN_DIR . '/mobile-device-redirection/lib/mdetect.php';



function mobile_device_redirection_wp_loaded() {

  // check to see if we have already performed detection
  $is_mobile = array_key_exists('mobile_device_redirection', $_COOKIE) && $_COOKIE['mobile_device_redirection'] === 'mobile';

  // check the useragent
  if ($is_mobile === false) {
    $mdetect = new uagent_info();
    $is_mobile = $mdetect->DetectMobileQuick() || $mdetect->DetectIpad();
  }

  // save tracking cookie indicating the result of the detection
  setcookie('mobile_device_redirection', $is_mobile ? 'mobile' : 'desktop', 0, '/');

  // redirect if a mobile device is found
  if ($is_mobile && array_key_exists('json', $_GET) === false) {
    $options = get_option('mobile_device_redirection_options');
    header('Location: ' . $options['mobile_device_redirection_url']);
    exit;
  }

}

add_action('wp_loaded', 'mobile_device_redirection_wp_loaded');



// ADMIN SETTINGS PAGE

function mobile_device_redirection_admin_menu() {
  add_options_page('Mobile Device Redirection', 'Mobile Device Redirection', 'manage_options', __FILE__, 'mobile_device_redirection_page');
}

function mobile_device_redirection_page() {
  ?>
  <div class="wrap">
    <h2>Mobile Device Redirection</h2>
    <p>
       This plugin integrates the mobile device detection from <a href="http://mobileesp.com">mobileesp.com</a>
       allowing you to redirect mobile devices to a specific url.
    </p>
    <form method="post" action="options.php">
      <?php settings_fields('mobile_device_redirection_options'); ?>
      <?php do_settings_sections(__FILE__); ?>
      <div class="submit">
        <input type="submit" name="submit" class="button-primary" value="<?php esc_attr_e('Save Changes'); ?>"/>
      </div>
    </form>
  </div>
  <?php
}

function mobile_device_redirection_admin_init() {
  register_setting(
    'mobile_device_redirection_options',
    'mobile_device_redirection_options',
    'mobile_device_redirection_admin_init_options');

  add_settings_section(
    'mobile_device_redirection_settings',
    'Settings',
    'mobile_device_redirection_admin_init_settings',
    __FILE__);

  add_settings_field(
    'mobile_device_redirection_url',
    'Redirect mobile devices to the following url',
    'admin_init_mobile_device_redirection_url',
    __FILE__,
    'mobile_device_redirection_settings');
}

function mobile_device_redirection_admin_init_options($data) {
  if (mobile_device_redirection_validate_url($data['mobile_device_redirection_url'], false) === false) {
    add_settings_error('mobile_device_redirection_url', 'mobile_device_redirection_url', 'The specified url is invalid or incorrect.', 'error');
    return;
  }
  return $data;
}

function mobile_device_redirection_admin_init_settings() {
}

function admin_init_mobile_device_redirection_url() {
  $options = get_option('mobile_device_redirection_options');
  echo '<input id="mobile_device_redirection_url" name="mobile_device_redirection_options[mobile_device_redirection_url]" type="text" value="' . $options['mobile_device_redirection_url'] . '" style="width: 400px;" /> ';
}

add_action('admin_menu', 'mobile_device_redirection_admin_menu');
add_action('admin_init', 'mobile_device_redirection_admin_init');


// Modified version of valid_url from Drupal 6
// http://drupalcode.org/viewvc/drupal/drupal/includes/common.inc?view=markup
function mobile_device_redirection_validate_url($url, $absolute = true) {
  if ($absolute) {
    return (bool)preg_match("
      /^                                                      # Start at the beginning of the text
      (?:ftp|https?|feed):\/\/                                # Look for ftp, http, https or feed schemes
      (?:                                                     # Userinfo (optional) which is typically
        (?:(?:[\w\.\-\+!$&'\(\)*\+,;=]|%[0-9a-f]{2})+:)*      # a username or a username and password
        (?:[\w\.\-\+%!$&'\(\)*\+,;=]|%[0-9a-f]{2})+@          # combination
      )?
      (?:
        (?:[a-z0-9\-\.]|%[0-9a-f]{2})+                        # A domain name or a IPv4 address
        |(?:\[(?:[0-9a-f]{0,4}:)*(?:[0-9a-f]{0,4})\])         # or a well formed IPv6 address
      )
      (?::[0-9]+)?                                            # Server port number (optional)
        (?:[\/|\?]
        (?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})   # The path and query (optional)
      *)?
      $/xi", $url);
  }
  else {
    return (bool)preg_match("/^(?:[\w#!:\.\?\+=&@$'~*,;\/\(\)\[\]\-]|%[0-9a-f]{2})+$/i", $url);
  }
}
