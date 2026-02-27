<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'PALAPLAST_EVENT_START_META_KEY', '_palaplast_event_start_date' );
define( 'PALAPLAST_EVENT_END_META_KEY', '_palaplast_event_end_date' );

add_action( 'init', 'palaplast_register_event_post_type' );
add_action( 'add_meta_boxes', 'palaplast_register_event_meta_box' );
add_action( 'save_post_event', 'palaplast_save_event_meta', 10, 2 );
add_action( 'admin_notices', 'palaplast_render_event_admin_notice' );
add_filter( 'redirect_post_location', 'palaplast_add_event_validation_error_to_redirect', 10, 2 );
add_filter( 'manage_event_posts_columns', 'palaplast_add_event_admin_columns' );
add_action( 'manage_event_posts_custom_column', 'palaplast_render_event_admin_columns', 10, 2 );
add_filter( 'the_content', 'palaplast_render_event_dates_on_single' );
add_action( 'pre_get_posts', 'palaplast_set_event_admin_default_sort' );
add_action( 'pre_get_posts', 'palaplast_set_event_archive_default_sort' );
add_filter( 'posts_clauses', 'palaplast_apply_event_chronological_sort', 10, 2 );
add_shortcode( 'palaplast_events', 'palaplast_render_events_shortcode' );

function palaplast_register_event_post_type() {
	$labels = array(
		'name'               => __( 'Events', 'palaplast' ),
		'singular_name'      => __( 'Event', 'palaplast' ),
		'menu_name'          => __( 'Events', 'palaplast' ),
		'add_new'            => __( 'Add New', 'palaplast' ),
		'add_new_item'       => __( 'Add New Event', 'palaplast' ),
		'edit_item'          => __( 'Edit Event', 'palaplast' ),
		'new_item'           => __( 'New Event', 'palaplast' ),
		'view_item'          => __( 'View Event', 'palaplast' ),
		'view_items'         => __( 'View Events', 'palaplast' ),
		'search_items'       => __( 'Search Events', 'palaplast' ),
		'not_found'          => __( 'No events found.', 'palaplast' ),
		'not_found_in_trash' => __( 'No events found in Trash.', 'palaplast' ),
		'all_items'          => __( 'Events', 'palaplast' ),
	);

	register_post_type(
		'event',
		array(
			'labels'          => $labels,
			'public'          => true,
			'publicly_queryable' => true,
			'show_ui'         => true,
			'show_in_menu'    => true,
			'has_archive'     => true,
			'rewrite'         => array( 'slug' => 'events' ),
			'supports'        => array( 'title', 'editor', 'thumbnail' ),
			'show_in_rest'    => true,
			'menu_position'   => 25,
			'menu_icon'       => 'dashicons-calendar-alt',
		)
	);
}

function palaplast_register_event_meta_box() {
	add_meta_box( 'palaplast-event-dates', __( 'Event Dates', 'palaplast' ), 'palaplast_render_event_meta_box', 'event', 'normal', 'default' );
}

function palaplast_render_event_meta_box( $post ) {
	$start = get_post_meta( $post->ID, PALAPLAST_EVENT_START_META_KEY, true );
	$end   = get_post_meta( $post->ID, PALAPLAST_EVENT_END_META_KEY, true );

	wp_nonce_field( 'palaplast_event_dates_nonce', 'palaplast_event_dates_nonce' );
	?>
	<p>
		<label for="palaplast_event_start_date"><strong><?php esc_html_e( 'Start Date', 'palaplast' ); ?></strong></label><br />
		<input type="datetime-local" id="palaplast_event_start_date" name="palaplast_event_start_date" value="<?php echo esc_attr( palaplast_format_event_datetime_for_input( $start ) ); ?>" required />
	</p>
	<p>
		<label for="palaplast_event_end_date"><strong><?php esc_html_e( 'End Date', 'palaplast' ); ?></strong></label><br />
		<input type="datetime-local" id="palaplast_event_end_date" name="palaplast_event_end_date" value="<?php echo esc_attr( palaplast_format_event_datetime_for_input( $end ) ); ?>" required />
	</p>
	<?php
}

