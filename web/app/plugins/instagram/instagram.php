<?php
/**
* Plugin Name: Instagram Connect
* Plugin URI: https://wecreate.digital
* Description: Connect to instagram to be able to use your images. This plugin uses ACF Pro!
* Author: Steven Hardy
* Author URI: https://wecreate.digital
* Version: 0.0.1
* Text Domain: instagram-connect
*
* @package instagram-connect
*/

class Instagram_Connect {

    private static $object;
    private $access_token;

    /*
	 * Singleton instance static method
	 */
    static function &object() {
        if ( ! self::$object instanceof Instagram_Connect ) {
            self::$object = new Instagram_Connect( );
        }
        return self::$object;
    }

    public function init() {
        require 'posts.php';
        require 'lib/shortcode.php';

        //Plugin activation/deactivation
        register_activation_hook( __FILE__, array($this, 'activation') );
        register_deactivation_hook( __FILE__, array($this, 'deactivation') );

        $access_token = get_option('instagram_access_token');
        $access_token_expires = get_option('instagram_access_token_expires');

        if( is_admin() ) {
            add_action( 'admin_init', array($this, 'handle_oauth_response') );

            if( empty($access_token) )
                add_action( 'admin_notices', array($this, 'instagram_connect_admin') );
        }

        if(class_exists('ACF')) {
            $this->createAcfFields();
        } else {
            add_action( 'admin_menu', array( $this, 'instagramConnectSettingsPage' ) );
        }

        // cron job
        add_action( 'instagram_connect_refresh_access_token', array( $this, 'refresh_access_token' ) );
    }

    public function activation() {
        if( !wp_next_scheduled( 'instagram_connect_refresh_access_token' ) ) {
            $start_of_day = time();
            $start_of_day -= $start_of_day % DAY_IN_SECONDS;
            wp_schedule_event( $start_of_day, 'twicedaily', 'instagram_connect_refresh_access_token' );
        }
    }

    public function deactivation() {
        delete_option( 'instagram_access_token' );
        delete_option( 'instagram_access_token_expires' );
        wp_clear_scheduled_hook( 'instagram_connect_refresh_access_token' );
        wp_cache_flush();
    }

    public function instagram_connect_admin() {
        if(class_exists('ACF')) {
            $oauth_url = 'https://api.instagram.com/oauth/authorize/?client_id=' . get_field('instagram_app_id', 'option') . '&redirect_uri=' . urlencode(admin_url('', 'https')) . '&scope=user_profile,user_media&response_type=code&state=instagram_oauth_nonce_1';
        } else {
            $oauth_url = 'https://api.instagram.com/oauth/authorize/?client_id=' . get_option('instagram_app_id') . '&redirect_uri=' . urlencode(admin_url('', 'https')) . '&scope=user_profile,user_media&response_type=code&state=instagram_oauth_nonce_1';
        }
    ?>
        <div class="notice notice-error error">
            <h2>Instagram Connect</h2>
            <p>Before you can generate an access token for Instagram, please create a facebook app using Instagram Basic Display API <a href="https://developers.facebook.com/" target="_blank">here</a>. Once you have created the app, please enter the details in the <?php if(!class_exists('ACF')): ?><a href="/wp-admin/admin.php?page=instagram-connect">theme options</a><?php else: ?>theme options<?php endif;?>.</p>
            <a href="<?php echo $oauth_url;?>" target="_blank">Generate access token for Instagram</a>
        </div>
<?php
    }

