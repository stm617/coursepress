/* global CoursePress */

(function(){
    'use strict';

    CoursePress.Define( 'CourseList', function($) {
        var CoursesList;

        CoursesList = CoursePress.View.extend({
            el: $('#coursepress-courselist'),
            events: {
                'click .cp-reset-step': 'resetEditStep',
                'change .cp-toggle-course-status': 'toggleCourseStatus',
                'click .menu-item-duplicate-course': 'duplicateCourse',
                'click .menu-item-delete': 'deleteCourse'
            },

            /**
             * Resets browser saved step and load course setup.
             */
            resetEditStep: function(ev) {
                var sender = $(ev.target),
                    step = sender.data('step'),
                    course_id = sender.parents('td').first().data('id');
                CoursePress.Cookie('course_setup_step_' + course_id ).set( step, 86400 * 7);
            },

            toggleCourseStatus: function() {
                // @todo: switch status via JS
            },

            duplicateCourse: function() {
                // @todo: duplicate course here
            },

            deleteCourse: function() {
                // @todo: delete course
            }
        });

        CoursesList = new CoursesList();
    });
})();