<?php

class CoursePress_Model_Course {

	private static $post_type = 'course';
	private static $post_taxonomy = 'course_category';
	public static $messages;
	private static $last_course_id = 0;
	private static $where_post_status;
	private static $email_type;
	public static $last_course_category = '';
	public static $last_course_subpage = '';
	public static $previewability = false;
	public static $structure_visibility = false;

	public static function get_format() {

		return array(
			'post_type' => self::get_post_type_name(),
			'post_args' => array(
				'labels'              => array(
					'name'               => __( 'Courses', CoursePress::TD ),
					'singular_name'      => __( 'Course', CoursePress::TD ),
					'add_new'            => __( 'Create New', CoursePress::TD ),
					'add_new_item'       => __( 'Create New Course', CoursePress::TD ),
					'edit_item'          => __( 'Edit Course', CoursePress::TD ),
					'edit'               => __( 'Edit', CoursePress::TD ),
					'new_item'           => __( 'New Course', CoursePress::TD ),
					'view_item'          => __( 'View Course', CoursePress::TD ),
					'search_items'       => __( 'Search Courses', CoursePress::TD ),
					'not_found'          => __( 'No Courses Found', CoursePress::TD ),
					'not_found_in_trash' => __( 'No Courses found in Trash', CoursePress::TD ),
					'view'               => __( 'View Course', CoursePress::TD )
				),
				'public'              => false,
				'exclude_from_search' => false,
				'has_archive'         => true,
				'show_ui'             => false,
				'publicly_queryable'  => true,
				'capability_type'     => 'course',
				'map_meta_cap'        => true,
				'query_var'           => true,
				'rewrite'             => array(
					'slug'       => CoursePress_Core::get_slug( 'course' ),
					'with_front' => false
				),
				'supports'            => array( 'thumbnail' ),
				'taxonomies'          => array( 'course_category' ),
			)
		);

	}

	public static function get_taxonomy() {
		$prefix = defined( 'COURSEPRESS_CPT_PREFIX' ) ? COURSEPRESS_CPT_PREFIX : '';
		$prefix = empty( $prefix ) ? '' : sanitize_text_field( $prefix ) . '_';

		return array(
			'taxonomy_type' => self::$post_taxonomy,
			'post_type'     => $prefix . self::$post_type,
			'taxonomy_args' => apply_filters( 'coursepress_register_course_category', array(
					'labels'            => array(
						'name'          => __( 'Course Categories', CoursePress::TD ),
						'singular_name' => __( 'Course Category', CoursePress::TD ),
						'search_items'  => __( 'Search Course Categories', CoursePress::TD ),
						'all_items'     => __( 'All Course Categories', CoursePress::TD ),
						'edit_item'     => __( 'Edit Course Categories', CoursePress::TD ),
						'update_item'   => __( 'Update Course Category', CoursePress::TD ),
						'add_new_item'  => __( 'Add New Course Category', CoursePress::TD ),
						'new_item_name' => __( 'New Course Category Name', CoursePress::TD ),
						'menu_name'     => __( 'Course Category', CoursePress::TD ),
					),
					'hierarchical'      => true,
					'sort'              => true,
					'args'              => array( 'orderby' => 'term_order' ),
					'rewrite'           => array( 'slug' => CoursePress_Core::get_setting( 'slugs/category', 'course_category' ) ),
					'show_admin_column' => true,
					'capabilities'      => array(
						'manage_terms' => 'coursepress_course_categories_manage_terms_cap',
						'edit_terms'   => 'coursepress_course_categories_edit_terms_cap',
						'delete_terms' => 'coursepress_course_categories_delete_terms_cap',
						'assign_terms' => 'coursepress_courses_cap'
					),
				)
			)
		);

	}

	public static function get_message( $key, $alternate = '' ) {

		$message_keys = array_keys( self::$messages );

		if ( ! in_array( $key, $message_keys ) ) {
			self::$messages = self::get_default_messages( $key );
		}

		return ! empty( self::$messages[ $key ] ) ? CoursePress_Helper_Utility::filter_content( self::$messages[ $key ] ) : CoursePress_Helper_Utility::filter_content( $alternate );
	}

	public static function get_default_messages( $key = '' ) {
		return apply_filters( 'coursepress_course_messages', array(
			'ca'  => __( 'New Course added successfully!', CoursePress::TD ),
			'cu'  => __( 'Course updated successfully.', CoursePress::TD ),
			'usc' => __( 'Unit status changed successfully', CoursePress::TD ),
			'ud'  => __( 'Unit deleted successfully', CoursePress::TD ),
			'ua'  => __( 'New Unit added successfully!', CoursePress::TD ),
			'uu'  => __( 'Unit updated successfully.', CoursePress::TD ),
			'as'  => __( 'Student added to the class successfully.', CoursePress::TD ),
			'ac'  => __( 'New class has been added successfully.', CoursePress::TD ),
			'dc'  => __( 'Selected class has been deleted successfully.', CoursePress::TD ),
			'us'  => __( 'Selected student has been withdrawed successfully from the course.', CoursePress::TD ),
			'usl' => __( 'Selected students has been withdrawed successfully from the course.', CoursePress::TD ),
			'is'  => __( 'Invitation sent sucessfully.', CoursePress::TD ),
			'ia'  => __( 'Successfully added as instructor.', CoursePress::TD ),
		), $key );
	}


