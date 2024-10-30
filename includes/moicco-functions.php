<?php
/*
 * Add moicco menu to the Admin Control Panel
 */
function cron_moicco()
{
  if (!wp_next_scheduled('moicco_cronjob')) {
    wp_schedule_event(time(), 'hourly', 'moicco_cronjob');
  }
}
add_action('wp', 'cron_moicco');
add_action('save_post', 'send_moicco_products_post');


add_action('admin_menu', 'moicco_admin_menu');
add_action('admin_init', 'update_moicco_token_info');

if (!function_exists('update_moicco_token_info')) {
  function update_moicco_token_info()
  {
    register_setting('moicco-settings', 'moicco_token_info');
  }
}

// Add a new top level menu link to the ACP
function moicco_admin_menu()
{
  add_menu_page(
    'Moicco connect', // Title of the page
    'Moicco connect', // Text to show on the menu link
    'manage_options', // Capability requirement to see the link
    'moicco-connect', // Title of the page
    'moicco_token_info_page', // The 'slug' - file to display when clicking the link
    "https://i.ibb.co/7YQJMPY/moicco.png",
    6
  );
}

function send_moicco_products_post()
{
  $all_ids = get_posts(array(
    'post_type' => 'product',
    'numberposts' => -1,
    'post_status' => 'publish',
  ));

  $post_data = array();
  foreach ($all_ids as $post) {
    $data = get_post_meta($post->ID);
    $data['post_id'] = $post->ID;
    array_push($post_data, $data);
  }

  if (count($all_ids) > 0 && count($post_data)) {
    $body = array(
      'posts' => $all_ids,
      'token' => get_option('moicco_token_info'),
      'post_meta' => $post_data
    );

    $body = wp_json_encode( $body );
    $options = [
      'body'        => $body,
      'headers'     => [
          'Content-Type' => 'application/json',
      ],
      'timeout'     => 60,
      'redirection' => 5,
      'blocking'    => true,
      'httpversion' => '1.0',
      'sslverify'   => false,
      'data_format' => 'body',
    ];

    wp_remote_post('http://api.moicco.com', $options);
   
  }
}

add_action('moicco_cronjob', 'send_moicco_products_post');


function moicco_token_info_page()
{
?>
  <h1>MOICCO CONNECT</h1>
  <form method="post" action="options.php">
    <?php settings_fields('moicco-settings'); ?>
    <?php do_settings_sections('moicco-settings'); ?>
    <span><?php echo _('Ingrese su API-TOKEN generado por el equipo moicco. para obtenerlo envie un mensaje a holamoda@moicco.com') ?></span>
    <table class="form-table">
      <tr valign="top">
        <th scope="row">
          <span style="color: gray;">MOICCO API TOKEN:</span>
        </th>
        <td>
          <input type="text" name="moicco_token_info" value="<?php echo get_option('moicco_token_info'); ?>" />
        </td>
      </tr>
    </table>
    <?php submit_button(); ?>
  </form> <?php
        }
