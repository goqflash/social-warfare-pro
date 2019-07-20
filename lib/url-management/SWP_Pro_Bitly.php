<?php

/**
 * SWP_Pro_Bitly
 *
 * This class will manage and process the shortened URLs for shared links
 * if the user has shortlinks enabled and if they have Bitly selected as
 * their link shortening integration of choice. The link modifications
 * made by this class are added via filter and will be accessed by
 * applying the swp_link_shortening filter.
 *
 * @since 4.0.0 | 17 JUL 2019 | Created
 *
 */
class SWP_Pro_Bitly {


	use SWP_Debug_Trait;


	/**
	 * The Magic Constructor Method
	 *
	 * This method will simply queue up the Bitly processing methods to run on
	 * the appropriate hooks as needed.
	 *
	 * @since  4.0.0 | 17 JUL 2019 | Created
	 * @param  void
	 * @return void
	 *
	 */
	public function __construct() {

		$this->key      = 'bitly';
		$this->name     = 'Bitly';

		$this->establish_button_properties();

		add_filter( 'swp_link_shortening', array( $this, 'shorten_link' ) );
		add_filter( 'swp_available_link_shorteners' , array( $this, 'register_self' ) );
		add_action( 'wp_ajax_nopriv_swp_bitly_oauth', array( $this , 'bitly_oauth_callback' ) );
		add_action( 'wp_footer', array( $this, 'debug' ) );
	}


	/**
	 * register_self()
	 *
	 * A function to register this link shortening integration with the
	 * 'swp_register_link_shortners' filter so that it will show up and become
	 * an option on the options page.
	 *
	 * @since  4.0.0 | 18 JUL 2019 | Created
	 * @param  array $array An array of link shortening integrations.
	 * @return array        The modified array with our integration added.
	 *
	 */
	public function register_self( $array ) {
		$array[$this->key] = $this;
		return $array;
	}


	/**
	 * generate_authentication_button_data()
	 *
	 * A method to generate an array of information that can be used to generate
	 * the authentication button for this network on the options page.
	 *
	 * @since  4.0.0 | 18 JUL 2019 | Created
	 * @param  void
	 * @return array The array of button data including the text, color_css,
	 *               target, and link.
	 *
	 */
	public function establish_button_properties() {


		/**
		 * If the integration has already been authenticated, then we'll need to
		 * populate a button that says, "Connected" so it's easy for the user to
		 * see.
		 *
		 */
		if ( SWP_Utility::get_option('bitly_access_token') ) {

			//* Display a confirmation button. On click takes them to bitly settings page.
			$text = __( 'Connected', 'social-warfare' );
			$text .= " for:<br/>" . SWP_Utility::get_option( 'bitly_access_login' );
			$class = 'button sw-green-button swp-revoke-button';
			$link = 'https://app.bitly.com/bitlinks/?actions=accountMain&actions=settings&actions=security';
			$target = '_blank';


		/**
		 * If the integration has not been authenticated, then it needs to
		 * contain the text and link that will allow the user to do so.
		 *
		 */
		} else {

			//* Display the button, which takes them to a Bitly auth page.
			$text = __( 'Authenticate', 'social-warfare' );
			$class = 'button sw-navy-button swp-revoke-button';
			$target = '';

			//* The base URL for authorizing SW to work on a user's Bitly account.
			$link = "https://bitly.com/oauth/authorize";

			//* client_id: The SWP application id, assigned by Bitly.
			$link .= "?client_id=96c9b292c5503211b68cf4ab53f6e2f4b6d0defb";

			//* state: Optional state to include in the redirect URI.
			$link .= "&state=" . admin_url( 'admin-ajax.php' );

			//* redirect_uri: The page to which a user is redirected upon successfully authenticating.
			$link .= "&redirect_uri=https://warfareplugins.com/bitly_oauth.php";
		}

		$this->button_text   = $text;
		$this->button_class  = $class;
		$this->button_target = $target;
		$this->button_link   = $link;
	}


