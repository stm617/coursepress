<?php

class audio_module extends Unit_Module {

    var $order = 2;
    var $name = 'audio_module';
    var $label = 'Audio';
    var $description = '';
    var $front_save = false;
    var $response_type = '';

    function __construct() {
        $this->on_create();
    }

    function audio_module() {
        $this->__construct();
    }

    function front_main($data) {
        ?>
        <div class="<?php echo $this->name; ?> front-single-module<?php echo ($this->front_save == true ? '-save' : ''); ?>">
            <?php if ($data->post_title != '' && $this->display_title_on_front($data)) { ?>
                <h2 class="module_title"><?php echo $data->post_title; ?></h2>
            <?php } ?>
            <?php if ($data->post_content != '') { ?>  
                <div class="module_description"><?php echo apply_filters('element_content_filter', $data->post_content); ?></div>
            <?php } ?>
            <?php if ($data->audio_url != '') { ?>  
                <div class="audio_player">
                    <?php
                    $attr = array(
                        'src' => $data->audio_url,
                        'loop' => (checked($data->loop, 'Yes', false) ? 'on' : ''),
                        'autoplay' => (checked($data->autoplay, 'Yes', false) ? 'on' : ''),
                    );
                    echo wp_audio_shortcode($attr);
                    ?>
                </div>
            <?php } ?>
        </div>
        <?php
    }

    function admin_main($data) {
        wp_enqueue_style('thickbox');
        wp_enqueue_script('thickbox');
        wp_enqueue_media();
        wp_enqueue_script('media-upload');

        $supported_audio_extensions = implode(",", wp_get_audio_extensions());

        if (!empty($data)) {

            if (!isset($data->autoplay) or empty($data->autoplay)) {
                $data->autoplay = 'No';
            }

            if (!isset($data->loop) or empty($data->loop)) {
                $data->loop = 'No';
            }
        }
        ?>

        <div class="<?php if (empty($data)) { ?>draggable-<?php } ?>module-holder-<?php echo $this->name; ?> module-holder-title" <?php if (empty($data)) { ?>style="display:none;"<?php } ?>>

            <h3 class="module-title sidebar-name">
                <span class="h3-label">
                    <span class="h3-label-left"><?php echo (isset($data->post_title) && $data->post_title !== '' ? $data->post_title : __('Untitled', 'cp')); ?></span>
                    <span class="h3-label-right"><?php echo $this->label; ?></span>
                    <?php
                    if (isset($data->ID)) {
                        parent::get_module_delete_link($data->ID);
                    } else {
                        parent::get_module_remove_link();
                    }
                    ?>
                </span>
            </h3>

            <div class="module-content">

                <input type="hidden" name="<?php echo $this->name; ?>_module_order[]" class="module_order" value="<?php echo (isset($data->module_order) ? $data->module_order : 999); ?>" />
                <input type="hidden" name="module_type[]" value="<?php echo $this->name; ?>" />
                <input type="hidden" name="<?php echo $this->name; ?>_id[]" value="<?php echo (isset($data->ID) ? $data->ID : ''); ?>" />

                <label><?php _e('Title', 'cp'); ?>
                    <input type="text" class="element_title" name="<?php echo $this->name; ?>_title[]" value="<?php echo esc_attr(isset($data->post_title) ? $data->post_title : ''); ?>" />
                </label>

                <label class="show_title_on_front"><?php _e('Show Title', 'cp'); ?>
                    <input type="checkbox" name="<?php echo $this->name; ?>_show_title_on_front[]" value="yes" <?php echo (isset($data->show_title_on_front) && $data->show_title_on_front == 'yes' ? 'checked' : (!isset($data->show_title_on_front)) ? 'checked' : '') ?> />
                    <a class="mp-help-icon" href="javascript:;"></a>
                    <div class="mp-help-text"><?php _e('The title is used to identify this element – useful for assessment. If checked, the title is displayed as a heading for this element for the student as well.', 'cp'); ?></div>
                </label>

                <div class="editor_in_place">
                    <label><?php _e('Content', 'cp'); ?></label>
                    <?php
                    $args = array("textarea_name" => $this->name . "_content[]", "textarea_rows" => 5, "teeny" => true, 'tinymce' =>
                        array(
                            'skin' => 'wp_theme',
                            'theme' => 'advanced',
                    ));
                    $editor_id = (esc_attr(isset($data->ID) ? 'editor_' . $data->ID : rand(1, 9999)));
                    wp_editor(htmlspecialchars_decode((isset($data->post_content) ? $data->post_content : '')), $editor_id, $args);
                    ?>
                </div>

                <div class="audio_url_holder">
                    <label><?php _e('Put a URL or Browse for an audio file. Supported audio extensions (' . $supported_audio_extensions . ')', 'cp'); ?>
                        <input class="audio_url" type="text" size="36" name="<?php echo $this->name; ?>_audio_url[]" value="<?php echo esc_attr((isset($data->audio_url) ? $data->audio_url : '')); ?>" />
                        <input class="audio_url_button" type="button" value="<?php _e('Browse', 'ub'); ?>" />
                    </label>
                </div>

                <div class="audio_additional_controls">
                    <label><?php _e('Play in a loop', 'cp'); ?></label>
                    <?php
                    $data_loop = (isset($data->loop) ? $data->loop : 'No');
                    $data_autoplay = (isset($data->autoplay) ? $data->autoplay : 'No');
                    ?>
                    <input type="radio" name="<?php echo $this->name; ?>_loop[]" value="Yes" <?php checked($data_loop, 'Yes', true); ?>> Yes<br /><br />
                    <input type="radio" name="<?php echo $this->name; ?>_loop[]" value="No" <?php checked($data_loop, 'No', true); ?>> No<br /><br />

                    <label><?php _e('Autoplay', 'cp'); ?></label>
                    <input type="radio" name="<?php echo $this->name; ?>_autoplay[]" value="Yes" <?php checked($data_autoplay, 'Yes', true); ?>> Yes<br /><br />
                    <input type="radio" name="<?php echo $this->name; ?>_autoplay[]" value="No" <?php checked($data_autoplay, 'No', true); ?>> No<br /><br />
                </div>

            </div>

        </div>

        <?php
    }