	public static function update( $course_id, $data ) {
		global $user_id;

		do_action( 'coursepress_course_pre_update', $course_id, $data );

		$new_course = empty( $course_id ) ? true : false;

		$course = $new_course ? false : get_post( $course_id );

		// Publishing toggle
		//$post_status = empty( $this->data[ 'status' ] ) ? 'publish' : $this->data[ 'status' ];

		$post = array(
			'post_author' => $course ? $course->post_author : $user_id,
			'post_status' => $course ? $course->post_status : 'private',
			'post_type'   => self::get_post_type_name( true ),
		);

		// Make sure we get existing settings if not all data is being submitted
		if ( ! $new_course ) {
			$post['post_excerpt'] = $course && isset( $data->course_excerpt ) ? CoursePress_Helper_Utility::filter_content( $data->course_excerpt ) : $course->post_excerpt;
			$post['post_content'] = $course && isset( $data->course_description ) ? CoursePress_Helper_Utility::filter_content( $data->course_description ) : $course->post_content;
			$post['post_title']   = $course && isset( $data->course_name ) ? CoursePress_Helper_Utility::filter_content( $data->course_name ) : $course->post_title;
			if ( ! empty( $data->course_name ) ) {
				$post['post_name'] = wp_unique_post_slug( sanitize_title( $post['post_title'] ), $course_id, 'publish', 'course', 0 );
			}
		} else {
			$post['post_excerpt'] = CoursePress_Helper_Utility::filter_content( $data->course_excerpt );
			if ( isset( $data->course_description ) ) {
				$post['post_content'] = CoursePress_Helper_Utility::filter_content( $data->course_description );
			}
			$post['post_title'] = CoursePress_Helper_Utility::filter_content( $data->course_name );
			$post['post_name']  = wp_unique_post_slug( sanitize_title( $post['post_title'] ), 0, 'publish', 'course', 0 );
		}

		// Set the ID to trigger update and not insert
		if ( ! empty ( $course_id ) ) {
			$post['ID'] = $course_id;
		}

		// Turn off ping backs
		$post['ping_status'] = 'closed';

		// Insert / Update the post
		$course_id = wp_insert_post( apply_filters( 'coursepress_pre_insert_post', $post ) );

		// Course Settings
		$settings = self::get_setting( $course_id, true );


		// @todo: remove this, its just here to help set initial meta that got missed during dev
		//$meta = get_post_meta( $course_id );
		//self::set_setting( $settings, 'structure_visible', self::upgrade_meta_val( $meta, 'course_structure_options', '' ) );

		// Upgrade old settings
		if ( empty( $settings ) && ! $new_course ) {
			self::upgrade_settings( $course_id );
		}

		if ( ! empty( $course_id ) ) {

			foreach ( $data as $key => $value ) {

				// Its easier working with arrays here
				$value = CoursePress_Helper_Utility::object_to_array( $value );

				// Set fields based on meta_ name prefix
				if ( preg_match( "/meta_/i", $key ) ) {//every field name with prefix "meta_" will be saved as post meta automatically
					self::set_setting( $settings, str_replace( 'meta_', '', $key ), CoursePress_Helper_Utility::filter_content( $value ) );
				}

				// MP Stuff.. this is no longer dealt with here!
				//if ( preg_match( "/mp_/i", $key ) ) {
				//	update_post_meta( $course_id, $key, cp_filter_content( $value ) );
				//}

				// Add taxonomy terms
				if ( $key == 'course_category' || $key == 'meta_course_category' ) {
					if ( isset( $data->meta_course_category ) ) {
						self::set_setting( $settings, 'course_category', CoursePress_Helper_Utility::filter_content( $value ) );

						if ( is_array( CoursePress_Helper_Utility::object_to_array( $data->meta_course_category ) ) ) {
							$sanitized_array = array();
							foreach ( $data->meta_course_category as $cat_id ) {
								$sanitized_array[] = (int) $cat_id;
							}

							wp_set_object_terms( $course_id, $sanitized_array, self::get_post_category_name( true ), false );
						} else {
							$cat = array( (int) $data->meta_course_category );
							if ( $cat ) {
								wp_set_object_terms( $course_id, $cat, self::get_post_category_name( true ), false );
							}
						}
					} // meta_course_category
				}

				//Add featured image
				if ( 'meta_listing_image' == $key ) {

					// Legacy, breaks theme support

					//$course_image_width  = CoursePress_Core::get_setting( 'course/image_width', 235 );
					//$course_image_height = CoursePress_Core::get_setting( 'course/image_height', 225 );
					//
					//$upload_dir_info = wp_upload_dir();
					//
					//$fl = trailingslashit( $upload_dir_info['path'] ) . basename( $value );
					//
					//$image = wp_get_image_editor( $fl ); // Return an implementation that extends <tt>WP_Image_Editor</tt>
					//
					//if ( ! is_wp_error( $image ) ) {
					//
					//	$image_size = $image->get_size();
					//
					//	if ( ( $image_size['width'] < $course_image_width || $image_size['height'] < $course_image_height ) || ( $image_size['width'] == $course_image_width && $image_size['height'] == $course_image_height ) ) {
					//		// legacy
					//		update_post_meta( $course_id, '_thumbnail_id', CoursePress_Helper_Utility::filter_content( $value ) );
					//	} else {
					//		$ext           = pathinfo( $fl, PATHINFO_EXTENSION );
					//		$new_file_name = str_replace( '.' . $ext, '-' . $course_image_width . 'x' . $course_image_height . '.' . $ext, basename( $value ) );
					//		$new_file_path = str_replace( basename( $value ), $new_file_name, $value );
					//		// legacy
					//		update_post_meta( $course_id, '_thumbnail_id', CoursePress_Helper_Utility::filter_content( $new_file_path ) );
					//	}
					//} else {
					//	// legacy
					//	update_post_meta( $course_id, '_thumbnail_id', CoursePress_Helper_Utility::filter_content( $value, true ) );
					//}

					// Remove Thumbnail
					delete_post_meta( $course_id, '_thumbnail_id' );

				}

				//Add instructors
				if ( 'instructor' == $key ) {

					//Get last instructor ID array in order to compare with posted one
					$old_post_meta = self::get_setting( $course_id, 'instructors', false );

					if ( serialize( array( $value ) ) !== serialize( $old_post_meta ) || 0 == $value ) {//If instructors IDs don't match
						delete_post_meta( $course_id, 'instructors' );
						self::delete_setting( $course_id, 'instructors' );
						CoursePress_Helper_Utility::delete_user_meta_by_key( 'course_' . $course_id );
					}

					if ( 0 != $value ) {

						update_post_meta( $course_id, 'instructors', CoursePress_Helper_Utility::filter_content( $value ) ); //Save instructors for the Course


						foreach ( $value as $instructor_id ) {
							$global_option = ! is_multisite();
							update_user_option( $instructor_id, 'course_' . $course_id, $course_id, $global_option ); //Link courses and instructors ( in order to avoid custom tables ) for easy MySql queries ( get instructor stats, his courses, etc. )
						}
					} // only add meta if array is sent
				}

			}

			// Update Meta
			$settings = apply_filters( 'coursepress_course_update_meta', $settings, $course_id );
			self::update_setting( $course_id, true, $settings );

			if ( $new_course ) {

				/**
				 * Perform action after course has been created.
				 *
				 * @since 1.2.1
				 */
				do_action( 'coursepress_course_created', $course_id, $settings );
			} else {

				/**
				 * Perform action after course has been updated.
				 *
				 * @since 1.2.1
				 */
				do_action( 'coursepress_course_updated', $course_id, $settings );
			}

			return $course_id;

		}


	}

