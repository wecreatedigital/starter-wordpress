<?php
/**
 * Deprecated functions from past LearnDash versions. You shouldn't use these
 * functions and look for the alternatives instead. The functions will be
 * removed in a later version.
 *
 * @package LearnDash
 * @subpackage Deprecated
 */

/*
 * Deprecated functions come here to die.
 */

/**
 * Is user a group leader
 *
 * @since 2.1.0
 *
 * @param  int|object  $user
 * @return bool
 */
if ( ! function_exists( 'is_group_leader' ) ) {
	function is_group_leader( $user ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.3.0', 'learndash_is_group_leader_user()' );
		}

		return learndash_is_group_leader_user( $user );
	}
}

/**
 * Set 'updated' admin messages for Groups post type
 *
 * @since 2.1.0
 *
 * @param  array $messages
 * @return array $messages
 */
if ( ! function_exists( 'learndash_group_updated_messages' ) ) {
	function learndash_group_updated_messages( $messages ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_post_updated_messages()' );
		}

		return learndash_post_updated_messages( $messages );
	}
}

// Get all users with explicit 'course_XX_access_from' access
if ( ! function_exists( 'get_course_users_access_from_meta' ) ) {
	function get_course_users_access_from_meta( $course_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_get_course_users_access_from_meta()' );
		}

		return learndash_get_course_users_access_from_meta( $course_id );
	}
}


// Get all the users for a given course_id that have 'learndash_course_expired_XX' user meta records.
if ( ! function_exists( 'get_course_expired_access_from_meta' ) ) {
	function get_course_expired_access_from_meta( $couese_id = 0 ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_get_course_expired_access_from_meta()' );
		}

		return learndash_get_course_expired_access_from_meta( $course_id );
	}
}

// Utility function to att the course settings in meta. Better than having this over inline over and over again.
if ( ! function_exists( 'get_course_meta_setting' ) ) {
	function get_course_meta_setting( $course_id = 0, $setting_key = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_get_course_meta_setting()' );
		}

		return learndash_get_course_meta_setting( $course_id, $setting_key );
	}
}

/**
 * Used when editing Lesson, Topic, Quiz or Question post items. This filter is needed to add
 * the 'course_id' parameter back to the edit URL after the post is submitted (saved).
 *
 * @since 2.5
 */
if ( ! function_exists( 'leandash_redirect_post_location' ) ) {
	function leandash_redirect_post_location( $location = '' ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '2.6.4', 'learndash_redirect_post_location()' );
		}

		return learndash_redirect_post_location( $location );
	}
}

/**
 * Update the course access time for a user.
 *
 * @since 2.6.0
 *
 * @param int   $course_id Course ID for update.
 * @param int   $user_id User ID for update.
 * @param mixed $access Value can be a date string (YYYY-MM-DD hh:mm:ss or integer value.
 * @param bool  $is_gmt If $access value is GMT (true) or relative to site timezone (false).
 *
 * @return bool Returns true if success.
 */
if ( ! function_exists( 'ld_course_access_update' ) ) {
	function ld_course_access_update( $course_id, $user_id, $access = '', $is_gmt = false ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '.0', 'ld_course_access_from_update()' );
		}

		return ld_course_access_from_update( $course_id, $user_id, $access, $is_gmt );
	}
}

if ( ( ! class_exists( 'Learndash_Admin_Settings_Data_Upgrades' ) ) && ( class_exists( 'Learndash_Admin_Data_Upgrades' ) ) ) {
	class Learndash_Admin_Settings_Data_Upgrades {
		public static function get_instance( $instance_key = '' ) {
			if ( function_exists( '_deprecated_function' ) ) {
				_deprecated_function( 'Learndash_Admin_Settings_Data_Upgrades::get_instance()', '2.6.0', 'Learndash_Admin_Data_Upgrades::get_instance()' );
			}

			return Learndash_Admin_Data_Upgrades::get_instance();
		}
	}
}

if ( ! function_exists( 'learndash_get_valid_transient' ) ) {
	function learndash_get_valid_transient( $transient_key = '' ) {
		//if ( function_exists( '_deprecated_function' ) ) {
		//	_deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::get' );
		//}

		return LDLMS_Transients::get( $transient_key );
	}
}

if ( ! function_exists( 'learndash_set_transient' ) ) {
	function learndash_set_transient( $transient_key = '', $transient_data = '', $transient_expire = MINUTE_IN_SECONDS ) {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::set()' );
		}
		
		return LDLMS_Transients::set( $transient_key, $transient_data, $transient_expire );
	}
}

if ( ! function_exists( 'learndash_purge_transients' ) ) {
	function learndash_purge_transients() {
		if ( function_exists( '_deprecated_function' ) ) {
			_deprecated_function( __FUNCTION__, '3.1', 'LDLMS_Transients::purge_all()' );
		}

		return LDLMS_Transients::purge_all();
	}
}