	/**
	 * The Bitly Link Shortener Method
	 *
	 * This is the function used to manage shortened links via the Bitly link
	 * shortening service.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @since  3.4.0 | 16 OCT 2018 | Modified order of conditionals, docblocked.
	 * @since  4.0.0 | 17 JUL 2019 | Migrated into this standalone Bitly class.
	 * @param  array $array An array of arguments and information.
	 * @return array $array The modified array.
	 *
	 */
	public function shorten_link( $array ) {


		/**
		 * Pull together the information that we'll need to generate bitly links.
		 *
		 */
		global $post;
		$post_id           = $array['post_id'];
		$google_analytics  = SWP_Utility::get_option('google_analytics');
		$access_token      = SWP_Utility::get_option( 'bitly_access_token' );
		$start_date        = SWP_Utility::get_option( 'link_shortening_start_date' );
		$cached_bitly_link = $this->fetch_local_link( $post_id, $array['network'] );


		/**
		 * Bail if link shortening is turned off.
		 *
		 */
		if( false == SWP_Utility::get_option( 'link_shortening_toggle' ) ) {
			$this->record_exit_status( 'link_shortening_toggle' );
			return $array;
		}


		/**
		 * Bail if Bitly is not the selected Link shortener.
		 *
		 */
		if( $this->key !== SWP_Utility::get_option( 'link_shortening_service' ) ) {
			$this->record_exit_status( 'link_shortening_service' );
			return $array;
		}


		/**
		 * Bail if we don't have a valid Bitly token.
		 *
		 */
		if ( false == $access_token ) {
			$this->record_exit_status( 'access_token' );
			return $array;
		}


		/**
		 * Bitly links can now be turned on or off at the post_type level on the
		 * options page. So if the bitly links are turned off for our current
		 * post type, let's bail and return the unmodified array.
		 * @todo Update this option in the DB to be more generic. Ensure current
		 *       setting migrates into the new one.
		 *
		 */
		$post_type_toggle = SWP_Utility::get_option( 'short_link_toggle_' . $post->post_type );
		if ( false === $post_type_toggle ) {
			$this->record_exit_status( 'short_link_toggle_' . $post->post_type );
			return $array;
		}


		/**
		 * If the chache is fresh and we have a valid bitly link stored in the
		 * database, then let's use our cached link.
		 *
		 * If the cache is fresh and we don't have a valid bitly link, we just
		 * return the unmodified array.
		 *
		 */
		if ( true == $array['fresh_cache'] ) {
			$this->record_exit_status( 'fresh_cache' );
			if( false !== $cached_bitly_link ) {
				$array['url'] = $cached_bitly_link;
			}
			return $array;
		}


		/**
		 * We don't want bitly links generated for the total shares buttons
		 * (since they don't have any links at all), and Pinterest doesn't allow
		 * shortlinks on their network.
		 *
		 */
		if ( $array['network'] == 'total_shares' || $array['network'] == 'pinterest' ) {
			return $array;
		}


		/**
		 * Users can select a date prior to which articles will not get short
		 * links. This is to prevent the case where some users get their quotas
		 * filled up as soon as the option is turned on because it is generating
		 * links for older articles. So this conditional checks the publish date
		 * of an article and ensures that the article is eligible for links.
		 *
		 */
		if ( $start_date ) {

			// Bail if we don't have a valid post object or post_date.
			if ( !is_object( $post ) || empty( $post->post_date ) ) {
				return $array;
			}

			$start_date = DateTime::createFromFormat( 'Y-m-d', $start_date );
			$post_date  = new DateTime( $post->post_date );

			//* The post is older than the minimum publication date.
			if ( $start_date > $post_date ) {
				$this->record_exit_status( 'publication_date' );
				return $array;
			}
		}


		/**
		 * If all checks have passed, let's generate a new bitly URL. If an
		 * existing link exists for the link passed to the API, it won't generate
		 * a new one, but will instead return the existing one.
		 *
		 */
		$network       = $array['network'];
		$url           = urldecode( $array['url'] );
		$new_bitly_url = SWP_Link_Manager::make_bitly_url( $url, $access_token );


		/**
		 * If a link was successfully created, let's store it in the database,
		 * let's store it in the url indice of the array, and then let's wrap up.
		 *
		 */
		if ( $new_bitly_url ) {
			$meta_key = 'bitly_link';

			if ( $google_analytics ) {
				$meta_key .= "_$network";
			}

			delete_post_meta( $post_id, $meta_key );
			update_post_meta( $post_id, $meta_key, $new_bitly_url );
			$array['url'] = $new_bitly_url;
		}

		return $array;
	}