    public function handle_oauth_response() {
            if( empty( $_REQUEST['code'] ) || empty( $_REQUEST['state'] ) || substr( $_REQUEST['state'], 0, 22 ) !== 'instagram_oauth_nonce_' )
                return;

            $nonce = substr( $_REQUEST['state'], 22 );
            if( empty( $nonce ) )
                return;

            if(class_exists('ACF')):
                $post_array = array(
                    'client_id' => get_field('instagram_app_id', 'option'),
                    'client_secret' => get_field('instagram_app_secret', 'option'),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => admin_url( '', 'https' ),
                    'code' => $_REQUEST['code']
                );

            else:
                $post_array = array(
                    'client_id' => get_option('instagram_app_id'),
                    'client_secret' => get_option('instagram_app_secret'),
                    'grant_type' => 'authorization_code',
                    'redirect_uri' => admin_url( '', 'https' ),
                    'code' => $_REQUEST['code']
                );
            endif;

            $headers = array();
            $args = array( 'headers' => $headers, 'sslverify' => false, 'body' => $post_array );

            $access_token = false;
            $expires = 0;

            // Request a short lived access token
            $response = wp_remote_post( 'https://api.instagram.com/oauth/access_token', $args) ;
            if( !is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
                $response_body = wp_remote_retrieve_body( $response );
                $response_body = json_decode( $response_body );
                if( !empty( $response_body->access_token ) ) {
                    // Exchange the short lived access token for a long lived on

                    if(class_exists('ACF')):
                        $query_string = build_query(
                            array(
                                'access_token' => $response_body->access_token,
                                'client_secret' => get_field('instagram_app_secret', 'option'),
                                'grant_type' => 'ig_exchange_token'
                            )
                        );
                    else:
                        $query_string = build_query(
                            array(
                                'access_token' => $response_body->access_token,
                                'client_secret' => get_option('instagram_app_secret'),
                                'grant_type' => 'ig_exchange_token'
                            )
                        );
                    endif;

                    $headers = array();
                    $args = array( 'headers' => $headers, 'sslverify' => false, 'body' => array() );
                    $response = wp_remote_get( "https://graph.instagram.com/access_token?$query_string", $args) ;
                    if( !is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
                        $response_body = wp_remote_retrieve_body( $response );
                        $response_body = json_decode( $response_body );

                        if( !empty( $response_body->access_token ) ) {
                            $access_token = $response_body->access_token;
                            $expires = time() + $response_body->expires_in - 30;
                        }
                    }
                }
            }

            update_option( 'instagram_access_token', $access_token, true );
            update_option( 'instagram_access_token_expires', $expires, true );

            wp_redirect( admin_url( '', 'https'), 307 );
            die();
        }

    public function refresh_access_token() {
        if( empty( $this->access_token ) || empty( $this->access_token_expires ) || $this->access_token_expires - time() > WEEK_IN_SECONDS )
            return;

        // Exchange the short lived access token for a long lived on
        $query_string = build_query(
            array(
                'access_token' => $this->access_token,
                'grant_type' => 'ig_refresh_token'
            )
        );

        $headers = array();
        $args = array( 'headers' => $headers, 'sslverify' => false, 'body' => array() );
        $response = wp_remote_get( "https://graph.instagram.com/refresh_access_token?$query_string", $args) ;
        if( !is_wp_error( $response ) && 200 == wp_remote_retrieve_response_code( $response ) ) {
            $response_body = wp_remote_retrieve_body( $response );
            $response_body = json_decode( $response_body );

            if( !empty( $response_body->access_token ) ) {
                $this->access_token = $response_body->access_token;
                $this->access_token_expires = time() + $response_body->expires_in - 30;
            }
        }

        update_option( 'instagram_access_token', $this->access_token, true );
        update_option( 'instagram_access_token_expires', $this->access_token_expires, true );
    }


    /**
     * SETTINGS AND OPTIONS
     */

