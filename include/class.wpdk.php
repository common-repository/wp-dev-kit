<?php

/**
 * wpdkPlugin
 *
 * @property        wpdkPlugin_Activation   $Activation
 * @property-read   wpdkPlugin_Admin        $Admin
 * @property        string                  $current_directory The current directory, absolute path, based on the target being processed.
 * @property        mixed[]                 $current_plugin    The metadata for the current plugin being processed.
 * @property        string                  $current_target    The current target build level. production (default) || prerelease
 * @property        wpdkPlugin_Database     $Database
 * @property        string                  $dir               The directory we live in.
 * @property        wpdkPlugin              $instance
 * @property        array                   $options    Our plugin options.
 * @property        wpdkPlugin_PluginMeta   $PluginMeta
 * @property-read   WPDK_REST_Handler       $rest_handler
 * @property        wpdkPlugin_UpdateEngine $UpdateEngine
 * @property        wpdkPlugin_UI           $UI
 * @property        wpdkPlugin_Woo          $Woo
 *
 *
 * @package   wpdkPlugin
 * @author    Lance Cleveland <lance@lancecleveland.com>
 * @copyright 2014-2016 Charleston Software Associates, LLC
 */
class wpdkPlugin {
    public $Database;
    public $PluginMeta;
    public $UI;
    public $UpdateEngine;
    public $Woo;
    public $current_directory = 'production';
    public $current_plugin;
    public $current_target = 'production';
    public $dir;
    public $instance;
    public $options = array(
        'installed_version'       => '0.0',
	    'ip_filter_list'          => '',
        'list_heading_tag'        => 'h1',
        'production_directory'    => '/var/www/html/wp-content/production_files/',
        'plugin_json_file'        => 'plugins.json',
        'prerelease_directory'    => '/var/www/html/wp-content/prerelease_files/',
        'requires_subscription'   => '',
        'subscription_product_id' => '',
        'update_history_limit'    => '10',
    );
    /**
     * @var string the requested slug
     */
    public $requested_slug = NULL;
    /**
     * The url to this plugin admin features.
     *
     * @var string $url
     */
    public $url;
    private $Activation;
    private $Admin;
    /**
     * Have the options been set (defaults merged with DB fetched options?)
     *
     * @var boolean $options_set
     */
    private $options_set = false;
    private $rest_handler;
    /**
     * Our slug.
     *
     * @var string $slug
     */
    private $slug = NULL;

    /**
     * Constructor.
     */
    function __construct() {
        $this->url = plugins_url( '', WPDK__PLUGIN_FILE );
        $this->dir = WPDK__PLUGIN_DIR;
        $this->slug = plugin_basename( WPDK__PLUGIN_FILE );

        require_once( 'base_class.object.php' );

        add_action( 'admin_menu', array( $this, 'admin_menu' ) );
        add_action( 'wp_enqueue_scripts', array( $this, 'createobject_UI' ) );
        add_action( 'rest_api_init', array( $this, 'load_rest_handler' ) );
    }

    /**
     * AJAX: Download the referenced file.
     */
    static function download_file() {
        $wpdk = wpdkPlugin::init();
        $wpdk->set_options();
        $wpdk->send_file( $_REQUEST[ 'slug' ] );
        die();
    }

    /**
     * Invoke the plugin as singleton.
     *
     * @static
     */
    public static function init() {
        static $instance = false;
        if ( !$instance ) {
            load_plugin_textdomain( 'wp-dev-kit', false, dirname( plugin_basename( WPDK__PLUGIN_FILE ) ) . '/languages/' );
            $instance = new wpdkPlugin();
        }

        return $instance;
    }

    /**
     * Set the options by merging those from the DB with the defaults for this add-on pack.
     */
    function set_options() {
        if ( !$this->options_set ) {
            $this->options = array_merge( $this->options, get_option( 'wpdevkit_options', array() ) );
            $this->options_set = true;
        }
    }

    /**
     * Send the requested file.
     *
     * @param string $slug
     */
    function send_file( $slug ) {
        if ( !empty ( $slug ) ) {
            $this->set_current_directory( $_REQUEST[ 'target' ] );
            $this->create_object_PluginMeta();
            $this->PluginMeta->set_plugin_metadata();
            $this->set_current_plugin();
	        $zip_content = file_get_contents( $this->current_plugin[ 'zipfile' ] );
	        $additional_headers = array();
	        $additional_headers[] = sprintf( 'Content-Length: %s' , strlen( $zip_content ) );
            $this->send_file_header( $additional_headers );
            print $zip_content;
        }
    }

    /**
     * Set the directory based on the target.
     *
     * Default is production.
     *
     * @param string $target
     */
    public function set_current_directory( $target = 'production' ) {
    	$this->set_options();

         $this->current_target = ( $target === 'prerelease' ) ? 'prerelease' : 'production';

        $this->current_directory =
            ( $this->current_target === 'prerelease' ) ?
                $this->options[ 'prerelease_directory' ] :
                $this->options[ 'production_directory' ];
    }

    /**
     * Create and attach the UI processing object.
     */
    function create_object_PluginMeta() {
        if ( ! isset ( $this->PluginMeta ) ) {
            require_once( 'class.plugin_meta.php' );
            $this->PluginMeta = new wpdkPlugin_PluginMeta(  array(  'addon' => $this,  ) );
        }
    }

