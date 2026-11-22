/**
 * Plugin Name: Growth Hub Events
 * Description: Event post type with details (date, time, venue, price, capacity, color, subtitle). Each event automatically gets a hidden WooCommerce ticket product, a "Get Ticket" button that goes straight to checkout, Elementor template shortcodes ([gh_single_event] and [gh_events]), and dynamic per-event color theming.
 * Version: 1.3.1
 * Author: Shina Adeleye
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * ============================================================
 * 1. EVENT POST TYPE
 *    Single events live at /event/event-name/ so that you are
 *    free to build your own listing page at /events/.
 * ============================================================
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
 * ============================================================
 * 2. EVENT DETAILS FORM (shown on the Add/Edit Event screen)
 * ============================================================
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
            <th><label for="gh_event_subtitle">Hero Subtitle / Description</label></th>
            <td>
                <textarea id="gh_event_subtitle" name="gh_event_subtitle" rows="2" class="large-text"><?php echo esc_textarea( $subtitle ); ?></textarea>
                <p class="description">Displays under the event title in the hero banner (e.g. "Karaoke, games, movies, and music, one night for the whole community.").</p>
            </td>
        </tr>
        <tr>
            <th><label for="gh_event_date">Event date</label></th>
            <td>
                <input type="date" id="gh_event_date" name="gh_event_date" value="<?php echo esc_attr( $date ); ?>">
                <p class="description">Needed for sorting upcoming events (soonest first).</p>
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
                <p class="description">Enter 0 for a free event.</p>
            </td>
        </tr>
        <tr>
            <th><label for="gh_event_capacity">Number of tickets</label></th>
            <td>
                <input type="number" min="0" id="gh_event_capacity" name="gh_event_capacity" value="<?php echo esc_attr( $capacity ); ?>" style="width:160px;">
                <p class="description">Leave blank for unlimited. When tickets run out the button changes to "Sold Out".</p>
            </td>
        </tr>
        <tr>
            <th><label for="gh_event_color">Event colour</label></th>
            <td>
                <input type="color" id="gh_event_color" name="gh_event_color" value="<?php echo esc_attr( $color ); ?>">
                <p class="description">Used for this event's hero banner, card button, and accent styling in templates.</p>
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
 * ============================================================
 * 3. TICKET PRODUCT — one hidden WooCommerce product per event,
 *    created and kept in sync automatically (price, stock, image).
 * ============================================================
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

    // Stock follows the "Number of tickets" field.
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

// Trashing an event takes its ticket product off sale.
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
 * ============================================================
 * 4. SHOW EVENT DETAILS — shortcode [gh_event field="..."]
 *    field="title"
 *    field="subtitle"
 *    field="date" format="jS F Y"
 *    field="time" format="g:i A"
 *    field="venue"
 *    field="price"
 *    field="tickets_left"
 *    field="color"
 *    field="url"
 * ============================================================
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
 * ============================================================
 * 5. GET TICKET BUTTON — shortcode [gh_event_ticket]
 * ============================================================
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

        // Make sure a guest's cart session exists before the ticket is added.
        if ( WC()->session && ! WC()->session->has_session() ) {
            WC()->session->set_customer_session_cookie( true );
        }

        // Always clear the cart first, so checkout holds ONLY the event that was just clicked.
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
 * ============================================================
 * 6. EVENT COLOUR CSS VARIABLES
 * ============================================================
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
 * ============================================================
 * 7. LISTING ORDER — Elementor Loop Query: gh_upcoming_events
 * ============================================================
 */
add_action( 'elementor/query/gh_upcoming_events', function( $query ) {
    $query->set( 'meta_key', 'gh_event_date' );
    $query->set( 'orderby', 'meta_value' );
    $query->set( 'order', 'ASC' );
} );

/**
 * ============================================================
 * 8. ADMIN LIST COLUMNS
 * ============================================================
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
 * ============================================================
 * 9. EVENT LISTING GRID — shortcode [gh_events]
 * ============================================================
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
 * ============================================================
 * 10. SINGLE EVENT TEMPLATE SHORTCODE — [gh_single_event]
 *     Renders the full hero banner & ticket card design.
 *     Hero color & button automatically use the event's colour!
 *     Fonts: Poppins for headings, Lato for body text.
 *     Fully responsive across desktop, tablet (1024px) & mobile (767px).
 *
 *     Usage in Elementor Single Event Template:
 *     [gh_single_event]
 * ============================================================
 */
