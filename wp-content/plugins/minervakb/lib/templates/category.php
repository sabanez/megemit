<?php
/**
 * Project: Minerva KB
 * Copyright: 2015-2016 @KonstruktStudio
 */

if (!MKB_Options::option('no_topic_header')):
	get_header();
endif;

do_action('minerva_category_root_before');

?><div class="<?php echo esc_attr(MKB_TemplateHelper::root_class('topic')); ?>"><?php

	MKB_TemplateHelper::maybe_render_left_sidebar( 'topic' );

	?><div class="<?php echo esc_attr(MKB_TemplateHelper::content_class('topic')); ?>"><?php
			
		do_action('minerva_category_title_before');

		?><div class="mkb-page-header"><?php

			do_action('minerva_category_title_inside_before');

			if (MKB_Options::option('topic_customize_title')) {
				?><h1 class="mkb-page-title"><?php
					single_term_title(MKB_Options::option('topic_custom_title_prefix'));
				?></h1><?php
			} else {
				the_archive_title( '<h1 class="mkb-page-title">', '</h1>' );
			}

			the_archive_description( '<div class="mkb-taxonomy-description">', '</div>' );

			do_action('minerva_category_title_inside_after');

		?></div><?php

		do_action('minerva_category_title_after');

		do_action('minerva_category_loop_before');

		while ( have_posts() ) : the_post();
			include( MINERVA_KB_PLUGIN_DIR . 'lib/templates/content.php' );
		endwhile;

		do_action('minerva_category_loop_after');

		?></div><!--.mkb-content-main--><?php

	MKB_TemplateHelper::maybe_render_right_sidebar( 'topic' );

	?></div><!--.mkb-container--><?php

do_action('minerva_category_root_after');

if (!MKB_Options::option('no_topic_footer')):
	get_footer();
endif;

?>