	public static function add_instructor( $course_id, $instructor_id ) {

		$instructors = maybe_unserialize( self::get_setting( $course_id, 'instructors', false ) );
		$instructors = empty( $instructors ) ? array() : $instructors;

		if( ! in_array( $instructor_id, $instructors ) ) {
			CoursePress_Model_Instructor::added_to_course( $instructor_id, $course_id );
			$instructors[] = $instructor_id;
		}

		self::update_setting( $course_id, 'instructors', $instructors );

	}

	public static function remove_instructor( $course_id, $instructor_id ) {
		$instructors = maybe_unserialize( self::get_setting( $course_id, 'instructors', false ) );

		foreach( $instructors as $idx => $instructor ) {
			if( (int) $instructor === $instructor_id ) {
				CoursePress_Model_Instructor::removed_from_course( $instructor_id, $course_id );
				unset( $instructors[ $idx ] );
			}
		}

		self::update_setting( $course_id, 'instructors', $instructors );

	}

	public static function get_setting( $course_id, $key = true, $default = null ) {

		$settings = get_post_meta( $course_id, 'course_settings', true );

		// Return all settings
		if ( true === $key ) {
			return $settings;
		}

		$setting = CoursePress_Helper_Utility::get_array_val( $settings, $key );
		$setting = is_null( $setting ) ? $default : $setting;
		$setting = ! is_array( $setting ) ? trim( $setting ) : $setting;

		return maybe_unserialize( $setting );
	}

	public static function update_setting( $course_id, $key = true, $value ) {

		$settings = get_post_meta( $course_id, 'course_settings', true );

		if ( true === $key ) {
			// Replace all settings
			$settings = $value;
		} else {
			// Replace only one setting
			CoursePress_Helper_Utility::set_array_val( $settings, $key, $value );
		}

		return update_post_meta( $course_id, 'course_settings', $settings );
	}

	public static function delete_setting( $course_id, $key = true ) {

		$settings = get_post_meta( $course_id, 'course_settings', true );

		if ( true === $key ) {
			// Replace all settings
			$settings = array();
		} else {
			// Replace only one setting
			CoursePress_Helper_Utility::unset_array_val( $settings, $key );
		}

		return update_post_meta( $course_id, 'course_settings', $settings );
	}

	/**
	 *
	 * Warning: This does not save the settings, it just updates the passed in array.
	 *
	 * @param $settings
	 * @param $key
	 * @param $value
	 */
	public static function set_setting( &$settings, $key, $value ) {
		CoursePress_Helper_Utility::set_array_val( $settings, $key, $value );
	}

	public static function allow_pages( $course_id ) {

		$pages = array(
			'course_discussion'	 => CoursePress_Helper_Utility::fix_bool( self::get_setting( $course_id, 'allow_discussion', true ) ),
			'workbook'			 => CoursePress_Helper_Utility::fix_bool( self::get_setting( $course_id, 'allow_workbook', true ) ),
			'grades'			 => CoursePress_Helper_Utility::fix_bool( self::get_setting( $course_id, 'allow_grades', true ) ),
		);

		return $pages;
	}

