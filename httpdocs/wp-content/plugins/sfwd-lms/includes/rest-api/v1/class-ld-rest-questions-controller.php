<?php
/**
 * Questions REST API Endpoint.
 *
 * Register interface to handle questions with the REST API.
 *
 * @package LearnDash
 */

if ( ! class_exists( 'LD_REST_Questions_Controller_V1' ) ) {

	/**
	 * Questions REST Controller.
	 */
	class LD_REST_Questions_Controller_V1 extends WP_REST_Controller {

		/**
		 * Register the routes for the objects of the controller.
		 */
		public function register_routes() {
			$version = '1';
			$namespace = LEARNDASH_REST_API_NAMESPACE . '/v' . $version;
			$base = 'sfwd-questions';

			register_rest_route( $namespace, '/' . $base, array(
				array(
				  'methods'             => WP_REST_Server::READABLE,
				  'callback'            => array( $this, 'get_items' ),
				  'permission_callback' => array( $this, 'permissions_check' ),
				  'args'                => array(),
				),
			) );

			register_rest_route( $namespace, '/' . $base . '/(?P<id>[\d]+)', array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param );
							},
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param );
							},
							'sanitize_callback' => 'absint',
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'permissions_check' ),
					'args'                => array(
						'id' => array(
							'required'          => true,
							'validate_callback' => function( $param, $request, $key ) {
								return is_numeric( $param );
							},
							'sanitize_callback' => 'absint',
						),
					),
				),
			) );
		}

		/**
		 * Check if a given request has access manage the item.
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|bool
		 */
		public function permissions_check( $request ) {
			$params      = $request->get_params();
			$question_id = $params['id'];

			return current_user_can( 'edit_post', $question_id );
		}

		/**
		 * Get a question items
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_items( $request ) {
			$data = [];
			return new WP_REST_Response( $data, 200 );
		}

		/**
		 * Get a question item
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|WP_REST_Response
		 */
		public function get_item( $request ) {
			$params      = $request->get_params();
			$question_id = $params['id'];
			$data        = $this->get_question_data( $question_id );

			return new WP_REST_Response( $data, 200 );
		}

		/**
		 * Delete one item from the collection
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|WP_REST_Request
		 */
		public function delete_item( $request ) {
			$params          = $request->get_params();
			$question_id     = $params['id'];
			$question_pro_id = (int) get_post_meta( $question_id, 'question_pro_id', true );
			$question_mapper = new \WpProQuiz_Model_QuestionMapper();

			if ( false !== $question_mapper->delete( $question_pro_id ) &&
				false !== wp_delete_post( $params['id'], false ) ) {
				return new WP_REST_Response( true, 200 );
			}

			return new WP_Error( 'cant-delete', sprintf(
				// translators: placeholder: Question label.
				esc_html_x( 'Could not delete the %s.', 'placeholder: Question label', 'learndash' ),
				\LearnDash_Custom_Label::get_label( 'question' )
			),
			array( 'status' => 500 ) );
		}

		/**
		 * Update one item from the collection
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 * @return WP_Error|WP_REST_Request
		 */
		public function update_item( $request ) {
			$params          = $request->get_params();
			$question_id     = $params['id'];
			$question_pro_id = (int) get_post_meta( $question_id, 'question_pro_id', true );
			$question_mapper = new \WpProQuiz_Model_QuestionMapper();

			$question_model  = $question_mapper->fetch( $question_pro_id );

			// Update answer data if available.
			if ( isset( $params['_answerData'] ) && is_string( $params['_answerData'] ) ) {
				$params['_answerData'] = json_decode( $params['_answerData'], true );
			}

			// Also save points at question's post meta data.
			if ( isset( $params['_points'] ) ) {
				update_post_meta( $question_id, 'question_points', $params['_points'] );
			}

			// Update question's post content.
			if ( isset( $params['_question'] ) ) {
				wp_update_post( [
					'ID'           => $question_id,
					'post_content' => wp_slash( $params['_question'] ),
				] );
			}

			// Update the question object with new data.
			$question_model->set_array_to_object( $params );

			// Save the new data to database.
			$question_mapper->save( $question_model );

			if ( true ) {
				return new WP_REST_Response( $this->get_question_data( $question_id ), 200 );
			}

			return new WP_Error( 'cant-delete', sprintf( esc_html__( 'Could not update the %s.', 'learndash' ), \LearnDash_Custom_Label::get_label( 'question' ) ), array( 'status' => 500 ) );
		}

		/**
		 * Get question data.
		 *
		 * @param int $question_id The question ID.
		 * @return object
		 */
		public function get_question_data( $question_id ) {
			// Get Answers from Question.
			$question_pro_id     = (int) get_post_meta( $question_id, 'question_pro_id', true );
			$question_mapper     = new \WpProQuiz_Model_QuestionMapper();

			if ( ! empty( $question_pro_id ) ) {
				$question_model = $question_mapper->fetch( $question_pro_id );
			} else {
				$question_model = $question_mapper->fetch( null );
			}

			// Get data as array.
			$question_data = $question_model->get_object_as_array();

			$answer_data = [];

			// Get answer data.
			foreach ( $question_data['_answerData'] as $answer ) {
				$answer_data[] = $answer->get_object_as_array();
			}

			unset( $question_data['_answerData'] );

			$question_data['_answerData'] = $answer_data;

			// Generate output object.
			$data = array_merge( $question_data, [
				'question_id'            => $question_id,
				'question_post_title'    => get_the_title( $question_id ),
			] );

			return $data;
		}
	}
}
