<?php

/**
 * Meta Box Loader Class
 *
 * A class to load up all of our custom meta boxes for things like the custom
 * Pinterest image, the Twitter description, etc.
 *
 * @package   SocialWarfare\Functions\Utilities
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since  3.1.0 | 02 JUL 2018 | Created
 *
 */
class SWP_Meta_Box_Loader {

	public function __construct() {
		if ( true === is_admin() ) {
			add_filter( 'swpmb_meta_boxes', array( $this, 'load_meta_boxes') );
            add_action( 'swpmb_before_social_warfare', array( $this, 'before_meta_boxes') );
            add_action( 'swpmb_after_social_warfare', array( $this, 'after_meta_boxes' ) );
		}
	}

	/**
	 * Load Meta Boxes
	 *
	 * @since  3.1.0 | 02 JUL 2018 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function load_meta_boxes( $meta_boxes ) {
		$post_id = $_GET['post'];
    	$prefix = 'swp_';
    	$twitter_id = isset( $options['twitter_id'] ) ? $options['twitter_id'] : false;

    	$twitter_handle = $this->get_twitter_handle( $twitter_id );

		//* Set a default value if the user has never toggled the switch.
		if ( metadata_exists( 'post', $post_id, 'swp_force_pin_image' ) ) {
			$pin_force_image_value = get_post_meta($post_id, 'swp_force_pin_image', true);
		} else {
			$pin_force_image_value = true;
		}

        $heading = array(
            'name'  => 'Share Customization',
            'id'    => 'swp_meta_box_heading',
            'type'  => 'heading',
            'class' => 'heading  swpmb-full-width',
            'desc'  => 'Make sure your content is shared exactly the way you want it to be shared by customizing the fields below.',
        );


        // Setup the Open Graph image.
        $open_graph_image = array(
            'name'  => __( 'Open Graph Image','social-warfare' ),
            'desc'  => __( 'Add an image that is optimized for maximum exposure on Facebook, Google+ and LinkedIn. We recommend 1,200px by 628px.','social-warfare' ),
            'id'    => $prefix . 'og_image',
            'type'  => 'image_advanced',
            'clone' => false,
            'class' => 'open-graph swpmb-left',
            'max_file_uploads' => 1,
        );

        // Setup the Open Graph title.
        $open_graph_title = array(
            'name'  => __( 'Open Graph Title','social-warfare' ),
            'placeholder'  => __( 'Add a title that will populate the open graph meta tag which will be used when users share your content onto Facebook, LinkedIn, and Google+. If nothing is provided here, we will use the post title as a backup.','social-warfare' ),
            'id'    => $prefix . 'og_title',
            'type'  => 'textarea',
            'class' => 'open-graph swpmb-right',
			'rows'	=> 1,
            'clone' => false,
        );

        // Setup the Open Graph description.
        $open_graph_description = array(
            'name'  => __( 'Open Graph Description','social-warfare' ),
            'placeholder'  => __( 'Add a description that will populate the open graph meta tag which will be used when users share your content onto Facebook, LinkedIn, and Google Plus.','social-warfare' ),
            'id'    => $prefix . 'og_description',
            'class' => 'open-graph swpmb-right',
            'type'  => 'textarea',
            'clone' => false,
        );

        // Setup the Open Graph image.
        $twitter_image = array(
            'name'  => __( 'Twitter Card Image','social-warfare' ),
            'desc'  => __( 'Add an image that is optimized for maximum exposure on Facebook, Google+ and LinkedIn. We recommend 1,200px by 628px.','social-warfare' ),
            'id'    => $prefix . 'twitter_card_image',
            'type'  => 'image_advanced',
            'clone' => false,
            'class' => 'twitter swpmb-left',
            'max_file_uploads' => 1,
        );

        // Setup the Twitter Card title.
        $twitter_title = array(
            'name'  => __( 'Twitter Card Title','social-warfare' ),
            'placeholder'  => __( 'Add a title that will populate the open graph meta tag which will be used when users share your content onto Facebook, LinkedIn, and Google+. If nothing is provided here, we will use the post title as a backup.','social-warfare' ),
            'id'    => $prefix . 'twitter_card_title',
            'type'  => 'textarea',
            'class' => $prefix . 'twitter_card_title twitter swpmb-right',
			'rows'	=> 1,
            'clone' => false,
        );

        // Setup the Twitter Card Description description.
        $twitter_description = array(
            'name'  => __( 'Twitter Card Description','social-warfare' ),
            'placeholder'  => __( 'Add a description that will populate the open graph meta tag which will be used when users share your content onto Facebook, LinkedIn, and Google Plus.','social-warfare' ),
            'id'    => $prefix . 'twitter_card_description',
            'class' => $prefix . 'twitter_card_description twitter swpmb-right',
            'type'  => 'textarea',
            'clone' => false,
        );

        // Setup the Custom Tweet box.
        $custom_tweet = array(
            'name'  => __( 'Custom Tweet','social-warfare' ),
            'placeholder' => 'If left empty, defaults to \'Open Graph Title\'' . the_permalink() . $twitter_handle ,
            'desc'  => ( $twitter_id ? sprintf( __( 'If this is left blank your post title will be used. Based on your username (@%1$s), <span class="tweetLinkSection">a link being added,</span> and the current content above, your tweet has %2$s characters remaining.','social-warfare' ),str_replace( '@','',$twitter_handle ),'<span class="counterNumber">140</span>' ) : sprintf( __( 'If this is left blank your post title will be used. <span ="tweetLinkSection">Based on a link being added, and</span> the current content above, your tweet has %s characters remaining.','social-warfare' ),'<span class="counterNumber">140</span>' )),
            'id'    => $prefix . 'custom_tweet',
            'class' => $prefix . 'custom_tweetWrapper twitter  swpmb-full-width',
            'type'  => 'textarea',
            'clone' => false,
        );

		$open_graph_toggle = array(
            'id'    => 'use_open_graph_twitter',
            'type'  => 'toggle',
            'name'  => __( 'Use Open Graph for Twitter Card?', 'social-warfare'),
			'desc'	=> '',
            'value'=> false,
            'class' => 'twitter swpmb-right',
        );

        // $twitter_handle_box = array(
        //     'name'  => $twitter_handle,
        //     'id'    => 'twitter_id',
        //     'class' => 'twitterIDWrapper twitter',
        //     'type'  => 'hidden',
        //     'std'   => $twitter_handle,
        // );

        // Setup the pinterest optimized image.
        $pinterest_image = array(
            'name'  => __( 'Pinterest Image','social-warfare' ),
            'desc'  => __( 'Add an image that is optimized for maximum exposure on Pinterest. We recommend using an image that is formatted in a 2:3 aspect ratio like 735x1102.','social-warfare' ),
            'id'    => $prefix . 'pinterest_image',
            'class' => $prefix . 'large_image pinterest swpmb-left',
            'type'  => 'image_advanced',
            'clone' => false,
            'max_file_uploads' => 1,
        );

        $pinterest_description = array(
            'name'  => __( 'Pinterest Description','social-warfare' ),
            'placeholder'  => __( 'Craft a customized description that will be used when this post is shared on Pinterest. Leave this blank to use the title of the post.','social-warfare' ),
            'id'    => $prefix . 'pinterest_description',
            'class' => $prefix . 'pinterest_descriptionWrapper pinterest swpmb-right',
            'type'  => 'textarea',
            'clone' => false,
        );

        // Setup the pinterest description.
        $pin_browser_extension = array(
            'name'    => __( 'Pin Image for Browser Extensions','social-warfare' ),
            'id'      => 'swp_pin_browser_extension',
            'type'    => 'select',
            'options' => array(
                'default' => __( 'Default','social-warfare' ),
                'on'      => __( 'On','social-warfare' ),
                'off'     => __( 'Off','social-warfare' ),
            ),
            'clone' => false,
            'class' => 'pinterest swpmb-right',
            'std'   => 'default',
        );

        $pin_browser_extension_location = array(
            'name'    => __( 'Pin Browser Image Location','social-warfare' ),
            'id'      => 'swp_pin_browser_extension_location',
            'type'    => 'select',
            'options' => array(
                'default' => __( 'Default','social-warfare' ),
                'hidden'  => __( 'Hidden','social-warfare' ),
                'top'     => __( 'At the Top of the Post','social-warfare' ),
                'bottom'  => __( 'At the Bottom of the Post','social-warfare' ),
            ),
            'clone' => false,
            'class' => 'pinterest swpmb-right',
            'std'   => 'default',
        );

        $pin_force_image = array(
            'id'    => 'swp_force_pin_image',
            'type'  => 'toggle',
            'name'  =>  __( 'Allow only this Pinterest image when pinning?', 'social-warfare'),
			'desc'  => '',
            'value' => $pin_force_image_value,
            'class' => 'pinterest swpmb-right',
        );

        $recover_shares_box = array(
            'name'  => __( 'Share Recovery','social-warfare' ),
            'desc'  => __( 'If you have changed the permalink for just this post, paste in the previous full URL for this post so we can recover shares for that link.','social-warfare' ),
            'id'    => 'swp_recovery_url',
            'class' => $prefix . 'share_recoveryWrapper other',
            'type'  => 'text',
            'clone' => false
        );

		$other_post_options = array(
            'name'  => 'Other Post Options',
            'id'    => 'swp_other_heading',
            'type'  => 'heading',
            'class' => 'other swpmb-full-width',
			'desc'	=> ''
        );

        // Set up the location on post options.
        $post_location = array(
            'name'    =>  __( 'Static Buttons Location','social-warfare' ),
            'id'      => $prefix . 'post_location',
            'type'    => 'select',
            'options' => array(
                'default' => __( 'Default','social-warfare' ),
                'above'   => __( 'Above the Content','social-warfare' ),
                'below'   => __( 'Below the Content','social-warfare' ),
                'both'    => __( 'Both Above and Below the Content','social-warfare' ),
                'none'    => __( 'None/Manual Placement','social-warfare' ),
            ),
            'clone' => false,
            'class' => 'other swpmb-left inline-select',
            'std'	=> 'default',
        );

        $float_location = array(
            'name'    =>  __( 'Floating Buttons Location','social-warfare' ),
            'id'      => $prefix . 'float_location',
            'type'    => 'select',
            'options' => array(
                'default' => __( 'Default','social-warfare' ),
                'on'      => __( 'On','social-warfare' ),
                'off'     => __( 'Off','social-warfare' ),
            ),
            'clone' => false,
            'class' => 'other swpmb-left inline-select',
            'std'   => 'default',
        );

    	// Setup our meta box using an array.
    	$meta_boxes[0] = array(
    		'id'       => 'social_warfare',
    		'title'    => __( 'Social Warfare Custom Options','social-warfare' ),
    		'pages'    => SWP_Utility::get_post_types(),
    		'context'  => 'normal',
    		'priority' => apply_filters( 'swp_metabox_priority', 'high' ),
    		'fields'   => array()
    	);

        $meta_boxes[0]['fields'][] = $heading;
        $meta_boxes[0]['fields'][] = $open_graph_image;
        $meta_boxes[0]['fields'][] = $open_graph_title;
        $meta_boxes[0]['fields'][] = $open_graph_description;
        $meta_boxes[0]['fields'][] = $pinterest_image;
        $meta_boxes[0]['fields'][] = $pin_force_image;
        $meta_boxes[0]['fields'][] = $open_graph_toggle;
        $meta_boxes[0]['fields'][] = $twitter_image;
        $meta_boxes[0]['fields'][] = $twitter_title;
        $meta_boxes[0]['fields'][] = $twitter_description;

        $meta_boxes[0]['fields'][] = $custom_tweet;

        if ( SWP_Utility::get_option( 'recover_shares' ) ) {
            $meta_boxes[0]['fields'][] = $recover_shares_box;
        }

        $meta_boxes[0]['fields'][] = $pinterest_description;
        $meta_boxes[0]['fields'][] = $pin_browser_extension;
        $meta_boxes[0]['fields'][] = $pin_browser_extension_location;
		$meta_boxes[0]['fields'][] = $other_post_options;
        $meta_boxes[0]['fields'][] = $post_location;
        $meta_boxes[0]['fields'][] = $float_location;
        // $meta_boxes[0]['fields'][] = $twitter_handle_box;

    	return $meta_boxes;
	}


	public function get_twitter_handle( $fallback = '' ) {
		// Fetch the Twitter handle for the Post Author if it exists.
		if ( isset( $_GET['post'] ) ) {
			$user_id = SWP_User_Profile::get_author( absint( $_GET['post'] ) );
		} else {
			$user_id = get_current_user_id();
		}

		$twitter_handle = get_the_author_meta( 'swp_twitter', $user_id );

		if ( ! $twitter_handle ) {
			$twitter_handle = $fallback;
		}

		return $twitter_handle;
	}

    /**
     * Echoes content before any meta box fields are printed.
     *
     * You must echo your content immediately, and return the $meta_box.
     *
     * @param  object $meta_box The Rylwis meta_box object.
     * @return object $meta_box The (optionally) filtered meta box.
     *
     */
    public function before_meta_boxes( $meta_box  ) {
		$boxes = array('heading', 'open-graph', 'twitter', 'pinterest', 'other');
		$boxes = apply_filters('swp_meta_boxes', $boxes );

		foreach ($boxes as $box) {
			$container = '<div class="swpmb-meta-container" data-type="' . $box . '">';
				$container .= '<div class="swpmb-full-width-wrap swpmb-flex"></div>';
				$container .= '<div class="swpmb-left-wrap swpmb-flex"></div>';
			    $container .= '<div class="swpmb-right-wrap swpmb-flex"></div>';
			$container .= '</div>';

			echo $container;
		}

        return $meta_box;
    }

    /**
     * Echoes content after the meta box fields are printed.
     *
     * You must echo your content immediately, and return the $meta_box.
     *
     * @param  object $meta_box The Rylwis meta_box object.
     * @return object $meta_box The (optionally) filtered meta box.
     *
     */
    public function after_meta_boxes( $meta_box ) {
        // echo '';

        return $meta_box;
    }
}