	public static function upgrade_settings( $course_id ) {

		$settings = array();

		$map = array(
			'allow_discussion'        => array( 'key' => 'allow_course_discussion', 'default' => '' ),
			'allow_grades'            => array( 'key' => 'allow_grades_page', 'default' => '' ),
			'allow_workbook'          => array( 'key' => 'allow_workbook_page', 'default' => true ),
			'course_category'         => array( 'key' => 'course_category', 'default' => '' ),
			'class_size'              => array( 'key' => 'class_size', 'default' => 0 ),
			'class_limited'           => array( 'key' => 'limit_class_size', 'default' => '' ),
			'course_open_ended'       => array( 'key' => 'open_ended_course', 'default' => true ),
			'course_start_date'       => array( 'key' => 'course_start_date', 'default' => '' ),
			'course_end_date'         => array( 'key' => 'course_end_date', 'default' => '' ),
			'course_order'            => array( 'key' => 'course_order', 'default' => 0 ),
			'enrollment_open_ended'   => array( 'key' => 'open_ended_enrollment', 'default' => true ),
			'enrollment_start_date'   => array( 'key' => 'enrollment_start_date', 'default' => '' ),
			'enrollment_end_date'     => array( 'key' => 'enrollment_end_date', 'default' => '' ),
			'enrollment_type'         => array( 'key' => 'enroll_type', 'default' => 'manually' ),
			'enrollment_prerequisite' => array( 'key' => 'prerequisite', 'default' => '' ),
			'enrollment_passcode'     => array( 'key' => 'passcode', 'default' => '' ),
			'listing_image'           => array( 'key' => 'featured_url', 'default' => '' ),
			'instructors'             => array( 'key' => 'instructors', 'default' => '' ),
			'course_language'         => array( 'key' => 'course_language', 'default' => '' ),
			'payment_paid_course'     => array( 'key' => 'paid_course', 'default' => '' ),
			'payment_auto_sku'        => array( 'key' => 'auto_sku', 'default' => '' ),
			'payment_product_id'      => array( 'key' => 'mp_product_id', 'default' => array() ),
			'setup_complete'          => array( 'key' => 'course_setup_complete', 'default' => '' ),
			'structure_visible'       => array( 'key' => 'course_structure_options', 'default' => '' ),
			'structure_show_duration' => array( 'key' => 'course_structure_time_display', 'default' => '' ),
			'structure_visible_units' => array( 'key' => 'show_unit_boxes', 'default' => '' ),
			'structure_preview_units' => array( 'key' => 'preview_unit_boxes', 'default' => '' ),
			'structure_visible_pages' => array( 'key' => 'show_page_boxes', 'default' => '' ),
			'structure_preview_pages' => array( 'key' => 'preview_page_boxes', 'default' => '' ),
			'featured_video'          => array( 'key' => 'course_video_url', 'default' => '' ),
		);

		$meta = get_post_meta( $course_id );

		foreach ( $map as $key => $old ) {
			self::set_setting( $settings, $key, self::upgrade_meta_val( $meta, $old['key'], $old['default'] ) );
		}

		self::update_setting( $course_id, true, $settings );

	}

	private static function upgrade_meta_val( $meta, $val, $default = '' ) {

		$val = isset( $meta[ $val ] ) ? $meta[ $val ] : $default;

		if ( is_array( $val ) ) {
			$val = $val[0];
		}

		if ( empty( $val ) ) {
			$val = $default;
		}

		return $val;
	}

	public static function get_post_type_name( $with_prefix = true ) {
		if ( ! $with_prefix ) {
			return self::$post_type;
		} else {
			$prefix = defined( 'COURSEPRESS_CPT_PREFIX' ) ? COURSEPRESS_CPT_PREFIX : '';
			$prefix = empty( $prefix ) ? '' : sanitize_text_field( $prefix ) . '_';

			return $prefix . self::$post_type;
		}
	}

	public static function get_post_category_name( $with_prefix = true ) {
		if ( ! $with_prefix ) {
			return self::$post_taxonomy;
		} else {
			$prefix = defined( 'COURSEPRESS_CPT_PREFIX' ) ? COURSEPRESS_CPT_PREFIX : '';
			$prefix = empty( $prefix ) ? '' : sanitize_text_field( $prefix ) . '_';

			return $prefix . self::$post_taxonomy;
		}
	}

	public static function get_terms() {
		$prefix   = defined( 'COURSEPRESS_CPT_PREFIX' ) ? COURSEPRESS_CPT_PREFIX : '';
		$prefix   = empty( $prefix ) ? '' : sanitize_text_field( $prefix ) . '_';
		$category = $prefix . self::get_post_category_name();

		$args = array(
			'orderby'      => 'name',
			'order'        => 'ASC',
			'hide_empty'   => false,
			'fields'       => 'all',
			'hierarchical' => true,
		);

		return get_terms( array( $category ), $args );
	}

	public static function get_course_terms( $course_id, $array = false ) {
		$prefix   = defined( 'COURSEPRESS_CPT_PREFIX' ) ? COURSEPRESS_CPT_PREFIX : '';
		$prefix   = empty( $prefix ) ? '' : sanitize_text_field( $prefix ) . '_';
		$category = $prefix . self::get_post_category_name();

		$course_terms = wp_get_object_terms( (int) $course_id, array( $category ) );

		if ( ! $array ) {
			return $course_terms;
		} else {
			$course_terms_array = array();
			foreach ( $course_terms as $course_term ) {
				$course_terms_array[] = $course_term->term_id;
			}

			return $course_terms_array;
		}

	}

	public static function get_course_categories( $course_id = false ) {
		$terms      = self::get_terms();
		$categories = array();

		if( ! $course_id ) {
			foreach ( $terms as $term ) {
				$categories[ $term->term_id ] = $term->name;
			}
		} else {
			$course_terms_array = self::get_course_terms( (int) $course_id, true );
			foreach ( $terms as $term ) {
				if( in_array( (int) $term->term_id, $course_terms_array ) ) {
					$categories[ $term->term_id ] = $term->name;
				}
			}
		}

		return $categories;
	}

