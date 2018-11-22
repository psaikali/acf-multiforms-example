<?php

namespace ACF_Multiforms_Example;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use ACF_Multiforms_Example\Utils;

/**
 * Output our [acf_multiforms_example] shortcode and process the ACF front-end form.
 */
class Shortcode {
	/**
	 * Our form ID, used internally to identify this specific ACF front-end form.
	 *
	 * @var string
	 */
	private $id;

	/**
	 * The post type this form should create.
	 *
	 * @var string
	 */
	private $post_type;

	/**
	 * List of ACF fields groups we want to display as steps.
	 * Each array item is an array of metaboxes IDs to display in a separate step.
	 *
	 * @var array
	 */
	private $metabox_ids;

	/**
	 * The constructor saves the necessary properties that we just described above.
	 */
	public function __construct() {
		$this->id          = 'acf-multiforms-example';
		$this->post_type   = 'estimate_request';
		$this->metabox_ids = [ [ 'group_5beab4a31f3ff', 'group_5beab4d56e9f5' ], [ 'group_5beab6515ba78' ], [ 'group_5beae8ce395d6' ] ];
		$this->hooks();
	}

	/**
	 * Register our hooks
	 *
	 * @return void
	 */
	public function hooks() {
		/**
		 * Register our [acf_multiforms_example] shortcode.
		 */
		add_shortcode( 'acf_multiforms_example', [ $this, 'output_shortcode' ] );

		/**
		 * Process the ACF form submission.
		 */
		add_action( 'acf/save_post', [ $this, 'process_acf_form' ], 20 );
	}

	/**
	 * Output the shortcode content: if form is not finished, output the form.
	 * If user just filled the last form step, output a thanks message.
	 *
	 * @return string The content of our shortcode.
	 */
	public function output_shortcode() {
		ob_start();

		if ( ! function_exists( 'acf_form' ) ) {
			return;
		}

		// User is currently filling the form, we display it.
		if ( ! $this->current_multiform_is_finished() ) {
			$this->output_acf_form( [
				'post_type' => $this->post_type,
			] );

		// Form has been filled entirely, we display a thanks message.
		} else {
			_e( 'Thanks for your submission, we will get back to you very soon!' );
		}

		return ob_get_clean();
	}

	/**
	 * Output the ACF front end form.
	 * Don't forget to add `acf_form_head()` in the header of your theme.
	 * 
	 * @link https://www.advancedcustomfields.com/resources/acf_form/
	 * @param array $args
	 * @return void
	 */
	private function output_acf_form( $args = [] ) {
		// Get post_id from URL (if we are @ step 2 and above), or create a new_post (if we are @ step 1).
		$requested_post_id = $this->get_request_post_id();
		// Get the current step we are at in the form.
		$requested_step    = $this->get_request_step();

		$args = wp_parse_args(
			$args,
			[
				'post_id'     => $requested_post_id,
				'step'        => 'new_post' === $requested_post_id ? 1 : $requested_step,
				'post_type'   => 'post',
				'post_status' => 'publish',
			]
		);

		$submit_label           = $args['step'] < count( $this->metabox_ids ) ? __( 'Next step' ) : __( 'Finish' );
		$current_step_metaboxes = ( $args['post_id'] !== 'new_post' && $args['step'] > 1 ) ? $this->metabox_ids[ (int) $args['step'] - 1 ] : $this->metabox_ids[0];

		// Optional: display a custom message before the form.
		$this->display_custom_message_before_form( $args );

		/**
		 * Display the form with acf_form().
		 *
		 * The key here is to tell ACF which fields groups (metaboxes) we want to display,
		 * depending on the current form step we are at.
		 * This is done via the "field_groups" parameter below.
		 */
		acf_form(
			[
				'id' 				=> $this->id,
				'post_id'			=> $args['post_id'],
				'new_post'			=> [
					'post_type'		=> $args['post_type'],
					'post_status'	=> $args['post_status'],
				],
				'field_groups'      => $current_step_metaboxes,
				'submit_value'      => $submit_label,
				'html_after_fields' => $this->output_hidden_fields( $args ),
			]
		);
	}

	/**
	 * Display a custom message before the form
	 *
	 * @param array $args The form arguments passed to acf_form().
	 * @return void
	 */
	private function display_custom_message_before_form( $args ) {
		if ( $args['post_id'] === 'new_post' ) {
			$message = __( 'Welcome to this form! This custom message should be different depending on the current step you are at.' );
		} else {
			switch ( $args['step'] ) {
				case 2:
				default:
					$message = sprintf( __( 'Hi %1$s, thanks for your interest! Please give us some more details :)' ), get_field( 'full_name', (int) $args['post_id'] ) );
					break;

				case 3:
					$message = sprintf( __( 'Thanks %1$s! That is the last step.' ), get_field( 'full_name', (int) $args['post_id'] ) );
					break;
			}
		}

		if ( $message ) {
			printf( '<p>%1$s</p>', $message );
		}
	}