    function on_create() {
        $this->description = __('Add audio files with player to the unit', 'cp');
        $this->save_module_data();
        parent::additional_module_actions();
    }

    function save_module_data() {
        global $wpdb, $last_inserted_unit_id;

        if (isset($_POST['module_type'])) {

            foreach (array_keys($_POST['module_type']) as $module_type => $module_value) {

                if ($module_value == $this->name) {
                    $data = new stdClass();
                    $data->ID = '';
                    $data->unit_id = '';
                    $data->title = '';
                    $data->excerpt = '';
                    $data->content = '';
                    $data->metas = array();
                    $data->metas['module_type'] = $this->name;
                    $data->post_type = 'module';

                    if (isset($_POST[$this->name . '_id'])) {
                        foreach ($_POST[$this->name . '_id'] as $key => $value) {
                            $data->ID = $_POST[$this->name . '_id'][$key];
                            $data->unit_id = ((isset($_POST['unit_id']) and $_POST['unit'] != '') ? $_POST['unit_id'] : $last_inserted_unit_id);
                            $data->title = $_POST[$this->name . '_title'][$key];
                            $data->content = $_POST[$this->name . '_content'][$key];
                            $data->metas['module_order'] = $_POST[$this->name . '_module_order'][$key];
                            $data->metas['audio_url'] = $_POST[$this->name . '_audio_url'][$key];
                            $data->metas['autoplay'] = $_POST[$this->name . '_autoplay'][$key];
                            $data->metas['loop'] = $_POST[$this->name . '_loop'][$key];
                            if (isset($_POST[$this->name . '_show_title_on_front'][$key])) {
                                $data->metas['show_title_on_front'] = $_POST[$this->name . '_show_title_on_front'][$key];
                            } else {
                                $data->metas['show_title_on_front'] = 'no';
                            }
                            parent::update_module($data);
                        }
                    }
                }
            }
        }
    }

}

coursepress_register_module('audio_module', 'audio_module', 'instructors');
?>