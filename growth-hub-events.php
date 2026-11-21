<?php
/**
 * Plugin Name: Growth Hub Events
 * Description: Event post type with WooCommerce ticket integration, dynamic color theming, and Elementor templates.
 * Version: 1.1.0
 * Author: Shina Adeleye
 * Text Domain: growth-hub-events
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Register post type.
 */
add_action( 'init', 'gh_event_register_cpt' );
function gh_event_register_cpt() {
    register_post_type( 'gh_event', array(
        'labels'       => array(
            'name'          => 'Events',
            'singular_name' => 'Event',
            'menu_name'     => 'Events',
            'all_items'     => 'All Events',
            'add_new_item'  => 'Add New Event',
            'edit_item'     => 'Edit Event',
        ),
        'public'       => true,
        'has_archive'  => false,
        'rewrite'      => array( 'slug' => 'event' ),
        'menu_icon'    => 'dashicons-tickets-alt',
        'supports'     => array( 'title', 'editor', 'thumbnail', 'excerpt' ),
        'show_in_rest' => true,
    ) );
}

register_activation_hook( __FILE__, function() {
    gh_event_register_cpt();
    flush_rewrite_rules();
} );

/**
 * Event meta boxes.
 */
add_action( 'add_meta_boxes', function() {
    add_meta_box( 'gh_event_details', 'Event Details', 'gh_event_render_meta_box', 'gh_event', 'normal', 'high' );
} );