	public static function get_units( $course_id, $status = array( 'publish' ), $ids_only = false, $include_count = false ) {

		$post_args = array(
			'post_type'     => CoursePress_Model_Unit::get_post_type_name(),
			'post_parent'   => $course_id,
			'post_status'   => $status,
			'posts_per_page'=> - 1,
			'order'         => 'ASC',
			'orderby'       => 'meta_value_num',
			'meta_key'      => 'unit_order'
		);

		if ( $ids_only ) {
			$post_args['fields'] = 'ids';
		}

		$query = new WP_Query( $post_args );

		if ( $include_count ) {
			// Handy if using pagination
			return array( 'units' => $query->posts, 'found' => $query->found_posts );
		} else {
			return $query->posts;
		}

	}

	public static function get_unit_ids( $course_id, $status = array( 'publish' ), $include_count = false ) {
		return self::get_units( $course_id, $status, true, $include_count );
	}

	// META
	public static function get_listing_image( $course_id ) {
		$url = CoursePress_Model_Course::get_setting( $course_id, 'listing_image' );
		$url = empty( $url ) ? get_post_meta( $course_id, '_thumbnail_id', true ) : $url;
		return apply_filters( 'coursepress_course_listing_image', $url, $course_id );
	}

	public static function get_units_with_modules( $course_id, $status = array( 'publish' ) ) {

		self::$last_course_id = $course_id;
		$combine = array();

		if( ! array( $status ) ) {
			$status = array( $status );
		};

		$sql = 'AND ( ';
		foreach( $status as $filter ) {
			$sql .= '%1$s.post_status = \'' . $filter . '\' OR ';
		}
		$sql = preg_replace('/(OR.)$/', '', $sql);
		$sql .= ' )';

		self::$where_post_status = $sql;

		add_filter( 'posts_where', array( __CLASS__, 'filter_unit_module_where' ) );

		$post_args = array(
			'post_type'     => array( CoursePress_Model_Unit::get_post_type_name(), CoursePress_Model_Module::get_post_type_name() ),
			'post_parent'   => $course_id,
			'posts_per_page' => -1,
			'order'         => 'ASC',
			'orderby'       => 'menu_order',
		);

		$query = new WP_Query( $post_args );

		$unit_cpt = CoursePress_Model_Unit::get_post_type_name();
		$module_cpt = CoursePress_Model_Module::get_post_type_name();

		foreach( $query->posts as $post ) {

			if( $module_cpt == $post->post_type ) {
				$post->module_order = get_post_meta( $post->ID, 'module_order', true );
				$pages = get_post_meta( $post->post_parent, 'page_title', true );
				$page = get_post_meta( $post->ID, 'module_page', true );
				$page = ! empty( $page ) ? $page : 1;
				$page_title = ! empty( $pages ) && isset( $pages[ 'page_'.$page ] ) ? esc_html( $pages[ 'page_'.$page ] ) : '';

				$path = $post->post_parent . '/pages/' . $page . '/title';
				CoursePress_Helper_Utility::set_array_val( $combine, $path, $page_title );

				$path = $post->post_parent . '/pages/' . $page . '/modules/' . $post->ID;
				CoursePress_Helper_Utility::set_array_val( $combine, $path, $post );
			} elseif( $unit_cpt == $post->post_type ) {
				CoursePress_Helper_Utility::set_array_val( $combine, $post->ID . '/order', get_post_meta( $post->ID, 'unit_order', true ) );
				CoursePress_Helper_Utility::set_array_val( $combine, $post->ID . '/unit', $post );
			}
		}

		// Fix legacy orphaned posts and page titles
		foreach( $combine as $post_id => $unit ) {

			if( ! isset( $unit['unit'] ) ) {
				unset( $combine[ $post_id ] );
			}

			// Fix broken page titles
			$page_titles = get_post_meta( $post_id, 'page_title', true );
			if( empty( $page_titles ) ) {
				$page_titles = array();
				$page_visible = array();
				foreach ( $unit['pages'] as $key => $page ) {
					$page_titles[ 'page_' . $key ] = $page['title'];
					$page_visible[] = true;
				}
				update_post_meta( $post_id, 'page_title', $page_titles );
				update_post_meta( $post_id, 'show_page_title', $page_visible );
			}

		}

		remove_filter( 'posts_where', array( __CLASS__, 'filter_unit_module_where' ) );

		return $combine;

	}


	public static function get_unit_modules( $unit_id, $status = array( 'publish' ), $ids_only = false, $include_count = false, $args = array() ) {

		$post_args = array(
			'post_type'     => CoursePress_Model_Module::get_post_type_name(),
			'post_parent'   => $unit_id,
			'post_status'   => $status,
			'posts_per_page'=> -1,
			'order'         => 'ASC',
			'orderby'       => 'meta_value_num',
			'meta_key'      => 'module_order'
		);

		if ( $ids_only ) {
			$post_args['fields'] = 'ids';
		}

		// Get modules for specific page
		if( isset( $args['page'] ) && (int) $args['page'] ) {
			$post_args['meta_query'] = array(
				array(
					'key'     => 'module_page',
					'value'   => (int) $args['page'],
					'compare' => '=',
				),
			);
		}

		$query = new WP_Query( $post_args );

		if ( $include_count ) {
			// Handy if using pagination
			return array( 'units' => $query->posts, 'found' => $query->found_posts );
		} else {
			return $query->posts;
		}

	}