	/**
	 * Fetch the bitly link that is cached in the local database.
	 *
	 * When the cache is fresh, we just pull the existing bitly link from the
	 * database rather than making an API call on every single page load.
	 *
	 * @since  3.3.2 | 12 SEP 2018 | Created
	 * @since  3.4.0 | 16 OCT 2018 | Refactored, Simplified, Docblocked.
	 * @param  int $post_id The post ID
	 * @param  string $network The key for the current social network
	 * @return mixed           string: The short url; false on failure.
	 *
	 */
	public static function fetch_local_link( $post_id, $network ) {


		/**
		 * Fetch the local bitly link. We'll use this one if Google Analytics is
		 * not enabled. Otherwise we'll switch it out below.
		 *
		 */
		$short_url = get_post_meta( $post_id, 'bitly_link', true );


		/**
		 * If Google analytics are enabled, we'll need to fetch a different
		 * shortlink for each social network. If they are disabled, we just use
		 * the same shortlink for all of them.
		 *
		 */
		if ( true == SWP_Utility::get_option('google_analytics') ) {
			$short_url = get_post_meta( $post_id, 'bitly_link_' . $network, true);
		}


		/**
		 * We need to make sure that the $short_url returned from get_post_meta()
		 * is not false or an empty string. If so, we'll return false.
		 *
		 */
		if ( !empty( $short_url ) ) {
			return $short_url;
		}

		return false;
	}


	/**
	 * Create a new Bitly short URL
	 *
	 * This is the method used to interface with the Bitly API with regard to creating
	 * new shortened URL's via their service.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @since  4.0.0 | 17 JUL 2019 | Migrated into this standalone Bitly class.
	 * @param  string $url          The URL to be shortened
	 * @param  string $network      The social network on which this URL is being shared.
	 * @param  string $access_token The user's Bitly access token.
	 * @return string               The shortened URL.
	 *
	 */
	public static function make_bitly_url( $url, $access_token ) {


		/**
		 * First we need to compile the link that we'll use to contact the
		 * Bitly API.
		 *
		 */
		$api_request_url = 'https://api-ssl.bitly.com/v3/shorten';
		$api_request_url .= "?access_token=$access_token";
		$api_request_url .= "&longUrl=" . urlencode( $url );
		$api_request_url .= "&format=json";


		/**
		 * Fetch a response from the Bitly API and then parse it from JSON into
		 * an array that we can use.
		 *
		 */
		$response = SWP_CURL::file_get_contents_curl( $api_request_url );
		$result   = json_decode( $response , true );

		//* The user no longer uses Bitly for link shortening.
		if ( isset( $result['status_txt'] ) && 'INVALID_ARG_ACCESS_TOKEN' == $result['status_txt'] )   {
			SWP_Utility::delete_option( 'bitly_access_token' );
			SWP_Utility::delete_option( 'bitly_access_login' );

			//* Turn Bitly link shortening off for the user.
			SWP_Utility::update_option( 'bitly_authentication', false );
		}


		/**
		 * If we have a valid link, we'll use that. If not, we'll return false.
		 *
		 */
		if ( isset( $result['data']['url'] ) ) {
			return $result['data']['url'];
		}

		return false;
	}


	/**
	 * The Bitly OAuth Callback Function
	 *
	 * When authenticating Bitly to the plugin, Bitly uses a back-and-forth handshake
	 * system. This function will intercept the ping from Bitly's server, process the
	 * information and provide a response to Bitly.
	 *
	 * @since  3.0.0 | 04 APR 2018 | Created
	 * @since  4.0.0 | 17 JUL 2019 | Migrated into this standalone Bitly class.
	 * @param  void
	 * @return void A response is echoed to the screen for Bitly to read.
	 * @access public
	 *
	 */
	public function bitly_oauth_callback() {


		/**
		 * If no access token or bitly login username is provided, then we're
		 * just going to store them in the database as empty strings.
		 *
		 */
		$access_token = '';
		$login        = '';


		/**
		 * If the callback contained a valid access token and login, then we'll
		 * use those and store them in the options field of the database.
		 *
		 */
		if( isset( $_GET['access_token'] ) && isset( $_GET['login'] ) ) {
			$access_token = $_GET['access_token'];
			$login        = $_GET['login'];
		}


		/**
		 * Update our options field in the database with our new values.
		 *
		 */
		SWP_Utility::update_option( 'bitly_access_token', $access_token );
		SWP_Utility::update_option( 'bitly_access_login', $login);


		/**
		 * We have to echo out the link to the settings page so that the file
		 * on our server that handles the handshake can initiate a nice clean
		 * redirect and put the user back in their own admin dashboard on our
		 * options page.
		 *
		 */
		echo admin_url( 'admin.php?page=social-warfare' );
	}
}
