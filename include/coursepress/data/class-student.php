<?php

class CoursePress_Data_Student {

	/**
	 * Filters through student meta to return only the course IDs.
	 *
	 * @uses Student::filter_course_meta_array() to filter the meta array
	 *
	 * @param $user_id
	 *
	 * @return array|mixed
	 */
	public static function get_course_enrollment_meta( $user_id ) {
		$course_ids = array();
		$meta = get_user_meta( $user_id );

		if ( $meta ) {

			// We only want to parse/return the meta-key; we ignore values.
			$meta_keys = array_filter(
				array_keys( $meta ),
				array( __CLASS__, 'filter_course_meta_array' )
			);

			// Convert the meta-key to a numeric course_id.
			$course_ids = array_map(
				array( __CLASS__, 'course_id_from_meta' ),
				$meta_keys
			);
		}

		return $course_ids;
	}

	/**
	 * Filters through student meta.
	 *
	 * @uses Student::course_id_from_meta()
	 *
	 * @return mixed
	 */
	public static function filter_course_meta_array( $var ) {
		$course_id_from_meta = self::course_id_from_meta( $var );
		if ( ! empty( $course_id_from_meta ) ) {
			return $var;
		}

		return false;
	}

	/**
	 * Extracts the correct Course ID from the meta.
	 *
	 * Makes sure that the correct ID gets returned from the correct blog
	 * regardless of single- or multisite.
	 *
	 * @param $meta_value
	 *
	 * @return bool|mixed
	 */
	public static function course_id_from_meta( $meta_value ) {
		global $wpdb;
		$prefix = $wpdb->prefix;
		$base_prefix = $wpdb->base_prefix;
		$current_blog = str_replace( '_', '', str_replace( $base_prefix, '', $prefix ) );
		if ( is_multisite() && empty( $current_blog ) && defined( 'BLOG_ID_CURRENT_SITE' ) ) {
			$current_blog = BLOG_ID_CURRENT_SITE;
		}

		if ( preg_match( '/enrolled\_course\_date\_/', $meta_value ) ) {

			if ( preg_match( '/^' . $base_prefix . '/', $meta_value ) ) {

				// Get the blog ID that this meta key belongs to
				$blog_id = '';
				preg_match( '/(?<=' . $base_prefix . ')\d*/', $meta_value, $blog_id );
				$blog_id = $blog_id[0];

				// First site...
				if ( defined( 'BLOG_ID_CURRENT_SITE' ) && BLOG_ID_CURRENT_SITE == $current_blog ) {
					$blog_id = $current_blog;
					$course_id = str_replace( $base_prefix . 'enrolled_course_date_', '', $meta_value );
				} else {
					$course_id = str_replace( $base_prefix . $blog_id . '_enrolled_course_date_', '', $meta_value );
				}

				// Only for current site...
				if ( $current_blog != $blog_id ) {
					return false;
				}
			} else {
				// old style, but should support it at least in the listings
				$course_id = str_replace( 'enrolled_course_date_', '', $meta_value );
			}

			if ( ! empty( $course_id ) ) {
				return $course_id;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	/**
	 * Get the IDs of enrolled courses.
	 *
	 * @uses Student::get_course_enrollment_meta()
	 * @param  int $student_id WP User ID.
	 * @return array Contains enrolled course IDs.
	 */
	public static function get_enrolled_courses_ids( $student_id ) {
		return self::get_course_enrollment_meta( $student_id );
	}

	/**
	 * Get the IDs of enrolled courses.
	 *
	 * @uses Student::get_course_enrollment_meta()
	 * @param  int $student_id WP User ID.
	 * @param  int $course_id The course ID to check.
	 * @return bool
	 */
	public static function is_enrolled_in_course( $student_id, $course_id ) {
		$enrolled = self::get_enrolled_courses_ids( $student_id );
		return in_array( $course_id, $enrolled );
	}

	/**
	 * Updates a student's data.
	 *
	 * @param $student_data
	 *
	 * @return bool
	 */
	public static function update_student_data( $student_id, $student_data ) {
		if ( ! isset( $student_data['ID'] ) ) {
			$student_data['ID'] = $student_id;
		}
		$student_data = apply_filters( 'coursepress_student_update_data', $student_data );
		if ( wp_update_user( $student_data ) ) {

			/**
			 * Perform action after a Student object is updated.
			 *
			 * @since 1.2.2
			 */
			do_action( 'coursepress_student_updated', $student_id );

			return true;
		} else {
			return false;
		}
	}

	public static function init_completion_data( $student_id, $course_id ) {
		$data = array();
		CoursePress_Helper_Utility::set_array_val( $data, 'version', '2.0' );

		self::update_completion_data( $student_id, $course_id, $data );

		return $data;
	}

	public static function get_completion_data( $student_id, $course_id ) {

		if ( ! function_exists( 'get_userdata' ) ) {
			require_once( ABSPATH . 'wp-includes/pluggable.php' );
		}

		$data = get_user_option( 'course_' . $course_id . '_progress', $student_id );

		if ( empty( $data ) ) {
			$data = self::init_completion_data( $student_id, $course_id );
		}

		return $data;
	}

	public static function update_completion_data( $student_id, $course_id, $data ) {

		$global_setting = ! is_multisite();
		update_user_option( $student_id, 'course_' . $course_id . '_progress', $data, $global_setting );

	}

	public static function visited_page( $student_id, $course_id, $unit_id, $page, &$data = false ) {

		if ( empty( $data ) ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		CoursePress_Helper_Utility::set_array_val( $data, 'units/' . $unit_id . '/visited_pages/' . $page, $page );
		CoursePress_Helper_Utility::set_array_val( $data, 'units/' . $unit_id . '/last_visited_page', $page );
		self::update_completion_data( $student_id, $course_id, $data );

		return $data;

	}

	public static function visited_module( $student_id, $course_id, $unit_id, $module_id, &$data = false ) {

		if ( empty( $data ) ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		CoursePress_Helper_Utility::set_array_val( $data, 'completion/' . $unit_id . '/modules_seen/' . $module_id, true );
		self::update_completion_data( $student_id, $course_id, $data );

		return $data;

	}

	public static function module_response( $student_id, $course_id, $unit_id, $module_id, $response, &$data = false ) {

		$attributes = CoursePress_Data_Module::attributes( $module_id );

		if ( empty( $attributes ) || 'output' === $attributes['mode'] ) {
			return;
		}

		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$grade = - 1;

		// Auto-grade the easy ones
		switch ( $attributes['module_type'] ) {
			case 'input-checkbox':
				$total = count( $attributes['answers_selected'] );
				$correct = 0;
				if ( is_array( $response ) ) {
					foreach ( $response as $answer ) {
						if ( in_array( $answer, $attributes['answers_selected'] ) ) {
							$correct += 1;
						}
					}
				}

				$grade = (int) ( $correct / $total * 100 );
				break;

			case 'input-select':
			case 'input-radio':
				if ( $response == $attributes['answers_selected'] ) {
					$grade = 100;
				} else {
					$grade = 0;
				}
				break;

			case 'input-quiz':
				$result = CoursePress_Data_Module::get_quiz_results(
					$student_id,
					$course_id,
					$unit_id,
					$module_id,
					$response,
					$data
				);
				$grade = $result['grade'];
				break;
		}

		$grade = apply_filters(
			'coursepress_autograde_module_response',
			$grade,
			$module_id,
			$student_id
		);

		$grade_data = array(
			'graded_by' => (-1 == $grade ? '' : 'auto'),
			'grade' => $grade,
			'date' => (-1 == $grade ? '' : current_time( 'mysql' ) ),
		);

		$response_data = array(
			'response' => $response,
			'date' => current_time( 'mysql' ),
			'grades' => (-1 == $grade ? array() : array( $grade_data ) ),
			'feedback' => array(),
		);

		if ( isset( $attributes['mandatory'] ) && $attributes['mandatory'] ) {
			$key = 'completion/' . $unit_id . '/completed_mandatory';
			$mandatory = (int) CoursePress_Helper_Utility::get_array_val( $data, $key );
			CoursePress_Helper_Utility::set_array_val( $data, $key, $mandatory + 1 );
		}

		CoursePress_Helper_Utility::set_array_val( $data, 'units/' . $unit_id . '/responses/' . $module_id . '/', $response_data );
		self::get_calculated_completion_data( $student_id, $course_id, $data );
		self::update_completion_data( $student_id, $course_id, $data );

		// Might as well do it on an AJAX call to make the experience a bit better.
		//self::calculate_completion( $student_id, $course_id );

		return $data;

	}

	public static function get_responses( $student_id, $course_id, $unit_id, $module_id, $response_only = false, &$data = false ) {

		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$responses = CoursePress_Helper_Utility::get_array_val( $data, 'units/' . $unit_id . '/responses/' . $module_id );

		// Don't return the dates
		if ( $response_only ) {

			$result = array();
			if ( ! empty( $responses ) ) {
				foreach ( $responses as $key => $r ) {
					$result[ $key ] = $r['response'];
				}
			}

			return $result;

		}

		return empty( $responses ) ? array() : $responses;

	}

	public static function get_grade(
		$student_id, $course_id, $unit_id, $module_id, $response_index = false, $grade_index = false, &$data = false
	) {
		$grade = false;

		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$response = self::get_response(
			$student_id,
			$course_id,
			$unit_id,
			$module_id,
			$response_index,
			$data
		);

		if ( ! isset( $response['grades'] ) ) {
			$response['grades'] = array();
		}

		// Get last grade.
		$last_grade = ( count( $response['grades'] ) - 1 );
		if ( ! $grade_index || $grade_index > $last_grade ) {
			$grade_index = $last_grade;
		}

		if ( isset( $response['grades'][ $grade_index ] ) ) {
			$grade = $response['grades'][ $grade_index ];

			if ( empty( $grade['grade'] ) && 0 != $grade['grade'] ) {
				$grade['grade'] = -1;
			}
			$grade['grade'] = (int) $grade['grade'];
		}

		return $grade;
	}

	public static function record_grade(
		$student_id, $course_id, $unit_id, $module_id, $grade, $response_index = false, &$data = false
	) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$responses = CoursePress_Helper_Utility::get_array_val(
			$data,
			'units/' . $unit_id . '/responses/' . $module_id
		);

		// Get last grade
		if ( ! $response_index ) {
			$response_index = ( count( $responses ) - 1 );
		}

		$grade_data = array(
			'graded_by' => get_current_user_id(),
			'grade' => (int) $grade,
			'date' => current_time( 'mysql' ),
		);

		CoursePress_Helper_Utility::set_array_val(
			$data,
			'units/' . $unit_id . '/responses/' . $module_id . '/' . $response_index . '/grades/',
			$grade_data
		);

		self::get_calculated_completion_data( $student_id, $course_id, $data );
		self::update_completion_data( $student_id, $course_id, $data );
		return $data;
	}

	public static function get_response(
		$student_id, $course_id, $unit_id, $module_id, $response_index = false, &$data = false
	) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$responses = CoursePress_Helper_Utility::get_array_val(
			$data,
			'units/' . $unit_id . '/responses/' . $module_id
		);

		// Get last grade
		if ( ! $response_index ) {
			$response_index = ( count( $responses ) - 1 );
		}

		return ! empty( $responses ) && isset( $responses[ $response_index ] ) ? $responses[ $response_index ] : false;
	}

	public static function get_feedback(
		$student_id, $course_id, $unit_id, $module_id, $response_index = false, $feedback_index = false, &$data = false
	) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$response = self::get_response(
			$student_id,
			$course_id,
			$unit_id,
			$module_id,
			$response_index,
			$data
		);
		$feedback = isset( $response['feedback'] ) ? $response['feedback'] : array();

		// Get last grade
		if ( ! $feedback_index ) {
			$feedback_index = ( count( $feedback ) - 1 );
		}

		return ! empty( $feedback ) && isset( $feedback[ $feedback_index ] ) ? $feedback[ $feedback_index ] : false;
	}

	public static function record_feedback(
		$student_id, $course_id, $unit_id, $module_id, $feedback_new, $response_index = false, &$data = false
	) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$responses = CoursePress_Helper_Utility::get_array_val(
			$data,
			'units/' . $unit_id . '/responses/' . $module_id
		);

		// Get last grade
		if ( ! $response_index ) {
			$response_index = ( count( $responses ) - 1 );
		}

		$feedback_data = array(
			'feedback_by' => get_current_user_id(),
			'feedback' => CoursePress_Helper_Utility::filter_content( $feedback_new ),
			'date' => current_time( 'mysql' ),
		);

		CoursePress_Helper_Utility::set_array_val(
			$data,
			'units/' . $unit_id . '/responses/' . $module_id . '/' . $response_index . '/feedback/',
			$feedback_data
		);

		self::update_completion_data( $student_id, $course_id, $data );

		return $data;
	}

	public static function get_calculated_completion_data( $student_id, $course_id, &$student_progress = false ) {

		if ( ! $student_progress ) {
			$student_progress = self::get_completion_data( $student_id, $course_id );
		}

		$student_units = isset( $student_progress['units'] ) ? array_keys( $student_progress['units'] ) : array();

		$units = CoursePress_Data_Course::get_units_with_modules( $course_id );

		$course_required_steps = 0;
		$course_completed_steps = 0;
		$course_mandatory_steps = 0;
		$course_all_steps = 0;
		$course_average_grade = 0;
		$course_completed = 0;
		$course_progress = 0;
		$valid_units = 0;
		$is_done = CoursePress_Helper_Utility::get_array_val(
			$student_progress,
			'completion/completed'
		);

		foreach ( $units as $unit_id => $unit ) {
			$unit_steps = 0;
			$unit_completed_steps = 0;
			$unit_required_steps = 0;
			$unit_required_completed_steps = 0;
			$unit_progress = 0;
			$is_valid_unit = false;

			$unit_mandatory_answered = 0;
			$unit_required_assessable = 0;
			$unit_assessables = 0;
			$unit_average_grade = 0;
			$force_current_unit_successful_completion = get_post_meta( $unit_id, 'force_current_unit_successful_completion', true );

			// Modules
			foreach ( $unit['pages'] as $page ) {
				foreach ( $page['modules'] as $module_id => $module ) {
					$attributes = CoursePress_Data_Module::attributes( $module_id );
					$is_mandatory = cp_is_true( $attributes['mandatory'] );
					$is_assessable = cp_is_true( $attributes['assessable'] );
					$is_answerable = preg_match( '%input-%', $attributes['module_type'] );

					// Only vaidate answerable modules
					if ( ! $is_answerable ) {
						continue;
					}

					$is_valid_unit = true;
					$responses = CoursePress_Helper_Utility::get_array_val(
						$student_progress,
						'units/' . $unit_id . '/responses/' . $module_id
					);

					// Only validate the last submitted response
					$last_answer = is_array( $responses ) ? array_pop( $responses ) : array();

					// Count all steps
					$unit_steps++;

					//Count mandatory modules
					if ( $is_mandatory ) {
						$unit_required_steps++;
					}

					if ( ! empty( $last_answer ) ) {
						$unit_completed_steps++;

						if ( $is_mandatory ) {
							$unit_mandatory_answered++;
						}
						
						$minimum_grade = (int) $attributes['minimum_grade'];
	
						// Get the last grade and see if we pass
						$grades = self::get_grade( $student_id, $course_id, $unit_id, $module_id, false, false, $student_progress );
						$grade = CoursePress_Helper_Utility::get_array_val(
							$grades,
							'grade'
						);
						$pass = (int) $grade >= (int) $minimum_grade;
						$unit_average_grade += $grade;

						if ( $pass ) {
							CoursePress_Helper_Utility::set_array_val(
								$student_progress,
								'completion/' . $unit_id . '/passed/' . $module_id,
								$grade
							);
						}

						if ( cp_is_true( $force_current_unit_successful_completion ) ) {
							if ( ! $pass ) {
								$unit_assessables++;
							}
						} 
					}
				}
			}

			if ( in_array( $unit_id, $student_units ) ) {
				CoursePress_Helper_Utility::set_array_val(
					$student_progress,
					'completion/' . $unit_id . '/required_steps',
					$unit_required_steps
				);
	
				CoursePress_Helper_Utility::set_array_val(
					$student_progress,
					'completion/' . $unit_id . '/completed_mandatory',
					$unit_mandatory_answered
				);
	
				if ( $unit_required_steps === $unit_mandatory_answered ) {
					CoursePress_Helper_Utility::set_array_val(
						$student_progress,
						'completion/' . $unit_id . '/all_mandatory',
						true
					);
				}

				if ( ! $unit_assessables && $unit_required_steps === $unit_mandatory_answered ) {
					CoursePress_Helper_Utility::set_array_val(
						$student_progress,
						'completion/' . $unit_id . '/completed',
						true
					);
					$course_completed += $unit_steps;
				}
	
				CoursePress_Helper_Utility::set_array_val(
					$student_progress,
					'completion/' . $unit_id . '/completed_steps',
					$unit_completed_steps
				);

				$unit_progress = ( $unit_completed_steps * ( 100 / $unit_steps ) );
				$course_progress += $unit_progress;
				CoursePress_Helper_Utility::set_array_val(
					$student_progress,
					'completion/' . $unit_id . '/progress',
					$unit_progress
				);
				
				if ( $is_valid_unit ) {
					$valid_units++;
				}
			}

			$course_all_steps += $unit_steps;
			$course_mandatory_steps += $unit_required_steps;
			$course_completed_steps += $unit_completed_steps;
			$course_average_grade += ( $unit_average_grade / 100 );
		}

		CoursePress_Helper_Utility::set_array_val(
			$student_progress,
			'completion/required_steps',
			$course_mandatory_steps
		);
		CoursePress_Helper_Utility::set_array_val(
			$student_progress,
			'completion/completed_steps',
			$course_completed_steps
		);
		CoursePress_Helper_Utility::set_array_val(
			$student_progress,
			'completion/average',
			( 100 / $course_all_steps ) * $course_average_grade
		);

		CoursePress_Helper_Utility::set_array_val(
			$student_progress,
			'completion/progress',
			$course_progress / $valid_units
		);

		if ( $course_completed === $course_all_steps ) {
			CoursePress_Helper_Utility::set_array_val(
				$student_progress,
				'completion/completed',
				true
			);

			if ( ! $is_done ) {
				// Notify other modules about the lucky student!
				do_action(
					'coursepress_student_course_completed',
					$student_id,
					$course_id,
					get_post_field( 'post_title', $course_id )
				);
				
				// Generate the certificate and send email to the student.
				CoursePress_Data_Certificate::generate_certificate(
					$student_id,
					$course_id
				);
			}
		}

		return $student_progress;
	}

	public static function calculate_completion( $student_id, $course_id ) {
		if ( empty( $student_id ) ) {
			return;
		}

		$student_progress = self::get_completion_data( $student_id, $course_id );
		$student_units = isset( $student_progress['units'] ) ? array_keys( $student_progress['units'] ) : array();
		$units = CoursePress_Data_Course::get_units_with_modules( $course_id );

		$course_required_steps = 0;
		$course_completed_steps = 0;

		$total_units = count( $units );
		$total_completion = 0;

		foreach ( $units as $unit_id => $unit ) {
			// Don't bother calculating completion if the student hasn't even started the unit.
			if ( ! in_array( $unit_id, $student_units ) ) {
				continue;
			}

			$required_steps = 0;
			$completed_steps = 0;

			// PAGES.
			$total_pages = count( $unit['pages'] );
			$required_steps += $total_pages;
			$visited_pages = CoursePress_Helper_Utility::get_array_val(
				$student_progress,
				'units/' . $unit_id . '/visited_pages'
			);
			$total_visited_pages = count( $visited_pages );
			$completed_steps += $total_visited_pages;

			if ( $total_pages === $total_visited_pages ) {
				CoursePress_Helper_Utility::set_array_val(
					$student_progress,
					'completion/' . $unit_id . '/all_pages',
					true
				);
			}

			// First milestone
			CoursePress_Helper_Utility::set_array_val(
				$student_progress,
				'completion/' . $unit_id . '/required_steps',
				$required_steps
			);
			CoursePress_Helper_Utility::set_array_val(
				$student_progress,
				'completion/' . $unit_id . '/completed_steps',
				$completed_steps
			);

			// MODULES
			$assessable_mandatory = 0;
			$mandatory = 0;
			$student_assessable_mandatory = 0;
			$student_mandatory = 0;
			foreach ( $unit['pages'] as $page ) {

				foreach ( $page['modules'] as $module_id => $module ) {

					$attributes = CoursePress_Data_Module::attributes( $module_id );

					if ( 'output' === $attributes['mode'] ) {
						continue;
					}

					// Only worry about assessable units if they are mandatory
					if ( $attributes['assessable'] && $attributes['mandatory'] ) {

						// Only worry about assessable units if they are mandatory
						$required_steps += 1;
						$assessable_mandatory += 1;

						// Get the last grade and see if we pass
						$grade = self::get_grade( $student_id, $course_id, $unit_id, $module_id, false, false, $student_progress );

						$pass = (int) $grade >= (int) $attributes['minimum_grade'];

						if ( $pass ) {

							$completed_steps += 1;
							$student_assessable_mandatory += 1;

							$check = CoursePress_Helper_Utility::get_array_val( $student_progress, 'completion/' . $unit_id . '/passed/' . $module_id );
							if ( isset( $check ) && empty( $check ) ) {
								do_action( 'coursepress_student_module_passed', $student_id, $module_id, get_post_field( 'post_tile' ), $unit_id, $course_id );
							}

							CoursePress_Helper_Utility::set_array_val( $student_progress, 'completion/' . $unit_id . '/passed/' . $module_id, true );

							$check = CoursePress_Helper_Utility::get_array_val( $student_progress, 'completion/' . $unit_id . '/answered/' . $module_id );
							if ( isset( $check ) && empty( $check ) ) {
								do_action( 'coursepress_student_module_attempted', $student_id, $module_id, get_post_field( 'post_tile' ), $unit_id, $course_id );
							}

							CoursePress_Helper_Utility::set_array_val( $student_progress, 'completion/' . $unit_id . '/answered/' . $module_id, true );

						}
					} elseif ( $attributes['mandatory'] ) {

						// Mandatory questions must at least have an answer, even if its not assessable
						$required_steps += 1;
						$mandatory += 1;

						// Is there a response?
						$responses = CoursePress_Helper_Utility::get_array_val( $student_progress, 'units/' . $unit_id . '/responses/' . $module_id );
						$response_count = ! empty( $responses ) ? count( $responses ) : 0;

						if ( ! empty( $response_count ) ) {

							$completed_steps += 1;
							$student_mandatory += 1;

							CoursePress_Helper_Utility::set_array_val( $student_progress, 'completion/' . $unit_id . '/answered/' . $module_id, true );

							$check = CoursePress_Helper_Utility::get_array_val( $student_progress, 'completion/' . $unit_id . '/answered/' . $module_id );
							if ( isset( $check ) && empty( $check ) ) {
								do_action( 'coursepress_student_module_attempted', $student_id, $module_id, get_post_field( 'post_tile' ), $unit_id, $course_id );
							}
						}
					}  // Mandatory Assessable or just Mandatory

				} // Module

			} // Page

			if ( $assessable_mandatory === $student_assessable_mandatory ) {
				CoursePress_Helper_Utility::set_array_val( $student_progress, 'completion/' . $unit_id . '/all_required_assessable', true );
			}

			$total_mandatory = $mandatory + $assessable_mandatory;
			$total_student_mandatory = $student_mandatory + $student_assessable_mandatory;

			if ( $total_mandatory === $total_student_mandatory ) {
				CoursePress_Helper_Utility::set_array_val( $student_progress, 'completion/' . $unit_id . '/all_mandatory', true );
			}

			// Next milestone
			CoursePress_Helper_Utility::set_array_val( $student_progress, 'completion/' . $unit_id . '/required_steps', $required_steps );
			CoursePress_Helper_Utility::set_array_val( $student_progress, 'completion/' . $unit_id . '/completed_steps', $completed_steps );

			// Is unit complete?
			if ( $required_steps === $completed_steps ) {
				$check = CoursePress_Helper_Utility::get_array_val( $student_progress, 'completion/' . $unit_id . '/completed' );
				if ( isset( $check ) && empty( $check ) ) {
					do_action( 'coursepress_student_unit_completed', $student_id, $unit_id, $unit['unit']->title, $course_id );
				}
				CoursePress_Helper_Utility::set_array_val( $student_progress, 'completion/' . $unit_id . '/completed', true );
			}

			$progress = (int) ($completed_steps / $required_steps * 100);
			CoursePress_Helper_Utility::set_array_val(
				$student_progress,
				'completion/' . $unit_id . '/progress',
				$progress
			);
			$total_completion += $progress;

			// Update Course Steps
			$course_required_steps += $required_steps;
			$course_completed_steps += $completed_steps;
			CoursePress_Helper_Utility::set_array_val(
				$student_progress,
				'completion/required_steps',
				$course_required_steps
			);
			CoursePress_Helper_Utility::set_array_val(
				$student_progress,
				'completion/completed_steps',
				$course_completed_steps
			);

		} // End of foreach ( $units ) ...

		// Record course progress.
		$progress = 0;
		if ( $total_units > 0 ) {
			$progress = (int) ($total_completion / $total_units * 100);
		}

		CoursePress_Helper_Utility::set_array_val(
			$student_progress,
			'completion/progress',
			$progress
		);

		// Check if course is completed.
		if ( $course_required_steps === $course_completed_steps && ! empty( $student_units ) ) {
			$check = CoursePress_Helper_Utility::get_array_val(
				$student_progress,
				'completion/completed'
			);

			// Only process if not completed yet.
			if ( empty( $check ) ) {
				// Notify other modules about the lucky student!
				do_action(
					'coursepress_student_course_completed',
					$student_id,
					$course_id,
					get_post_field( 'post_title', $course_id )
				);

				// Generate the certificate and send email to the student.
				CoursePress_Data_Certificate::generate_certificate(
					$student_id,
					$course_id
				);

				// Mark course as completed.
				CoursePress_Helper_Utility::set_array_val(
					$student_progress,
					'completion/completed',
					true
				);
			}
		}

		self::update_completion_data(
			$student_id,
			$course_id,
			$student_progress
		);

		return $student_progress;
	}

	public static function get_mandatory_completion( $student_id, $course_id, $unit_id, &$data = false ) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		return array(
			'required' => CoursePress_Data_Unit::get_number_of_mandatory( $unit_id ),
			'completed' => CoursePress_Helper_Utility::get_array_val(
				$data,
				'completion/' . $unit_id . '/completed_mandatory'
			),
		);
	}