    /**
     * Set the current_plugin property via a slug.
     *
     * Assumes JSON_metadata_array has already been loaded.
     *
     * @param string  $slug       the slug to set current plugin data from
     * @param boolean $get_readme set to false to skip fetching readme file contents.
     *
     * @return boolean TRUE if set current plugin is OK.
     */
    function set_current_plugin( $slug = NULL, $get_readme = true ) {

        // Set slug
        //
        if ( $slug === NULL ) {
            $slug = $this->set_plugin_slug();
            if ( $slug === NULL ) {
                return false;
            }
        }

        // Set meta
        //
        $this->create_object_PluginMeta();
        $this->PluginMeta->set_plugin_metadata( NULL, false, $get_readme );
        $this->PluginMeta->metadata_array[ 'pluginMeta' ][ $slug ][ 'slug' ] = $slug;

        // Set current plugin
        //
        $this->current_plugin = $this->PluginMeta->metadata_array[ 'pluginMeta' ][ $slug ];
        $this->current_plugin[ 'slug' ] = $slug;
        $this->current_plugin[ 'zipbase' ] = ( !empty( $this->current_plugin[ 'zipbase' ] ) ) ? $this->current_plugin[ 'zipbase' ] : $slug;
        $this->current_plugin[ 'zipfile' ] = $this->current_directory . $this->set_zip_filename();

        return true;
    }

    /**
     * Set the slug requested.
     *
     * @return mixed current slug from request if set.
     */
    function set_plugin_slug() {
        if ( $this->requested_slug === NULL ) {
            if ( isset( $_REQUEST[ 'slug' ] ) && ( !empty( $_REQUEST[ 'slug' ] ) ) ) {
                $this->requested_slug = $_REQUEST[ 'slug' ];
            } else if ( isset( $_REQUEST[ 'plugin' ] ) && ( !empty( $_REQUEST[ 'plugin' ] ) ) ) {
                $this->requested_slug = $_REQUEST[ 'plugin' ];
            }
        }

        return $this->requested_slug;
    }

    /**
     * Set a plugin base file name for a zip file.
     *
     * @param string $slug   the slug to get the zip file base for.
     * @param string $suffix what do we want to end the filename with? (default '.zip')
     *
     * @return string
     */
    function set_zip_filename( $slug = '', $suffix = '.zip' ) {
        if ( empty ( $slug ) ) {
            $slug = $this->current_plugin[ 'slug' ];
        }

        return (
        isset( $this->PluginMeta->metadata_array[ 'pluginMeta' ][ $slug ][ 'zipbase' ] ) ?
            $this->PluginMeta->metadata_array[ 'pluginMeta' ][ $slug ][ 'zipbase' ] :
            $slug
        ) .
        $suffix;
    }

    /**
     * Send the file Header
     *
     * @param string[]  $strings
     */
    function send_file_header( $strings ) {
        header( 'Content-Description: File Transfer' );
        header( 'Content-Disposition: attachment; filename=' . $this->set_zip_filename() );
        header( 'Content-Type: application/zip' );
        header( 'Pragma: no-cache' );
        header( 'Expires: 0' );
	    foreach ( $strings as $string ) {
	    	header( $string );
	    }
    }

    /**
     * Activate or update this plugin.
     */
    public static function plugin_activation() {
        $instance = wpdkPlugin::init();
        $instance->set_options();
        if ( !isset( $instance->options[ 'installed_version' ] ) || empty( $instance->options[ 'installed_version' ] ) ||
            version_compare( $instance->options[ 'installed_version' ], WPDK__VERSION, '<' )
        ) {
            $instance->createobject_Activation();
        }
    }

    /**
     * Create and attach the activation processing object.
     */
    function createobject_Activation() {
        if ( !isset ( $this->Activation ) ) {
            require_once( 'class.activation.php' );
            $this->Activation = new wpdkPlugin_Activation();
        }
    }

    /**
     * AJAX: Handle plugin update requests.
     */
    static function updater() {
        $wpdk = wpdkPlugin::init();
        $wpdk->set_options();
        $wpdk->create_object_UpdateEngine();
        $wpdk->UpdateEngine->process_request();
        die();
    }

    /**
     * Create and attach the update engine object.
     */
    function create_object_UpdateEngine() {
        if ( !isset ( $this->UpdateEngine ) ) {
            require_once( 'class.update_engine.php' );
            $this->UpdateEngine =
                new wpdkPlugin_UpdateEngine(
                    array(
                        'addon' => $this,
                    )
                );
        }
    }

    /**
     * WordPress admin_menu hook.
     *
     * Do not put any hooks/filters here other than the admin init hook.
     */
    function admin_menu() {
        $this->createobject_Admin();
    }

    /**
     * Create and attach the admin processing object.
     */
    function createobject_Admin() {
        if ( !isset ( $this->Admin ) ) {
            require_once( 'class.admin.php' );
            $this->Admin = new wpdkPlugin_Admin();
        }
    }

    /**
     * Create and attach the admin processing object.
     */
    function create_object_Database() {
        if ( !isset ( $this->Database ) ) {
            require_once( 'class.database.php' );
            $this->Database = new wpdkPlugin_Database();
        }
    }

    /**
     * Create the Woo interface object.
     */
    public function create_object_Woo() {
        if ( !isset ( $this->Woo ) ) {
            require_once( 'class.woo.php' );
            $this->Woo = new  wpdkPlugin_Woo();
        }
    }

    /**
     * Create and attach the UI processing object.
     */
    function createobject_UI() {
        if ( !isset ( $this->UI ) ) {
            require_once( 'class.ui.php' );
            $this->UI = new wpdkPlugin_UI();
        }
    }

    /**
     * Load the rest handler.
     */
    function load_rest_handler() {
        if ( !defined( 'REST_API_VERSION' ) ) {
            return;
        }      // No WP REST API.  Leave.
        if ( version_compare( REST_API_VERSION, '2.0', '<' ) ) {
            return;
        }      // Require REST API version 2.
        if ( !isset( $this->rest_handler ) ) {
            require_once( 'class.handler.rest.php' );
            $this->rest_handler = new WPDK_REST_Handler();
        }
    }

}

