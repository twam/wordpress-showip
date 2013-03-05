<?php
/*
Plugin Name: ShowIP
Plugin URI: https://github.com/twam/wordpress-showip
Version: 1.0
Author: <a href="http://www.twam.info/">Tobias Müller</a>
Description: A simple plugin to show client ip and location.
*/

if (!class_exists("ShowIPPlugin")) {
  class ShowIPPlugin {
    public $geoip_avail;

    function __construct() {
      // Check if GeoIP works
       $this->geoip_avail = (function_exists(geoip_db_avail) && geoip_db_avail(GEOIP_COUNTRY_EDITION));
    }

    function ShowIPPlugin() {
    }

    function InitPlugin() {
      add_action('wp_enqueue_scripts', 'ShowIPPlugin::showip_add_stylesheet');

      // load l10n information
      $plugin_dir = basename(dirname(__FILE__));
      load_plugin_textdomain('ShowIP', 'wp-content/plugins/' . $plugin_dir, $plugin_dir );
    }

    function showip_add_stylesheet() {
        // Respects SSL, Style.css is relative to the current file
        wp_register_style('showip-style', plugins_url('style.css', __FILE__));
        wp_enqueue_style('showip-style');
    }

    function ShowWidget($args) {
      // $args is an array of strings that help widgets to conform to
      // the active theme: before_widget, before_title, after_widget,
      // and after_title are the array keys. Default tags: li and h2.
      extract($args);

      $options = get_option('ShowIP');

      echo $before_widget . $before_title . $options['title'] . $after_title;
   
      echo '<ul id="showip">';

    
      // Get correct flag if we have GeoIP support
      if ($this->geoip_avail && $options['flag']) {
        $flag = '';
        // We need a geoip database
        if ((geoip_db_avail(GEOIP_COUNTRY_EDITION)) && (@geoip_country_code_by_name($_SERVER['REMOTE_ADDR'])!='')) {
          // Check if flag img exists
          if (file_exists('wp-content/plugins/showip/images/'.strtolower(@geoip_country_code_by_name($_SERVER['REMOTE_ADDR'])).'.png')) {
            $flag = '<img src="'.WP_PLUGIN_URL.'/showip/images/'.strtolower(@geoip_country_code_by_name($_SERVER['REMOTE_ADDR'])).'.png" alt="'.geoip_country_name_by_name($_SERVER['REMOTE_ADDR']).'" class="showipflag" />';
          }
        }
      }

      // Show IP if enabled
      if ($options['ip']) {
        echo '<li><span class="showiplabel">'.__('IP:','ShowIP').'</span> '.$_SERVER['REMOTE_ADDR'];
        // If location is disabled, but flat is enabled, then display flag right here
        if (($options['flag']) && (isset($flag)) && (!$options['location'])) {
           echo ' '.$flag;
        }
        echo '</li>';
      }

      // check if geoip database is available & if user wants location information
      if ($this->geoip_avail && geoip_db_avail(GEOIP_CITY_EDITION_REV0) && $options['location']) {
        $record = geoip_record_by_name($_SERVER['REMOTE_ADDR']);
        echo '<li><span class="showiplabel">'.__('Location:','ShowIP').'</span> ';
        if ($record['city']=='') {
          echo '-';
        } else {
          echo utf8_encode($record['city']);
        }
        if (($options['flag']) && (isset($flag))) {
          echo ' '.$flag;
        }
        echo '</li>';
      }

      echo '</ul>';
      echo $after_widget;
    }

    function ControlWidget() {
      // Get our options and see if we're handling a form submission.
      $options = get_option('ShowIP');

      // Set the default options for the widget here
      if ( !is_array($options) )
        $options = array('title'=>'ShowIP', 'ip'=>'1', '/'=>'0', 'location'=>'0');

        if ( $_POST['showip-submit'] ) {
          // Remember to sanitize and format use input appropriately.
          $options['title'] = strip_tags(stripslashes($_POST['showip-title']));
          $options['ip'] = ($_POST['showip-ip']=='on' ? 1: 0 );
          $options['flag']= ($_POST['showip-flag']=='on' ? 1: 0 );
          $options['location'] = ($_POST['showip-location']=='on' ? 1: 0 );

          update_option('ShowIP', $options);
        }

        // Be sure you format your options to be valid HTML attributes.
        $title = htmlspecialchars($options['title'], ENT_QUOTES);

        // Here is our little form segment. Notice that we don't need a
        // complete form. This will be embedded into the existing form.
        echo '<p><label for="showip-title">' . __('Title:','ShowIP') . ' <input class="widefat" id="showip-title" name="showip-title" type="text" value="'.$title.'" /></label></p>';
        echo '<p><label for="showip-ip"><input type="checkbox" class="checkbox" id="showip-ip" name="showip-ip" '.($options['ip'] ? 'checked="checked" ' : '').'/> '.__('Show IP address','ShowIP').'</label></p>';
        if ($this->geoip_avail) {
          echo '<p><label for="showip-flag"><input type="checkbox" class="checkbox" id="showip-flag" name="showip-flag" '.($options['flag'] ? 'checked="checked" ' : '').'/> '.__('Show flag','ShowIP').'</label></p>';
          echo '<p><label for="showip-location"><input type="checkbox" class="checkbox" id="showip-location" name="showip-location" '.($options['location'] ? 'checked="checked" ' : '').'/> '.__('Show location','ShowIP').'</label></p>';
        }
        echo '<input type="hidden" id="showip-submit" name="showip-submit" value="1" />';
        if (!$this->geoip_avail) {
          echo '<p>'.__('Note: GeoIP is not supported and GeoIP options are therefor hidden.','ShowIP').'</p>';
        }
    }

    function RegisterWidget() {
      // exit if there are no dynamic sidebar widgets possible
      if ( !function_exists('register_sidebar_widget') )
        return;

        register_sidebar_widget(array('ShowIP','widgets'),array(&$this, 'ShowWidget'));
        register_widget_control(array('ShowIP','widgets'),array(&$this, 'ControlWidget'));
    }

  }
} // end class ShowIPPlugin

if (class_exists("ShowIPPlugin")) {
  $showIPPlugin = new ShowIPPlugin();
}

if (isset($showIPPlugin)) {
  // Actions
  add_action('init', array(&$showIPPlugin, 'InitPlugin'));
  add_action('widgets_init',array(&$showIPPlugin, 'RegisterWidget'));

  // Filters
}

?>
