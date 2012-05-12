<?php
/*
Plugin Name: WP-Sugar
Plugin URI: http://www.projectassistant.org
Description: Wordpress SugarCRM Integration
Version: 1.0
Author: Jubal Mabaquiao
Author URI: http://jubalm.github.com
*/
define('sugarEntry', true);


require_once( plugin_dir_path( __FILE__ ) . '/nusoap/lib/nusoap.php' );
require_once( plugin_dir_path( __FILE__ ) .'sugarcrmwebservice.class.php');

add_action('admin_init', 'wp_sugar_register_deps');
function wp_sugar_register_deps(){
  // register dependencies
  wp_register_script( 'wp_sugar', plugins_url('/wp_sugar.js', __FILE__) );
}

// add the admin options page
add_action('admin_menu', 'wp_sugar_admin_add_page');
function wp_sugar_admin_add_page() {
  $page = add_options_page('WP Sugar Settings', 'WP Sugar', 'manage_options', 'wp_sugar', 'wp_sugar_options_page');
  // load dependencies only on admin page
  add_action('admin_print_styles-' . $page, 'wp_sugar_load_deps');
}

// dependencies
function wp_sugar_load_deps(){
  wp_enqueue_script( 'wp_sugar' );
}

// display the admin options page
function wp_sugar_options_page() { 
  
  $options = get_option('wp_sugar_options');
  
?>
<div>
  <h2>WP Sugar Settings</h2>
    <form action="options.php" method="post" id="wp_sugar_general_settings">
      <?php settings_fields('wp_sugar_options'); ?>
    
      <?php do_settings_sections('wp_sugar'); ?>
      <p class="submit">
        <input name="submit" type="submit" class="button-primary" value="<?php esc_attr_e('Save »'); ?>" />
        <input id="test_connect" type="button" value="<?php esc_attr_e('Test Connection »'); ?>" />
        <span class="test-connect-result"></span>
      </p>
  
    </form>
<hr>
  <h3>Gravity Forms Integration</h3>
  <form action="options.php" method="post" id="wp_sugar_form_settings">
  <p>Fill out the fields with it's the corresponding field ID from SugarCRM.</p>
  <p>
  Select Form: <select class="wp_sugar_select_form">
  <?php
  // from gravityforms widget.php
  $forms = RGFormsModel::get_forms(1, "title");
    foreach ($forms as $form) {
        $selected = '';
        if ($form->id == 9/* $options['wp_sugar_form'] */)
            $selected = ' selected="selected"';
        echo '<option value="'.$form->id.'" '.$selected.'>'.$form->title.'</option>';
    }
  ?>
  </select>
  </p>
  <div id="wp_sugar_ajax_result"></div>
  </form>
</div>

<?php 
}

// add the admin settings and such
add_action('admin_init', 'wp_sugar_admin_init');
function wp_sugar_admin_init(){
  register_setting(
    'wp_sugar_options', // same as settings_fields()
    'wp_sugar_options', // option name
    'wp_sugar_options_validate' // callback
    );
  add_settings_section(
    'wp_sugar_main',          // section's unique ID
    'Main Settings',        // page title
    'wp_sugar_section_html',  // callback to display the section
    'wp_sugar'                // page name, same as do_settings_section
    );
  add_settings_field(
    'wp_sugar_url',             // field ID
    'Sugar URL',                // field title
    'wp_sugar_setting_string',  // callback, display the input
    'wp_sugar',                  // page name (do_settings_section)
    'wp_sugar_main',             // section id it is attached to (add_settings_section)
    array( 'field_name' => 'wp_sugar_url' )
    );
  add_settings_field(
    'wp_sugar_user',             // field ID
    'Sugar Username',                // field title
    'wp_sugar_setting_string',  // callback, display the input
    'wp_sugar',                  // page name (do_settings_section)
    'wp_sugar_main',             // section id it is attached to (add_settings_section)
    array( 'field_name' => 'wp_sugar_user' )
    );
  add_settings_field(
    'wp_sugar_pass',             // field ID
    'Sugar Password',                // field title
    'wp_sugar_setting_string',  // callback, display the input
    'wp_sugar',                  // page name (do_settings_section)
    'wp_sugar_main',             // section id it is attached to (add_settings_section)
    array( 'field_name' => 'wp_sugar_pass' )
    );
}

function wp_sugar_section_html() {
  echo '<p>Wordpress Sugar Integration.</p>';
}

function wp_sugar_setting_string($args) {
  $options = get_option('wp_sugar_options');
  $field = $args['field_name'];
  echo "<input id='wp_sugar_url' name='wp_sugar_options[{$field}]' size='40' type='text' value='{$options[$field]}' />";
}

// Validate 
function wp_sugar_options_validate($input) {
  return $input;
}

// AJAX Callbacks

add_action('wp_ajax_wp_sugar', 'wp_sugar_callback');
function wp_sugar_callback() {
	global $wpdb; // this is how you get access to the database

  $sugar = new SugarCRMWebServices();
  $sugar->login();
  $result = $sugar->login();
  echo ($result) ? $result : 'Unknown Error. Make sure the Sugar URL exists.';

	die(); // this is required to return a proper result
}