add_shortcode( 'gh_single_event', 'gh_single_event_template_shortcode' );
add_shortcode( 'gh_event_template', 'gh_single_event_template_shortcode' ); // Alias

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

    // Event Data
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

    // Format Date & Time
    $date_raw  = get_post_meta( $event_id, 'gh_event_date', true );
    $date_dt   = $date_raw ? date_create_from_format( 'Y-m-d', $date_raw, wp_timezone() ) : false;
    $date_text = $date_dt ? wp_date( 'jS F Y', $date_dt->getTimestamp() ) : 'TBA';

    $time_raw  = get_post_meta( $event_id, 'gh_event_time', true );
    $time_dt   = $time_raw ? date_create_from_format( 'H:i', $time_raw, wp_timezone() ) : false;
    $time_text = $time_dt ? wp_date( 'g:i A', $time_dt->getTimestamp() ) : 'TBA';

    // Format Price
    if ( function_exists( 'wc_price' ) ) {
        $price_html = $price > 0 ? wc_price( $price ) : 'Free';
    } else {
        $price_html = $price > 0 ? '&#8358;' . number_format( $price, 2 ) : 'Free';
    }

    // WooCommerce Stock Check
    $product_id = (int) get_post_meta( $event_id, '_gh_event_product_id', true );
    $product    = ( $product_id && function_exists( 'wc_get_product' ) ) ? wc_get_product( $product_id ) : false;
    $sold_out   = $product && ! $product->is_in_stock();

    // Post the ticket form to the ticket product's page: its cart cookies survive for guests.
    $form_action = $product ? get_permalink( $product->get_id() ) : '';

    ob_start();
    ?>
    <!-- Enqueue Google Fonts: Poppins & Lato -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Lato:wght@400;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">

    <style>
      /* Viewport Breakout Technique (Matching courses pattern - zero impact on site header, logo, or nav) */
      html, body {
        overflow-x: hidden;
      }

      /* Eliminate top gap from Astra's site-content on single events */
      body.single-gh_event .site-content {
        padding-top: 0 !important;
        padding-bottom: 0 !important;
        margin-top: 0 !important;
      }

      /* Neutralize Astra post content area only (Leave site-header untouched) */
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

      /* Alignment container - Matches Elementor Inner Boxed Container (ID 8af001a: 1180px, 0 padding, centered) */
      .gh-single-container {
        width: 100%;
        max-width: 1180px;
        margin: 0 auto;
        padding: 0;
        box-sizing: border-box;
      }

      /* Full-Width Top Banner - Matches Elementor Outer Container (ID 8aa8761: 50px top/bottom, 20px left/right) */
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

      /* Content Area Below Banner - Matches Elementor Outer Container padding (20px left/right) */
      .gh-single-body {
        width: 100%;
        background-color: #FFFFFF;
        padding: 50px 20px 80px;
        box-sizing: border-box;
      }

      /* Ticket Card */
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

      /* Ticket Card CTA Button */
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

      /* Responsive Breakpoints (Matches Elementor padding_mobile: 30px 20px) */
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
      
      <!-- Top Dynamic Hero Banner -->
      <header class="gh-single-banner">
        <div class="gh-single-container">
          <div class="gh-single-category"><?php echo esc_html( $atts['tag'] ); ?></div>
          <h1 class="gh-single-title"><?php echo esc_html( $title ); ?></h1>
          <?php if ( $subtitle ) : ?>
            <p class="gh-single-desc"><?php echo esc_html( $subtitle ); ?></p>
          <?php endif; ?>
        </div>
      </header>

      <!-- Main Body with Dynamic Ticket Card -->
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

            <!-- Direct Checkout Button Form -->
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
 * ============================================================
 * 11. SINGLE EVENT TEMPLATE OVERRIDE (Same pattern as Courses)
 *     Renders single events directly between get_header() and
 *     get_footer(), eliminating Astra's container & white gap.
 * ============================================================
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


/**
 * ============================================================
 * 12. EVENT CHECKOUT — a lighter checkout when the cart holds
 *     ONLY event tickets: no account needed, fewer fields and no
 *     CartFlows returning-customer login box. Course checkouts
 *     are not affected.
 * ============================================================
 */

// True only when the cart holds nothing but event tickets.
function gh_cart_is_events_only() {
    if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
        return false;
    }
    foreach ( WC()->cart->get_cart() as $item ) {
        if ( ! get_post_meta( $item['product_id'], '_gh_event_id', true ) ) {
            return false;
        }
    }
    return true;
}

// Event tickets: no account needed, no account creation.
add_filter( 'woocommerce_checkout_registration_required', function( $required ) {
    return gh_cart_is_events_only() ? false : $required;
} );
add_filter( 'woocommerce_checkout_registration_enabled', function( $enabled ) {
    return gh_cart_is_events_only() ? false : $enabled;
} );
add_filter( 'pre_option_woocommerce_enable_guest_checkout', function( $pre ) {
    return gh_cart_is_events_only() ? 'yes' : $pre;
} );
add_filter( 'pre_option_woocommerce_enable_signup_and_login_from_checkout', function( $pre ) {
    return gh_cart_is_events_only() ? 'no' : $pre;
} );

// Event tickets: trim the checkout form to the essentials.
add_filter( 'woocommerce_checkout_fields', function( $fields ) {
    if ( ! gh_cart_is_events_only() ) {
        return $fields;
    }

    foreach ( array(
        'billing_company', 'billing_address_1', 'billing_address_2',
        'billing_city', 'billing_state', 'billing_postcode', 'billing_country',
    ) as $key ) {
        unset( $fields['billing'][ $key ] );
    }

    if ( isset( $fields['billing']['billing_phone'] ) ) {
        $fields['billing']['billing_phone']['required'] = false;
    }
    unset( $fields['order']['order_comments'] );

    return $fields;
}, 9999 );

// Event tickets: hide CartFlows's returning-customer login box and message.
add_action( 'wp_head', function() {
    if ( ! gh_cart_is_events_only() ) {
        return;
    }
    echo '<style id="gh-events-checkout-css">'
       . '.wcf-email-validation-block,'
       . '.wcf-customer-login-section{display:none!important;}'
       . '</style>';
}, 30 );


