<?php
/**
 * Template Name: 404
 */

get_header(); ?>

<div class="page-title child-theme-404 page-title-default title-size-small color-scheme-dark title-design-default" style="">
    <div class="container">
        <header class="entry-header">
            <h1 class="entry-title"><?php echo get_the_title('8363'); ?></h1>
            <div class="breadcrumbs" xmlns:v="http://rdf.data-vocabulary.org/#"><a href="https://www.megemit.org/" rel="v:url" property="v:title">Hauptseite</a> » <span class="current"><?php echo get_the_title('8363'); ?></span></div><!-- .breadcrumbs -->
        </header><!-- .entry-header -->
    </div>
</div>

<div class="container">
    <div class="row">

        <div class="site-content col-sm-9" role="main">

            <div class="page-wrapper">
                <div class="entry-content">
                    <?php 
                    $id=8363; 
                    $post = get_post($id); 
                    $content = apply_filters('the_content', $post->post_content); 
                    echo $content;  
                    ?>
                </div>
            </div><!-- .page-wrapper -->

        </div><!-- .site-content -->

        <?php get_sidebar(); ?>

    </div>
</div>

<?php get_footer(); ?>