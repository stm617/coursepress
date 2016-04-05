<?php

class CoursePress_View_Admin_Course_Student {

	public static function render() {
		/**
		 * Student List
		 */
		$course_id = (int) $_GET['id'];
		$list_course = new CoursePress_Helper_Table_CourseStudent();

		$list_course->set_course( $course_id );
		$list_course->set_add_new( true );
		$list_course->prepare_items();

		$content = '<div class="coursepress_Course_Student_wrapper">';

		ob_start();
		$list_course->display();
		$content .= ob_get_clean();

		$content .= '</div>';

		/**
		 * Invite Student
		 */
		if ( CoursePress_Data_Capabilities::can_invite_students( $course_id ) ) {
			// Show lists of previously invited students
			$invited_students = CoursePress_Data_Course::get_setting( $list_course->get_course_id(), 'invited_students', array() );
			$invited_students = array_filter( (array) $invited_students );
			$student_invite_nonce = wp_create_nonce( 'coursepress_remove_invite' );

			if ( ! empty( $invited_students ) ) {
				$content .= '<div class="coursepress_course_invite_student_wrapper invited-students">';
				$content .= '<h3>'. esc_html__( 'Invited Students', 'CP_TD' ) . '</h3>';
				$content .= '<p class="description">' . esc_html__( 'List of invited students.', 'CP_TD' ) . '</p>';
				$content .= '<table class="wp-list-table widefat fixed striped">';
				$content .= '<thead><tr><th>' . __( 'First Name', 'CP_TD' ) . '</th>';
				$content .= '<th>'. __( 'Last Name', 'CP_TD' ) . '</th><th>' . __( 'Email', 'CP_TD' ) . '</th><th></th></tr></thead>';
				foreach ( $invited_students as $student_email => $student_data ) {
					$content .= '<tr class="invited-list">';
					$content .= '<td>' . $student_data['first_name'] . '</td>';
					$content .= '<td>'. $student_data['last_name'] . '</td>';
					$content .= '<td>'. $student_data['email'] . '</td>';
					$content .= '<td class="actions column-actions">';
					$content .= sprintf(
						'<a href="%s" title="%s" class="resend-invite" data-firstname="%s" data-lastname="%s" data-email="%s"><i class="fa fa-send"></i></a> ',
						'',
						esc_attr( __( 'Resend Invitation', 'CP_TD') ),
						esc_attr( $student_data['first_name'] ),
						esc_attr( $student_data['last_name'] ),
						esc_attr( $student_data['email'] )
					);
					$content .= sprintf( '<a href="%s" title="%s" data-email="%s" data-nonce="%s" class="remove-invite"><i class="fa fa-times-circle remove-btn"></i></a>',
									'',
									esc_attr( __( 'Remove Invitation', 'CP_TD' ) ),
									esc_attr( $student_email ),
									esc_attr( $student_invite_nonce )
								);
					$content .= '</td></tr>';
				}
				$content .= '</table>';
				$content .= '</div><br />';
			}

			$nonce = wp_create_nonce( 'invite_student' );
			$content .= '<div class="coursepress_course_invite_student_wrapper">';
			$content .= '<h3>' . esc_html__( 'Invite Student', 'CP_TD' ) .'</h3>';
			$content .= '<label class="invite-firstname"><span>' . esc_html__( 'First Name', 'CP_TD' ) . '</span><input type="text" name="invite-firstname"></label>';
			$content .= '<label class="invite-lastname"><span>' . esc_html__( 'Last Name', 'CP_TD' ) . '</span><input type="text" name="invite-lastname"></label>';
			$content .= '<label class="invite-email"><span>' . esc_html__( 'E-mail', 'CP_TD' ) . '</span><input type="text" name="invite-email"></label>';
			$content .= '<div class="invite-submit button button-primary" name="invite-submit" data-nonce="' . $nonce . '">' . esc_html__( 'Invite', 'CP_TD' ) . '</div>';
			$content .= '</div>';
		}

		return $content;
	}

