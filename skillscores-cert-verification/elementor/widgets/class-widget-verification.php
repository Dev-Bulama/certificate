<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class SSCV_Widget_Verification extends \Elementor\Widget_Base {

    public function get_name() {
        return 'sscv_verification';
    }

    public function get_title() {
        return __( 'Certificate Verification Portal', 'skillscores-cert' );
    }

    public function get_icon() {
        return 'eicon-search';
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
        echo do_shortcode( '[certificate_verification class="' . esc_attr( $settings['custom_class'] ) . '"]' );
    }
}
