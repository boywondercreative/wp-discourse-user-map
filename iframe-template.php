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
		html, body {
			margin: 0 !important;
			padding: 0 !important;
		}
		body {
			background: #ffffff !important;
		}
		.discourse-community-map {
			max-width: 690px;
		}
		/*.leaflet-control-fullscreen-button,*/
		/*.leaflet-control-attribution {*/
			/*display: none !important;*/
		/*}*/
	</style>
</head>

<body <?php body_class(); ?>>
<?php add_filter( 'show_admin_bar', '__return_false' ); ?>
<div id="page" class="site">
	<?php while ( have_posts() ) : the_post(); ?>

		<article id="post-<?php the_ID(); ?>" class="discourse-community-map">

			<?php the_content(); ?>

		</article><!-- #post-## -->

	<?php endwhile; ?>
</div><!-- .site -->

<?php wp_footer(); ?>
</body>
</html>