	public static function filter_unit_module_where( $sql ) {
		global $wpdb;

		/* @todo build in post type prefixing */
		$sql = 'AND ( %1$s.post_type = \'module\' AND %1$s.post_parent IN (SELECT ID FROM %1$s AS wpp WHERE wpp.post_type = \'unit\' AND wpp.post_parent = %2$d) OR (%1$s.post_type = \'unit\' AND %1$s.post_parent = %2$d ) ) ' . self::$where_post_status;
		$sql = $wpdb->prepare( $sql, $wpdb->posts, self::$last_course_id );

		return $sql;
	}

	public static function set_last_course_id( $course_id ) {
		self::$last_course_id = $course_id;
	}

	public static function last_course_id() {
		return self::$last_course_id;
	}

	public static function is_paid_course( $course_id ) {
		$is_paid = self::get_setting( $course_id, 'payment_paid_course', false );
		$is_paid = empty( $is_paid ) || 'off' === $is_paid ? false : true;
	}


	public static function get_users( $args ) {
		return new WP_User_Query( $args );
	}

	public static function get_students( $course_id, $per_page = 0, $offset = 0 ) {
		global $wpdb;

		$args = array(
			'meta_key' => 'last_name',
			'orderby' => 'meta_value',
			'meta_query' => array(
				array(
					'key'     => $wpdb->prefix . 'enrolled_course_date_' . $course_id,
					'compare' => 'EXISTS'
				),
			)
		);

		if( $per_page > 0 ) {
			$args['number'] = $per_page;
			$args['offset'] = $offset;
		}

		$students = self::get_users( $args );

		return $students->get_results();
	}

	public static function get_student_ids( $course_id, $count = false ) {
		global $wpdb;

		$students = self::get_users( array(
			'meta_key' => 'last_name',
			'orderby' => 'meta_value',
			'meta_query' => array(
				array(
					'key'     => $wpdb->prefix . 'enrolled_course_date_' . $course_id,
					'compare' => 'EXISTS'
				),
			),
			'fields' => 'ID'
		));

		if( ! $count ) {
			return $students->get_results();
		} else {
			return $students->get_total();
		}

	}

	public static function count_students( $course_id ) {
		$count = self::get_student_ids( $course_id, true );
		return empty( $count ) ? 0 : $count;
	}

	public static function student_enrolled( $student_id, $course_id ) {
		$enrolled = get_user_option( 'enrolled_course_date_' . $course_id, $student_id );
		return ! empty( $enrolled ) ? $enrolled : '';
	}

	public static function student_completed( $student_id, $course_id ) {
		// COMPLETION LOGIC
		return false;
	}

	public static function enroll_student( $student_id, $course_id, $class = '', $group = '' ) {

		$current_time = current_time( 'mysql' );

		$global_option = !is_multisite();

		// If student doesn't exist, exit.
		$student = get_userdata( $student_id );
		if( empty( $student ) ) {
			return false;
		}

		// If student is already enrolled, exit.
		$enrolled = self::student_enrolled( $student_id, $course_id );
		if( ! empty( $enrolled ) ) {
			return $course_id;
		}


		/**
		 * Update metadata with relevant details.
		 */
		update_user_option( $student_id, 'enrolled_course_date_' . $course_id, $current_time, $global_option ); //Link courses and student ( in order to avoid custom tables ) for easy MySql queries ( get courses stats, student courses, etc. )
		update_user_option( $student_id, 'enrolled_course_class_' . $course_id, $class, $global_option );
		update_user_option( $student_id, 'enrolled_course_group_' . $course_id, $group, $global_option );
		update_user_option( $student_id, 'role', 'student', $global_option ); //alternative to roles used


		self::_add_enrollment_email_hooks();

		self::$email_type = CoursePress_Helper_Email::ENROLLMENT_CONFIRM;

		$email_args = array();
		$email_args['email_type'] = self::$email_type;
		$email_args['course_id'] = $course_id;
		$email_args['email'] = sanitize_email( $student->user_email );
		$email_args['first_name'] = $student->user_firstname;
		$email_args['last_name'] = $student->user_lastname;

		$email_args = apply_filters( 'coursepress_student_enrollment_email_args', $email_args );

		if( is_email( $email_args['email'] ) ) {
			if ( CoursePress_Helper_Utility::send_email( $email_args ) ) {
				// Could add something on successful email
			} else {
				// Could add something if email fails
			}
		}

		/**
		 * Setup actions for when a student enrolls.
		 * Can be used to create notifications or tracking student actions.
		 */
		$instructors = self::get_setting( $course_id, 'instructors', false );

		do_action( 'student_enrolled_instructor_notification', $student_id, $course_id, $instructors );
		do_action( 'student_enrolled_student_notification', $student_id, $course_id );

		/**
		 * Perform action after a Student is enrolled.
		 *
		 * @since 1.2.2
		 */
		do_action( 'coursepress_student_enrolled', $student_id, $course_id );

		return true;
	}

	private static function _add_enrollment_email_hooks() {
		add_filter( 'coursepress_email_fields', array( __CLASS__, 'enrollment_email_fields' ), 10, 2 );
		add_filter( 'wp_mail_from', array( __CLASS__, 'email_from' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'email_from_name' ) );
	}