    public function createAcfFields() {
        acf_add_local_field_group(array(
            'key' => 'instagram_field_group',
            'title' => 'Instagram',
            'fields' => array(
                array(
                    'key' => 'amount_of_instagram_posts',
                    'label' => 'Number of Instagram posts',
                    'name' => 'amount_of_instagram_posts',
                    'type' => 'number',
                    'min' => 3,
                    'max' => 12,
                    'default_value' => 6,
                ),
                array(
                    'key' => 'instagram_app_id',
                    'label' => 'Instagram App ID',
                    'name' => 'instagram_app_id',
                    'type' => 'text'
                ),
                array(
                    'key' => 'instagram_app_secret',
                    'label' => 'Instagram App Secret',
                    'name' => 'instagram_app_secret',
                    'type' => 'text'
                ),
            ),
            'location' => array(
                array(
                    array(
                        'param' => 'options_page',
                        'operator' => '==',
                        'value' => 'acf-options',
                    ),
                ),
            ),
        ));
    }

    public function instagramConnectSettingsPage() {
        $page_title = 'Instagram Connect Settings Page';
        $menu_title = 'Instagram';
        $capability = 'manage_options';
        $slug = 'instagram-connect';
        $callback = array( $this, 'instagramConnectSettings' );
        $icon = 'dashicons-instagram';
        $position = 1;

        add_menu_page( $page_title, $menu_title, $capability, $slug, $callback, $icon, $position );
    }

    public function instagramConnectSettings() {
        if( $_POST['updated'] === 'true' ){
            $this->handleSettingsForm();
        }
        ?>
        <div class="wrap">
            <h2>Instagram Connect Settings Page</h2>
            <form method="POST">
                <input type="hidden" name="updated" value="true" />
                <?php wp_nonce_field( 'instagram_connect_settings', 'instagram_connect' ); ?>
                <table class="form-table">
                    <tbody>
                    <tr>
                        <th><label for="app_id">Instagram App ID</label></th>
                        <td><input name="app_id" id="app_id" type="text" value="<?php echo get_option('instagram_app_id'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="app_secret">Instagram App Secret</label></th>
                        <td><input name="app_secret" id="app_secret" type="text" value="<?php echo get_option('instagram_app_secret'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="amount_of_instagram_posts">Number of instagram posts</label></th>
                        <td><input name="amount_of_instagram_posts" id="amount_of_instagram_posts" type="text" value="<?php echo get_option('amount_of_instagram_posts'); ?>" class="regular-text" /></td>
                    </tr>
                    <tr>
                        <th><label for="instagram_title">Title of Instagram Block</label></th>
                        <td><input name="instagram_title" id="instagram_title" type="text" value="<?php echo get_option('instagram_title'); ?>" class="regular-text" /></td>
                    </tr>
                    </tbody>
                </table>
                <p class="submit">
                    <input type="submit" name="submit" id="submit" class="button button-primary" value="Update Instagram Settings">
                </p>
            </form>
        </div> <?php
    }

    public function handleSettingsForm() {
        if(
            ! isset( $_POST['instagram_connect'] ) ||
            ! wp_verify_nonce( $_POST['instagram_connect'], 'instagram_connect_settings' )
        ){ ?>
            <div class="error">
                <p>Sorry, your nonce was not correct. Please try again.</p>
            </div> <?php
            exit;
        } else {
            $app_id = sanitize_text_field( $_POST['app_id'] );
            $app_secret = sanitize_text_field( $_POST['app_secret'] );
            $amount_of_instagram_posts = sanitize_text_field( $_POST['amount_of_instagram_posts'] );
            $instagram_title = sanitize_text_field( $_POST['instagram_title'] );


            if( $app_id && $app_secret) {
                update_option('instagram_app_id', $app_id);
                update_option( 'instagram_app_secret', $app_secret );
                update_option( 'amount_of_instagram_posts', $amount_of_instagram_posts );
                update_option( 'instagram_title', $instagram_title );

                ?>
                <div class="updated">
                    <p>Your instagram settings have been saved!</p>
                </div>
                <?php
            } else { ?>
                <div class="error">
                    <p>Your instagram settings are invalid.</p>
                </div> <?php
            }
        }
    }

}


$instagram_connect = Instagram_Connect::object();
$instagram_connect->init();
