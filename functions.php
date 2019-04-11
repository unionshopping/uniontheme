<?php
include('settings.php');
add_theme_support( 'woocommerce' );
if (function_exists('add_theme_support')) {
	add_theme_support('menus');
	register_nav_menu('header-menu','Header Menu');
//	register_nav_menu('footer-menu','Footer Menu');
	add_theme_support( 'post-thumbnails' );
	add_image_size('home-small-box',360,360,true);
	add_image_size('st-blog-image',930,400,true);
}
/*
add_filter( 'woocommerce_get_catalog_ordering_args', 'custom_woocommerce_get_catalog_ordering_args' );
 
function custom_woocommerce_get_catalog_ordering_args( $args ) {
	$orderby_value = isset( $_GET['orderby'] ) ? woocommerce_clean( $_GET['orderby'] ) : apply_filters( 'woocommerce_default_catalog_orderby', get_option( 'woocommerce_default_catalog_orderby' ) );
	 
	if ( 'price-desc' == $orderby_value ) {
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'desc';
		$args['meta_key'] = '_price';
	} elseif ( 'price' == $orderby_value ) {
		$args['orderby'] = 'meta_value_num';
		$args['order'] = 'asc';
		$args['meta_key'] = '_price';
	}
	 
	return $args;
}*/
function ds_get_excerpt($num_chars) {
    $temp_str = substr(strip_tags(strip_shortcodes(get_the_content())),0,$num_chars);
    $temp_parts = explode(" ",$temp_str);
    $temp_parts[(count($temp_parts) - 1)] = '';
    
    if(strlen(strip_tags(strip_shortcodes(get_the_content()))) > $num_chars)
      return implode(" ",$temp_parts) . '...';
    else
      return implode(" ",$temp_parts);
}
if ( function_exists('register_sidebar') ) {
        register_sidebar(array(
                'name'=>'Sidebar',
		'before_widget' => '<div class="side_box">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="side_title">',
		'after_title' => '</h3>',
	));
        register_sidebar(array(
                'name'=>'Footer Widget 1',
        'before_widget' => '<div class="footer_box">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="footer_title">',
        'after_title' => '</h3>',
    ));
        register_sidebar(array(
                'name'=>'Footer Widget 2',
        'before_widget' => '<div class="footer_box">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="footer_title">',
        'after_title' => '</h3>',
    ));
        register_sidebar(array(
                'name'=>'Footer Widget 3',
        'before_widget' => '<div class="footer_box">',
        'after_widget' => '</div>',
        'before_title' => '<h3 class="footer_title">',
        'after_title' => '</h3>',
    ));            
}

show_admin_bar(false);


/**
 * @snippet       Edit Order Functionality @ WooCommerce My Account Page
 * @how-to        Watch tutorial @ https://businessbloomer.com/?p=19055
 * @sourcecode    https://businessbloomer.com/?p=91893
 * @author        Rodolfo Melogli
 * @compatible    WooCommerce 3.5.3
 * @donate $9     https://businessbloomer.com/bloomer-armada/
 */
 
// ----------------
// 1. Allow Order Again for Processing Status
 
add_filter( 'woocommerce_valid_order_statuses_for_order_again', 'bbloomer_order_again_statuses' );
 
function bbloomer_order_again_statuses( $statuses ) {
    $statuses[] = 'processing';
    return $statuses;
}
 
// ----------------
// 2. Add Order Actions @ My Account
 
add_filter( 'woocommerce_my_account_my_orders_actions', 'bbloomer_add_edit_order_my_account_orders_actions', 50, 2 );
 
function bbloomer_add_edit_order_my_account_orders_actions( $actions, $order ) {
    if ( $order->has_status( 'processing' ) ) {
        $actions['edit-order'] = array(
            'url'  => wp_nonce_url( add_query_arg( array( 'order_again' => $order->get_id(), 'edit_order' => $order->get_id() ) ), 'woocommerce-order_again' ),
            'name' => __( 'Edit Order', 'woocommerce' )
        );
    }
    return $actions;
}
 
// ----------------
// 3. Detect Edit Order Action and Store in Session
 
add_action( 'woocommerce_cart_loaded_from_session', 'bbloomer_detect_edit_order' );
            
function bbloomer_detect_edit_order( $cart ) {
    if ( isset( $_GET['edit_order'] ) ) WC()->session->set( 'edit_order', absint( $_GET['edit_order'] ) );
}
 
// ----------------
// 4. Display Cart Notice re: Edited Order
 
add_action( 'woocommerce_before_cart', 'bbloomer_show_me_session' );
 
function bbloomer_show_me_session() {
    if ( ! is_cart() ) return;
    $edited = WC()->session->get('edit_order');
    if ( ! empty( $edited ) ) {
        $order = new WC_Order( $edited );
        $credit = $order->get_total();
        wc_print_notice( 'A credit of ' . wc_price($credit) . ' has been applied to this new order. Feel free to add or edit products.', 'notice' );
    }
}
 
// ----------------
// 5. Calculate New Total if Edited Order
  
add_action( 'woocommerce_cart_calculate_fees', 'bbloomer_use_edit_order_total', 20, 1 );
  
function bbloomer_use_edit_order_total( $cart ) {
   
  if ( is_admin() && ! defined( 'DOING_AJAX' ) ) return;
    
  $edited = WC()->session->get('edit_order');
  if ( ! empty( $edited ) ) {
      $order = new WC_Order( $edited );
      $credit = -1 * $order->get_total();
      $cart->add_fee( 'Credit', $credit );
  }
   
}
 
// ----------------
// 6. Save Order Action if New Order is Placed
 
add_action( 'woocommerce_checkout_update_order_meta', 'bbloomer_save_edit_order' );
  
function bbloomer_save_edit_order( $order_id ) {
    $edited = WC()->session->get('edit_order');
    if ( ! empty( $edited ) ) {
        // update this new order
        update_post_meta( $order_id, '_edit_order', $edited );
        $neworder = new WC_Order( $order_id );
        $oldorder_edit = get_edit_post_link( $edited );
        $neworder->add_order_note( 'Order placed after editing. Old order number: <a href="' . $oldorder_edit . '">' . $edited . '</a>' );
        // cancel previous order
        $oldorder = new WC_Order( $edited );
        $neworder_edit = get_edit_post_link( $order_id );
        $oldorder->update_status( 'cancelled', 'Order cancelled after editing. New order number: <a href="' . $neworder_edit . '">' . $order_id . '</a> -' );
    }
}
?>

