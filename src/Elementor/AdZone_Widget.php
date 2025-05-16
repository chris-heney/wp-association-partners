<?php
namespace AssociationPartners\Elementor;

use Elementor\Widget_Base;
use Elementor\Controls_Manager;
use AssociationPartners\Plugin;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class AdZone_Widget extends Widget_Base {
    public function get_name() {
        return 'association_adzone';
    }

    public function get_title() {
        return __( 'Association AdZone', 'association-partners' );
    }

    public function get_icon() {
        return 'eicon-ad';
    }

    public function get_categories() {
        return [ 'basic' ];
    }

    protected function register_controls() {
        // Fetch AdZones from plugin settings
        $settings = get_option( Plugin::get_instance()->get_option_name(), [] );
        $adzones_array = $settings['adzones'] ?? [];
        $options = [];
        foreach ( $adzones_array as $zone ) {
            $options[ $zone['slug'] ] = sprintf( '%s (%s)', $zone['name'], $zone['slug'] );
        }

        $this->start_controls_section( 'section_content', [
            'label' => __( 'AdZone Settings', 'association-partners' ),
        ] );

        $this->add_control( 'slug', [
            'label' => __( 'Select AdZone', 'association-partners' ),
            'type' => Controls_Manager::SELECT,
            'options' => $options,
            'default' => array_key_first( $options ) ?: '',
        ] );

        $this->add_control( 'override_heading', [
            'label' => __( 'Override Settings (optional)', 'association-partners' ),
            'type' => Controls_Manager::HEADING,
            'separator' => 'before',
        ] );

        $this->add_control( 'name', [
            'label' => __( 'Name', 'association-partners' ),
            'type' => Controls_Manager::TEXT,
            'default' => '',
        ] );

        $this->add_control( 'description', [
            'label' => __( 'Description', 'association-partners' ),
            'type' => Controls_Manager::TEXTAREA,
            'default' => '',
        ] );

        $this->add_control( 'classes', [
            'label' => __( 'Class(es)', 'association-partners' ),
            'type' => Controls_Manager::TEXT,
            'default' => '',
        ] );

        $this->add_control( 'width', [
            'label' => __( 'Width (px)', 'association-partners' ),
            'type' => Controls_Manager::NUMBER,
            'default' => '',
        ] );

        $this->add_control( 'height', [
            'label' => __( 'Height (px)', 'association-partners' ),
            'type' => Controls_Manager::NUMBER,
            'default' => '',
        ] );

        $this->add_control( 'fpo', [
            'label' => __( 'FPO URL', 'association-partners' ),
            'type' => Controls_Manager::URL,
            'default' => [ 'url' => '' ],
        ] );

        $this->add_control( 'ad_source', [
            'label' => __( 'AdSource (JS/HTML)', 'association-partners' ),
            'type' => Controls_Manager::CODE,
            'language' => 'html',
            'options' => [ 'language' => 'html' ],
            'default' => '',
        ] );

        $this->end_controls_section();
    }

    protected function render() {
        $settings = $this->get_settings_for_display();
        // Prepare args merging selected zone with overrides
        $o = get_option( Plugin::get_instance()->get_option_name(), [] );
        $zone = null;
        if ( ! empty( $settings['slug'] ) && ! empty( $o['adzones'] ) ) {
            foreach ( $o['adzones'] as $z ) {
                if ( $z['slug'] === $settings['slug'] ) {
                    $zone = $z;
                    break;
                }
            }
        }
        // Base args from zone or defaults
        $args = $zone ? $zone : [];
        // Apply overrides
        foreach ( [ 'name', 'description', 'classes', 'width', 'height', 'ad_source' ] as $key ) {
            if ( '' !== $settings[ $key ] ) {
                $args[ $key ] = $settings[ $key ];
            }
        }
        if ( ! empty( $settings['fpo']['url'] ) ) {
            $args['fpo'] = esc_url( $settings['fpo']['url'] );
        }

        echo Plugin::get_instance()->print_ad( $args );
    }
}
