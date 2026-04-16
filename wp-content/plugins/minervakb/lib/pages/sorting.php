<?php
/**
 * Project: Minerva KB
 * Copyright: 2015-2016 @KonstruktStudio
 */
/**
 * Terms hierarchy helper
 * Class MKBTermsTree
 */
class MinervaKB_TermsTree {

	private $taxonomy = null;

	private $terms = null;

	private $tree = null;

	public function __construct($settings) {
		$this->taxonomy = $settings['taxonomy'];

		$this->terms = get_terms($this->taxonomy, array(
			"hide_empty" => true
		));

		$this->tree = $this->build_tree($this->terms);
	}

	/**
	 * Gets tree
	 * @return null
	 */
	public function get_tree() {
		return $this->tree;
	}

	/**
	 * Builds terms tree
	 */
	private function build_tree(&$terms) {
		$tree = array(
			'0' => array(
				'term' => null
			)
		);

		while(!empty($terms)) {
			foreach($terms as $term) {
				if ($this->locate_in_tree($term, $tree, $terms)) {
					continue;
				}
			}
		}

		return $tree['0']['children'];
	}

	/**
	 * Places term in existing tree
	 * @param $term
	 * @param $tree
	 * @param $terms
	 *
	 * @return bool
	 */
	private function locate_in_tree($term, &$tree, &$terms) {
		$is_found = false;

		foreach($tree as $id => $tree_item) {
			if ($term->parent == $id) {
				$is_found = true;

				if (!isset($tree[$id]['children'])) {
					$tree[$id]['children'] = array();
				}

				$this->remove_term_by_id($term->term_id, $terms);

				$tree[$id]['children'][$term->term_id] = array(
					'term' => $term
				);

				break;
			} else {
				if (isset($tree_item['children'])) {
					if ($this->locate_in_tree($term, $tree[$id]['children'], $terms)) {
						$is_found = true;
						break;
					}
				}
			}
		}

		return $is_found;
	}

	/**
	 * Removes term given its id
	 * @param $id
	 * @param $terms
	 */
	private function remove_term_by_id($id, &$terms) {
		$found = null;

		foreach($terms as $index => $term) {
			if ($term->term_id == $id) {
				$found = $index;
				break;
			}
		}

		unset($terms[$found]);
	}

	/**
	 * Renders available terms tree
	 * @param $tree
	 * @param $path
	 */
	public function render_tree($tree, $path = '') {

		if (!sizeof($tree)) {
			?>
			<p><?php esc_html_e('You have no content yet.', 'minerva-kb'); ?></p>
			<?php

			return;
		}

		?>
		<ul>
			<?php foreach($tree as $branch):
				$term = $branch["term"];
				$children = isset($branch["children"]) ? $branch["children"] : array();
				$branch_path = ($path ? $path . '/' : '') . $term->name;
				?>
				<li><?php

					$this->render_tree_item($term, $path);

					if (!empty($children)):
						$this->render_tree($children, $branch_path);
					endif;

					?>
				</li>
			<?php endforeach; ?>
		</ul>
	<?php
	}

	/**
	 * Renders single term
	 * @param $term
	 */
	protected function render_tree_item($term, $path) {
		?>
		<span data-id="<?php echo esc_attr($term->term_id); ?>"
		      data-count="<?php echo esc_attr($term->count); ?>"
		      data-path="<?php echo esc_attr($path); ?>">
			<i class="fa fa-folder"></i>
			<?php echo esc_html($term->name . ( $term->count ? ' (' . $term->count . ')' : '' )); ?>
		</span>
	<?php
	}
}
/**
 * Custom tree walker
 * Class SortingTermsTree
 */
class MinervaKB_SortingTermsTree extends MinervaKB_TermsTree {
	/**
	 * Renders single term
	 * @param $term
	 */
	protected function render_tree_item($term, $path) {
		?>
		<span class="mkb-sorting-tree-item fn-mkb-sorting-tree-item"
			  data-id="<?php echo esc_attr($term->term_id); ?>">
			<i class="fa fa-list-alt"></i>
			<?php echo esc_html($term->name); ?>
			<div class="mkb-term-posts fn-mkb-posts-wrap"
			     data-term-id="<?php echo esc_attr($term->term_id); ?>">
				<?php
				$query_args = array(
					'post_type' => MKB_Options::option('article_cpt'),
					'posts_per_page' => -1,
					'ignore_sticky_posts' => 1,
					'orderby' => 'menu_order',
					'order' => 'ASC',
					'tax_query' => array(
						array(
							'taxonomy' => MKB_Options::option('article_cpt_category'),
							'field' => 'slug',
							'terms' => $term->slug,
							'include_children' => false
						),
					)
				);

				$loop = new WP_Query($query_args);

				if ( $loop->have_posts() ) :
					while ( $loop->have_posts() ) : $loop->the_post();
						?>
					<div class="mkb-sorting-tree-post fn-mkb-sorting-tree-post"
						       data-id="<?php esc_attr_e(get_the_ID()); ?>">
						<i class="fa fa-book"></i>
						<?php the_title(); ?>
					</div>
					<?php
					endwhile;
				endif;

				wp_reset_postdata();
				?>
			</div>
		</span>
	<?php
	}
}

