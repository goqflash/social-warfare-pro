<?php

/**
 * VKontakte
 *
 * Class to add a VKontakte share button to the available buttons
 *
 * @package   SocialWarfare\Functions\Social-Networks
 * @copyright Copyright (c) 2020, Warfare Plugins, LLC
 * @license   GPL-3.0+
 * @since     4.0.0 | 21 FEB 2020 | CREATED
 *
 */
class SWP_VKontakte extends SWP_Social_Network {


		/**
		 * The Magic __construct Method
		 *
		 * This method is used to instantiate the social network object. It does three things.
		 * First it sets the object properties for each network. Then it adds this object to
		 * the globally accessible swp_social_networks array. Finally, it fetches the active
		 * state (does the user have this button turned on?) so that it can be accessed directly
		 * within the object.
		 *
		 * @since  3.0.0 | 06 APR 2018 | Created
		 * @param  none
		 * @return none
		 * @access public
		 *
		 */
		public function __construct() {

			// Update the class properties for this network
			$this->name           = __( 'VKontakte','social-warfare' );
			$this->cta            = __( 'Share','social-warfare' );
			$this->key            = 'vk';
			$this->default        = 'false';

			// This is the link that is clicked on to share an article to their network.
			$this->base_share_url = 'http://vk.com/share.php?url=';

			$this->init_social_network();
		}


		/**
		 * Generate the API Share Count Request URL
		 *
		 * @since  4.0.0 | 21 FEB 2020 | Created
		 * @access public
		 * @param  string $url The permalink of the page or post for which to fetch share counts
		 * @return string $request_url The complete URL to be used to access share counts via the API
		 *
		 */
		public function get_api_link( $url ) {
			$api_url = 'http://vkontakte.ru/share.php?act=count&index=1&url='. $url .'&format=json&callback=?';
			return $api_url;
		}


		/**
		 * Parse the response to get the share count
		 *
		 * @since  1.0.0 | 06 APR 2018 | Created
		 * @since  3.6.0 | 22 APR 2019 | Updated to parse API v.3.2.
		 * @since  4.0.0 | 03 DEC 2019 | Updated to parse API v.3.2 without token.
		 * @access public
		 * @param  string  $response The raw response returned from the API request
		 * @return integer The number of shares reported from the API
		 *
		 */
		public function parse_api_response( $response ) {

			// Parse the response into a generic PHP object.
			$response = json_decode( $response );

			// Parse the response to get integers.
			if( !empty( $response->og_object ) && !empty( $response->og_object->engagement ) ) {
				return $response->og_object->engagement->count;
			}

			// Return 0 if no valid counts were able to be extracted.
			return 0;
		}
}