/**
 * ============================================================
 * 13. TICKETS WITH QR CODE
 *
 *     After a ticket order is paid, every ticket gets its own
 *     long random code, stored on your site. The buyer receives
 *     an email with a link to their ticket page, which shows a
 *     QR code. At the door, event staff scan the QR with a phone
 *     camera. The site looks the code up, so a QR that was made
 *     up or edited is rejected, and a ticket can only be checked
 *     in once.
 *
 *     Ticket page (buyer):  yoursite.com/?gh_pass=CODE
 *     Check-in (staff):     yoursite.com/?gh_verify=CODE
 * ============================================================
 */

// A failure in ticket code must never block a payment.
function gh_ticket_safe( $callback, ...$args ) {
    try {
        return call_user_func_array( $callback, $args );
    } catch ( Throwable $e ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            wc_get_logger()->error( $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine(), array( 'source' => 'gh-events' ) );
        }
        return null;
    }
}

/* ---------- 13a. Event staff role ---------- */

add_action( 'init', function() {
    if ( ! get_role( 'gh_event_staff' ) ) {
        add_role( 'gh_event_staff', 'Event Staff', array( 'read' => true, 'gh_check_in' => true ) );
    }
    foreach ( array( 'administrator', 'shop_manager' ) as $role_name ) {
        $role = get_role( $role_name );
        if ( $role && ! $role->has_cap( 'gh_check_in' ) ) {
            $role->add_cap( 'gh_check_in' );
        }
    }
} );

function gh_ticket_user_can_check_in() {
    return is_user_logged_in() && ( current_user_can( 'gh_check_in' ) || current_user_can( 'manage_options' ) );
}

/* ---------- 13b. Ticket records (Events > Tickets) ---------- */

add_action( 'init', function() {
    register_post_type( 'gh_ticket', array(
        'labels'              => array(
            'name'          => 'Tickets',
            'singular_name' => 'Ticket',
            'menu_name'     => 'Tickets',
            'all_items'     => 'Tickets (Attendees)',
            'edit_item'     => 'View Ticket',
            'search_items'  => 'Search tickets',
        ),
        'public'              => false,
        'publicly_queryable'  => false,
        'exclude_from_search' => true,
        'show_ui'             => true,
        'show_in_menu'        => 'edit.php?post_type=gh_event',
        'has_archive'         => false,
        'rewrite'             => false,
        'supports'            => array( 'title' ),
        'capabilities'        => array( 'create_posts' => 'do_not_allow' ),
        'map_meta_cap'        => true,
    ) );
} );

function gh_ticket_status_labels() {
    return array(
        'valid'      => 'Valid',
        'checked_in' => 'Checked in',
        'void'       => 'Void (cancelled or refunded)',
    );
}

function gh_ticket_clean_code( $raw ) {
    return strtolower( preg_replace( '/[^a-f0-9]/i', '', is_scalar( $raw ) ? (string) $raw : '' ) );
}

function gh_ticket_find_by_code( $code ) {
    $code = gh_ticket_clean_code( $code );
    if ( 24 !== strlen( $code ) ) {
        return 0;
    }
    $ids = get_posts( array(
        'post_type'   => 'gh_ticket',
        'post_status' => 'any',
        'numberposts' => 1,
        'fields'      => 'ids',
        'meta_key'    => '_gh_ticket_code',
        'meta_value'  => $code,
    ) );
    return $ids ? (int) $ids[0] : 0;
}

function gh_ticket_tickets_for_order( $order_id ) {
    return get_posts( array(
        'post_type'   => 'gh_ticket',
        'post_status' => 'any',
        'numberposts' => -1,
        'fields'      => 'ids',
        'orderby'     => 'ID',
        'order'       => 'ASC',
        'meta_key'    => '_gh_ticket_order',
        'meta_value'  => (int) $order_id,
    ) );
}

function gh_ticket_event_color( $event_id ) {
    $color = sanitize_hex_color( (string) get_post_meta( $event_id, 'gh_event_color', true ) );
    return $color ? $color : '#EC7766';
}

function gh_ticket_create( $order, $event_id ) {
    try {
        $code = bin2hex( random_bytes( 12 ) ); // 24 characters, 96 bits: impossible to guess.
    } catch ( Throwable $e ) {
        $code = strtolower( wp_generate_password( 24, false, false ) );
        $code = preg_replace( '/[^a-f0-9]/', 'a', $code );
    }

    $name = trim( $order->get_billing_first_name() . ' ' . $order->get_billing_last_name() );

    $ticket_id = wp_insert_post( array(
        'post_type'   => 'gh_ticket',
        'post_status' => 'publish',
        'post_title'  => ( $name ? $name : $order->get_billing_email() ) . ' - ' . get_the_title( $event_id ) . ' - #' . strtoupper( substr( $code, 0, 8 ) ),
    ) );
    if ( is_wp_error( $ticket_id ) || ! $ticket_id ) {
        return 0;
    }

    update_post_meta( $ticket_id, '_gh_ticket_code', $code );
    update_post_meta( $ticket_id, '_gh_ticket_event', (int) $event_id );
    update_post_meta( $ticket_id, '_gh_ticket_order', (int) $order->get_id() );
    update_post_meta( $ticket_id, '_gh_ticket_name', $name );
    update_post_meta( $ticket_id, '_gh_ticket_email', $order->get_billing_email() );
    update_post_meta( $ticket_id, '_gh_ticket_status', 'valid' );

    return (int) $ticket_id;
}

