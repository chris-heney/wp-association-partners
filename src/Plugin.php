<?php

namespace AssociationPartners;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Plugin {
    private static $instance = null;
    private $option_name = 'association_partners_settings';

    public static function get_instance() {
        if ( null === self::$instance ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct() {
        // Admin
        add_action( 'admin_menu', [ $this, 'add_admin_menu' ] );
        add_action( 'admin_init', [ $this, 'register_settings' ] );
        add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_admin_scripts' ] );

        // Front‑end
        add_action( 'wp_head', [ $this, 'inject_head_code' ], 1 );
        add_action( 'wp_body_open', [ $this, 'inject_body_code' ], 1 );

        // Accessors
        add_shortcode( 'AssociationAd', [ $this, 'handle_shortcode' ] );
        add_action( 'elementor/widgets/widgets_registered', [ $this, 'register_elementor_widget' ] );
    }

    public function add_admin_menu() {
        add_options_page(
            'Association Partners',
            'Association Partners',
            'manage_options',
            'association_partners',
            [ $this, 'settings_page' ]
        );
    }

    public function enqueue_admin_scripts( $hook ) {
        if ( 'settings_page_association_partners' !== $hook ) {
            return;
        }
        wp_enqueue_script(
            'association-partners-admin',
            plugin_dir_url( dirname( __FILE__ ) ) . 'assets/js/admin.js',
            [ 'jquery' ],
            '1.0',
            true
        );
    }

    public function register_settings() {
        register_setting(
            'association_partners', 
            $this->option_name, 
            [ $this, 'sanitize_settings' ]
        );

        // ----- General Settings -----
        add_settings_section(
            'general_settings',
            'General Settings',
            null,
            'association_partners'
        );

        add_settings_field(
            'gtm_id',
            'Google Tag Manager ID',
            [ $this, 'field_gtm_id_cb' ],
            'association_partners',
            'general_settings'
        );

        add_settings_field(
            'ad_impression_event',
            'Ad Impression Event',
            [ $this, 'field_ad_impression_event_cb' ],
            'association_partners',
            'general_settings'
        );

        add_settings_field(
            'ad_click_event',
            'Ad Click Event',
            [ $this, 'field_ad_click_event_cb' ],
            'association_partners',
            'general_settings'
        );

        add_settings_field(
            'enable_elementor_adzones',
            'Enable Elementor AdZones',
            [ $this, 'field_checkbox_cb' ],
            'association_partners',
            'general_settings',
            [ 'option_key' => 'enable_elementor_adzones' ]
        );

        add_settings_field(
            'enable_shortcode_adzones',
            'Enable Shortcode AdZones',
            [ $this, 'field_checkbox_cb' ],
            'association_partners',
            'general_settings',
            [ 'option_key' => 'enable_shortcode_adzones' ]
        );

        add_settings_field(
            'enable_bb_adzones',
            'Enable Beaver Builder AdZones <em>(coming soon)</em>',
            [ $this, 'field_checkbox_cb' ],
            'association_partners',
            'general_settings',
            [ 'option_key' => 'enable_bb_adzones', 'disabled' => true ]
        );

        add_settings_field(
            'enable_gutenberg_adzones',
            'Enable Gutenberg AdZones <em>(coming soon)</em>',
            [ $this, 'field_checkbox_cb' ],
            'association_partners',
            'general_settings',
            [ 'option_key' => 'enable_gutenberg_adzones', 'disabled' => true ]
        );

        // ----- AdZones Repeater -----
        add_settings_section(
            'adzones_section',
            'AdZones',
            null,
            'association_partners'
        );

        add_settings_field(
            'adzones',
            'Manage AdZones',
            [ $this, 'field_adzones_cb' ],
            'association_partners',
            'adzones_section'
        );
    }

    public function sanitize_settings( $input ) : array {
        $out = [];

        // simple text fields
        $out['gtm_id']              = isset( $input['gtm_id'] ) ? sanitize_text_field( $input['gtm_id'] ) : '';
        $out['ad_impression_event'] = isset( $input['ad_impression_event'] ) ? sanitize_text_field( $input['ad_impression_event'] ) : '';
        $out['ad_click_event']      = isset( $input['ad_click_event'] ) ? sanitize_text_field( $input['ad_click_event'] ) : '';

        // toggles
        foreach ( [ 'elementor', 'shortcode', 'bb', 'gutenberg' ] as $key ) {
            $flag = "enable_{$key}_adzones";
            $out[ $flag ] = ! empty( $input[ $flag ] ) ? 1 : 0;
        }

        // AdZones array
        $out['adzones'] = [];
        if ( ! empty( $input['adzones'] ) && is_array( $input['adzones'] ) ) {
            foreach ( $input['adzones'] as $zone ) {
                $san = [];
                $san['name']        = sanitize_text_field( $zone['name'] ?? '' );
                // slug: lowercase alphanumeric + dash
                $raw_slug          = strtolower( $zone['slug'] ?? '' );
                $san['slug']       = sanitize_text_field( preg_replace( '/[^a-z0-9\-]/', '', $raw_slug ) );
                $san['classes']    = sanitize_text_field( $zone['classes'] ?? '' );
                $san['description']= sanitize_textarea_field( $zone['description'] ?? '' );
                $san['width']      = isset( $zone['width'] ) ? intval( $zone['width'] ) : '';
                $san['height']     = isset( $zone['height'] ) ? intval( $zone['height'] ) : '';
                $san['fpo']        = isset( $zone['fpo'] ) ? esc_url_raw( $zone['fpo'] ) : '';
                $san['fpo_link']   = isset( $zone['fpo_link'] ) ? esc_url_raw( $zone['fpo_link'] ) : '';
                // allow raw JS/HTML in ad_source
                $san['ad_source']  = $zone['ad_source'] ?? '';
                $out['adzones'][]  = $san;
            }
        }

        return $out;
    }

    public function settings_page() {
        ?>
        <div class="wrap">
          <h1>Association Partners Settings</h1>
          <form method="post" action="options.php">
            <?php
            settings_fields( 'association_partners' );
            do_settings_sections( 'association_partners' );
            submit_button();
            ?>
          </form>
        </div>
        <?php
    }

    //–– Field Callbacks ––//

    public function field_gtm_id_cb() {
        $o = get_option( $this->option_name, [] );
        printf(
            '<input type="text" name="%1$s[gtm_id]" value="%2$s" class="regular-text" />',
            esc_attr( $this->option_name ),
            esc_attr( $o['gtm_id'] ?? '' )
        );
    }

    public function field_ad_impression_event_cb() {
        $o = get_option( $this->option_name, [] );
        printf(
            '<input type="text" name="%1$s[ad_impression_event]" value="%2$s" class="regular-text" />',
            esc_attr( $this->option_name ),
            esc_attr( $o['ad_impression_event'] ?? '' )
        );
    }

    public function field_ad_click_event_cb() {
        $o = get_option( $this->option_name, [] );
        printf(
            '<input type="text" name="%1$s[ad_click_event]" value="%2$s" class="regular-text" />',
            esc_attr( $this->option_name ),
            esc_attr( $o['ad_click_event'] ?? '' )
        );
    }

    public function field_checkbox_cb( $args ) {
        $o     = get_option( $this->option_name, [] );
        $key   = $args['option_key'];
        $label = isset( $args['disabled'] ) ? 'disabled' : '';
        $checked = ! empty( $o[ $key ] ) ? 'checked' : '';
        printf(
            '<input type="checkbox" name="%1$s[%2$s]" value="1" %3$s %4$s />',
            esc_attr( $this->option_name ),
            esc_attr( $key ),
            $checked,
            $label
        );
    }

    public function field_adzones_cb() {
        $o      = get_option( $this->option_name, [] );
        $zones  = $o['adzones'] ?? []; ?>

        <style>
        #adzones-wrapper {
          display: flex ;
          flex-wrap: wrap;
          gap: 10px;
          margin-bottom: 10px;
        }
        .adz-row {
          border: 1px solid #ccc;
          padding: 10px;
          display: flex;
          gap: 10px;
          flex-direction: column;
          background-color: #fff;
          box-shadow: 0 1px 3px rgba(0,0,0,0.2);
          border-radius: 5px;
          width: 350px;
        }
        .hw-wrapper {
          display: flex;
          gap: 10px;
        }
        .hw-wrapper label {
          width: 50%;
        }
        .hw-wrapper input[type="number"],
        .adz-row input[type="text"],
        .adz-row input[type="url"] {
          width: 100%;
        }
        #adzones-wrapper textarea {
          width: 100%;
          min-height: 100px;
        }
        </style>
        <div id="adzones-wrapper">
          <?php foreach ( $zones as $i => $z ): ?>
            <div class="adz-row">
              <p><strong>AdZone #<?php echo $i+1; ?></strong></p>
              <p>
                <label>Name<br/>
                  <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][name]" value="<?php echo esc_attr( $z['name'] ); ?>" />
                </label>
              </p>
              <p>
                <label>Slug<br/>
                  <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][slug]" value="<?php echo esc_attr( $z['slug'] ); ?>" />
                </label>
              </p>
              <p>
                <label>Class(es)<br/>
                  <input type="text" name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][classes]" value="<?php echo esc_attr( $z['classes'] ); ?>" />
                </label>
              </p>
              <p>
                <label>Description<br/>
                  <textarea name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][description]" rows="3"><?php echo esc_textarea( $z['description'] ); ?></textarea>
                </label>
              </p>
              <p class="hw-wrapper">
                <label>Width<br/>
                  <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][width]" value="<?php echo esc_attr( $z['width'] ); ?>" />
                </label>
                &nbsp;
                <label>Height<br/>
                  <input type="number" name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][height]" value="<?php echo esc_attr( $z['height'] ); ?>" />
                </label>
              </p>
              <p>
                <label>FPO URL<br/>
                  <input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][fpo]" value="<?php echo esc_attr( $z['fpo'] ); ?>" />
                </label>
              </p>
              <p>
                <label>FPO Link URL<br/>
                  <input type="url" name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][fpo_link]" value="<?php echo esc_attr( $z['fpo_link'] ?? '' ); ?>" />
                </label>
              </p>
              <p>
                <label>AdSource (JS/HTML)<br/>
                  <textarea name="<?php echo esc_attr( $this->option_name ); ?>[adzones][<?php echo $i; ?>][ad_source]" rows="4"><?php echo esc_textarea( $z['ad_source'] ); ?></textarea>
                </label>
              </p>
              <p><button class="button remove-adzone">Remove This AdZone</button></p>
              <hr/>
            </div>
          <?php endforeach; ?>
        </div>

        <p><button id="add-adzone" class="button button-primary">Add New AdZone</button></p>

        <script>
        jQuery(function($){
          var wrapper = $('#adzones-wrapper'), idx = <?php echo count($zones); ?>;
          $('#add-adzone').click(function(e){
            e.preventDefault();
            var row = `<div class="adz-row">
              <p><strong>AdZone #${idx+1}</strong></p>
              <p><label>Name<br/><input type="text" name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][name]" /></label></p>
              <p><label>Slug<br/><input type="text" name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][slug]" /></label></p>
              <p><label>Class(es)<br/><input type="text" name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][classes]" /></label></p>
              <p><label>Description<br/><textarea name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][description]" rows="3"></textarea></label></p>
              <p class="hw-wrapper"><label>Width<br/><input type="number" name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][width]" /></label>
                 &nbsp;
                 <label>Height<br/><input type="number" name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][height]" /></label>
              </p>
              <p><label>FPO URL<br/><input type="url" name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][fpo]" /></label></p>
              <p><label>FPO Link URL<br/><input type="url" name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][fpo_link]" /></label></p>
              <p><label>AdSource (JS/HTML)<br/><textarea name="<?php echo esc_js($this->option_name); ?>[adzones][${idx}][ad_source]" rows="4"></textarea></label></p>
              <p><button class="button remove-adzone">Remove This AdZone</button></p>
              <hr/>
            </div>`;
            wrapper.append(row);
            idx++;
          });

          wrapper.on('click', '.remove-adzone', function(e){
            e.preventDefault();
            $(this).closest('.adz-row').remove();
          });
        });
        </script>
        <?php
    }

    //–– GTM injections & accessors are next––//

    public function inject_head_code() {
        $o = get_option( $this->option_name, [] );
        if ( empty( $o['gtm_id'] ) ) {
            return;
        }
        printf(
            "\n<!-- Association Partners GTM -->\n<script>(function(w,d,s,l,i){w[l]=w[l]||[];w[l].push({'gtm.start':\nnew Date().getTime(),event:'gtm.js'});var f=d.getElementsByTagName(s)[0],\nj=d.createElement(s),dl=l!='dataLayer'?'&l='+l:'';j.async=true;j.src=\n'https://www.googletagmanager.com/gtm.js?id='+i+dl;f.parentNode.insertBefore(j,f);\n})(window,document,'script','dataLayer','%s');</script>\n<!-- End Association Partners GTM -->\n",
            esc_js( $o['gtm_id'] )
        );
    }

    public function inject_body_code() {
        $o = get_option( $this->option_name, [] );
        if ( empty( $o['gtm_id'] ) ) {
            return;
        }
        printf(
            "\n<!-- Association Partners GTM (noscript) -->\n<noscript><iframe src=\"https://www.googletagmanager.com/ns.html?id=%s\"\nheight=\"0\" width=\"0\" style=\"display:none;visibility:hidden\"></iframe></noscript>\n<!-- End Association Partners GTM (noscript) -->\n",
            esc_attr( $o['gtm_id'] )
        );
    }

    public function get_option_name() {
        return $this->option_name;
    }

    public function print_ad( $args = [] ) {
        // Ensure we have at least a name
        if ( empty( $args['name'] ) ) {
            return '';
        }

        // Build classes
        $classes = [ 'association-ad' ];
        if ( ! empty( $args['classes'] ) ) {
            $classes = array_merge( $classes, explode( ' ', $args['classes'] ) );
        }

        // Build style
        $style = [];
        if ( ! empty( $args['width'] ) ) {
            $style[] = sprintf( 'width: %dpx', intval( $args['width'] ) );
        }
        if ( ! empty( $args['height'] ) ) {
            $style[] = sprintf( 'height: %dpx', intval( $args['height'] ) );
        }

        // Start output
        $out = sprintf(
            '<div class="%s" data-adzone="%s" %s>',
            esc_attr( implode( ' ', $classes ) ),
            esc_attr( $args['slug'] ?? '' ),
            ! empty( $style ) ? sprintf( 'style="%s"', esc_attr( implode( '; ', $style ) ) ) : ''
        );

        // Add FPO image if present and no ad source
        if ( ! empty( $args['fpo'] ) && empty( $args['ad_source'] ) ) {
            $img = sprintf(
                '<img src="%s" alt="%s" class="ad-fpo" />',
                esc_url( $args['fpo'] ),
                esc_attr( $args['name'] )
            );
            
            // Wrap in link if fpo_link is set
            if ( ! empty( $args['fpo_link'] ) ) {
                $img = sprintf(
                    '<a href="%s" target="_blank" rel="noopener">%s</a>',
                    esc_url( $args['fpo_link'] ),
                    $img
                );
            }
            
            $out .= $img;
        }

        // Add ad source if present
        if ( ! empty( $args['ad_source'] ) ) {
            $out .= $args['ad_source'];
        }

        $out .= '</div>';

        return $out;
    }

    public function register_elementor_widget() {
        // Check if Elementor is installed and activated
        if ( ! did_action( 'elementor/loaded' ) ) {
            return;
        }

        // Check if AdZones are enabled for Elementor
        $o = get_option( $this->option_name, [] );
        if ( empty( $o['enable_elementor_adzones'] ) ) {
            return;
        }

        // Include the widget class
        require_once plugin_dir_path( dirname( __FILE__ ) ) . 'src/Elementor/AdZone_Widget.php';

        // Register the widget
        \Elementor\Plugin::instance()->widgets_manager->register_widget_type( new \AssociationPartners\Elementor\AdZone_Widget() );
    }

}
