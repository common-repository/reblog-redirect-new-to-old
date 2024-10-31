<?php
/**
 * @package   reblog-redirect-new-to-old
 * @author    Stefan Böttcher
 *
 * @wordpress-plugin
 * Plugin Name: Reblog: redirect new to old
 * Description: adds the ability to reblog posts. write new headlines but keep the old link juice intact.
 * Version:     1.0
 * Author:      wp-hotline.com ~ Stefan
 * Author URI:  https://wp-hotline.com/m/reblog-redirect-new-to-old/
 * License: GPLv2 or later
 */

add_action( 'template_redirect', 'reblog_post_redirect' );
function reblog_post_redirect() {
    if( !is_singular() ) return;

    global $post;
    $redirect = esc_url( get_post_meta( $post->ID, 'reblog_redirect', true ) );
    if( $redirect ) {
        wp_redirect( $redirect, apply_filters( 'reblog_redirect_code', 301 ) );
        exit;
    }
}

add_filter('redirect_post_location', 'reblog_admin_redirect');
function reblog_admin_redirect($location){

    if ( isset( $_POST['reblog'] ) ) {
      global $post;
      $last_id = (int) get_post_meta( $post->ID, '_reblog_last_id', true );
      //var_dump( get_edit_post_link( $last_id ) );
      if( $last_id ) { $location = get_edit_post_link( $last_id, false ); }

      $location = apply_filters( 'reblog_redirect_location', $location );
    }
    return $location;
}

add_action( 'post_submitbox_misc_actions', 'reblog_add_button_html' );
function reblog_add_button_html(){
  global $post;
  $last_id = get_post_meta( $post->ID, '_reblog_last_id', true );
  if($last_id) $last = get_post( $last_id );
  //var_dump( $last );
  if($post->post_status=="publish" && $post->post_type!="page") {
    ?>
    <div>
        <input name="reblog" type="submit" class="button-large button-primary" value="reblog" style="width:auto; min-width: 33%; border-radius: 0;" />
        <?php if($last_id) { echo '<small style="display:block;padding: .3em .5em .5em .5em; color: #888;">last reblogged <span title="'. get_the_date( 'd.m.Y', $last ) .' '. get_the_time( 'H:i', $last ) .'">'. human_time_diff( get_the_time('U', $last), current_time('timestamp') ) .'</span> as "'  .$last->post_title .'"</small>'; } ?>
    </div>
    <?php
    }
}

add_action('save_post', 'reblog_save_meta');
function reblog_save_meta( $post_id ) {

  global $post;
  // unhook this function so it doesn't loop infinitely
  remove_action('save_post', 'reblog_save_meta');

  if ( !current_user_can( 'edit_post', $post_id )) { return; }

  if ( isset($_POST['reblog']) ) {

    $reblog_id = wp_insert_post( array(
      'post_status' => 'draft',
      'post_title' => apply_filters( 'reblog_title_prefix', __('Reblog') ) .' '.$post->post_title,
      'meta_input' => array(
        'reblog_redirect' => get_permalink( $post_id ),
        '_reblog_redirect_id' => $post_id
      )
    ) );

    if( $reblog_id ) update_post_meta( $post_id, '_reblog_last_id', $reblog_id );

    //do action when reblog save
    do_action( 'save_reblog', $post_ID, $post, $reblog_id );

  }

  add_action('save_post', 'reblog_save_meta');
  return;
}