/* ---------- 13c. Issue tickets when the order is paid ---------- */

function gh_ticket_issue_for_order( $order_id ) {
    $order = wc_get_order( $order_id );
    if ( ! $order || $order->get_meta( '_gh_tickets_issued' ) ) {
        return;
    }

    // Which event tickets are in this order?
    $wanted = array();
    foreach ( $order->get_items() as $item ) {
        $event_id = (int) get_post_meta( $item->get_product_id(), '_gh_event_id', true );
        if ( $event_id && 'gh_event' === get_post_type( $event_id ) ) {
            $wanted[] = array( $event_id, max( 1, (int) $item->get_quantity() ) );
        }
    }
    if ( ! $wanted ) {
        return;
    }

    // Set the flag first, so two payment hooks firing together cannot create duplicates.
    $order->update_meta_data( '_gh_tickets_issued', 1 );
    $order->save();

    $created = 0;
    foreach ( $wanted as $row ) {
        for ( $i = 0; $i < $row[1]; $i++ ) {
            if ( gh_ticket_create( $order, $row[0] ) ) {
                $created++;
            }
        }
    }

    if ( ! $created ) {
        $order->delete_meta_data( '_gh_tickets_issued' );
        $order->add_order_note( 'Event tickets could not be created for this order. Use "Resend event ticket email" after checking Events > Tickets.' );
        $order->save();
        return;
    }

    $order->add_order_note( $created . ' event ticket(s) created.' );
    gh_ticket_send_email( $order );
}
add_action( 'woocommerce_order_status_processing', function( $id ) { gh_ticket_safe( 'gh_ticket_issue_for_order', $id ); }, 50 );
add_action( 'woocommerce_order_status_completed', function( $id ) { gh_ticket_safe( 'gh_ticket_issue_for_order', $id ); }, 50 );

// A refunded, cancelled or failed order voids its tickets (unless already used).
function gh_ticket_void_for_order( $order_id ) {
    foreach ( gh_ticket_tickets_for_order( $order_id ) as $ticket_id ) {
        if ( 'checked_in' !== get_post_meta( $ticket_id, '_gh_ticket_status', true ) ) {
            update_post_meta( $ticket_id, '_gh_ticket_status', 'void' );
        }
    }
}
foreach ( array( 'refunded', 'cancelled', 'failed' ) as $gh_ticket_void_status ) {
    add_action( 'woocommerce_order_status_' . $gh_ticket_void_status, function( $id ) { gh_ticket_safe( 'gh_ticket_void_for_order', $id ); } );
}

/* ---------- 13d. Ticket email ---------- */

function gh_ticket_send_email( $order, $force = false ) {
    if ( ! $force && $order->get_meta( '_gh_tickets_emailed' ) ) {
        return;
    }

    $to = $order->get_billing_email();
    if ( ! is_email( $to ) ) {
        return;
    }

    $blocks = '';
    $titles = array();
    $count  = 0;

    foreach ( gh_ticket_tickets_for_order( $order->get_id() ) as $ticket_id ) {
        if ( 'void' === get_post_meta( $ticket_id, '_gh_ticket_status', true ) ) {
            continue;
        }
        $code     = get_post_meta( $ticket_id, '_gh_ticket_code', true );
        $event_id = (int) get_post_meta( $ticket_id, '_gh_ticket_event', true );
        $color    = gh_ticket_event_color( $event_id );
        $link     = add_query_arg( 'gh_pass', $code, home_url( '/' ) );
        $when     = trim( gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'date' ) ) . ' ' . gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'time' ) ) );
        $venue    = gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'venue' ) );

        $titles[] = get_the_title( $event_id );
        $count++;

        $blocks .= '<div style="border-left:5px solid ' . esc_attr( $color ) . ';background:#faf9ff;padding:14px 16px;margin:16px 0;border-radius:6px;">'
                 . '<div style="font-size:17px;font-weight:700;">' . esc_html( get_the_title( $event_id ) ) . '</div>'
                 . ( $when ? '<div style="margin-top:4px;">' . $when . '</div>' : '' )
                 . ( $venue ? '<div>' . $venue . '</div>' : '' )
                 . '<div style="font-size:12px;color:#777;margin-top:4px;">Ticket #' . esc_html( strtoupper( substr( $code, 0, 8 ) ) ) . '</div>'
                 . '<p style="margin:14px 0 0;"><a href="' . esc_url( $link ) . '" style="background:' . esc_attr( $color ) . ';color:#fff;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:700;display:inline-block;">View your ticket</a></p>'
                 . '</div>';
    }

    if ( ! $count ) {
        return;
    }

    $first = $order->get_billing_first_name() ? $order->get_billing_first_name() : 'there';
    $html  = '<div style="font-family:Arial,Helvetica,sans-serif;max-width:560px;margin:0 auto;color:#222;line-height:1.5;">'
           . '<p>Hi ' . esc_html( $first ) . ',</p>'
           . '<p>Thank you, your booking is confirmed. Open your ticket below and show its QR code at the entrance.</p>'
           . $blocks
           . '<p style="color:#666;font-size:13px;">Tip: open your ticket now and take a screenshot, in case the signal is weak at the venue. Each ticket can only be used once, so please do not share it.</p>'
           . '</div>';

    $subject = 1 === count( array_unique( $titles ) ) ? 'Your ticket for ' . $titles[0] : 'Your event tickets';

    // Send through WooCommerce's mailer, so the ticket email comes from the same sender
    // address and name as your order emails (WooCommerce > Settings > Emails).
    $sent = false;
    if ( function_exists( 'WC' ) && WC()->mailer() ) {
        $message = WC()->mailer()->wrap_message( 'Your event ticket', $html );
        $email   = new WC_Email();
        $sent    = $email->send( $to, $subject, $message, "Content-Type: text/html\r\n", array() );
    }

    // Fallback: plain WordPress mail, still using WooCommerce's sender details.
    if ( ! $sent ) {
        $from_name    = wp_specialchars_decode( (string) get_option( 'woocommerce_email_from_name' ), ENT_QUOTES );
        $from_address = sanitize_email( (string) get_option( 'woocommerce_email_from_address' ) );
        $headers      = array( 'Content-Type: text/html; charset=UTF-8' );
        if ( $from_address ) {
            $headers[] = 'From: ' . ( $from_name ? $from_name : get_bloginfo( 'name' ) ) . ' <' . $from_address . '>';
        }
        $sent = wp_mail( $to, $subject, $html, $headers );
    }

    if ( $sent ) {
        $order->update_meta_data( '_gh_tickets_emailed', 1 );
        $order->add_order_note( 'Event ticket email sent to ' . $to . '.' );
    } else {
        $order->add_order_note( 'Event ticket email FAILED to send to ' . $to . '. Check your SMTP email settings, then use "Resend event ticket email".' );
    }
    $order->save();
}