function gh_event_render_meta_box( $post ) {
    wp_nonce_field( 'gh_event_save_' . $post->ID, 'gh_event_nonce' );

    $subtitle = get_post_meta( $post->ID, 'gh_event_subtitle', true );
    $date     = get_post_meta( $post->ID, 'gh_event_date', true );
    $time     = get_post_meta( $post->ID, 'gh_event_time', true );
    $venue    = get_post_meta( $post->ID, 'gh_event_venue', true );
    $price    = get_post_meta( $post->ID, 'gh_event_price', true );
    $capacity = get_post_meta( $post->ID, 'gh_event_capacity', true );
    $color    = sanitize_hex_color( (string) get_post_meta( $post->ID, 'gh_event_color', true ) );
    if ( ! $color ) {
        $color = '#EC7766';
    }
    ?>
    <table class="form-table">
        <tr>
            <th><label for="gh_event_subtitle">Hero Subtitle</label></th>
            <td>
                <textarea id="gh_event_subtitle" name="gh_event_subtitle" rows="2" class="large-text"><?php echo esc_textarea( $subtitle ); ?></textarea>
                <p class="description">Short summary displayed under the event title in the hero banner.</p>
            </td>
        </tr>
        <tr>
            <th><label for="gh_event_date">Event date</label></th>
            <td>
                <input type="date" id="gh_event_date" name="gh_event_date" value="<?php echo esc_attr( $date ); ?>">
                <p class="description">Date of the event for chronological sorting.</p>
            </td>
        </tr>
        <tr>
            <th><label for="gh_event_time">Event time</label></th>
            <td><input type="time" id="gh_event_time" name="gh_event_time" value="<?php echo esc_attr( $time ); ?>"></td>
        </tr>
        <tr>
            <th><label for="gh_event_venue">Venue</label></th>
            <td><input type="text" id="gh_event_venue" name="gh_event_venue" value="<?php echo esc_attr( $venue ); ?>" class="regular-text"></td>
        </tr>
        <tr>
            <th><label for="gh_event_price">Ticket price</label></th>
            <td>
                <input type="number" step="0.01" min="0" id="gh_event_price" name="gh_event_price" value="<?php echo esc_attr( $price === '' ? 0 : $price ); ?>" style="width:160px;">
                <p class="description">Enter 0 for free events.</p>
            </td>
        </tr>
        <tr>
            <th><label for="gh_event_capacity">Number of tickets</label></th>
            <td>
                <input type="number" min="0" id="gh_event_capacity" name="gh_event_capacity" value="<?php echo esc_attr( $capacity ); ?>" style="width:160px;">
                <p class="description">Leave blank for unlimited capacity.</p>
            </td>
        </tr>
        <tr>
            <th><label for="gh_event_color">Event colour</label></th>
            <td>
                <input type="color" id="gh_event_color" name="gh_event_color" value="<?php echo esc_attr( $color ); ?>">
                <p class="description">Accent color for hero banner, badges, and buttons.</p>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post_gh_event', 'gh_event_save_meta', 10, 2 );
function gh_event_save_meta( $post_id, $post ) {

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }
    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! isset( $_POST['gh_event_nonce'] ) || ! wp_verify_nonce( $_POST['gh_event_nonce'], 'gh_event_save_' . $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    update_post_meta( $post_id, 'gh_event_subtitle', sanitize_text_field( wp_unslash( $_POST['gh_event_subtitle'] ?? '' ) ) );
    update_post_meta( $post_id, 'gh_event_date', sanitize_text_field( wp_unslash( $_POST['gh_event_date'] ?? '' ) ) );
    update_post_meta( $post_id, 'gh_event_time', sanitize_text_field( wp_unslash( $_POST['gh_event_time'] ?? '' ) ) );
    update_post_meta( $post_id, 'gh_event_venue', sanitize_text_field( wp_unslash( $_POST['gh_event_venue'] ?? '' ) ) );
    update_post_meta( $post_id, 'gh_event_price', max( 0, (float) ( $_POST['gh_event_price'] ?? 0 ) ) );

    $capacity_raw = isset( $_POST['gh_event_capacity'] ) ? trim( wp_unslash( $_POST['gh_event_capacity'] ) ) : '';
    update_post_meta( $post_id, 'gh_event_capacity', '' === $capacity_raw ? '' : max( 0, (int) $capacity_raw ) );

    $color = sanitize_hex_color( wp_unslash( $_POST['gh_event_color'] ?? '' ) );
    update_post_meta( $post_id, 'gh_event_color', $color ? $color : '#EC7766' );
}

/**
 * Synchronize hidden WooCommerce ticket product.
 */
add_action( 'save_post_gh_event', 'gh_event_sync_product', 20, 2 );
function gh_event_sync_product( $post_id, $post ) {

    if ( ! function_exists( 'wc_get_product' ) ) {
        return;
    }
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( in_array( $post->post_status, array( 'auto-draft', 'inherit', 'trash' ), true ) ) {
        return;
    }

    $price      = (float) get_post_meta( $post_id, 'gh_event_price', true );
    $capacity   = get_post_meta( $post_id, 'gh_event_capacity', true );
    $product_id = (int) get_post_meta( $post_id, '_gh_event_product_id', true );
    $product    = $product_id ? wc_get_product( $product_id ) : false;

    if ( ! $product ) {
        $product = new WC_Product_Simple();
    }

    $product->set_name( $post->post_title ? $post->post_title : 'Event ticket' );
    $product->set_status( 'publish' === $post->post_status ? 'publish' : 'draft' );
    $product->set_catalog_visibility( 'hidden' );
    $product->set_virtual( true );
    $product->set_regular_price( (string) $price );

    // Manage inventory based on capacity.
    if ( '' === $capacity ) {
        $product->set_manage_stock( false );
        $product->set_stock_status( 'instock' );
    } else {
        $capacity = (int) $capacity;
        $synced   = get_post_meta( $post_id, '_gh_event_capacity_synced', true );

        if ( ! $product->get_manage_stock() || '' === $synced ) {
            $new_stock = $capacity;
        } else {
            $new_stock = (int) $product->get_stock_quantity() + ( $capacity - (int) $synced );
        }

        $product->set_manage_stock( true );
        $product->set_stock_quantity( max( 0, $new_stock ) );
        $product->set_backorders( 'no' );
    }

    if ( has_post_thumbnail( $post_id ) ) {
        $product->set_image_id( get_post_thumbnail_id( $post_id ) );
    }

    $term = term_exists( 'Event Tickets', 'product_cat' );
    if ( ! $term ) {
        $term = wp_insert_term( 'Event Tickets', 'product_cat' );
    }
    if ( ! is_wp_error( $term ) ) {
        $term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
        $product->set_category_ids( array( $term_id ) );
    }

    $product->update_meta_data( '_gh_event_id', $post_id );
    $saved_id = $product->save();

    update_post_meta( $post_id, '_gh_event_product_id', $saved_id );
    update_post_meta( $post_id, '_gh_event_capacity_synced', $capacity );
}

// Draft ticket product when event is trashed.
add_action( 'wp_trash_post', function( $post_id ) {
    if ( 'gh_event' !== get_post_type( $post_id ) || ! function_exists( 'wc_get_product' ) ) {
        return;
    }
    $product_id = (int) get_post_meta( $post_id, '_gh_event_product_id', true );
    $product    = $product_id ? wc_get_product( $product_id ) : false;
    if ( $product ) {
        $product->set_status( 'draft' );
        $product->save();
    }
} );

/**
 * Display individual event fields.
 */
function gh_event_resolve_id( $id = 0 ) {
    $event_id = (int) $id ? (int) $id : (int) get_the_ID();
    return ( $event_id && 'gh_event' === get_post_type( $event_id ) ) ? $event_id : 0;
}

add_shortcode( 'gh_event', 'gh_event_field_shortcode' );
function gh_event_field_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'field'     => 'title',
        'id'        => 0,
        'format'    => '',
        'free_text' => 'Free',
    ), $atts );

    $event_id = gh_event_resolve_id( $atts['id'] );
    if ( ! $event_id ) {
        return '';
    }

    switch ( $atts['field'] ) {

        case 'title':
            return esc_html( get_the_title( $event_id ) );

        case 'subtitle':
            $sub = get_post_meta( $event_id, 'gh_event_subtitle', true );
            return esc_html( $sub ? $sub : get_the_excerpt( $event_id ) );

        case 'date':
            $date = get_post_meta( $event_id, 'gh_event_date', true );
            $dt   = $date ? date_create_from_format( 'Y-m-d', $date, wp_timezone() ) : false;
            if ( ! $dt ) {
                return '';
            }
            $format = $atts['format'] ? $atts['format'] : 'jS F Y';
            return esc_html( wp_date( $format, $dt->getTimestamp() ) );

        case 'time':
            $time = get_post_meta( $event_id, 'gh_event_time', true );
            $dt   = $time ? date_create_from_format( 'H:i', $time, wp_timezone() ) : false;
            if ( ! $dt ) {
                return '';
            }
            $format = $atts['format'] ? $atts['format'] : 'g:i A';
            return esc_html( wp_date( $format, $dt->getTimestamp() ) );

        case 'venue':
            return esc_html( get_post_meta( $event_id, 'gh_event_venue', true ) );

        case 'price':
            $price = (float) get_post_meta( $event_id, 'gh_event_price', true );
            if ( function_exists( 'wc_price' ) ) {
                return $price > 0 ? wc_price( $price ) : esc_html( $atts['free_text'] );
            }
            return $price > 0 ? '&#8358;' . number_format( $price, 2 ) : esc_html( $atts['free_text'] );

        case 'tickets_left':
            $product_id = (int) get_post_meta( $event_id, '_gh_event_product_id', true );
            $product    = ( $product_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $product_id ) : false;
            if ( $product && $product->get_manage_stock() ) {
                return esc_html( (int) $product->get_stock_quantity() );
            }
            return '';

        case 'color':
            $color = sanitize_hex_color( (string) get_post_meta( $event_id, 'gh_event_color', true ) );
            return esc_html( $color ? $color : '#EC7766' );

        case 'url':
            return esc_url( get_permalink( $event_id ) );
    }

    return '';
}