	public static function enrollment_email_fields( $fields, $args ) {

		$email_settings = CoursePress_Helper_Email::get_email_fields( CoursePress_Helper_Email::ENROLLMENT_CONFIRM );

		$course_id = (int) $args['course_id'];

		// To Email Address
		$fields['email'] = sanitize_email( $args['email'] );

		// Email Subject
		$fields['subject'] = $email_settings['subject'];

		$post = get_post( $course_id );

		$course_name = $post->post_title;

		$permalink = '';
		if ( in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
			$permalink = CoursePress_Core::get_slug( 'course', true ) . '/' . $post->post_name . '/';
		} else {
			$permalink = get_permalink( $course_id );
		}
		$course_address = esc_url( $permalink );

		// Email Content
		$tags = array(
			'STUDENT_FIRST_NAME',
			'STUDENT_LAST_NAME',
			'COURSE_TITLE',
			'COURSE_ADDRESS',
			'STUDENT_DASHBOARD',
			'COURSES_ADDRESS',
			'BLOG_NAME'
		);

		$tags_replaces = array(
			sanitize_text_field( $args[ 'first_name' ] ),
			sanitize_text_field( $args[ 'last_name' ] ),
			$course_name,
			$course_address,
			//$student_login_address = get_option( 'use_custom_login_form', 1 ) ? trailingslashit( home_url() . '/' . get_option( 'login_slug', 'student-login' ) ) : wp_login_url(),
			wp_login_url(),
			trailingslashit( home_url() ) . trailingslashit( CoursePress_Core::get_slug( 'course' ) ),
			get_bloginfo()
		);

		$fields['message'] = str_replace( $tags, $tags_replaces, $email_settings['content'] );

		return $fields;
	}

	public static function email_from( $from ) {

		$email_settings = CoursePress_Helper_Email::get_email_fields( self::$email_type );

		$from = $email_settings['email'];

		return $from;
	}

	public static function email_from_name( $from_name ) {

		$email_settings = CoursePress_Helper_Email::get_email_fields( self::$email_type );

		$from = $email_settings['name'];

		return $from;
	}

	public static function withdraw_student( $student_id, $course_id ) {

		$global_option = !is_multisite();
		$current_time = current_time( 'mysql' );

		delete_user_option( $student_id, 'enrolled_course_date_' . $course_id, $global_option );
		delete_user_option( $student_id, 'enrolled_course_class_' . $course_id, $global_option );
		delete_user_option( $student_id, 'enrolled_course_group_' . $course_id, $global_option );
		delete_user_option( $student_id, 'role', $global_option );

		update_user_option( $student_id, 'withdrawn_course_date_' . $course_id, $current_time, $global_option );

		$instructors = self::get_setting( $course_id, 'instructors', false );
		do_action( 'student_withdraw_from_course_instructor_notification', $student_id, $course_id, $instructors );
		do_action( 'student_withdraw_from_course_student_notification', $student_id, $course_id );
		do_action( 'coursepress_student_withdrawn', $student_id, $course_id );

	}

	public static function withdraw_all_students( $course_id ) {

		$students = self::get_student_ids( $course_id );

		foreach( $students as $student ) {
			self::withdraw_student( $student, $course_id );
		}
	}

	public static function send_invitation( $email_data ) {

		// So that we can use it later
		CoursePress_Model_Course::set_last_course_id( (int) $email_data['course_id'] );
		$course_id = (int) $email_data['course_id'];


		// We need to hook the email fields for the Utility method.
		self::_add_invitation_email_hooks();

		$type = self::get_setting( $course_id, 'enrollment_type', 'manually' );

		if( 'passcode' === $type ) {
			$email_args['email_type'] = CoursePress_Helper_Email::COURSE_INVITATION_PASSWORD;
			$type = CoursePress_Helper_Email::COURSE_INVITATION_PASSWORD;
		} else {
			$email_args['email_type'] = CoursePress_Helper_Email::COURSE_INVITATION;
			$type = CoursePress_Helper_Email::COURSE_INVITATION;
		}

		self::$email_type = $type;

		$email_args['course_id'] = $email_data['course_id'];
		$email_args['email'] = sanitize_email( $email_data['email'] );

		$user = get_user_by( 'email', $email_args['email'] );
		if( $user ) {
			$email_data['user'] = $user;
			$email_args['first_name'] = sanitize_text_field( $email_data['first_name'] );
			$email_args['last_name'] = sanitize_text_field( $email_data['last_name'] );
		}

		if( CoursePress_Helper_Utility::send_email( $email_args ) ) {
			// successful
			return true;
		} else {
			// failed
			return false;
		}

	}

	private static function _add_invitation_email_hooks() {

		add_filter( 'coursepress_email_fields', array( __CLASS__, 'invite_email_fields' ), 10, 2 );
		add_filter( 'wp_mail_from', array( __CLASS__, 'email_from' ) );
		add_filter( 'wp_mail_from_name', array( __CLASS__, 'email_from_name' ) );

	}

	public static function invite_email_fields( $fields, $args ) {

		$email_settings = CoursePress_Helper_Email::get_email_fields( self::$email_type );

		$course_id = (int) $args['course_id'];

		// To Email Address
		$fields['email'] = sanitize_email( $args['email'] );

		// Email Subject
		$fields['subject'] = $email_settings['subject'];

		$post = get_post( $course_id );

		$course_name = $post->post_title;

		$permalink = '';
		if ( in_array( $post->post_status, array( 'draft', 'pending', 'auto-draft' ) ) ) {
			$permalink = CoursePress_Core::get_slug( 'course', true ) . '/' . $post->post_name . '/';
		} else {
			$permalink = get_permalink( $course_id );
		}
		$course_address = esc_url( $permalink );

		// Email Content
		$tags = array(
			'STUDENT_FIRST_NAME',
			'STUDENT_LAST_NAME',
			'COURSE_NAME',
			'COURSE_EXCERPT',
			'COURSE_ADDRESS',
			'WEBSITE_ADDRESS',
			'PASSCODE'
		);

		$tags_replaces = array(
			sanitize_text_field( $args[ 'first_name' ] ),
			sanitize_text_field( $args[ 'last_name' ] ),
			$course_name,
			$post->post_excerpt,
			$course_address,
			trailingslashit( home_url() ),
			self::get_setting( $course_id, 'enrollment_passcode', '' )
		);

		$fields['message'] = str_replace( $tags, $tags_replaces, $email_settings['content'] );

		return $fields;
	}

