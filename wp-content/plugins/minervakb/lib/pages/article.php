<?php
/**
 * Project: MinervaKB.
 * Copyright: 2015-2017 @KonstruktStudio
 */

class MinervaKB_ArticleEdit implements KST_EditScreen_Interface {

	private $restrict;

	/**
	 * Constructor
	 */
	public function __construct($deps) {
		$this->setup_dependencies($deps);

		add_action( 'add_meta_boxes', array($this, 'add_meta_boxes') );
		add_action( 'save_post', array($this, 'save_post') );
	}

	/**
	 * Sets up dependencies
	 * @param $deps
	 */
	private function setup_dependencies($deps) {
		if (isset($deps['restrict'])) {
			$this->restrict = $deps['restrict'];
		}
	}

	/**
	 * Register article meta box(es).
	 */
	public function add_meta_boxes() {

		// feedback meta box
		add_meta_box(
			'mkb-article-meta-related-id',
			__( 'Related articles', 'minerva-kb' ),
			array($this, 'related_html'),
			MKB_Options::option( 'article_cpt' ),
			'normal',
			'high'
		);

		// feedback meta box
		add_meta_box(
			'mkb-article-meta-feedback-id',
			__( 'Article feedback', 'minerva-kb' ),
			array($this, 'feedback_html'),
			MKB_Options::option( 'article_cpt' ),
			'normal',
			'high'
		);

		// restrict meta box
		if (MKB_Options::option('restrict_on')) {
			add_meta_box(
				'mkb-article-meta-restrict-id',
				__( 'MinervaKB: Restrict access', 'minerva-kb' ),
				array($this, 'restrict_html'),
				MKB_Options::option( 'article_cpt' ),
				'normal',
				'high'
			);
		}

		// reset meta box
		add_meta_box(
			'mkb-article-meta-reset-id',
			__( 'MinervaKB: Reset article stats', 'minerva-kb' ),
			array($this, 'reset_html'),
			MKB_Options::option( 'article_cpt' ),
			'side',
			'default'
		);
	}

	/**
	 * Restrict access settings
	 * @param $post
	 */
	public function restrict_html( $post ) {

		$settings_helper = new MKB_SettingsBuilder(array(
			'post' => true,
			'no_tabs' => true
		));

		$options = array(
			array(
				'id' => 'mkb_article_access_role',
				'type' => 'roles_select',
				'label' => __( 'Restrict access', 'minerva-kb' ),
				'default' => 'none',
				'description' => __('You can restrict access for specific articles or for whole topics.', 'minerva-kb')
			),
		);

		foreach ( $options as $option ):

			$value = '';

			switch ($option["id"]) {
				case 'mkb_article_access_role':
					$value = stripslashes(get_post_meta(get_the_ID(), '_mkb_restrict_role', true));
					$value = $value ? $value : "none";
					break;

				default:
					break;
			}

			$settings_helper->render_option(
				$option["type"],
				$value,
				$option
			);

		endforeach;

	}

	/**
	 * Reset stats settings
	 * @param $post
	 */
	public function reset_html( $post ) {

		$options = array(
			array(
				'id' => 'views',
				'type' => 'checkbox',
				'label' => __( 'Reset views?', 'minerva-kb' ),
				'default' => false
			),
			array(
				'id' => 'likes',
				'type' => 'checkbox',
				'label' => __( 'Reset likes?', 'minerva-kb' ),
				'default' => false
			),
			array(
				'id' => 'dislikes',
				'type' => 'checkbox',
				'label' => __( 'Reset dislikes?', 'minerva-kb' ),
				'default' => false
			)
		);
		$settings_helper = new MKB_SettingsBuilder(array("no_tabs" => true));

		?>
		<div class="mkb-clearfix">
			<div class="mkb-settings-content fn-mkb-article-reset-form mkb-article-reset-form">
				<form action="" novalidate>
					<?php
					foreach ($options as $option):
						$settings_helper->render_option(
							$option["type"],
							$option["default"],
							$option
						);
					endforeach;
					?>
					<a href="#" class="mkb-action-button mkb-action-danger fn-mkb-article-reset-stats-btn"
					   data-id="<?php esc_attr_e(get_the_ID()); ?>"
					   title="<?php esc_attr_e('Reset data', 'minerva-kb'); ?>"><?php echo __('Reset data', 'minerva-kb'); ?></a>
				</form>
			</div>
		</div>
	<?php

	}