/**
 * Event ticket button shortcode: [gh_event_ticket]
 */
add_shortcode( 'gh_event_ticket', 'gh_event_ticket_shortcode' );
function gh_event_ticket_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id'             => 0,
        'label'          => 'Buy Ticket, Get Instant QR Code',
        'sold_out_label' => 'Sold Out',
        'class'          => '',
    ), $atts );

    $event_id = gh_event_resolve_id( $atts['id'] );
    if ( ! $event_id || 'publish' !== get_post_status( $event_id ) || ! function_exists( 'wc_get_product' ) ) {
        return '';
    }

    $product_id = (int) get_post_meta( $event_id, '_gh_event_product_id', true );
    $product    = $product_id ? wc_get_product( $product_id ) : false;
    if ( ! $product ) {
        return '';
    }

    $extra_class = $atts['class'] ? ' ' . esc_attr( $atts['class'] ) : '';

    $form_action = get_permalink( $product->get_id() );

    ob_start();

    if ( isset( $_GET['gh_event_error'], $_GET['gh_event_id'] ) && (int) $_GET['gh_event_id'] === $event_id ) {
        echo '<p style="color:#c0392b;font-size:13px;margin-bottom:8px;">' . esc_html( sanitize_text_field( wp_unslash( $_GET['gh_event_error'] ) ) ) . '</p>';
    }

    if ( ! $product->is_in_stock() ) {
        echo '<button type="button" class="gh-ticket-btn' . $extra_class . '" disabled style="opacity:.5;cursor:not-allowed;">' . esc_html( $atts['sold_out_label'] ) . '</button>';
    } else {
        ?>
        <form method="post" action="<?php echo esc_url( $form_action ); ?>" class="gh-ticket-form">
            <input type="hidden" name="gh_event_action" value="buy">
            <input type="hidden" name="gh_event_id" value="<?php echo esc_attr( $event_id ); ?>">
            <?php wp_nonce_field( 'gh_event_buy_' . $event_id, 'gh_event_buy_nonce' ); ?>
            <button type="submit" class="gh-ticket-btn<?php echo $extra_class; ?>"><?php echo esc_html( $atts['label'] ); ?></button>
        </form>
        <?php
    }

    return ob_get_clean();
}