/**
 * Class SortingPage
 * Sorting page controller
 */
class MinervaKB_SortingPage implements KST_SubmenuPage_Interface {

	private $info;

	private $ajax;

	const SCREEN_BASE = 'kb_page_kb-sorting';

	/**
	 * Constructor
	 * @param $deps
	 */
	public function __construct($deps) {

		$this->setup_dependencies( $deps );

		add_action( 'admin_menu', array( $this, 'add_submenu_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'load_assets' ) );
	}

	/**
	 * Sets up dependencies
	 * @param $deps
	 */
	private function setup_dependencies($deps) {
		if (isset($deps['info'])) {
			$this->info = $deps['info'];
		}

		if (isset($deps['ajax'])) {
			$this->ajax = $deps['ajax'];
		}
	}

	/**
	 * Adds menu entry
	 */
	public function add_submenu_page() {
		add_submenu_page(
			'edit.php?post_type=' . MKB_Options::option('article_cpt'),
			__( 'Sorting', 'minerva-kb' ),
			__( 'Sorting', 'minerva-kb' ),
			'manage_options',
			'kb-sorting',
			array( $this, 'submenu_html' )
		);
	}

	/**
	 * Page HTML
	 */
	public function submenu_html() {
		?>
		<div class="mkb-admin-page-header">
			<span class="mkb-header-logo mkb-header-item" data-version="v<?php echo esc_attr(MINERVA_KB_VERSION); ?>">
				<img class="logo-img" src="<?php echo esc_attr(MINERVA_KB_IMG_URL . 'logo.png'); ?>" title="logo" />
			</span>
			<span class="mkb-header-title mkb-header-item"><?php echo __( 'Sorting', 'minerva-kb' ); ?></span>
			<a href="#" id="mkb-plugin-sorting-save" class="mkb-action-button mkb-action-default mkb-header-item"
			   title="<?php esc_attr_e('Save Order', 'minerva-kb'); ?>"><?php echo __( 'Save Order', 'minerva-kb' ); ?></a>
		</div>

		<form class="mkb-plugin-page-wrap mkb-loading mkb-sorting-form js-mkb-sorting-form" novalidate
			data-taxonomy="<?php esc_attr_e(MKB_Options::option('article_cpt_category')); ?>">
			<div class="mkb-plugin-page-preloader">
				<div class="mkb-loader">
					<span class="inner1"></span>
					<span class="inner2"></span>
					<span class="inner3"></span>
				</div>
			</div>
			<div class="mkb-plugin-page-content">

				<div class="mkb-sorting-content fn-mkb-sorting-container">
					<h3><?php esc_html_e('Knowledge Base Sorting', 'minerva-kb'); ?></h3>

					<p><?php esc_html_e('Drag n drop items within each category to reorder them. Press Save Order when done.', 'minerva-kb'); ?></p>
					<p><?php esc_html_e('Note: this only works when custom order is enabled in settings.', 'minerva-kb'); ?></p>

					<div>
						<?php
						$terms_helper = new MinervaKB_SortingTermsTree(array(
							'taxonomy' => MKB_Options::option('article_cpt_category')
						));

						$tree = $terms_helper->get_tree();

						?>
						<div class="mkb-sorting-tree fn-mkb-sorting-tree">
							<?php
							$terms_helper->render_tree($tree);
							?>
						</div>

					</div>
				</div>

			</div>
		</form>
	<?php
	}

	/**
	 * Loads admin assets
	 */
	public function load_assets() {

		$screen = get_current_screen();

		if ( $screen->base !== self::SCREEN_BASE ) {
			return;
		}

		wp_enqueue_script( 'jquery-ui-sortable' );

		// toastr
		wp_enqueue_style( 'minerva-kb/admin-toastr', MINERVA_KB_PLUGIN_URL . 'assets/css/vendor/toastr/toastr.min.css', false, '2.1.3' );
		wp_enqueue_script( 'minerva-kb/admin-toastr-js', MINERVA_KB_PLUGIN_URL . 'assets/js/vendor/toastr/toastr.min.js', array(), '2.1.3', true );

		wp_enqueue_script( 'minerva-kb/admin-sorting-js', MINERVA_KB_PLUGIN_URL . 'assets/js/minerva-kb-sorting.js', array(
			'jquery',
			'minerva-kb/admin-ui-js',
			'minerva-kb/admin-toastr-js'
		), MINERVA_KB_VERSION, true );
	}
}
