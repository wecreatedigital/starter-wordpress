<?php
/**
 * Added logic to only load the modal template once.
 */
global $login_model_load_once;
if ( true === $login_model_load_once ) {
	return false;
}
$login_model_load_once = true;

$can_register = get_option( 'users_can_register' );  ?>

<div class="ld-modal ld-login-modal <?php if( $can_register) echo 'ld-can-register'; ?>">

	<span class="ld-modal-closer ld-icon ld-icon-delete"></span>

	<div class="ld-login-modal-login">
		<div class="ld-login-modal-wrapper">
			<?php
			/**
			 * Action to add custom content before the modal heading
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-login-modal-heading-before' ); ?>
			<div class="ld-modal-heading">
				<?php echo esc_html_e( 'Login', 'learndash' ); ?>
			</div>
			<?php
			/**
			 * Action to add custom content after the modal heading
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-login-modal-heading-after' );

			if ( in_array( get_post_type(), learndash_get_post_types( 'course' ), true ) ) {
				?>
				<div class="ld-modal-text">
					<?php echo sprintf(
						// translators: placeholder: course.
						esc_html_x( 'Accessing this %s requires a login, please enter your credentials below!', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'course' )
				); ?>
				</div>
				<?php
			}

			/**
			 * Action to add custom content after the modal text
			 *
			 * @since 3.0
			 */
			do_action( 'learndash-login-modal-text-after' );
			if( isset($_GET['login']) && $_GET['login'] == 'failed' ):

				learndash_get_template_part(
					'modules/alert.php',
					array(
						'type'      =>  'warning',
						'icon'      =>  'alert',
						'message'	=>	__( 'Incorrect username or password. Please try again', 'learndash' )
					), true );

					/**
					 * Action to add custom content after the modal alert
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-login-modal-alert-after' );

			elseif( isset($_GET['ld-resetpw']) && $_GET['ld-resetpw'] == 'true' ):

				learndash_get_template_part(
					'modules/alert.php',
					array(
						'type'      =>  'warning',
						'icon'      =>  'alert',
						'message'	=>	__( 'Please check your email for the password reset link.', 'learndash' )
					), true );

					/**
					 * Action to add custom content after the modal alert
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-login-modal-alert-after' );

			endif; ?>
			<div class="ld-login-modal-form">

				<?php
				/**
				 * Action to add custom content before the modal form
				 *
				 * @since 3.0
				 */
				do_action( 'learndash-login-modal-form-before' );

				// Add a filter for validation returns
				add_filter( 'login_form_top', 'learndash_add_login_field_top' );

				// Just so users can supply their own args if desired
				$login_form_args = array();

				/**
				 * Remove the query string param '?login=failed' and hash '#login' from previous
				 * login failed attempt. This way on success the user is returned back to the course
				 * and not shown the login form again.
				 */
				$login_form_args['redirect'] = remove_query_arg( 'login', get_permalink() );
				$login_form_args['redirect'] = str_replace( '#login', '', $login_form_args['redirect'] );

				$login_form_args = apply_filters( 'learndash-login-form-args', $login_form_args );

				wp_login_form( $login_form_args );

				/**
				 * Action to add custom content after the modal form
				 *
				 * @since 3.0
				 */
				do_action( 'learndash-login-modal-form-after' ); ?>

				<a class="ld-forgot-password-link" href="<?php echo esc_url(wp_lostpassword_url( add_query_arg( 'ld-resetpw', 'true', get_the_permalink() . '#login' ) ) ); ?>"><?php esc_html_e( 'Lost Your Password?', 'learndash' ); ?></a>

				<?php
				$logo_id = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Theme_LD30', 'login_logo' );

				if( $logo_id ): ?>
					<div class="ld-login-modal-branding">
						<img src="<?php echo esc_url(wp_get_attachment_url($logo_id)); ?>" alt="<?php echo esc_attr(get_post_meta($logo_id , '_wp_attachment_image_alt', true)); ?>">
					</div>
				<?php endif;

				/**
				 * Action to add custom content after the modal form
				 *
				 * @since 3.0
				 */
				do_action( 'learndash-login-modal-after' ); ?>

			</div> <!--/.ld-login-modal-form-->
		</div> <!--/.ld-login-modal-wrapper-->
	</div> <!--/.ld-login-modal-login-->

	<?php
	if ( $can_register ) : ?>
		<?php
		$register_url    = apply_filters( 'learndash_login_model_register_url', '#ld-user-register' );
		$register_header = apply_filters( 'learndash_login_model_register_header', esc_html__( 'Register', 'learndash' ) );
		$register_text   = apply_filters( 'learndash_login_model_register_text', esc_html__( 'Don\'t have an account? Register one!', 'learndash' ) );
		?>
		<div class="ld-login-modal-register">
			<div class="ld-login-modal-wrapper">
				<div class="ld-content">
					<?php
					/**
					 * Action to add custom content before the register modal heading
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-register-modal-heading-before' );
					?>
					<div class="ld-modal-heading">
						<?php echo esc_html( $register_header ); ?>
					</div>
					<?php
					/**
					 * Action to add custom content after the register modal heading
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-register-modal-heading-after' ); ?>

					<div class="ld-modal-text">
						<?php echo esc_html( $register_text ); ?>
					</div>
					<?php
					/**
					 * Action to add custom content before the register modal heading
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-register-modal-text-after' );

					$errors = array(
						'has_errors' => false,
						'message' => ''
					);

					$errors_conditions = apply_filters( 'learndash-registration-errors', array(
						'username_exists'  => __( 'Registration username exists.', 'learndash' ),
						'email_exists'     => __( 'Registration email exists.', 'learndash' ),
						'empty_username'   => __( 'Registration requires a username.', 'learndash' ),
						'empty_email'	   => __( 'Registration requires a valid email.', 'learndash' ),
						'invalid_username' => __( 'Invalid username.', 'learndash' ),
						'invalid_email'	   => __( 'Invalid email.', 'learndash' )
					) );

					foreach( $errors_conditions as $param => $message ) {

						if( isset($_GET[$param]) && $_GET[$param] ) {
							$errors['has_errors'] = true;
							$errors['message'] .= $message . '<br>';
						}

					} ?>

					<?php
					if( $errors['has_errors'] ):

						learndash_get_template_part(
							'modules/alert.php',
							array(
								'type'      =>  'warning',
								'icon'      =>  'alert',
								'message'	=>	$errors['message']
							), true );

							/**
							 * Action to add custom content after the register modal errors
							 *
							 * @since 3.0
							 */
							do_action( 'learndash-register-modal-errors-after', $errors );

					elseif( isset($_GET['ld-registered']) && $_GET['ld-registered'] == 'true' ):

						learndash_get_template_part(
							'modules/alert.php',
							array(
								'type'      =>  'success',
								'icon'      =>  'alert',
								'message'	=>	__( 'Registration successful, please check your email to set your password.', 'learndash' )
							), true );

							/**
							 * Action to add custom content after the register modal errors
							 *
							 * @since 3.0
							 */
							do_action( 'learndash-register-successful-after', $errors );

					endif; ?>

					<?php
						if ( '#ld-user-register' === $register_url ) {
							$register_button_class = apply_filters( 'learndash_login_model_register_button_class', 'ld-js-register-account' );
						} else {
							$register_button_class = apply_filters( 'learndash_login_model_register_button_class', '' );
						}
					?>
					<a href="<?php esc_html_e( $register_url ) ?>" class="ld-button ld-button-reverse <?php echo $register_button_class; ?>"><?php echo esc_html_e( 'Register an Account', 'learndash' ); ?></a>

					<?php
					/**
					 * Action to add custom content before the register modal heading
					 *
					 * @since 3.0
					 */
					do_action( 'learndash-register-modal-registration-link-after' ); ?>

				</div> <!--/.ld-content-->

				<?php
				/**
				 * Only if we are showing the LD register form.
				 */
				?>
				<?php if ( '#ld-user-register' === $register_url ) { ?>
					<div id="ld-user-register" class="ld-hide">
						<?php
						/**
						 * Action to add custom content before the register modal heading
						 *
						 * @since 3.0
						 */
						do_action( 'learndash-register-modal-register-form-before' ); ?>
						<form name="registerform" id="registerform" action="<?php echo esc_url( site_url( 'wp-login.php?action=register', 'login_post' ) ); ?>" method="post" novalidate="novalidate">
							<p>
								<label for="user_reg_login"><?php esc_html_e( 'Username', 'learndash' ); ?><br />
								<input type="text" name="user_login" id="user_reg_login" class="input" value="" size="20" /></label>
							</p>
							<p>
								<label for="user_reg_email"><?php esc_html_e( 'Email', 'learndash' ) ?><br />
								<input type="email" name="user_email" id="user_reg_email" class="input" value="" size="25" /></label>
							</p>
							<?php
							/**
							 * Fires following the 'Email' field in the user registration form.
							 *
							 * @since 3.0
							 */
							do_action( 'register_form' );
							do_action( 'learndash_register_form' ); ?>
							<input name="learndash-registration-form" value="true" type="hidden">
							<?php
								$course_id = learndash_get_course_id( get_the_ID() );
								if ( ( ! empty( $course_id ) ) && ( apply_filters( 'learndash_registration_form_include_course', true, $course_id ) ) ) {
									?><input name="learndash-registration-form-course" value="<?php echo $course_id; ?>" type="hidden" /><?php
									wp_nonce_field( 'learndash-registration-form-course-' . $course_id . '-nonce', 'learndash-registration-form-course-nonce' );
								}
							?>
							<input name="learndash-registration-form-redirect" type="hidden" value="<?php echo esc_url( apply_filters( 'learndash-registration-form-redirect', get_permalink() ) ); ?>">
							<p id="reg_passmail"><?php esc_html_e( 'Registration confirmation will be emailed to you.', 'learndash' ); ?></p>
							<?php /* ?><br class="clear" /><?php */ ?>
							<input type="hidden" name="redirect_to" value="<?php echo esc_attr( add_query_arg( 'ld-registered', 'true', get_the_permalink() . '#login' ) ); ?>" />
							<p class="submit"><input type="submit" name="wp-submit" id="wp-submit" class="button button-primary button-large" value="<?php esc_attr_e( 'Register', 'learndash' ); ?>" /></p>
						</form>
						<?php
						/**
						 * Action to add custom content before the register modal heading
						 *
						 * @since 3.0
						 */
						do_action( 'learndash-register-modal-register-form-after' ); ?>
					</div> <!--/#ld-user-register-->
				<?php } ?>
				<?php
				/**
				 * Action to add custom content before the register modal heading
				 *
				 * @since 3.0
				 */
				do_action( 'learndash-register-modal-register-wrapper-after' ); ?>
			</div> <!--/.ld-login-modal-wrapper-->
		</div> <!--/.ld-login-modal-register-->
	<?php endif; ?>

</div> <!--/.ld-modal-->