add_action( 'template_redirect', 'gh_event_handle_ticket_submit' );
function gh_event_handle_ticket_submit() {

    if ( ! isset( $_POST['gh_event_action'] ) || 'buy' !== $_POST['gh_event_action'] ) {
        return;
    }

    $event_id = isset( $_POST['gh_event_id'] ) ? (int) $_POST['gh_event_id'] : 0;

    if ( ! $event_id || ! wp_verify_nonce( $_POST['gh_event_buy_nonce'] ?? '', 'gh_event_buy_' . $event_id ) ) {
        wp_die( 'Security check failed. Please go back and try again.' );
    }

    $back = wp_get_referer() ? wp_get_referer() : home_url();
    $back = remove_query_arg( array( 'gh_event_error', 'gh_event_id' ), $back );

    $fail = function( $message ) use ( $back, $event_id ) {
        wp_safe_redirect( add_query_arg( array(
            'gh_event_error' => urlencode( $message ),
            'gh_event_id'    => $event_id,
        ), $back ) );
        exit;
    };

    if ( 'gh_event' !== get_post_type( $event_id ) || 'publish' !== get_post_status( $event_id ) ) {
        $fail( 'This event is no longer available.' );
    }

    $product_id = (int) get_post_meta( $event_id, '_gh_event_product_id', true );
    $product    = $product_id ? wc_get_product( $product_id ) : false;

    if ( ! $product || ! $product->is_in_stock() ) {
        $fail( 'Sorry, tickets for this event are sold out.' );
    }

    if ( function_exists( 'WC' ) && WC()->cart ) {

        // Initialize guest session if needed.
        if ( WC()->session && ! WC()->session->has_session() ) {
            WC()->session->set_customer_session_cookie( true );
        }

        // Clear cart for direct ticket checkout.
        WC()->cart->empty_cart();

        $cart_item_key = WC()->cart->add_to_cart( $product->get_id(), 1 );

        if ( ! $cart_item_key ) {
            $reason  = 'We could not add your ticket. Please try again.';
            $notices = wc_get_notices( 'error' );
            if ( ! empty( $notices ) ) {
                $first  = reset( $notices );
                $reason = wp_strip_all_tags( is_array( $first ) ? $first['notice'] : $first );
            }
            wc_clear_notices();
            $fail( $reason );
        }

        wp_safe_redirect( wc_get_checkout_url() );
        exit;
    }

    $fail( 'WooCommerce is not active.' );
}

/**
 * Output dynamic event color CSS variables.
 */
add_action( 'wp_head', 'gh_event_print_css', 20 );
function gh_event_print_css() {
    global $wpdb;

    $rows = $wpdb->get_results( $wpdb->prepare(
        "SELECT pm.post_id, pm.meta_value
         FROM {$wpdb->postmeta} pm
         INNER JOIN {$wpdb->posts} p ON p.ID = pm.post_id
         WHERE pm.meta_key = %s AND p.post_type = %s AND p.post_status = 'publish'",
        'gh_event_color',
        'gh_event'
    ) );

    $css = '';
    foreach ( (array) $rows as $row ) {
        $color = sanitize_hex_color( $row->meta_value );
        if ( $color ) {
            $id   = (int) $row->post_id;
            $css .= ".post-{$id},.postid-{$id},.e-loop-item-{$id}{--gh-event-color:{$color};}";
        }
    }

    $css .= '.gh-bg-event{background-color:var(--gh-event-color,#EC7766)!important;}';
    $css .= '.gh-text-event{color:var(--gh-event-color,#EC7766)!important;}';
    $css .= '.gh-border-event{border-color:var(--gh-event-color,#EC7766)!important;}';
    $css .= '.gh-btn-event .elementor-button,.gh-btn-event.elementor-button{background-color:var(--gh-event-color,#EC7766)!important;border-color:var(--gh-event-color,#EC7766)!important;color:#fff!important;}';

    echo '<style id="gh-events-css">' . $css . '</style>';
}

/**
 * Filter Elementor query to order upcoming events by date.
 */
add_action( 'elementor/query/gh_upcoming_events', function( $query ) {
    $query->set( 'meta_key', 'gh_event_date' );
    $query->set( 'orderby', 'meta_value' );
    $query->set( 'order', 'ASC' );
} );

/**
 * Admin post list columns.
 */
add_filter( 'manage_gh_event_posts_columns', function( $columns ) {
    $new = array();
    foreach ( $columns as $key => $label ) {
        $new[ $key ] = $label;
        if ( 'title' === $key ) {
            $new['gh_date']  = 'Event Date';
            $new['gh_price'] = 'Price';
            $new['gh_left']  = 'Tickets Left';
        }
    }
    return $new;
} );