	/**
	 * The action only fires if the current user is editing their own profile.
	 *
	 * @since 2.0.0
	 *
	 * @param WP_User $profileuser The current WP_User object.
	 */
	public static function render_student_courses( $student ) {
		if ( ! isset( $_GET['courses'] ) || 'show' != $_GET['courses'] ) {
			return;
		}
		printf( '<h2 id="courses">%s</h2>', __( 'Courses', 'CP_TD' ) );
		$enrolled_courses = CoursePress_Data_Student::get_enrolled_courses_ids( $student->ID );
		if ( empty( $enrolled_courses ) ) {
			echo wpautop( __( 'No enrolled courses.', 'CP_TD' ) );
			return;
		}
		echo '<table class="wp-list-table widefat fixed striped">';
?>
<thead>
	<tr>
		<th scope="col" class="column-slug"><span><?php _e( 'Title', 'CP_TD' ); ?></span></th>
		<th scope="col"><span><?php _e( 'Excerpt', 'CP_TD' ); ?></span></th>
		<th scope="col" class="column-rating"><span class="dashicons dashicons-calendar-alt"></span> <span><?php _e( 'Enrolled', 'CP_TD' ); ?></span></th>
		<th scope="col" class="column-rating"><span class="dashicons dashicons-calendar-alt"></span> <span><?php _e( 'Start', 'CP_TD' ); ?></span></th>
		<th scope="col" class="column-rating"><span class="dashicons dashicons-calendar-alt"></span> <span><?php _e( 'End', 'CP_TD' ); ?></span></th>
		<th scope="col" class="column-rating"><span class="dashicons dashicons-clock"></span> <span><?php _e( 'Duration', 'CP_TD' ); ?></span></th>
	</tr>
</thead>
<?php
		$date_format = get_option( 'date_format' );
foreach ( $enrolled_courses as $course_id ) {
	$course = get_post( $course_id );
	if ( empty( $course ) ) {
		continue;
	}
	$is_open_end_course = 'on' == CoursePress_Data_Course::get_setting( $course_id, 'course_open_ended' );
?>
<tr class="student-course">
<td class="title">
<strong class="edit"><a href="<?php echo admin_url( 'admin.php?page=course_details&course_id=' . $course_id ); ?>"><?php echo $course->post_title; ?></a></strong>
<div class="row-actions">
	<span class="edit"><a href="<?php echo  admin_url( 'admin.php?page=course_details&course_id=' . $course_id ); ?>" target="_blank"><?php _e( 'Edit', 'CP_TD' ); ?></a> | </span>
	<span class="view"><a href="<?php echo get_permalink( $course_id ); ?>" target="_blank"><?php _e( 'View', 'CP_TD' ); ?></a></span>
</td>
<td><?php echo $course->post_excerpt; ?></td>
<td><?php echo date_i18n( $date_format, strtotime( get_user_meta( $student->id, sprintf( 'enrolled_course_date_%d', $course_id ), true ) ) ); ?></td>
<td><?php echo date_i18n( $date_format, strtotime( CoursePress_Data_Course::get_setting( $course_id, 'course_start_date', true ) ) ); ?></td>
<td><?php echo $is_open_end_course? __( 'Open-ended', 'CP_TD' ) : date_i18n( $date_format, strtotime( CoursePress_Data_Course::get_setting( $course_id, 'course_end_date', true ) ) ); ?></td>
<td><?php
if ( $is_open_end_course ) {
	_e( '&infin; Days', 'CP_TD' );
} else {
	$start = strtotime( CoursePress_Data_Course::get_setting( $course_id, 'course_start_date', true ) );
	$end = strtotime( CoursePress_Data_Course::get_setting( $course_id, 'course_end_date', true ) );
	$diff = abs( $end - $start );
	$days = $diff / DAY_IN_SECONDS;
	$days = intval( $days );
	printf( _n( '%s Day', '%s Days', $days, 'CP_TD' ), $days );
}
?></td>
</tr>
<?php
}
		echo '</table>';
	}
}