// Resend from the WooCommerce order screen.
add_filter( 'woocommerce_order_actions', function( $actions ) {
    global $theorder;
    if ( $theorder && $theorder->get_meta( '_gh_tickets_issued' ) ) {
        $actions['gh_resend_tickets'] = 'Resend event ticket email';
    }
    return $actions;
} );
add_action( 'woocommerce_order_action_gh_resend_tickets', function( $order ) {
    gh_ticket_safe( 'gh_ticket_send_email', $order, true );
} );

// Also show the ticket link on the order confirmation page, when the theme shows it.
add_action( 'woocommerce_thankyou', function( $order_id ) {
    $links = '';
    foreach ( gh_ticket_tickets_for_order( $order_id ) as $ticket_id ) {
        if ( 'void' === get_post_meta( $ticket_id, '_gh_ticket_status', true ) ) {
            continue;
        }
        $code   = get_post_meta( $ticket_id, '_gh_ticket_code', true );
        $event  = (int) get_post_meta( $ticket_id, '_gh_ticket_event', true );
        $links .= '<p><a href="' . esc_url( add_query_arg( 'gh_pass', $code, home_url( '/' ) ) ) . '" style="background:' . esc_attr( gh_ticket_event_color( $event ) ) . ';color:#fff;padding:12px 22px;border-radius:999px;text-decoration:none;font-weight:700;display:inline-block;">View your ticket: ' . esc_html( get_the_title( $event ) ) . '</a></p>';
    }
    if ( $links ) {
        echo '<div style="margin:24px 0;"><h3>Your event ticket</h3><p>We have also emailed it to you. Show the QR code at the entrance.</p>' . $links . '</div>';
    }
}, 20 );

/* ---------- 13e. Ticket page (buyer) and check-in page (staff) ---------- */