add_action( 'manage_gh_event_posts_custom_column', function( $column, $post_id ) {
    switch ( $column ) {
        case 'gh_date':
            $date = get_post_meta( $post_id, 'gh_event_date', true );
            $dt   = $date ? date_create_from_format( 'Y-m-d', $date, wp_timezone() ) : false;
            echo $dt ? esc_html( wp_date( 'j M Y', $dt->getTimestamp() ) ) : '—';
            break;

        case 'gh_price':
            $price = (float) get_post_meta( $post_id, 'gh_event_price', true );
            if ( function_exists( 'wc_price' ) ) {
                echo $price > 0 ? wp_kses_post( wc_price( $price ) ) : 'Free';
            } else {
                echo $price > 0 ? '&#8358;' . number_format( $price, 2 ) : 'Free';
            }
            break;

        case 'gh_left':
            $product_id = (int) get_post_meta( $post_id, '_gh_event_product_id', true );
            $product    = ( $product_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $product_id ) : false;
            if ( ! $product ) {
                echo '—';
            } elseif ( $product->get_manage_stock() ) {
                echo esc_html( (int) $product->get_stock_quantity() );
            } else {
                echo 'Unlimited';
            }
            break;
    }
}, 10, 2 );

add_filter( 'manage_edit-gh_event_sortable_columns', function( $columns ) {
    $columns['gh_date'] = 'gh_event_date';
    return $columns;
} );

add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() && $query->is_main_query() && 'gh_event_date' === $query->get( 'orderby' ) ) {
        $query->set( 'meta_key', 'gh_event_date' );
        $query->set( 'orderby', 'meta_value' );
    }
} );

/**
 * Event grid shortcode: [gh_events]
 */
add_shortcode( 'gh_events', 'gh_events_list_shortcode' );
function gh_events_list_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'limit'    => 12,
        'columns'  => 4,
        'button'   => 'Get Ticket',
        'sold_out' => 'Sold Out',
    ), $atts );

    $event_ids = get_posts( array(
        'post_type'      => 'gh_event',
        'post_status'    => 'publish',
        'posts_per_page' => max( 1, (int) $atts['limit'] ),
        'fields'         => 'ids',
        'meta_key'       => 'gh_event_date',
        'orderby'        => 'meta_value',
        'order'          => 'ASC',
    ) );

    if ( empty( $event_ids ) ) {
        return '<p>No upcoming events right now. Please check back soon.</p>';
    }

    $columns = max( 1, min( 6, (int) $atts['columns'] ) );

    static $css_printed = false;
    $css = '';
    if ( ! $css_printed ) {
        $css_printed = true;
        $css  = '<style>';
        $css .= '.gh-events-grid{display:grid;grid-template-columns:repeat(var(--gh-cols,4),minmax(0,1fr));gap:20px;}';
        $css .= '@media(max-width:1024px){.gh-events-grid{grid-template-columns:repeat(2,minmax(0,1fr));}}';
        $css .= '@media(max-width:767px){.gh-events-grid{grid-template-columns:1fr;}}';
        $css .= '.gh-event-card{background:#fff;border-radius:12px;overflow:hidden;box-shadow:0 1px 6px rgba(0,0,0,.12);display:flex;flex-direction:column;}';
        $css .= '.gh-event-img{display:block;aspect-ratio:16/10;background:var(--gh-event-color,#EC7766);overflow:hidden;}';
        $css .= '.gh-event-img img{width:100%;height:100%;object-fit:cover;display:block;}';
        $css .= '.gh-event-body{padding:14px;display:flex;flex-direction:column;gap:4px;flex:1;}';
        $css .= '.gh-event-date{font-size:11px;font-weight:700;letter-spacing:.04em;text-transform:uppercase;color:var(--gh-event-color,#EC7766);}';
        $css .= '.gh-event-title{margin:0;font-size:16px;line-height:1.3;font-weight:700;}';
        $css .= '.gh-event-title a{color:#111!important;text-decoration:none!important;}';
        $css .= '.gh-event-price{font-size:13px;color:#444;}';
        $css .= '.gh-event-price .woocommerce-Price-amount{color:inherit;}';
        $css .= '.gh-event-btn{margin-top:auto;display:block;text-align:center;padding:10px 16px;border-radius:999px;background:var(--gh-event-color,#EC7766);color:#fff!important;font-weight:600;font-size:13px;text-decoration:none!important;}';
        $css .= '.gh-event-body .gh-event-price{margin-bottom:10px;}';
        $css .= '.gh-event-btn--soldout{opacity:.5;}';
        $css .= '</style>';
    }

    ob_start();
    echo $css;
    ?>
    <div class="gh-events-grid" style="--gh-cols:<?php echo (int) $columns; ?>;">
    <?php
    foreach ( $event_ids as $event_id ) :
        $color = sanitize_hex_color( (string) get_post_meta( $event_id, 'gh_event_color', true ) );
        if ( ! $color ) {
            $color = '#EC7766';
        }

        $url        = get_permalink( $event_id );
        $product_id = (int) get_post_meta( $event_id, '_gh_event_product_id', true );
        $product    = ( $product_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $product_id ) : false;
        $sold_out   = $product && ! $product->is_in_stock();
        ?>
        <article class="gh-event-card" style="--gh-event-color:<?php echo esc_attr( $color ); ?>;">
            <a class="gh-event-img" href="<?php echo esc_url( $url ); ?>">
                <?php
                if ( has_post_thumbnail( $event_id ) ) {
                    echo get_the_post_thumbnail( $event_id, 'medium_large' );
                }
                ?>
            </a>
            <div class="gh-event-body">
                <span class="gh-event-date"><?php echo gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'date', 'format' => 'j M Y' ) ); ?></span>
                <h3 class="gh-event-title"><a href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( get_the_title( $event_id ) ); ?></a></h3>
                <span class="gh-event-price"><?php echo gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'price' ) ); ?></span>
                <?php if ( $sold_out ) : ?>
                    <span class="gh-event-btn gh-event-btn--soldout"><?php echo esc_html( $atts['sold_out'] ); ?></span>
                <?php else : ?>
                    <a class="gh-event-btn" href="<?php echo esc_url( $url ); ?>"><?php echo esc_html( $atts['button'] ); ?></a>
                <?php endif; ?>
            </div>
        </article>
    <?php endforeach; ?>
    </div>
    <?php
    return ob_get_clean();
}

