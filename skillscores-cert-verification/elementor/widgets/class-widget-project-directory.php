<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SSCV_Widget_Project_Directory extends \Elementor\Widget_Base {

    public function get_name() {
        return 'sscv_project_directory';
    }

    public function get_title() {
        return __( 'Public Project Directory', 'skillscores-cert' );
    }

    public function get_icon() {
        return 'eicon-table';
    }

    public function get_categories() {
        return array( 'sscv-widgets' );
    }

    protected function register_controls() {
        $this->start_controls_section( 'content_section', array(
            'label' => __( 'Settings', 'skillscores-cert' ),
            'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
        ) );

        $this->add_control( 'per_page', array(
            'label'   => __( 'Projects Per Page', 'skillscores-cert' ),
            'type'    => \Elementor\Controls_Manager::NUMBER,
            'default' => 10,
            'min'     => 5,
            'max'     => 50,
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
        echo do_shortcode( '[public_project_directory per_page="' . intval( $settings['per_page'] ) . '" class="' . esc_attr( $settings['custom_class'] ) . '"]' );
    }
}
