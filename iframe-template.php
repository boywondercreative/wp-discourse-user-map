<?php
/**
 * Template Name: Iframe Template
 */
?>

<!DOCTYPE html >
<html <?php language_attributes(); ?> class="no-js">
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
	<style>
		body {
			background: #ffffff !important;
		}
		.entry-content {
			margin: 0 !important;
		}
	</style>
</head>

<body <?php body_class(); ?>>
<?php add_filter( 'show_admin_bar', '__return_false' ); ?>
<div id="page" class="site">
	<div class="site-inner">
		<div id="content" class="site-content">

			<div id="primary" class="content-area">
				<main id="main" class="site-main" role="main">
					<?php
					// Start the loop.
					while ( have_posts() ) : the_post();

						// Include the page content template.
						get_template_part( 'template-parts/content', 'page' );

						// If comments are open or we have at least one comment, load up the comment template.
						if ( comments_open() || get_comments_number() ) {
							comments_template();
						}

						// End of the loop.
					endwhile;
					?>
				</main><!-- .site-main -->
			</div><!-- .content-area -->
		</div><!-- .site-content -->

		<footer id="colophon" class="site-footer" role="contentinfo">

		</footer><!-- .site-footer -->
	</div><!-- .site-inner -->
</div><!-- .site -->

<?php wp_footer(); ?>
</body>
</html>
