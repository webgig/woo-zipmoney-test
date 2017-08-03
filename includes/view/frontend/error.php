<?php
get_header();
?>
    <div id="primary" class="content-area">
        <main id="main" class="site-main" role="main">
            <section class="error-404 not-found">
                <header class="page-header">
                    <h1 class="page-title"><?php _e('An error occurred'); ?></h1>
                </header>

                <div class="page-content">
                    <h2><?php _e('This is somewhat embarrassing, isn&rsquo;t it?'); ?></h2>
                    <p><?php _e('It looks like an error occurred while processing your transaction. Please contact zipMoney for futher details.'); ?></p>
                </div><!-- .page-content -->
            </section><!-- .error-404 -->

        </main><!-- .site-main -->
    </div><!-- .content-area -->
<?php get_footer();?>