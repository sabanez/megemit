<?php
/**
 * Project: MinervaKB.
 * Copyright: 2015-2017 @KonstruktStudio
 */

class MinervaKB_GuestPostShortcode extends KST_Shortcode implements KST_Shortcode_Interface {

	protected $ID = 'guestpost';
	protected $name = 'Guest Posting Form';
	protected $description = 'Allows guests to submit articles from the client side';
	protected $icon = 'fa fa-paper-plane-o';

	/**
	 * Renders shortcode
	 * @param $atts
	 * @param string $content
	 */
	public function render($atts, $content = '') {

		global $minerva_kb;

		?>
	<div class="js-mkb-client-submission mkb-client-submission">
		<div class="mkb-client-submission__heading"><?php esc_html_e(MKB_Options::option('submit_form_heading_label')); ?></div>
		<div class="mkb-client-submission__subheading"><?php esc_html_e(MKB_Options::option('submit_form_subheading_label')); ?></div>

		<?php

		do_action('minerva_guestpost_form_subheading_after');

		?>

		<?php

		if (MKB_Options::option('submit_disable')) {
			if (MKB_Options::option('submit_disable_message')) {
				?><p><?php esc_html_e(MKB_Options::option('submit_disable_message')); ?></p><?php
			}
		} else if (MKB_Options::option('submit_restrict_enable') &&
		           !$minerva_kb->restrict->check_current_user_against_option('submit_restrict_role')) {

			if (MKB_Options::option('submit_restriction_failed_message')) {
				?><p><?php esc_html_e(MKB_Options::option('submit_restriction_failed_message')); ?></p><?php
			}
		} else {

			// all is well, render form
			$this->render_form();
		}

?>
	</div>
	<?php
	}

	private function render_form() {
		?>
		<div class="mkb-form-messages js-mkb-form-messages mkb-hidden"></div>

		<form class="mkb-client-submission-form js-mkb-client-submission-form" action="" novalidate>
			<div class="mkb-submission-title-wrap">
				<div class="mkb-form-input-label">
					<?php esc_html_e(MKB_Options::option('submit_article_title_label')); ?>
				</div>
				<input type="text" name="mkb-submission-title" class="js-mkb-submission-title" />
			</div>
			<?php

			do_action('minerva_guestpost_form_title_after');

			?>
			<br/>
			<div class="mkb-submission-content-wrap">
				<div class="mkb-form-input-label">
					<?php esc_html_e(MKB_Options::option('submit_content_label')); ?>
				</div>
				<div id="mkb-client-editor">
					<p><?php esc_html_e(MKB_Options::option('submit_content_default_text')); ?></p>
				</div>
			</div>
			<?php

			do_action('minerva_guestpost_form_content_after');

			?>
			<br/>
			<?php if (MKB_Options::option('submit_allow_topics_select')):?>
				<div class="mkb-submission-topic-wrap">
					<div class="mkb-form-input-label">
						<?php esc_html_e(MKB_Options::option('submit_topic_select_label')); ?>
					</div>
					<?php

					$topics_args = array(
						'taxonomy'     => MKB_Options::option('article_cpt_category'),
						'orderby'      => 'name',
						'show_count'   => false,
						'pad_counts'   => false,
						'hierarchical' => true,
						'name'         => 'mkb-submission-topic',
						'class'        => 'js-mkb-submission-topic'
					);

					wp_dropdown_categories( $topics_args );
					?>
				</div>
				<?php

				do_action('minerva_guestpost_form_topics_after');

				?>
			<?php endif; ?>
			<br/>
			<?php if (MKB_Options::option('antispam_quiz_enable')): ?>
				<p><?php esc_html_e(MKB_Options::option('antispam_quiz_question')); ?> <input name="mkb-submission-answer" class="js-mkb-real-human-answer mkb-real-human-answer" type="text" /></p>
				<?php

				do_action('minerva_guestpost_form_antispam_after');

				?>
			<?php endif; ?>
			<div>
				<span class="js-mkb-client-submission-send mkb-client-submission-send">
					<?php esc_html_e(MKB_Options::option('submit_send_button_label')); ?>
				</span>
				<?php

				do_action('minerva_guestpost_form_submit_after');

				?>
			</div>
		</form>
<?php

		if (MKB_Options::option('submit_content_editor_skin') === 'snow') {
			wp_enqueue_style( 'minerva-kb/client-editor-snow-css', MINERVA_KB_PLUGIN_URL . 'assets/css/vendor/quill/quill.snow.css', false, '1.3.6' );
		} else {
			wp_enqueue_style( 'minerva-kb/client-editor-bubble-css', MINERVA_KB_PLUGIN_URL . 'assets/css/vendor/quill/quill.bubble.css', false, '1.3.6' );
		}

		wp_enqueue_script( 'minerva-kb/client-editor-js', MINERVA_KB_PLUGIN_URL . 'assets/js/vendor/quill/quill.min.js', array(), '1.3.6', true );
	}
}