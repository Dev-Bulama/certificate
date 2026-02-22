<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SSCV_Widget_Application_Form extends \Elementor\Widget_Base {

    public function get_name() {
        return 'sscv_application_form';
    }

    public function get_title() {
        return __( 'Certificate Application Form', 'skillscores-cert' );
    }

    public function get_icon() {
        return 'eicon-form-horizontal';
    }

    public function get_categories() {
        return array( 'sscv-widgets' );
    }

    protected function register_controls() {
        $this->start_controls_section( 'content_section', array(
            'label' => __( 'Settings', 'skillscores-cert' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ) );

        $this->add_control( 'custom_class', array(
            'label'   => __( 'Custom CSS Class', 'skillscores-cert' ),
            'type'    => \Elementor\Controls_Manager::TEXT,
            'default' => '',
        ) );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        echo do_shortcode( '[certificate_application_form class="' . esc_attr( $settings['custom_class'] ) . '"]' );
    }
}