/**
 * Single event template shortcode: [gh_single_event]
 */
add_shortcode( 'gh_single_event', 'gh_single_event_template_shortcode' );
add_shortcode( 'gh_event_template', 'gh_single_event_template_shortcode' );

function gh_single_event_template_shortcode( $atts ) {
    $atts = shortcode_atts( array(
        'id'             => 0,
        'tag'            => 'COMMUNITY EVENT, THE GROWTH HUB',
        'tier'           => 'GENERAL ADMISSION',
        'button_label'   => 'Buy Ticket, Get Instant QR Code',
        'sold_out_label' => 'Sold Out',
    ), $atts );

    $event_id = gh_event_resolve_id( $atts['id'] );
    if ( ! $event_id ) {
        return '';
    }

    $title     = get_the_title( $event_id );
    $subtitle  = get_post_meta( $event_id, 'gh_event_subtitle', true );
    if ( ! $subtitle ) {
        $subtitle = get_the_excerpt( $event_id );
    }

    $venue     = get_post_meta( $event_id, 'gh_event_venue', true );
    $price     = (float) get_post_meta( $event_id, 'gh_event_price', true );
    $color     = sanitize_hex_color( (string) get_post_meta( $event_id, 'gh_event_color', true ) );
    if ( ! $color ) {
        $color = '#EC7766';
    }

    // Format date and time.
    $date_raw  = get_post_meta( $event_id, 'gh_event_date', true );
    $date_dt   = $date_raw ? date_create_from_format( 'Y-m-d', $date_raw, wp_timezone() ) : false;
    $date_text = $date_dt ? wp_date( 'jS F Y', $date_dt->getTimestamp() ) : 'TBA';

    $time_raw  = get_post_meta( $event_id, 'gh_event_time', true );
    $time_dt   = $time_raw ? date_create_from_format( 'H:i', $time_raw, wp_timezone() ) : false;
    $time_text = $time_dt ? wp_date( 'g:i A', $time_dt->getTimestamp() ) : 'TBA';

    // Format price.
    if ( function_exists( 'wc_price' ) ) {
        $price_html = $price > 0 ? wc_price( $price ) : 'Free';
    } else {
        $price_html = $price > 0 ? '&#8358;' . number_format( $price, 2 ) : 'Free';
    }

    // Ticket inventory check.
    $product_id = (int) get_post_meta( $event_id, '_gh_event_product_id', true );
    $product    = ( $product_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $product_id ) : false;
    $sold_out   = $product && ! $product->is_in_stock();

    $form_action = $product ? get_permalink( $product->get_id() ) : '';

    ob_start();
    ?>
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
      /* Viewport breakout */
      html, body {
        overflow-x: hidden;
      }

      /* Container resets */
      body.single-gh_event .site-content {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
      }

      body.single-gh_event #primary,
      body.single-gh_event #main,
      body.single-gh_event #content .ast-container,
      body.single-gh_event .site-content .ast-container,
      body.single-gh_event .ast-article-single,
      body.single-gh_event .entry-content,
      body.single-gh_event article,
      body.single-gh_event .elementor-27304,
      body.single-gh_event .elementor-27304 .e-con,
      body.single-gh_event .elementor-27304 .e-con-inner {
        padding: 0 !important;
        margin: 0 !important;
        max-width: 100% !important;
        width: 100% !important;
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
      }

      body.single-gh_event .entry-header {
        display: none !important;
      }

      .gh-single-event-wrap {
        --gh-hero-color: <?php echo esc_attr( $color ); ?>;
        --gh-card-bg: #120A31;
        --gh-gold: #F3C343;
        --gh-muted: #A6A3B2;
        width: 100vw !important;
        position: relative !important;
        left: 50% !important;
        right: 50% !important;
        margin-left: -50vw !important;
        margin-right: -50vw !important;
        max-width: 100vw !important;
        margin-top: 0 !important;
        box-sizing: border-box !important;
      }

      .gh-single-event-wrap *,
      .gh-single-event-wrap *::before,
      .gh-single-event-wrap *::after {
        box-sizing: border-box;
      }

      /* Inner container */
      .gh-single-container {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 0;
        box-sizing: border-box;
      }

      /* Hero banner */
      .gh-single-banner {
        width: 100%;
        background-color: var(--gh-hero-color);
        color: #FFFFFF;
        padding: 50px 20px;
        box-sizing: border-box;
      }

      .gh-single-category {
        font-family: 'Poppins', sans-serif;
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.08em;
        text-transform: uppercase;
        color: #FFFFFF;
        margin-bottom: 12px;
        opacity: 0.95;
      }

      .gh-single-title {
        font-family: 'Poppins', sans-serif;
        font-size: 42px;
        font-weight: 800;
        line-height: 1.2;
        letter-spacing: -0.01em;
        color: #FFFFFF !important;
        margin: 0 0 12px 0;
      }

      .gh-single-desc {
        font-family: 'Lato', sans-serif;
        font-size: 16px;
        font-weight: 400;
        line-height: 1.6;
        color: rgba(255, 255, 255, 0.95);
        max-width: 640px;
        margin: 0;
      }

      /* Content area */
      .gh-single-body {
        width: 100%;
        background-color: #FFFFFF;
        padding: 50px 20px 80px;
        box-sizing: border-box;
      }

      /* Ticket card */
      .gh-single-card {
        background-color: var(--gh-card-bg);
        border-radius: 20px;
        padding: 30px 26px 26px;
        width: 100%;
        max-width: 350px;
        color: #FFFFFF;
        box-shadow: 0 16px 36px rgba(18, 10, 49, 0.16);
      }

      .gh-single-tier {
        font-family: 'Poppins', sans-serif;
        color: var(--gh-gold);
        font-size: 11px;
        font-weight: 700;
        letter-spacing: 0.06em;
        text-transform: uppercase;
        margin-bottom: 10px;
      }

      .gh-single-price {
        font-family: 'Poppins', sans-serif;
        font-size: 34px;
        font-weight: 800;
        color: #FFFFFF;
        line-height: 1;
        margin-bottom: 24px;
        letter-spacing: -0.01em;
      }

      .gh-single-price .woocommerce-Price-amount {
        color: #FFFFFF !important;
      }

      .gh-single-list {
        display: flex;
        flex-direction: column;
        gap: 14px;
        margin-bottom: 26px;
      }

      .gh-single-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-family: 'Lato', sans-serif;
        font-size: 14px;
        line-height: 1.4;
        gap: 12px;
      }

      .gh-single-label {
        color: var(--gh-muted);
        font-weight: 400;
        white-space: nowrap;
      }

      .gh-single-val {
        color: #FFFFFF;
        font-weight: 700;
        text-align: right;
      }

      /* Checkout button */
      .gh-single-btn {
        display: block;
        width: 100%;
        background-color: var(--gh-hero-color);
        color: #FFFFFF !important;
        border: none;
        border-radius: 999px;
        padding: 14px 20px;
        font-family: 'Poppins', sans-serif;
        font-size: 13.5px;
        font-weight: 600;
        text-align: center;
        text-decoration: none !important;
        cursor: pointer;
        box-sizing: border-box;
        transition: opacity 0.2s ease, transform 0.15s ease;
        box-shadow: 0 4px 14px rgba(0, 0, 0, 0.2);
      }

      .gh-single-btn:hover {
        opacity: 0.92;
        transform: translateY(-2px);
      }

      .gh-single-btn:disabled,
      .gh-single-btn--soldout {
        opacity: 0.5 !important;
        cursor: not-allowed !important;
        transform: none !important;
      }

      /* Responsive */
      @media (max-width: 1024px) {
        .gh-single-banner {
          padding: 40px 20px;
        }
        .gh-single-title {
          font-size: 34px;
        }
        .gh-single-body {
          padding: 40px 20px 60px;
        }
      }

      @media (max-width: 767px) {
        .gh-single-container {
          padding: 0;
        }
        .gh-single-banner {
          padding: 30px 20px;
        }
        .gh-single-category {
          font-size: 10.5px;
          margin-bottom: 10px;
        }
        .gh-single-title {
          font-size: 26px;
        }
        .gh-single-desc {
          font-size: 14.5px;
        }
        .gh-single-body {
          padding: 30px 20px 50px;
        }
        .gh-single-card {
          max-width: 100%;
          padding: 24px 20px 22px;
          border-radius: 16px;
        }
        .gh-single-price {
          font-size: 30px;
          margin-bottom: 20px;
        }
      }
    </style>

    <div class="gh-single-event-wrap">
      
      <!-- Hero Banner -->
      <header class="gh-single-banner">
        <div class="gh-single-container">
          <div class="gh-single-category"><?php echo esc_html( $atts['tag'] ); ?></div>
          <h1 class="gh-single-title"><?php echo esc_html( $title ); ?></h1>
          <?php if ( $subtitle ) : ?>
            <p class="gh-single-desc"><?php echo esc_html( $subtitle ); ?></p>
          <?php endif; ?>
        </div>
      </header>

      <!-- Event Body -->
      <main class="gh-single-body">
        <div class="gh-single-container">
          <div class="gh-single-card">
            <div class="gh-single-tier"><?php echo esc_html( $atts['tier'] ); ?></div>
            <div class="gh-single-price"><?php echo $price_html; ?></div>

            <div class="gh-single-list">
              <div class="gh-single-row">
                <span class="gh-single-label">Date</span>
                <span class="gh-single-val"><?php echo esc_html( $date_text ); ?></span>
              </div>
              <div class="gh-single-row">
                <span class="gh-single-label">Time</span>
                <span class="gh-single-val"><?php echo esc_html( $time_text ); ?></span>
              </div>
              <?php if ( $venue ) : ?>
                <div class="gh-single-row">
                  <span class="gh-single-label">Venue</span>
                  <span class="gh-single-val"><?php echo esc_html( $venue ); ?></span>
                </div>
              <?php endif; ?>
            </div>

            <!-- Checkout Form -->
            <?php if ( $sold_out ) : ?>
              <button type="button" class="gh-single-btn gh-single-btn--soldout" disabled>
                <?php echo esc_html( $atts['sold_out_label'] ); ?>
              </button>
            <?php else : ?>
              <form method="post" action="<?php echo esc_url( $form_action ); ?>">
                <input type="hidden" name="gh_event_action" value="buy">
                <input type="hidden" name="gh_event_id" value="<?php echo esc_attr( $event_id ); ?>">
                <?php wp_nonce_field( 'gh_event_buy_' . $event_id, 'gh_event_buy_nonce' ); ?>
                <button type="submit" class="gh-single-btn">
                  <?php echo esc_html( $atts['button_label'] ); ?>
                </button>
              </form>
            <?php endif; ?>

          </div>
        </div>
      </main>

    </div>
    <?php
    return ob_get_clean();
}

/**
 * Single event template redirect.
 */
add_filter( 'template_include', function ( $template ) {
    if ( ! is_singular( 'gh_event' ) ) {
        return $template;
    }

    get_header();
    ?>
    <style>
        html, body {
            overflow-x: hidden;
        }
        body.single-gh_event .site-content {
            padding-top: 0 !important;
            padding-bottom: 0 !important;
            margin: 0 !important;
        }
        .gh-event-page-wrap {
            width: 100vw;
            position: relative;
            left: 50%;
            margin-left: -50vw;
            margin-right: -50vw;
            max-width: 100vw;
            overflow-x: hidden;
            box-sizing: border-box;
        }
        .gh-event-page-wrap .elementor,
        .gh-event-page-wrap .ast-article-single,
        .gh-event-page-wrap article {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
            background: transparent !important;
            border: 0 !important;
            box-shadow: none !important;
        }
    </style>
    <div class="gh-event-page-wrap">
        <?php echo do_shortcode( '[gh_single_event]' ); ?>
    </div>
    <?php
    get_footer();
    exit;
}, 99999 );