	public static function is_full( $course_id ) {

		$limited = CoursePress_Helper_Utility::fix_bool( self::get_setting( $course_id, 'class_size' ) );
		if( $limited ) {

			$limit = self::get_setting( $course_id, 'class_size' );
			$students = self::count_students( $course_id );

			return $limit <= $students;

		} else {
			return false;
		}

	}

	public static function get_time_estimation( $course_id ) {

		$units = self::get_units_with_modules( $course_id );

		$seconds = 0;
		$minutes = 0;
		$hours = 0;

		foreach( $units as $unit ) {

			$estimations = CoursePress_Model_Unit::get_time_estimation( $unit['unit']->ID, $units );
			$components = explode( ':', $estimations['unit']['estimation'] );

			$part = array_pop( $components );
			$seconds += ! empty( $part ) ? (int) $part : 0;
			$part = count( $components > 0 ) ? array_pop( $components ) : 0;
			$minutes += ! empty( $part ) ? (int) $part : 0;
			$part = count( $components > 0 ) ? array_pop( $components ) : 0;
			$hours += ! empty( $part ) ? (int) $part : 0;

		}

		$total_seconds = $seconds + ( $minutes * 60 ) + ( $hours * 3600 );

		$hours = floor( $total_seconds / 3600 );
		$total_seconds = $total_seconds % 3600;
		$minutes = floor( $total_seconds / 60 );
		$seconds = $total_seconds % 60;

		$estimation = sprintf("%02d:%02d:%02d", $hours, $minutes, $seconds );

		return $estimation;
	}

	public static function get_instructors( $course_id, $objects = false ) {

		$instructors = maybe_unserialize( self::get_setting( $course_id, 'instructors', false ) );

		$instructor_objects = array();
		if( ! $objects ) {
			return $instructors;
		} else {
			foreach( $instructors as $instructor ) {
				$instructor_id = (int) $instructor;
				if( ! empty( $instructor_id ) ) {
					$instructor_objects[] = get_userdata( $instructor_id );
				}
			}
			return $instructor_objects;
		}

	}

	public static function structure_visibility( $course_id ) {

		if( empty( self::$structure_visibility ) ) {
			$units   = array_filter( CoursePress_Model_Course::get_setting( $course_id, 'structure_visible_units', array() ) );
			$pages   = array_filter( CoursePress_Model_Course::get_setting( $course_id, 'structure_visible_pages', array() ) );
			$modules = array_filter( CoursePress_Model_Course::get_setting( $course_id, 'structure_visible_modules', array() ) );

			$visibility = array();

			foreach( array_keys( $units ) as $key ) {
				$visibility[ $key ] = true;
			}

			foreach( array_keys( $pages ) as $key ) {
				list( $unit, $page ) = explode( '_', $key );
				CoursePress_Helper_Utility::set_array_val( $visibility, $unit . '/' . $page , true );
			}

			foreach( array_keys( $modules ) as $key ) {
				list( $unit, $page, $module ) = explode( '_', $key );
				CoursePress_Helper_Utility::set_array_val( $visibility, $unit . '/' . $page . '/' . $module, true );
			}

			self::$structure_visibility['structure'] = $visibility;

			if( ! empty( $units) || ! empty( $page ) || ! empty( $modules ) ) {
				self::$structure_visibility['has_visible'] = true;
			} else {
				self::$structure_visibility['has_visible'] = false;
			}
		}

		return self::$structure_visibility;
	}

	public static function previewability( $course_id ) {

		if( empty( self::$previewability ) ) {

			$units  = array_filter( CoursePress_Model_Course::get_setting( $course_id, 'structure_preview_units', array() ) );
			$pages  = array_filter( CoursePress_Model_Course::get_setting( $course_id, 'structure_preview_pages', array() ) );
			$modules = array_filter( CoursePress_Model_Course::get_setting( $course_id, 'structure_preview_modules', array() ) );

			$preview_structure = array();

			foreach( array_keys( $units ) as $key ) {
				$preview_structure[ $key ] = true;
			}

			foreach( array_keys( $pages ) as $key ) {
				list( $unit, $page ) = explode( '_', $key );
				CoursePress_Helper_Utility::set_array_val( $preview_structure, $unit . '/' . $page , true );
			}

			foreach( array_keys( $modules ) as $key ) {
				list( $unit, $page, $module ) = explode( '_', $key );
				CoursePress_Helper_Utility::set_array_val( $preview_structure, $unit . '/' . $page . '/' . $module, true );
			}

			self::$previewability['structure'] = $preview_structure;

			if( ! empty( $units) || ! empty( $page ) || ! empty( $modules ) ) {
				self::$previewability['has_previews'] = true;
			} else {
				self::$previewability['has_previews'] = false;
			}
		}

		return self::$previewability;
	}

	static function by_name( $slug, $id_only ) {

		$args = array(
			'name'			 => $slug,
			'post_type'		 => self::get_post_type_name( true ),
			'post_status'	 => 'any',
			'posts_per_page' => 1,
		);

		if( $id_only ) {
			$args['fields']	= 'ids';
		}

		$post = get_posts( $args );

		if ( $post ) {
			if( $id_only ) {
				return (int) $post[ 0 ];
			}
			return $post[ 0 ];
		} else {
			return false;
		}
	}



}