<?php
/**
 * The class that handles student submissions.
 **/
class CoursePress_Module {
	public static $error_message = '';

	public static function process_submission() {
		if ( ! empty( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( $_REQUEST['_wpnonce'], 'coursepress_submit_modules' ) ) {
			$input = $_POST;
			$has_error = false;
			$student_id = get_current_user_id();

			if ( empty( $input['course_id'] ) ) {
				$has_error = true;
				self::$error_message = __( 'Invalid course ID!', 'cp' );
			} elseif ( false === CoursePress_Data_Course::student_enrolled( $student_id, $input['course_id'] ) ) {
				$has_error = true;
				self::$error_message = __( 'You are currently not enrolled to this course!', 'cp' );
			} elseif ( 'closed' == ( $course_status = CoursePress_Data_Course::get_course_status( $input['course_id'] ) ) ) {
				$has_error = true;
				self::$error_message = __( 'This course is completed, you can not submit answers anymore.', 'cp' );
			} elseif ( empty( $input['unit_id'] ) ) {
				$has_error = true;
				self::$error_message = __( 'Invalid unit!', 'cp' );
			} elseif ( empty( $input['module'] ) && ! isset( $_FILES ) ) {
				$has_error = true;
				self::$error_message = __( 'No answered modules!', 'cp' );
			}

			if ( $has_error ) {
				add_action( 'coursepress_before_unit_modules', array( __CLASS__, 'show_error_message' ) );
			} else {
				$course_id = (int) $input['course_id'];
				$unit_id = (int) $input['unit_id'];
				$module = (array) $input['module'];

				foreach ( $module as $module_id => $response ) {
					$attributes = CoursePress_Data_Module::attributes( $module_id );
					$module_type = $attributes['module_type'];
					$record = true;

					if ( empty( $response ) && ( 'input-textarea' == $module_type || 'input-text' == $module_type ) ) {
						$record = false;
					}

					if ( 'input-quiz' == $module_type ) {
						foreach ( $attributes['questions'] as $qi => $question ) {
							if ( ! empty( $response[ $qi ] ) ) {
								if ( 'multiple' == $question['type'] ) {
									$values = array_values( $response[ $qi ] );
									$values = array_fill_keys( $values, 1 );
									$response[ $qi ] = $values;
								} else {
									$response[ $qi ] = array( $response[ $qi ] => 1 );
								}
							}
						}
					}

					if ( $record ) {
						CoursePress_Data_Student::module_response( $student_id, $course_id, $unit_id, $module_id, $response );
					}
				}

				// Check for file submission
				if ( ! empty( $_FILES['module'] ) ) {
					if ( ! function_exists( 'wp_handle_upload' ) ) {
						require_once ABSPATH . 'wp-admin/includes/file.php';
					}
					$upload_overrides = array(
						'test_form' => false,
						'mimes' => CoursePress_Helper_Utility::allowed_student_mimes(),
					);
					$files = $_FILES['module'];

					foreach ( $files['name'] as $module_id => $filename ) {
						$attributes = CoursePress_Data_Module::attributes( $module_id );
						$response = CoursePress_Data_Student::get_response( $student_id, $course_id, $unit_id, $module_id );
						$required = ! empty( $attributes['mandatory'] );

						if ( true === $required ) {
							if ( ! empty( $response ) && empty( $filename ) ) {
								continue;
							}
						} else {
							// If it is not required and no submission, break
							if ( empty( $filename ) ) {
								continue;
							}
						}

						$file = array(
							'name' => $filename,
							'size' => $files['size'][ $module_id ],
							'error' => $files['error'][ $module_id ],
							'type' => $files['type'][ $module_id ],
							'tmp_name' => $files['tmp_name'][ $module_id ]
						);
						$response = wp_handle_upload( $file, $upload_overrides );
						$response['size'] = $file['size'];

						if ( ! empty( $response['error'] ) ) {
							$has_error = true;
							self::$error_message = $response['error'];
							add_action( 'coursepress_before_unit_modules', array( __CLASS__, 'show_error_message' ) );
						} else {
							CoursePress_Data_Student::module_response( $student_id, $course_id, $unit_id, $module_id, $response );
						}
					}
				}

				if ( false === $has_error ) {
					$wp_referer = $_REQUEST['_wp_http_referer'];

					if ( ! empty( $_POST['next_page'] ) ) {
						$url_path = CoursePress_Data_Unit::get_unit_url( $unit_id );
						$url_path .= trailingslashit( 'page' ) . $_POST['next_page'];
						
						$wp_referer = $url_path;
					} elseif ( ! empty( $_POST['next_unit'] ) ) {
						$url_path = CoursePress_Data_Unit::get_unit_url( $_POST['next_unit'] );
						$wp_referer = $url_path;
					}

					wp_safe_redirect( $wp_referer ); exit;
				} else {
					die( self::$error_message );
				}
			}
		}
	}

	public static function show_error_message() {
		if ( ! empty( self::$error_message ) ) {
			$format = '<div class="cp-error"><p>%s</p></div>';
			return sprintf( $format, self::$error_message );
		}
	}
}