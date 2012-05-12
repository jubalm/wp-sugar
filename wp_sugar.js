jQuery(function($){

  $admin_url = window.location.href.substr(0, window.location.href.search('wp-admin/'));
  $ajax_spinner = '<span id="ajax_spinner"><img src="' + $admin_url + '/wp-admin/images/wpspin_light.gif" /></span>';
  
  // test connection
  $('#test_connect').click(function(){  
    $('#ajax_spinner').remove();
    $('#wp_sugar_general_settings #test_connect').after($ajax_spinner);
    
    $data = { action: 'wp_sugar' }
    
    $.post(ajaxurl, $data, function(response) {
      $('#ajax_spinner')
        .html(response)
        $('#ajax_spinner').delay(2000)
        .fadeOut('slow');
    });
  });

  // load form entries on change
  $('form#wp_sugar_form_settings select.wp_sugar_select_form').change(function(){
    $select = $(this).val();
    $.post(ajaxurl,{action:'wp_sugar_load_gforms', formid:$select}, function(response) {
    		$('#wp_sugar_ajax_result').html(response);
    	});
  });
  
  // submit form
  $('form#wp_sugar_form_settings').submit(function(){
    $this = $(this);
    $data = 'action=wp_sugar_submit_form&' + $(this).serialize();
    $('#ajax_spinner').remove();
    $('#wp_sugar_form_settings #submit_form_data').after($ajax_spinner);

    $.post(ajaxurl, $data, function(response) {

      response_text = (response) ? 'Updated!' : 'Error!';

      $('#ajax_spinner')
        .html(response_text)
        $('#ajax_spinner').delay(2000)
        .fadeOut('slow');
    });
    
    return false;
  })

});