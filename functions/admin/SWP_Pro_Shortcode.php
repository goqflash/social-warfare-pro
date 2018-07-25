<?php

/**
 * A class of functions used to render shortcodes for the user
 *
 * The SWP_Pro_Shortcodes Class used to add our shorcodes to WordPress
 * registry of registered functions.
 *
 * @package   social-warfare-pro
 * @copyright Copyright (c) 2018, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     3.2.0
 *
 */
class SWP_Pro_Shortcode {


	/**
	 * Constructs a new SWP_Shortcodes instance
	 *
	 * This function is used to add our shortcodes to WordPress' registry of
	 * shortcodes and to map our functions to each one.
	 *
	 * @since  3.0.0
	 * @param  none
	 * @return none
	 *
	 */
    public function __construct() {
        add_shortcode( 'pinterest_image', array( $this, 'pinterest_image' ) );

	}


	/**
	 * Create the [pinterest_image] shortcode.
	 *
	 * @since  3.2.0 | 25 JUL 2018 | Created
	 * @param  array $atts  Shortcode parameters
	 * @return string       The rendered HTML for a Pinterest image.
	 *
	 */
    public function pinterest_image( $atts ) {
        global $post;

        $whitelist = ['id', 'width', 'height', 'class', 'alignment'];

        //* Instantiate and santiize each of the variables passed as attributes.
        foreach( $whitelist as $var ) {
            $$var = isset( $atts[$var] ) ? sanitize_text_field( trim ( $atts[$var] ) ) : "";
        }

        if ( empty( $id ) ) {
            $id = get_post_meta( $post->ID, 'swp_pinterest_image', true);
            $src = get_post_meta( $post->ID, 'swp_pinterest_image_url', true );
        } else {
            $src = wp_get_attachment_url( $id );
        }

        if ( !is_numeric( $id ) ) {
            return;
        }

        $image = get_post( $id );

        //* Prefer the user-defined Pin Description.
        $description = get_post_meta( $post->ID, 'swp_pinterest_description', true );

        if ( empty( $description ) ) :
            //* The description as set in the Media Gallery.
            $description = $image->post_content;
        endif;

        //* Pinterest limits the description to 500 characters.
        if ( empty( $description ) || strlen( $description ) > 500 ) {
            $alt = get_post_meta( $id, '_wp_attachment_image_alt', true );

            if ( !empty( $alt ) ) :
                $description = $alt;
            else:
                //* Use the caption instead.
                $description = $image->post_excerpt;
            endif;
        }

        if ( !empty( $width ) && !empty( $height ) ):
            $dimensions = ' width="' . $width . '"';
            $dimensions .= ' height="' . $height . '"';
        else :
            $dimensions = "";
        endif;

        if ( empty( $class ) ) :
            $class = "swp-pinterest-image";
        endif;

        if ( !empty( $alignment ) ) :
            switch ( $alignment ) {
                default:
                    $alignment = '';
                case 'left':
                    $alignment = 'style="text-align: left";';
                    break;
                case 'right':
                    $alignment = 'style="text-align: right";';
                    break;
                case 'center':
                    $alignment = 'style="text-align: center"';
                    break;
            }
        endif;

        $html = '<div class="swp-pinterest-image-wrap" ' . $alignment . '>';
            $html .= '<img src="' . $src . '"';
            $html .= $alignment;
            $html .= $dimensions;
            $html .= ' class="' . $class . '"';
            $html .= ' data-pin-description="' . $description . '"';
            $html .= ' />';
        $html .= '</div>';

        return $html;
    }
}
