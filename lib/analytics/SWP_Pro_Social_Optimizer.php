<?php

/**
 * The Social Optimizer class will do the same thing as the Gutenberg sidebar,
 * except this one will run the scoring on the server side allowing us to go
 * back through old posts and generate optimization scores for them. This will
 * also make it easier for us to store the grades in the database. We will then
 * use these numbers to make recommendations for the user to go back and optimize
 * their highest performing posts with the least amount of optimizations.
 *
 * @since 4.2.0 | 20 AUG 2020 | Created
 *
 */
class SWP_Pro_Social_Optimizer {


	/**
	 * The local $post_id property will obviously store the post ID of the post
	 * that is currently being graded. The entire class will shut down and bail
	 * out if a proper post ID is not provided.
	 *
	 * @var integer
	 *
	 */
	public $post_id = 0;


	/**
	 * The local $field_data property will contain all of the data for each of
	 * the fields that are being graded. This contains things like the maximum
	 * length for input fields, optimim length, minimum length, image ratios, etc.
	 *
	 * @var array
	 *
	 */
	public $field_data = array(

		// The Open Graph Image
		'swp_og_image' => array(
			'name'        => 'Open Graph Image',
			'type'        => 'image',
			'width'       => 1200,
			'height'      => 628,
			'min_width'   => 200,
			'min_height'  => 200,
			'numerator'   => '1.9',
			'denominator' => '1',
		),

		// The Open Graph Title
		'swp_og_title' => array(
			'name'       => 'Open Graph Title',
			'type'       => 'input',
			'length'     => 55,
			'max_length' => 95,
		),

		// The Open Graph Description
		'swp_og_description' => array(
			'name'       => 'Open Graph Description',
			'type'       => 'input',
			'length'     => 60,
			'max_length' => 200,
		),

		// The Twitter Card Title
		'swp_twitter_card_title' => array(
			'name'       => 'Twitter Card Title',
			'type'       => 'input',
			'length'     => 55,
			'max_length' => 95,
		),

		// The Twitter Card Description
		'swp_twitter_card_description' => array(
			'name'       => 'Twitter Card Description',
			'type'       => 'input',
			'length'     => 55,
			'max_length' => 150,
		),

		// The Twitter Card Image
		'swp_twitter_card_image' => array(
			'name'        => 'Twitter Card Image',
			'type'        => 'image',
			'width'       => 1200,
			'height'      => 628,
			'min_width'   => 200,
			'min_height'  => 200,
			'numerator'   => '1.9',
			'denominator' => '1',
		),

		// The Custom Tweet Field
		'swp_custom_tweet' => array(
			'name'       => 'Custom Tweet',
			'type'       => 'input',
			'length'     => 100,
			'max_length' => 240,
		),

		// The Pinterest Image Field
		'swp_pinterest_image' => array(
			'name'        => 'Pinterest Image',
			'type'        => 'image',
			'width'       => 735,
			'height'      => 1102,
			'min_width'   => 238,
			'min_height'  => 356,
			'numerator'   => '2',
			'denominator' => '3',
		),

		// The Pinterest Description Field
		'swp_pinterest_description' => array(
			'name'       => 'Pinterest Description',
			'type'       => 'input',
			'length'     => 500,
			'max_length' => 500,
		)
	);

	public function __construct( $post_id ) {
		if( is_admin() ) {
			return;
		}

		$this->post_id = $post_id;
		$this->establish_maximum_scores();
		var_dump($this);
	}

	public function update_score() {

	}

	private function cache_score() {

	}


	/**
	 * The get_field() method is a shortcut method for get_post_meta(). Since
	 * we'll be using the same post id and we'll always only want one field
	 * being returned, that makes the first and third parameters reduntant. This
	 * method eliminates that. Just name the field you want, and it will return it.
	 *
	 * @since  4.2.0 | 20 AUG 2020 | Created
	 * @see    https://developer.wordpress.org/reference/functions/get_post_meta/
	 * @param  string $name The name of the meta field you want.
	 * @return mixed  The value of the meta field from get_post_meta()
	 *
	 */
	private function get_field($name) {
		return get_post_meta( $this->post_id, $name, true );
	}


	/**
	 * The establish_maximum_scores() method will provide the baseline for how
	 * many points each field can be worth. These scores need to always add up
	 * to 100 points. As such, if the Twitter card fields are activated we will
	 * have 9 fields adding up to 100 versus 6 fields without them. So this
	 * allows us to check that field and assign those maximum values accordingly.
	 *
	 * @since  4.2.0 | 20 AUG 2020 | Created
	 * @param  void
	 * @return void
	 *
	 */
	private function establish_maximum_scores() {

		// If the Twitter fields don't exist, we have 6 fields.
		if( true == $this->get_field('swp_twitter_use_open_graph') ) {
			$max_grades = array(
				'swp_og_image' => 20,
				'swp_og_title' => 15,
				'swp_og_description' => 15,
				'swp_custom_tweet' => 15,
				'swp_pinterest_image' => 20,
				'swp_pinterest_description' => 15
			);

		// If they do exist, we have all 9 fields to grade.
		} else {
			$max_grades = array(
				'swp_og_image' => 15,
				'swp_og_title' => 10,
				'swp_og_description' => 10,
				'swp_custom_tweet' => 10,
				'swp_twitter_card_image' => 10,
				'swp_twitter_card_title' => 10,
				'swp_twitter_card_description' => 10,
				'swp_pinterest_image' => 15,
				'swp_pinterest_description' => 10
			);
		}

		// Loop through and add each one to our existing $field_data property.
		foreach( $max_grades as $key => $value ) {
			$this->field_data[$key]['max_grade'] = $value;
		}
	}
}