function palaplast_save_event_meta( $post_id, $post ) {
	if ( ! isset( $_POST['palaplast_event_dates_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['palaplast_event_dates_nonce'] ) ), 'palaplast_event_dates_nonce' ) ) {
		return;
	}

	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
		return;
	}

	if ( wp_is_post_revision( $post_id ) ) {
		return;
	}

	if ( ! current_user_can( 'edit_post', $post_id ) ) {
		return;
	}

	$validation = palaplast_validate_event_dates_from_request();
	if ( is_wp_error( $validation ) ) {
		palaplast_set_event_admin_error( $validation->get_error_code() );

		if ( 'publish' === $post->post_status ) {
			remove_action( 'save_post_event', 'palaplast_save_event_meta', 10 );
			wp_update_post(
				array(
					'ID'          => $post_id,
					'post_status' => 'draft',
				)
			);
			add_action( 'save_post_event', 'palaplast_save_event_meta', 10, 2 );
		}

		return;
	}

	update_post_meta( $post_id, PALAPLAST_EVENT_START_META_KEY, $validation['start'] );
	update_post_meta( $post_id, PALAPLAST_EVENT_END_META_KEY, $validation['end'] );
}

function palaplast_validate_event_dates_from_request() {
	$start_raw = isset( $_POST['palaplast_event_start_date'] ) ? sanitize_text_field( wp_unslash( $_POST['palaplast_event_start_date'] ) ) : '';
	$end_raw   = isset( $_POST['palaplast_event_end_date'] ) ? sanitize_text_field( wp_unslash( $_POST['palaplast_event_end_date'] ) ) : '';

	if ( '' === $start_raw || '' === $end_raw ) {
		return new WP_Error( 'missing_dates', __( 'Start Date and End Date are required.', 'palaplast' ) );
	}

	$timezone   = wp_timezone();
	$start_date = date_create_immutable_from_format( 'Y-m-d\TH:i', $start_raw, $timezone );
	$end_date   = date_create_immutable_from_format( 'Y-m-d\TH:i', $end_raw, $timezone );

	if ( ! $start_date || ! $end_date ) {
		return new WP_Error( 'invalid_dates', __( 'Please provide valid Start Date and End Date values.', 'palaplast' ) );
	}

	if ( $end_date->getTimestamp() < $start_date->getTimestamp() ) {
		return new WP_Error( 'end_before_start', __( 'End Date cannot be earlier than Start Date.', 'palaplast' ) );
	}

	return array(
		'start' => $start_date->format( 'Y-m-d H:i:s' ),
		'end'   => $end_date->format( 'Y-m-d H:i:s' ),
	);
}

function palaplast_set_event_admin_error( $error_code ) {
	set_transient( 'palaplast_event_error_' . get_current_user_id(), sanitize_key( $error_code ), MINUTE_IN_SECONDS );
}

function palaplast_add_event_validation_error_to_redirect( $location, $post_id ) {
	if ( 'event' !== get_post_type( $post_id ) ) {
		return $location;
	}

	$error_code = get_transient( 'palaplast_event_error_' . get_current_user_id() );
	if ( ! $error_code ) {
		return $location;
	}

	delete_transient( 'palaplast_event_error_' . get_current_user_id() );

	return add_query_arg( 'palaplast_event_error', $error_code, $location );
}

function palaplast_render_event_admin_notice() {
	if ( empty( $_GET['palaplast_event_error'] ) ) {
		return;
	}

	$screen = get_current_screen();
	if ( ! $screen || 'event' !== $screen->post_type ) {
		return;
	}

	$error_code = sanitize_key( wp_unslash( $_GET['palaplast_event_error'] ) );
	$message    = __( 'There was a validation error while saving the event.', 'palaplast' );

	if ( 'missing_dates' === $error_code ) {
		$message = __( 'Start Date and End Date are required. Event dates were not saved.', 'palaplast' );
	} elseif ( 'invalid_dates' === $error_code ) {
		$message = __( 'Please provide valid Start Date and End Date values. Event dates were not saved.', 'palaplast' );
	} elseif ( 'end_before_start' === $error_code ) {
		$message = __( 'End Date cannot be earlier than Start Date. Event dates were not saved.', 'palaplast' );
	}

	echo '<div class="notice notice-error is-dismissible"><p>' . esc_html( $message ) . '</p></div>';
}

function palaplast_add_event_admin_columns( $columns ) {
	$updated_columns = array();

	foreach ( $columns as $key => $label ) {
		$updated_columns[ $key ] = $label;
		if ( 'title' === $key ) {
			$updated_columns['palaplast_event_start'] = __( 'Start Date', 'palaplast' );
			$updated_columns['palaplast_event_end']   = __( 'End Date', 'palaplast' );
		}
	}

	return $updated_columns;
}

function palaplast_render_event_admin_columns( $column, $post_id ) {
	if ( 'palaplast_event_start' === $column ) {
		echo esc_html( palaplast_format_event_datetime( get_post_meta( $post_id, PALAPLAST_EVENT_START_META_KEY, true ) ) );
	}

	if ( 'palaplast_event_end' === $column ) {
		echo esc_html( palaplast_format_event_datetime( get_post_meta( $post_id, PALAPLAST_EVENT_END_META_KEY, true ) ) );
	}
}

function palaplast_set_event_admin_default_sort( $query ) {
	if ( ! is_admin() || ! $query->is_main_query() ) {
		return;
	}

	if ( 'event' !== $query->get( 'post_type' ) ) {
		return;
	}

	if ( ! empty( $query->get( 'orderby' ) ) ) {
		return;
	}

	$query->set( 'palaplast_event_sort', true );
}

function palaplast_set_event_archive_default_sort( $query ) {
	if ( is_admin() || ! $query->is_main_query() || ! $query->is_post_type_archive( 'event' ) ) {
		return;
	}

	if ( ! empty( $query->get( 'orderby' ) ) ) {
		return;
	}

	$query->set( 'palaplast_event_sort', true );
}

function palaplast_apply_event_chronological_sort( $clauses, $query ) {
	if ( ! $query->get( 'palaplast_event_sort' ) ) {
		return $clauses;
	}

	global $wpdb;

	$join_alias = 'palaplast_event_start_meta';
	$meta_key   = esc_sql( PALAPLAST_EVENT_START_META_KEY );

	$clauses['join']   .= " LEFT JOIN {$wpdb->postmeta} {$join_alias} ON ({$wpdb->posts}.ID = {$join_alias}.post_id AND {$join_alias}.meta_key = '{$meta_key}')";
	$clauses['orderby'] = "CASE WHEN {$join_alias}.meta_value >= NOW() THEN 0 ELSE 1 END ASC, CASE WHEN {$join_alias}.meta_value >= NOW() THEN {$join_alias}.meta_value END ASC, CASE WHEN {$join_alias}.meta_value < NOW() THEN {$join_alias}.meta_value END DESC";

	return $clauses;
}

function palaplast_render_event_dates_on_single( $content ) {
	if ( ! is_singular( 'event' ) || ! in_the_loop() || ! is_main_query() ) {
		return $content;
	}

	$start = get_post_meta( get_the_ID(), PALAPLAST_EVENT_START_META_KEY, true );
	$end   = get_post_meta( get_the_ID(), PALAPLAST_EVENT_END_META_KEY, true );

	if ( '' === $start || '' === $end ) {
		return $content;
	}

	$date_html = palaplast_get_event_date_range_html( $start, $end );
	if ( '' === $date_html ) {
		return $content;
	}

	return $date_html . $content;
}

function palaplast_get_event_date_range_html( $start, $end ) {
	$start_timestamp = strtotime( (string) $start );
	$end_timestamp   = strtotime( (string) $end );

	if ( false === $start_timestamp || false === $end_timestamp ) {
		return '';
	}

	$date_format = get_option( 'date_format' );
	$time_format = get_option( 'time_format' );

	if ( wp_date( 'Y-m-d', $start_timestamp ) === wp_date( 'Y-m-d', $end_timestamp ) ) {
		$display = sprintf(
			/* translators: 1: event date, 2: start time, 3: end time */
			__( '%1$s, %2$s - %3$s', 'palaplast' ),
			wp_date( $date_format, $start_timestamp ),
			wp_date( $time_format, $start_timestamp ),
			wp_date( $time_format, $end_timestamp )
		);

		return '<div class="palaplast-event-dates"><p><strong>' . esc_html__( 'Date', 'palaplast' ) . ':</strong> ' . esc_html( $display ) . '</p></div>';
	}

	$start_display = wp_date( $date_format . ' ' . $time_format, $start_timestamp );
	$end_display   = wp_date( $date_format . ' ' . $time_format, $end_timestamp );

	return '<div class="palaplast-event-dates"><p><strong>' . esc_html__( 'Start Date', 'palaplast' ) . ':</strong> ' . esc_html( $start_display ) . '</p><p><strong>' . esc_html__( 'End Date', 'palaplast' ) . ':</strong> ' . esc_html( $end_display ) . '</p></div>';
}

function palaplast_format_event_datetime_for_input( $datetime ) {
	$timezone = wp_timezone();
	$date     = date_create_immutable_from_format( 'Y-m-d H:i:s', (string) $datetime, $timezone );
	if ( ! $date ) {
		return '';
	}

	return $date->format( 'Y-m-d\TH:i' );
}

function palaplast_format_event_datetime( $datetime ) {
	$timestamp = strtotime( (string) $datetime );
	if ( false === $timestamp ) {
		return '—';
	}

	return wp_date( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), $timestamp, wp_timezone() );
}

