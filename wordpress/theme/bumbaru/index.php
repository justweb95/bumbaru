<?php
/**
 * Rezervni šablon (blog i ostalo što nema svoj šablon).
 */
defined( 'ABSPATH' ) || exit;
get_header();
?>
<div class="wrap page-wrap">
	<?php bu_crumbs( array( array( 'label' => is_singular() ? get_the_title() : wp_strip_all_tags( get_the_archive_title() ) ) ) ); ?>
	<?php if ( have_posts() ) : ?>
		<?php while ( have_posts() ) : the_post(); ?>
			<article class="panel doc-panel" style="margin-bottom:24px">
				<h1 class="h2"><?php if ( ! is_singular() ) : ?><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a><?php else : the_title(); endif; ?></h1>
				<div class="page-content"><?php is_singular() ? the_content() : the_excerpt(); ?></div>
			</article>
		<?php endwhile; ?>
	<?php else : ?>
		<p class="lead">Nema sadržaja.</p>
	<?php endif; ?>
</div>
<?php
get_footer();
