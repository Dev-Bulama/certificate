<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SSCV_Widget_Student_Dashboard extends \Elementor\Widget_Base {

    public function get_name() {
        return 'sscv_student_dashboard';
    }

    public function get_title() {
        return __( 'Student Dashboard', 'skillscores-cert' );
    }

    public function get_icon() {
        return 'eicon-person';
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
        echo do_shortcode( '[student_dashboard class="' . esc_attr( $settings['custom_class'] ) . '"]' );
    }
}