function palaplast_render_events_shortcode( $atts ) {
	$defaults = array(
		'posts_per_page' => 10,
		'show_excerpt'   => 'true',
		'type'           => '',
		'post_type'      => '',
	);

	$atts = shortcode_atts( $defaults, $atts, 'palaplast_events' );

	$post_type_input = '';
	if ( '' !== (string) $atts['post_type'] ) {
		$post_type_input = (string) $atts['post_type'];
	} elseif ( '' !== (string) $atts['type'] ) {
		$post_type_input = (string) $atts['type'];
	}

	$post_type = sanitize_key( $post_type_input );
	if ( 'events' === $post_type ) {
		$post_type = 'event';
	}

	if ( 'event' !== $post_type ) {
		$post_type = 'event';
	}

	$query_args = array(
		'post_type'            => $post_type,
		'post_status'          => 'publish',
		'posts_per_page'       => max( 1, (int) $atts['posts_per_page'] ),
		'ignore_sticky_posts'  => true,
		'palaplast_event_sort' => true,
	);

	$events_query = new WP_Query( $query_args );

	if ( ! $events_query->have_posts() ) {
		return '<div class="palaplast-events-page"><p>' . esc_html__( 'No events found.', 'palaplast' ) . '</p></div>';
	}

	$show_excerpt = 'false' !== strtolower( (string) $atts['show_excerpt'] );

	ob_start();
	?>
	<div class="palaplast-events-page">
		<?php
		while ( $events_query->have_posts() ) {
			$events_query->the_post();

			$start = get_post_meta( get_the_ID(), PALAPLAST_EVENT_START_META_KEY, true );
			$end   = get_post_meta( get_the_ID(), PALAPLAST_EVENT_END_META_KEY, true );
			?>
			<article class="palaplast-events-page__item">
				<h3 class="palaplast-events-page__title"><a href="<?php echo esc_url( get_permalink() ); ?>"><?php the_title(); ?></a></h3>
				<?php if ( '' !== $start && '' !== $end ) : ?>
					<div class="palaplast-events-page__dates"><?php echo wp_kses_post( palaplast_get_event_date_range_html( $start, $end ) ); ?></div>
				<?php endif; ?>
				<?php if ( $show_excerpt ) : ?>
					<div class="palaplast-events-page__excerpt"><?php the_excerpt(); ?></div>
				<?php endif; ?>
			</article>
			<?php
		}
		?>
	</div>
	<?php

	wp_reset_postdata();

	return (string) ob_get_clean();
}
