<?php

namespace ACF_Multiforms_Example;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ACF_Multiforms_Example\Utils;

class Shortcode {
	private $id          = 'acf-multiforms-example';
	private $post_type   = 'estimate_request';
	private $metabox_ids = [ 'group_5beab4a31f3ff', 'group_5beab4d56e9f5', 'group_5beab6515ba78' ];

	public function __construct() {
		$this->hooks();
	}

	public function hooks() {
		add_shortcode( 'acf_multiforms_example', [ $this, 'output_shortcode' ] );
		add_action( 'acf/save_post', [ $this, 'process_acf_form' ], 20 );
	}

	public function output_shortcode() {
		ob_start();

		if ( ! $this->current_multiform_is_finished() ) {
			$this->output_acf_form( [
				'post_type' => $this->post_type,
			] );
		} else {
			_e( 'Thanks for your submission, we will get back to you very soon!' );
		}

		return ob_get_clean();
	}

	private function output_acf_form( $args = [] ) {
		$args = wp_parse_args(
			$args,
			[
				'post_id'     => $this->get_request_post_id(),
				'step'        => $this->get_request_post_id() === 'new_post' ? 1 : $this->get_request_step(),
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);

		$submit_label = $args['step'] < count( $this->metabox_ids ) ? __( 'Next step' ) : __( 'Finish' );
		$metabox_id   = ( $args['post_id'] !== 'new_post' && $args['step'] > 1 ) ? $this->metabox_ids[ (int) $args['step'] - 1 ] : $this->metabox_ids[0];

		$this->display_custom_message_before_form( $args );

		acf_form(
			[
				'id' 				=> $this->id,
				'post_id'			=> $args['post_id'],
				'new_post'			=> [
					'post_type'		=> $args['post_type'],
					'post_status'	=> $args['post_status'],
				],
				'field_groups'      => [ $metabox_id ],
				'submit_value'      => $submit_label,
				'html_after_fields' => $this->output_hidden_fields( $args ),
			]
		);
	}

	private function display_custom_message_before_form( $args ) {
		if ( $args['post_id'] === 'new_post' ) {
			_e( 'Welcome to this form! This custom message should be different depending on the current step you are at.' );
			return;
		}

		switch ( $args['step'] ) {
			case 2:
			default:
				printf( __( 'Hi %1$s, thanks for your interest! Please give us some more details :)' ), get_field( 'full_name', (int) $args['post_id'] ) );
				break;

			case 3:
				printf( __( 'Thanks %1$s! That is the last step.' ), get_field( 'full_name', (int) $args['post_id'] ) );
				break;
		}
	}

	private function output_hidden_fields( $args ) {
		$inputs   = [];
		$inputs[] = sprintf( '<input type="hidden" name="ame-multiform-id" value="%1$s"/>', $this->id );
		$inputs[] = isset( $args['step'] ) ? sprintf( '<input type="hidden" name="ame-current-step" value="%1$d"/>', $args['step'] ) : '';

		return implode( ' ', $inputs );
	}

	private function get_request_post_id() {
		if ( isset( $_GET['post_id'] ) && $this->requested_post_is_valid() && $this->can_continue_current_multiform() ) {
			return (int) $_GET['post_id'];
		}

		return 'new_post';
	}

	private function get_request_step() {
		if ( isset( $_REQUEST['step'] ) && absint( $_REQUEST['step'] ) <= count( $this->metabox_ids ) ) {
			return absint( $_REQUEST['step'] );
		}

		return 1;
	}

	private function requested_post_is_valid() {
		return ( get_post_type( (int) $_GET['post_id'] ) === $this->post_type && get_post_status( (int) $_GET['post_id'] ) === 'publish' );
	}

	private function can_continue_current_multiform() {
		return true;
	}

	public function process_acf_form( $post_id ) {
		$current_step = $this->get_request_step();

		/**
		 * First step: ACF just created the post, let's store some initial values
		 */
		if ( $current_step === 1 ) {
			$email = Utils::get_acf_post_value( 'email' );

			$updated_post = wp_update_post( 
				[
					'ID'    => (int) $post_id,
					'post_title' => sprintf( 'Request from %1$s', sanitize_text_field( $email ) ),
				],
				true
			);

			if ( is_wp_error( $updated_post ) ) {
				// Something went wrong when trying to update the post.
				wp_die( __( 'We could not process your request :(' ) );
			}
		}

		/**
		 * Middle steps: we are "editing" the post but we are not yet finished.
		 */
		if ( $current_step < count( $this->metabox_ids ) ) {
			$query_args = [
				'step'    => ++$current_step,
				'post_id' => $post_id,
			];

		/**
		 * Final step: maybe send an admin email, change post_status, ...
		 */
		} else {
			$query_args = [ 'finished' => 1 ];
		}

		$redirect_url = add_query_arg( $query_args, wp_get_referer() );
		wp_redirect( $redirect_url );

		exit();
	}

	private function current_multiform_is_finished() {
		return ( isset( $_GET['finished'] ) && (int) $_GET['finished'] === 1 );
	}
}