	/**
	 * Output some vital hidden fields in our form in order to properly process it
	 * and redirect user to next step accordingly.
	 * Basically, we need to pass this form ID (to be able to do stuff when it's submitted,
	 * and do nothing when other ACF forms are submitted).
	 * We also need to pass the current step we are at; we could get it from $_GET but
	 * I find it better to have a single source of truth to pick data from, instead of having to mix
	 * between $_POST and $_GET when processing the form.
	 *
	 * @param array $args The form arguments passed to acf_form().
	 * @return string HTML hidden <input /> fields.
	 */
	private function output_hidden_fields( $args ) {
		$inputs   = [];
		$inputs[] = sprintf( '<input type="hidden" name="ame-multiform-id" value="%1$s"/>', $this->id );
		$inputs[] = isset( $args['step'] ) ? sprintf( '<input type="hidden" name="ame-current-step" value="%1$d"/>', $args['step'] ) : '';

		return implode( ' ', $inputs );
	}

	/**
	 * Helper function to check if the current $_GET['post_id] is a valid post for this form.
	 *
	 * @return int|boolean Returns the post ID if post is considered valid, or "new_post" to initiate a blank "new post" form.
	 */
	private function get_request_post_id() {
		if ( isset( $_GET['post_id'] ) && $this->requested_post_is_valid() && $this->can_continue_current_multiform() ) {
			return (int) $_GET['post_id'];
		}

		return 'new_post';
	}

	/**
	 * Analyse the WP_Post related to the $_GET['post_id'] we received, and determine if
	 * this specific post should be used for this ACF form request.
	 *
	 * @return boolean Whether the requested post can be edited.
	 */
	private function requested_post_is_valid() {
		return ( get_post_type( (int) $_GET['post_id'] ) === $this->post_type && get_post_status( (int) $_GET['post_id'] ) === 'publish' );
	}

	/**
	 * Can we continue current post/form edition?
	 * I added this method to offer a granular way to authorize the current multi-steps form edition.
	 * In our case, we analyze a token passed in URL to determine if it matches a post meta, so that continuing
	 * the form edition can not be done by anyone passing a random $_GET['post_id] parameter without its correct secret token.
	 * Any logged-in user verification could be done here.
	 * 
	 * @return boolean If the current multiform edition should continue, or should we discard it and initiate a "new post" form.
	 */
	private function can_continue_current_multiform() {
		if ( ! isset( $_GET['token'] ) ) {
			return false;
		}

		$token_from_url       = sanitize_text_field( $_GET['token'] );
		$token_from_post_meta = get_post_meta( (int) $_GET['post_id'], 'secret_token', true );

		return ( $token_from_url === $token_from_post_meta );
	}

	/**
	 * Get the requested form step. Used to display the proper metaboxes.
	 *
	 * @return int Current step, fallback to 1 (first set of metaboxes).
	 */
	private function get_request_step() {
		if ( isset( $_POST['ame-current-step'] ) && absint( $_POST['ame-current-step'] ) <= count( $this->metabox_ids ) ) {
			return absint( $_POST['ame-current-step'] );
		}

		else if ( isset( $_GET['step'] ) && absint( $_GET['step'] ) <= count( $this->metabox_ids ) ) {
			return absint( $_GET['step'] );
		}

		return 1;
	}

	/**
	 * Process the form!
	 * ACF did its magic and created/updated the post with proper meta values.
	 * Now let's add some custom logic to update the title and redirect user to next form step,
	 * or final "thank you" finished state of the form.
	 *
	 * @param int $post_id ACF will give us the post ID.
	 * @return void
	 */
	public function process_acf_form( $post_id ) {
		// Bail early if we are editing a post in back-office, or if we're dealing with a different front-end ACF form.
		if ( is_admin() || ! isset( $_POST['ame-multiform-id'] ) || $_POST['ame-multiform-id'] !== $this->id ) {
			return;
		}

		$current_step = $this->get_request_step();

		// First step: ACF just created the post, we might want to store some initial values.
		if ( $current_step === 1 ) {
			$email     = Utils::get_acf_post_value( 'email' );
			$full_name = Utils::get_acf_post_value( 'full_name' );

			// Post title should be empty, we update it to a more readable one.
			$updated_post = wp_update_post( 
				[
					'ID'    => (int) $post_id,
					'post_title' => sprintf( 'Request from %1$s (%2$s)', sanitize_text_field( $full_name ), sanitize_text_field( $email ) ),
				],
				true
			);

			// Generate a secret token that will be required in URL to continue this form flow and edit this specific WP_Post.
			$token = wp_generate_password( rand( 10, 20 ), false, false );
			update_post_meta( (int) $post_id, 'secret_token', $token );
		}

		// First and middle steps: we are "editing" the post but user has not yet finished the entire flow.
		if ( $current_step < count( $this->metabox_ids ) ) {
			// Add the post ID in URL and inform our front-end logic that we want to display the NEXT step.
			$query_args = [
				'step'    => ++$current_step,
				'post_id' => $post_id,
				'token'   => isset( $token ) ? $token : $_GET['token'],
			];

		// Final step: maybe add an admin email to a queue, change post_status... Anything, really!
		} else {
			// Pass a "finished" parameter to inform our front-end logic that we're done with the form.
			$query_args = [ 'finished' => 1 ];
		}

		// Redirect user back to the form page, with proper new $_GET parameters.
		$redirect_url = add_query_arg( $query_args, wp_get_referer() );
		wp_safe_redirect( $redirect_url );

		exit();
	}

	/**
	 * Determine if the current multiform flow is over.
	 *
	 * @return boolean Whether the current multiform flow is over.
	 */
	private function current_multiform_is_finished() {
		return ( isset( $_GET['finished'] ) && 1 === (int) $_GET['finished'] );
	}
}
