<div class="wrap">
    <h2>
        <!--<a href="<?php echo  esc_url(admin_url('admin.php?page=swpm-form-builder&action=add')); ?>" class="add-new-h2">
            <?php echo  esc_html(__('Add New', 'swpm-form-builder')) ?>
        </a>-->
        <?php if ($searched): ?>
            <span class="subtitle">
                <?php echo  sprintf(__('Search results for "%s"', 'swpm-form-builder'), $_REQUEST['s']); ?>
            </span>
        <?php endif; ?>
    </h2>
    <div id="swpm-form-list">
        <!--<div id="swpm-sidebar">
            <div id="swpm-upgrade-column">
                <div class="swpm-pro-upgrade">
                    <h2><a href="http://swpmpro.com">Visual Form Builder</a></h2>
                </div>
            </div>
        </div>-->
        <div id="swpm-sidebar"></div>
        <div id="swpm-main" class="swpm-order-type-list">
            <form id="forms-filter" method="post" action="">
                <?php
                $forms_list->views();
                $forms_list->prepare_items();

                $forms_list->search_box('search', 'search_id');
                $forms_list->display();
                ?>
            </form>
        </div> <!-- #swpm-main -->
    </div> <!-- #swpm-form-list -->