// Prints a complete standalone page and stops. Not cached, not indexed by search engines.
function gh_ticket_page( $title, $body, $status = 200 ) {
    status_header( $status );
    nocache_headers();
    header( 'Content-Type: text/html; charset=utf-8' );
    header( 'X-Robots-Tag: noindex, nofollow' );
    ?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?php echo esc_html( $title ); ?></title>
<style>
body{margin:0;background:#f3f1fa;font-family:-apple-system,BlinkMacSystemFont,"Segoe UI",Roboto,Helvetica,Arial,sans-serif;color:#1b1b2f;}
.gh-t-wrap{max-width:440px;margin:0 auto;padding:20px 16px 40px;}
.gh-t-card{background:#fff;border-radius:18px;overflow:hidden;box-shadow:0 8px 28px rgba(18,10,49,.14);}
.gh-t-head{padding:22px 22px 18px;color:#fff;}
.gh-t-pad{padding:22px;}
.gh-t-label{font-size:11px;letter-spacing:.08em;text-transform:uppercase;font-weight:700;opacity:.9;}
h1{margin:6px 0 0;font-size:22px;line-height:1.25;}
.gh-t-qr{display:flex;justify-content:center;padding:6px 0 14px;}
.gh-t-qr img,.gh-t-qr canvas{max-width:100%;height:auto;}
.gh-t-row{display:flex;justify-content:space-between;gap:12px;padding:9px 0;border-bottom:1px solid #eee;font-size:14px;}
.gh-t-row span:first-child{color:#777;}
.gh-t-row span:last-child{font-weight:700;text-align:right;}
.gh-t-badge{display:inline-block;padding:6px 14px;border-radius:999px;font-size:13px;font-weight:700;color:#fff;}
.gh-ok{background:#1e8e3e;}.gh-used{background:#b26a00;}.gh-bad{background:#c5221f;}
.gh-t-banner{padding:20px 22px;color:#fff;font-weight:800;font-size:21px;text-align:center;}
.gh-t-warn{background:#fff4d6;color:#7a5200;padding:10px 22px;font-size:13px;font-weight:600;}
.gh-t-btn{display:block;width:100%;border:0;border-radius:999px;padding:15px;font-size:16px;font-weight:700;color:#fff;background:#1e8e3e;cursor:pointer;margin-top:16px;}
.gh-t-note{font-size:12.5px;color:#777;text-align:center;margin:14px 0 0;}
.gh-t-link{color:#6C4CE0;font-weight:700;}
</style>
</head>
<body><div class="gh-t-wrap"><?php echo $body; ?></div></body>
</html>
    <?php
    exit;
}

function gh_ticket_row( $label, $value_html ) {
    if ( '' === trim( (string) $value_html ) ) {
        return '';
    }
    return '<div class="gh-t-row"><span>' . esc_html( $label ) . '</span><span>' . $value_html . '</span></div>';
}

function gh_ticket_message_page( $heading, $text, $status = 200, $extra_html = '' ) {
    gh_ticket_page(
        $heading,
        '<div class="gh-t-card"><div class="gh-t-pad"><h1>' . esc_html( $heading ) . '</h1><p>' . esc_html( $text ) . '</p>' . $extra_html . '</div></div>',
        $status
    );
}

// What the buyer sees: their ticket and QR code.
function gh_ticket_show_pass( $raw ) {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }

    $code      = gh_ticket_clean_code( $raw );
    $ticket_id = gh_ticket_find_by_code( $code );

    if ( ! $ticket_id ) {
        gh_ticket_message_page( 'Ticket not found', 'This link is not valid. Please use the link in your ticket email, or contact the organisers.', 404 );
    }

    $event_id = (int) get_post_meta( $ticket_id, '_gh_ticket_event', true );
    $status   = get_post_meta( $ticket_id, '_gh_ticket_status', true );
    $name     = get_post_meta( $ticket_id, '_gh_ticket_name', true );
    $ref      = strtoupper( substr( $code, 0, 8 ) );
    $verify   = add_query_arg( 'gh_verify', $code, home_url( '/' ) );

    if ( 'checked_in' === $status ) {
        $badge_class = 'gh-used';
        $badge_text  = 'Already used';
    } elseif ( 'void' === $status ) {
        $badge_class = 'gh-bad';
        $badge_text  = 'Cancelled';
    } else {
        $badge_class = 'gh-ok';
        $badge_text  = 'Valid ticket';
    }

    ob_start();
    ?>
    <div class="gh-t-card">
      <div class="gh-t-head" style="background:<?php echo esc_attr( gh_ticket_event_color( $event_id ) ); ?>;">
        <div class="gh-t-label">Event ticket</div>
        <h1><?php echo esc_html( get_the_title( $event_id ) ); ?></h1>
      </div>
      <div class="gh-t-pad">
        <p style="text-align:center;margin:0 0 14px;"><span class="gh-t-badge <?php echo esc_attr( $badge_class ); ?>"><?php echo esc_html( $badge_text ); ?></span></p>

        <?php if ( 'void' !== $status ) : ?>
          <div class="gh-t-qr"><div id="gh-qr"></div></div>
          <p class="gh-t-note" style="margin:0 0 12px;">Show this QR code at the entrance.</p>
        <?php else : ?>
          <p class="gh-t-note">This ticket was cancelled or refunded and can no longer be used.</p>
        <?php endif; ?>

        <?php
        echo gh_ticket_row( 'Name', esc_html( $name ) );
        echo gh_ticket_row( 'Date', gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'date' ) ) );
        echo gh_ticket_row( 'Time', gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'time' ) ) );
        echo gh_ticket_row( 'Venue', gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'venue' ) ) );
        echo gh_ticket_row( 'Ticket no.', esc_html( $ref ) );
        ?>

        <p class="gh-t-note">Tip: take a screenshot now, in case the signal is weak at the venue. Each ticket can only be used once.</p>
      </div>
    </div>

    <?php if ( 'void' !== $status ) : ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js"></script>
    <script>
    (function () {
      var el = document.getElementById('gh-qr');
      try {
        new QRCode(el, { text: <?php echo wp_json_encode( $verify ); ?>, width: 230, height: 230, correctLevel: QRCode.CorrectLevel.M });
      } catch (e) {
        el.innerHTML = '<p style="font-size:13px;color:#777;text-align:center;">The QR code could not load. Show this ticket number instead: <b><?php echo esc_js( $ref ); ?></b></p>';
      }
    })();
    </script>
    <?php endif; ?>
    <?php
    gh_ticket_page( 'Your ticket', ob_get_clean() );
}

// Marks a ticket as used. Atomic: if two staff scan the same ticket at once, only one succeeds.
function gh_ticket_check_in( $ticket_id ) {
    global $wpdb;

    $updated = $wpdb->query( $wpdb->prepare(
        "UPDATE {$wpdb->postmeta} SET meta_value = %s WHERE post_id = %d AND meta_key = %s AND meta_value = %s",
        'checked_in',
        $ticket_id,
        '_gh_ticket_status',
        'valid'
    ) );
    wp_cache_delete( $ticket_id, 'post_meta' );

    if ( 1 !== (int) $updated ) {
        return false;
    }

    update_post_meta( $ticket_id, '_gh_ticket_checked_in_at', time() );
    update_post_meta( $ticket_id, '_gh_ticket_checked_in_by', get_current_user_id() );
    return true;
}

// What staff see after scanning: valid, already used, or not valid.
function gh_ticket_show_verify( $raw ) {
    if ( ! defined( 'DONOTCACHEPAGE' ) ) {
        define( 'DONOTCACHEPAGE', true );
    }

    $code = gh_ticket_clean_code( $raw );
    $self = add_query_arg( 'gh_verify', $code, home_url( '/' ) );

    // Only event staff may see ticket details or check people in.
    if ( ! gh_ticket_user_can_check_in() ) {
        gh_ticket_message_page(
            'Event staff only',
            'This ticket can only be checked by event staff. If you are staff, please log in.',
            200,
            '<p><a class="gh-t-link" href="' . esc_url( wp_login_url( $self ) ) . '">Staff log in</a></p>'
        );
    }

    $ticket_id = gh_ticket_find_by_code( $code );
    if ( ! $ticket_id ) {
        gh_ticket_page(
            'Not valid',
            '<div class="gh-t-card"><div class="gh-t-banner gh-bad">NOT VALID</div><div class="gh-t-pad"><p><b>No ticket matches this code.</b> It may be fake or edited. Do not admit.</p></div></div>',
            404
        );
    }

    $just_checked_in = false;
    if ( 'POST' === ( $_SERVER['REQUEST_METHOD'] ?? '' ) && isset( $_POST['gh_ticket_checkin'] )
        && wp_verify_nonce( $_POST['gh_ticket_nonce'] ?? '', 'gh_ticket_checkin_' . $ticket_id ) ) {
        $just_checked_in = gh_ticket_check_in( $ticket_id );
    }

    $event_id = (int) get_post_meta( $ticket_id, '_gh_ticket_event', true );
    $status   = get_post_meta( $ticket_id, '_gh_ticket_status', true );
    $name     = get_post_meta( $ticket_id, '_gh_ticket_name', true );
    $ref      = strtoupper( substr( $code, 0, 8 ) );

    $event_date = get_post_meta( $event_id, 'gh_event_date', true );
    $wrong_day  = $event_date && $event_date !== wp_date( 'Y-m-d' );

    ob_start();
    ?>
    <div class="gh-t-card">
      <?php if ( $just_checked_in ) : ?>
        <div class="gh-t-banner gh-ok">CHECKED IN &#10003;</div>
      <?php elseif ( 'valid' === $status ) : ?>
        <div class="gh-t-banner gh-ok">VALID TICKET</div>
      <?php elseif ( 'checked_in' === $status ) : ?>
        <div class="gh-t-banner gh-used">ALREADY USED</div>
      <?php else : ?>
        <div class="gh-t-banner gh-bad">VOID - DO NOT ADMIT</div>
      <?php endif; ?>

      <?php if ( $wrong_day && 'void' !== $status ) : ?>
        <div class="gh-t-warn">This ticket is for <?php echo gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'date' ) ); ?>, which is not today.</div>
      <?php endif; ?>

      <div class="gh-t-pad">
        <h1 style="font-size:20px;margin:0 0 10px;"><?php echo esc_html( get_the_title( $event_id ) ); ?></h1>
        <?php
        echo gh_ticket_row( 'Name', esc_html( $name ) );
        echo gh_ticket_row( 'Date', gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'date' ) ) );
        echo gh_ticket_row( 'Time', gh_event_field_shortcode( array( 'id' => $event_id, 'field' => 'time' ) ) );
        echo gh_ticket_row( 'Ticket no.', esc_html( $ref ) );
        ?>

        <?php if ( 'checked_in' === $status ) :
            $at   = (int) get_post_meta( $ticket_id, '_gh_ticket_checked_in_at', true );
            $by   = (int) get_post_meta( $ticket_id, '_gh_ticket_checked_in_by', true );
            $user = $by ? get_userdata( $by ) : false;
            ?>
            <?php if ( $at ) : ?>
              <p class="gh-t-note" style="color:#7a5200;font-weight:600;">Checked in at <?php echo esc_html( wp_date( 'g:i A, j M Y', $at ) ); ?><?php echo $user ? ' by ' . esc_html( $user->display_name ) : ''; ?>.</p>
            <?php endif; ?>
        <?php elseif ( 'valid' === $status ) : ?>
            <form method="post" action="<?php echo esc_url( $self ); ?>">
              <input type="hidden" name="gh_ticket_checkin" value="1">
              <input type="hidden" name="gh_ticket_nonce" value="<?php echo esc_attr( wp_create_nonce( 'gh_ticket_checkin_' . $ticket_id ) ); ?>">
              <button type="submit" class="gh-t-btn">Check in</button>
            </form>
        <?php endif; ?>
      </div>
    </div>
    <?php
    gh_ticket_page( 'Ticket check', ob_get_clean() );
}

add_action( 'template_redirect', function() {
    if ( isset( $_GET['gh_pass'] ) ) {
        gh_ticket_safe( 'gh_ticket_show_pass', wp_unslash( $_GET['gh_pass'] ) );
    } elseif ( isset( $_GET['gh_verify'] ) ) {
        gh_ticket_safe( 'gh_ticket_show_verify', wp_unslash( $_GET['gh_verify'] ) );
    }
}, 1 );

/* ---------- 13f. Admin: attendee list (Events > Tickets) ---------- */

add_filter( 'manage_gh_ticket_posts_columns', function( $columns ) {
    return array(
        'cb'        => isset( $columns['cb'] ) ? $columns['cb'] : '',
        'title'     => 'Ticket',
        'gh_event'  => 'Event',
        'gh_email'  => 'Buyer email',
        'gh_status' => 'Status',
        'gh_when'   => 'Checked in',
    );
} );

add_action( 'manage_gh_ticket_posts_custom_column', function( $column, $post_id ) {
    switch ( $column ) {
        case 'gh_event':
            echo esc_html( get_the_title( (int) get_post_meta( $post_id, '_gh_ticket_event', true ) ) );
            break;
        case 'gh_email':
            echo esc_html( get_post_meta( $post_id, '_gh_ticket_email', true ) );
            break;
        case 'gh_status':
            $labels = gh_ticket_status_labels();
            $status = get_post_meta( $post_id, '_gh_ticket_status', true );
            echo esc_html( isset( $labels[ $status ] ) ? $labels[ $status ] : $status );
            break;
        case 'gh_when':
            $at = (int) get_post_meta( $post_id, '_gh_ticket_checked_in_at', true );
            echo $at ? esc_html( wp_date( 'j M Y, g:i A', $at ) ) : '—';
            break;
    }
}, 10, 2 );

add_action( 'restrict_manage_posts', function( $post_type ) {
    if ( 'gh_ticket' !== $post_type ) {
        return;
    }
    $events   = get_posts( array(
        'post_type'   => 'gh_event',
        'post_status' => array( 'publish', 'draft', 'private' ),
        'numberposts' => -1,
        'orderby'     => 'title',
        'order'       => 'ASC',
    ) );
    $selected = isset( $_GET['gh_event_filter'] ) ? (int) $_GET['gh_event_filter'] : 0;

    echo '<select name="gh_event_filter"><option value="">All events</option>';
    foreach ( $events as $event ) {
        echo '<option value="' . esc_attr( $event->ID ) . '"' . selected( $selected, $event->ID, false ) . '>' . esc_html( $event->post_title ) . '</option>';
    }
    echo '</select>';
} );

add_action( 'pre_get_posts', function( $query ) {
    if ( is_admin() && $query->is_main_query() && 'gh_ticket' === $query->get( 'post_type' ) && ! empty( $_GET['gh_event_filter'] ) ) {
        $query->set( 'meta_key', '_gh_ticket_event' );
        $query->set( 'meta_value', (int) $_GET['gh_event_filter'] );
    }
} );

add_action( 'add_meta_boxes', function() {
    add_meta_box( 'gh_ticket_details', 'Ticket Details', 'gh_ticket_render_meta_box', 'gh_ticket', 'normal', 'high' );
} );

function gh_ticket_render_meta_box( $post ) {
    wp_nonce_field( 'gh_ticket_save_' . $post->ID, 'gh_ticket_save_nonce' );

    $code     = get_post_meta( $post->ID, '_gh_ticket_code', true );
    $status   = get_post_meta( $post->ID, '_gh_ticket_status', true );
    $event_id = (int) get_post_meta( $post->ID, '_gh_ticket_event', true );
    $order_id = (int) get_post_meta( $post->ID, '_gh_ticket_order', true );
    ?>
    <table class="form-table">
        <tr><th>Event</th><td><?php echo esc_html( get_the_title( $event_id ) ); ?></td></tr>
        <tr><th>Buyer</th><td><?php echo esc_html( get_post_meta( $post->ID, '_gh_ticket_name', true ) ); ?> (<?php echo esc_html( get_post_meta( $post->ID, '_gh_ticket_email', true ) ); ?>)</td></tr>
        <tr><th>Order</th><td>#<?php echo (int) $order_id; ?></td></tr>
        <tr><th>Ticket page</th><td><a href="<?php echo esc_url( add_query_arg( 'gh_pass', $code, home_url( '/' ) ) ); ?>" target="_blank" rel="noopener">Open the buyer's ticket page</a></td></tr>
        <tr>
            <th><label for="gh_ticket_status">Status</label></th>
            <td>
                <select id="gh_ticket_status" name="gh_ticket_status">
                    <?php foreach ( gh_ticket_status_labels() as $key => $label ) : ?>
                        <option value="<?php echo esc_attr( $key ); ?>" <?php selected( $status, $key ); ?>><?php echo esc_html( $label ); ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="description">Change this only to fix a mistake, for example to undo a check-in.</p>
            </td>
        </tr>
    </table>
    <?php
}

add_action( 'save_post_gh_ticket', function( $post_id ) {
    if ( ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) || wp_is_post_revision( $post_id ) ) {
        return;
    }
    if ( ! isset( $_POST['gh_ticket_save_nonce'] ) || ! wp_verify_nonce( $_POST['gh_ticket_save_nonce'], 'gh_ticket_save_' . $post_id ) ) {
        return;
    }
    if ( ! current_user_can( 'manage_options' ) ) {
        return;
    }

    $status = isset( $_POST['gh_ticket_status'] ) ? sanitize_key( wp_unslash( $_POST['gh_ticket_status'] ) ) : '';
    if ( ! array_key_exists( $status, gh_ticket_status_labels() ) ) {
        return;
    }

    update_post_meta( $post_id, '_gh_ticket_status', $status );
    if ( 'valid' === $status ) {
        delete_post_meta( $post_id, '_gh_ticket_checked_in_at' );
        delete_post_meta( $post_id, '_gh_ticket_checked_in_by' );
    }
} );