	public static function get_unit_progress( $student_id, $course_id, $unit_id, &$data = false ) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		return (int) CoursePress_Helper_Utility::get_array_val(
			$data,
			'completion/' . $unit_id . '/progress'
		);
	}

	public static function get_course_progress( $student_id, $course_id, &$data = false ) {
		if ( empty( $data ) ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		return (int) CoursePress_Helper_Utility::get_array_val(
			$data,
			'completion/progress'
		);
	}

	public static function is_mandatory_done( $student_id, $course_id, $unit_id, &$data = false ) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$mandatory = CoursePress_Helper_Utility::get_array_val(
			$data,
			'completion/' . $unit_id . '/all_mandatory'
		);

		return cp_is_true( $mandatory );
	}

	public static function is_unit_complete( $student_id, $course_id, $unit_id, &$data = false ) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$completed = CoursePress_Helper_Utility::get_array_val(
			$data,
			'completion/' . $unit_id . '/completed'
		);

		return cp_is_true( $completed );
	}

	public static function is_course_complete( $student_id, $course_id, &$data = false ) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$progress = CoursePress_Helper_Utility::get_array_val(
			$data,
			'completion/completed'
		);

		return cp_is_true( $progress );
	}

	public static function count_course_responses( $student_id, $course_id, $data = false ) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}

		$units = isset( $data['units'] ) ? $data['units'] : array();

		$response_count = 0;
		foreach ( $units as $key => $unit ) {
			$modules = CoursePress_Helper_Utility::get_array_val(
				$data,
				'units/' . $key . '/responses'
			);

			if ( ! empty( $modules ) ) {
				$response_count += count( $modules );
			}
		}

		return $response_count;
	}

	public static function average_course_responses( $student_id, $course_id, $data = false ) {
		if ( false === $data ) {
			$data = self::get_completion_data( $student_id, $course_id );
		}
		$average = CoursePress_Helper_Utility::get_array_val(
			$data,
			'completion/average'
		);
		return (int) $average;
/*

		$units = isset( $data['units'] ) ? $data['units'] : array();
		$total_response = 0;
		$total_grade = 0;

		foreach ( $units as $key => $unit ) {
			$modules = CoursePress_Helper_Utility::get_array_val(
				$data,
				'units/' . $key . '/responses'
			);

			$total_response += count( $modules );

			foreach ( $modules as $mod_key => $module ) {
				$attributes = CoursePress_Data_Module::attributes( $mod_key );
				if ( 'output' === $attributes['mode'] || ! $attributes['assessable'] ) {
					unset( $modules[ $mod_key ] );
					continue;
				}

				$responses = CoursePress_Helper_Utility::get_array_val(
					$data,
					'units/' . $key . '/responses/' . $mod_key
				);

				if ( ! is_array( $responses ) ) { continue; }
				if ( ! count( $responses ) ) { continue; }

				$last_response = array_pop( $responses );

				if ( ! isset( $last_response['grades'] ) ) { continue; }
				if ( ! is_array( $last_response['grades'] ) ) { continue; }

				$grade = array_pop( $last_response['grades'] );
				$total_grade += (int) $grade['grade'];
			}
		}

		if ( $total_response > 0 ) {
			return (int) ($total_grade / $total_response);
		}
		return 0;
/*/
	}

	/**
	 * Send email about successful account creation.
	 * The email contains several links but no login name or password.
	 *
	 * @since  1.0.0
	 * @param  int $student_id The newly created WP User ID.
	 * @return bool True on success.
	 */
	public static function send_registration( $student_id ) {
		$student_data = get_userdata( $student_id );

		$email_args = array();
		$email_args['email'] = $student_data['user_email'];
		$email_args['first_name'] = $student_data['first_name'];
		$email_args['last_name'] = $student_data['last_name'];
		$email_args['fields'] = array();
		$email_args['fields']['student_id'] = $student_id;
		$email_args['fields']['student_username'] = $student_data['user_login'];
		$email_args['fields']['student_password'] = $student_data['user_pass'];

		$sent = CoursePress_Helper_Email::send_email(
			CoursePress_Helper_Email::REGISTRATION,
			$email_args
		);

		return $sent;
	}

	public static function get_admin_workbook_link( $student_id, $course_id ) {
		$workbook_link = add_query_arg(
			array( 
				'page' => CoursePress_View_Admin_Student::$slug,
				'view' => 'workbook',
				'course_id' => $course_id,
				'student_id' => $student_id,
			),
			admin_url( 'admin.php' )
		);

		return $workbook_link;
	}
}