add_action('wp_ajax_wp_sugar_load_gforms', 'wp_sugar_load_gforms_callback');
function wp_sugar_load_gforms_callback() {
	global $wpdb; // this is how you get access to the database
	$options = get_option('wp_sugar_forms');
  $selected = $_POST['formid'];
  $forms = RGFormsModel::get_form_meta($selected);
  $html = '<table class="form-table"><tbody>';
 
  // set exclude types $field[type]
  $excludes = array('html', 'captcha', 'fileupload', 'message', 'page');
  
  //$paging = array('page', 'section');
  
  foreach ($forms['fields'] as $field) {
  
    if( $field['type'] == 'section' ) {
      $html .= '<tr valign="top"><th scope="row"><h3>'. htmlspecialchars($field['label']) .'</h3></th><td></td><tr>';
      continue;
      }
    
    if( $field['type'] == 'page' ) {
      $html .= '<tr valign="top"><th scope="row"><p>------------</p></th><tr>';
      continue;
    }

    if( $field['inputs'] ) {
      foreach ( $field['inputs'] as $input) {
        $html .= '<tr valign="top"><th scope="row">'. htmlspecialchars($input['label']) .'</th><td><input type="text" name="'. htmlspecialchars($input['id']) . '" value="'.$options[$selected]["entries"][str_replace('.', '_', $input['id'])].'"></td></tr>';
      }
      continue;
    }
    
    if( $field['type'] == 'email' ) {
      $value = $options[$selected]["entries"][str_replace('.', '_', $field['id'])];
      $checked = $options[$selected]["entries"][str_replace('.', '_', $field['id'] . '.1')];
      $checked = ($checked == 'validate') ? 'checked="checked"' : '';
      $html .= '<tr valign="top"><th scope="row">'
        . htmlspecialchars($field['label'])
        . '</th><td><input type="text" name="'
        . htmlspecialchars($field['id']) 
        . '" value="'. $value .'"><input type="checkbox" name="'
        . htmlspecialchars($field['id'])
        . '.1" value="validate"'
        . $checked
        .'  /></td></tr>';
      continue;
    }
    
    
    // excludes
    if( in_array( $field['type'], $excludes ) ) continue;
    
    // todo: populate values
    $html .= '<tr valign="top"><th scope="row">'. htmlspecialchars($field['label']) .'</th><td><input type="text" name="'. htmlspecialchars($field['id']) . '" value="'.$options[$selected]["entries"][str_replace('.', '_', $field['id'])].'"></td></tr>';
        
  }
  $html .= '</tbody></table>';
  $html .= '<input type="hidden" name="form_id" value="'. $_POST['formid'] . '" />';
  echo $html;
  echo '<p class="submit"><input id="submit_form_data" name="submit_form_data" type="submit" value="Update Fields »"></p>';

	die(); // this is required to return a proper result
}

add_action('wp_ajax_wp_sugar_submit_form', 'wp_sugar_submit_form_callback');
function wp_sugar_submit_form_callback() {

/* this is the data structure 

    '0'    => array(
      'entries'    => array(
        '1.1'  => 'first_name',
        '1.3'  => 'last_name',
        '2'    => array ( 'email1', true ),
        
        ),
      'validate'  => true     
    )
  
*/

//  var_dump($_POST);
	global $wpdb;
	$data = get_option('wp_sugar_forms');
  $form_entries = array();
  
  foreach ( $_POST as $key => $value) {
    if($key == 'action' || $key == 'form_id' /*|| $value == ''*/) continue;
    $form_entries[$key] = $value;
  }
  
  $data[$_POST["form_id"]]["entries"] = $form_entries;
  
  // update wordpress option
  $update = update_option('wp_sugar_forms', $data);
  
  // return update status
  echo (!$update) ? '1' : '0';

	die(); // this is required to return a proper result
}

add_filter("gform_field_validation", "validate_lead_email", 10, 4); // live form
function validate_lead_email($result, $value, $form, $field)
{
  
  $forms = get_option('wp_sugar_forms');
  
  if( $field["type"] == 'email' ) {
    // get value of checkbox
    $checked = $forms[$form["id"]]["entries"][$field["id"].'_1'];
    
    if( $checked == 'validate' ) {
      // login to create session
      $sugar = new SugarCRMWebServices();
      $sugar->login();
       
      $exists = $sugar->lead_email_exists($value);
      
      if($value && $exists){
        $result["is_valid"] = false;
        $result['message'] = 'This email is already registered';
      }
      
    }
    
  }

  return $result;

}

add_action( 'gform_after_submission', 'wp_sugar_create_lead', 10, 2 );
function wp_sugar_create_lead( $entry, $form ) {

  $options = get_option('wp_sugar_options');
  $form_data = get_option('wp_sugar_forms');
  $data = array();

  foreach( $form["fields"] as $field ) {
    $email_field_id = '';
//    var_dump($options);
    $sugar_field_id = $form_data[$form["id"]]["entries"][str_replace('.', '_', $field['id'])];

    if( !empty($field["inputs"]) ) {

      foreach ( $field["inputs"] as $input ) {

        $input_id = $input['id'];
        // convert the dot to underscore
        $input_dot_id = $form_data[$form["id"]]["entries"][str_replace('.', '_', $input['id'])];
        // echo str_replace('.', '_', $input['id']);
        $data[$input_dot_id] = $entry["$input_id"];
        
      }
      continue;
    }
    
    // primary email
    if( $field['type'] == 'email' ) echo $field['id'];
    
    // build $data   
    $data[$sugar_field_id] = $entry[$field["id"]];
    
    // todo: better filter?  
    $data = array_filter($data);
  
  }

  $sugar = new SugarCRMWebServices();
  $sugar->login(); 

//  print_r($data);
  
  // create the lead
 return $sugar->create_lead($data);

}
