<?php
if (! class_exists('wpdkPlugin_PluginMeta')) {

    /**
     * Holds the ui-only code.
     *
     * @package wpdkPlugin\PluginMeta
     * @author Lance Cleveland <lance@charlestonsw.com>
     * @copyright 2015 - 2016 Charleston Software Associates, LLC
     *
     * @property    wpdkPlugin  $addon
     * @property    array       $file_meta_map  Map of file names to meta keys.
     */
    class wpdkPlugin_PluginMeta extends WPDK_BaseClass_Object {
        public $addon;
	    private $file_meta_map;

		    /**
         * The plugins.json and readme data as a named array of slug=>mixed[] properties
         * @var mixed[] $metadata_array
         */
        public $metadata_array;

        /**
         * Has the metadata been set already?
         *
         * @var bool
         */
        private $meta_set;

        /**
         * The readme file processor object.
         *
         * @var \wpdkPlugin_ReadMe $readme
         */
        public $readme;

        /**
         * Things we do at the start.
         */
        function initialize() {
            $this->meta_set['production'] = false;
            $this->meta_set['prerelease'] = false;
        }

        /**
         * Return false if there is no plugin meta data.  Try to read the JSON file first and set it up.
         *
         * @return boolean
         */
        function check_plugin_meta() {
            if ( count( $this->metadata_array['pluginMeta'] ) < 1 ) { $this->set_plugin_metadata_json(); }
            if ( ! empty ( $this->metadata_array['pluginMeta']['error']  ) ) { return false; }
            return ( count( $this->metadata_array['pluginMeta'] ) > 0 );
        }

        /**
         * Setup the readme file parser object.
         */
        private function createobject_ReadMe() {
            if ( ! is_a( $this->readme , '' ) ) {
                require_once('class.readme.php');
                $this->readme =
                    new wpdkPlugin_ReadMe(
                        array(
                            'addon'     => $this->addon
                        )
                    );
            }
        }

	    /**
	     * Get product meta by file.
	     *
	     * @param string $file
	     *
	     * @return null||array  null if no meta available, the meta_array element otherwise.
	     */
	    public function get_meta_by_file( $file ) {
		    $this->set_file_to_meta_map();

		    if ( ! isset( $this->file_meta_map ) ) {
		    	return null;
		    }

		    if ( empty( $this->file_meta_map[ $file ] ) ) {
			    return null;
		    }

		    $meta_key = $this->file_meta_map[ $file ];
		    if ( ! empty( $this->metadata_array['pluginMeta'][ $meta_key ] ) ) {
		    	return $this->metadata_array['pluginMeta'][ $meta_key ];
		    }

		    return null;
	    }

	    /**
	     * Get product meta by sku.
	     *
	     * @param string $sku
	     *
	     * @return null||array  null if no meta available, the meta_array element otherwise.
	     */
        public function get_meta_by_sku( $sku ) {
			if ( ! $this->set_and_check_meta() ) {
				return null;
			}

	        $lower_sku = strtolower( $sku );
	        if ( empty( $this->metadata_array['pluginMeta'][ $lower_sku ] ) ) {
	        	return null;
	        }

	        // We have a direct hit... return the meta info.
	        //
	        if ( ! empty( $this->metadata_array['pluginMeta'][ $lower_sku ][ 'production' ] ) ) {
		        return $this->metadata_array['pluginMeta'][ $lower_sku ];
	        }

	        // alias check
	        //
	        $meta_key = $this->metadata_array['pluginMeta'][ $lower_sku ];
	        if ( ! empty( $this->metadata_array['pluginMeta'][ $meta_key ][ 'production' ] ) ) {
	        	return $this->metadata_array['pluginMeta'][ $meta_key ];
	        }

	        return null;
        }

        /**
         * Return the error message string.
         *
         * @return string
         */
        public function get_error_message() {
            $error_message = isset( $this->metadata_array['pluginMeta']['error'] ) ? $this->metadata_array['pluginMeta']['error'] : '';
            return $error_message;
        }


        /**
         * Return true if an error was caught.
         *
         * @return bool
         */
        public function has_error() {
            return ( ! empty( $this->metadata_array['pluginMeta']['error'] ) );

        }

	    /**
	     * Set meta and check to make sure basic elements are active.
	     *
	     * @return boolean true if meta OK
	     */
	    private function set_and_check_meta( ) {
		    $this->addon->set_current_directory();

		    $this->set_plugin_metadata_json();

		    if ( ! $this->check_plugin_meta() ) {
			    return false;
		    }
		    if ( ! isset( $this->metadata_array['pluginMeta'] ) ) {
			    return false;
		    }

		    return true;
	    }

	    /**
	     * Create the filename to meta map.
	     */
	    private function set_file_to_meta_map( ) {
	    	if ( ! isset( $this->file_meta_map ) ) {
	    		if ( $this->set_and_check_meta() ) {
	    			foreach ( $this->metadata_array[ 'pluginMeta' ] as $slug => $meta_entry ) {
	    				$this->set_file_meta_map_for_target( $slug , 'production' );
					    $this->set_file_meta_map_for_target( $slug , 'prerelease' );

				    }
			    }
		    }
	    }

	    /**
	     * Set file to meta map for specified target.
	     *
	     * @param string $slug
	     * @param string $target
	     */
	    private function set_file_meta_map_for_target( $slug , $target ) {
	    	if ( isset( $this->metadata_array['pluginMeta'][$slug][$target] ) ) {
			    $this->addon->set_current_directory( $target );
			    $this->addon->set_current_plugin( $slug, false );
			    $this->file_meta_map[ $this->addon->current_plugin['zipfile'] ] = $slug;
		    }
	    }

        /**
         * Set the JSON and readme metadata array for the plugins.
         *
         * @param string $slug slug for a specific product
         * @param boolean $extended show extra readme data
         * @param boolean $get_readme set to false to skip fetching readme file contents.
         */
        function set_plugin_metadata( $slug = null, $extended = false , $get_readme = true ) {
            if ( ! $this->meta_set[$this->addon->current_target] ) {
                if ($slug === null) {
                    $slug = $this->addon->set_plugin_slug();
                    if ($slug === null) {
                        return;
                    }
                }
                $this->set_plugin_metadata_json();
                if ( $get_readme ) {
                    $this->set_plugin_metadata_readme($slug, $extended);
                }
                $this->meta_set[$this->addon->current_target] = true;
            }
        }

        /**
         * Set the plugins.json properties for the metadata array for the plugins.
         */
        function set_plugin_metadata_json() {
            $plugin_file = $this->addon->current_directory . $this->addon->options['plugin_json_file'];

            if ( file_exists( $plugin_file ) && is_readable( $plugin_file ) ) {
                $this->metadata_array = json_decode( file_get_contents( $plugin_file ), true );

            } else {
                $this->metadata_array['pluginMeta']['error'] =
                    file_exists( $plugin_file )                 ?
                        ' Could not read ' . $plugin_file . '.' :
                        $plugin_file . ' does not exist.'       ;
            }
        }

        /**
         * Set the readme properties for the metadata array for the plugins.
         *
         * @param string $slug slug for a specific product
         * @param boolean $extended true to get more data from the readme file
         *
         * @return string
         */
        function set_plugin_metadata_readme( $slug, $extended = false ) {
            if ( ! $this->check_plugin_meta() ) { return ''; }
            $this->createobject_ReadMe();

            foreach ( $this->metadata_array['pluginMeta'] as $plugin_slug => $plugin_details ) {
                if ( ! empty( $slug ) && ( $plugin_slug !== $slug) ) {
                    continue;
                }

                // Drop Aliases From Plugin Info
                //
                if ( !is_array( $this->metadata_array['pluginMeta'][$plugin_slug] ) ) {
                    unset( $this->metadata_array['pluginMeta'][$plugin_slug] );
                    continue;
                }


                // Load data for non-aliased plugins.
                //
                $this->readme->filename = $this->addon->set_zip_filename( $plugin_slug, '_readme.txt' );

                $this->metadata_array['pluginMeta'][$plugin_slug] =
                    array_merge(
                        $this->metadata_array['pluginMeta'][$plugin_slug],
                        $this->readme->get_readme_data($extended)
                    );
            }
        }
	}
}