	/**
	 * Article feedback list
	 * @param $post
	 */
	public function feedback_html( $post ) {
		$feedback_args = array(
			'posts_per_page'   => - 1,
			'offset'           => 0,
			'category'         => '',
			'category_name'    => '',
			'orderby'          => 'DATE',
			'order'            => 'DESC',
			'include'          => '',
			'exclude'          => '',
			'meta_key'         => 'feedback_article_id',
			'meta_value'       => get_the_ID(),
			'post_type'        => 'mkb_feedback',
			'post_mime_type'   => '',
			'post_parent'      => '',
			'author'           => '',
			'author_name'      => '',
			'post_status'      => 'publish'
		);

		$feedback = get_posts( $feedback_args );

		if ( sizeof( $feedback ) ):
			foreach ( $feedback as $item ):
				?>
				<div class="mkb-article-feedback-item">
					<div class="mkb-article-feedback-item-inner">
						<a href="#"
						   data-id="<?php echo esc_attr( $item->ID ); ?>"
						   class="fn-remove-feedback mkb-article-feedback-item-remove"
						   title="<?php esc_attr_e( 'Remove this entry?', 'minerva-kb' ); ?>">
							<i class="fa fa-close"></i>
						</a>
						<h4><?php echo esc_html( __('Submitted:', 'minerva-kb' ) ); ?> <?php echo esc_html( $item->post_date ); ?></h4>

						<p><?php echo esc_html( $item->post_content ); ?></p>
					</div>
				</div>
			<?php
			endforeach;
		else:
			?>
			<p><?php echo esc_html( __( 'No feedback was submitted for this article', 'minerva-kb' ) ); ?></p>
		<?php
		endif;
	}

	/**
	 * Related articles meta boxes html
	 * @param $post
	 */
	function related_html( $post ) {

		$related = get_post_meta(get_the_ID(), '_mkb_related_articles', true);

		?>
		<?php wp_nonce_field( 'mkb_save_article', 'mkb_save_article_nonce' ); ?>
		<div class="mkb-related-articles fn-related-articles">
			<?php
			if ($related && is_array($related) && !empty($related)):

				$query_args = array(
					'post_type' => MKB_Options::option( 'article_cpt' ),
					'post__not_in' => array( get_the_ID() ),
					'posts_per_page' => -1
				);

				$articles_loop = new WP_Query( $query_args );

				$articles_list = array();

				if ( $articles_loop->have_posts() ) :
					while ( $articles_loop->have_posts() ) : $articles_loop->the_post();
						array_push( $articles_list, array(
							"title"  => get_the_title(),
							"id"   => get_the_ID()
						) );
					endwhile;
				endif;
				wp_reset_postdata();

				foreach($related as $article_id):
					?>
					<div class="mkb-related-articles__item">
						<select class="mkb-related-articles__select" name="mkb_related_articles[]">
							<?php foreach($articles_list as $article): ?>
								<option value="<?php echo esc_attr($article["id"]); ?>"<?php if ($article["id"] == $article_id) {
									echo ' selected="selected"';
								}?>><?php echo esc_html($article["title"]); ?></option>
							<?php endforeach; ?>
						</select>
						<a class="mkb-related-articles__item-remove fn-related-remove mkb-unstyled-link" href="#">
							<i class="fa fa-close"></i>
						</a>
					</div>
				<?php
				endforeach;
			else:
				?>
				<div class="fn-no-related-message mkb-no-related-message">
					<p><?php echo esc_html( __('No related articles selected', 'minerva-kb' ) ); ?></p>
				</div>
			<?php
			endif;
			?>
		</div>
		<div class="mkb-related-actions">
			<a href="#"
			   id="mkb_add_related_article"
			   data-id="<?php echo esc_attr(get_the_ID()); ?>"
			   class="button button-primary button-large"
			   title="<?php esc_attr_e('Add related article', 'minerva-kb'); ?>">
				<?php _e('Add related article', 'minerva-kb'); ?>
			</a>
		</div>
	<?php
	}

	/**
	 * Saves article meta box fields
	 * @param $post_id
	 * @return mixed|void
	 */
	function save_post( $post_id ) {
		/**
		 * Verify user is indeed user
		 */
		if (
			! isset( $_POST['mkb_save_article_nonce'] )
			|| ! wp_verify_nonce( $_POST['mkb_save_article_nonce'], 'mkb_save_article' )
		) {
			return;
		}

		$post_type = get_post_type($post_id);

		if ($post_type !== MKB_Options::option( 'article_cpt' )) {
			return;
		}

		// TODO: normalize all these maybe
		update_post_meta(
			$post_id,
			'_mkb_related_articles',
			isset($_POST['mkb_related_articles']) ?
				$_POST['mkb_related_articles'] :
				array()
		);

		// restrict access
		if (MKB_Options::option('restrict_on')) {
			update_post_meta(
				$post_id,
				'_mkb_restrict_role',
				isset($_POST['mkb_article_access_role']) ?
					$_POST['mkb_article_access_role'] :
					'none'
			);
		}

		$this->restrict->invalidate_restriction_cache();
	}
}
