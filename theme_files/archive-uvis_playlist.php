<?php

/**
 * UVisualize!
 * The template for displaying all published playlists
 */

get_header(); ?>

<section id="primary" class="content-area">
  <main id="main" class="site-main" role="main">

<?php
if (have_posts()) :
?>
    <header class="page-header">
      <h1><?php echo get_option( "uvis_playlist_post_type_name_plural"); ?></h1>
    </header>
<?php
  while (have_posts()) : the_post();
?>

		<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>

		  <?php
		    // Post thumbnail
		    twentyfifteen_post_thumbnail();
		  ?>

		  <header class="entry-header">
		    <?php
		      if ( is_single() ) :
		        the_title( '<h1 class="entry-title">', '</h1>' );
		      else :
		        the_title( sprintf( '<h2 class="entry-title"><a href="%s" rel="bookmark">', esc_url( get_permalink() ) ), '</a></h2>' );
		      endif;
		    ?>
		  </header><!-- .entry-header -->

		  <div class="entry-content">
		    <?php
		      /* translators: %s: Name of current post */
		      the_content( sprintf(
		        __( 'Continue reading %s', 'twentyfifteen' ),
		        the_title( '<span class="screen-reader-text">', '</span>', false )
		      ) );

		      wp_link_pages( array(
		        'before'      => '<div class="page-links"><span class="page-links-title">' . __( 'Pages:', 'twentyfifteen' ) . '</span>',
		        'after'       => '</div>',
		        'link_before' => '<span>',
		        'link_after'  => '</span>',
		        'pagelink'    => '<span class="screen-reader-text">' . __( 'Page', 'twentyfifteen' ) . ' </span>%',
		        'separator'   => '<span class="screen-reader-text">, </span>',
		      ) );
		    ?>

		      <div class="uvis-playlist-number-items"><?php echo count( get_post_meta( $post->ID, "uvis_playlist_items", true ) ); ?> item(s) &middot; <?php echo count( get_posts( "post_type=uvis_visualization&post_parent=" . $post->ID ) ); ?> visualization(s) </div>

		  </div><!-- .entry-content -->

		  <?php
		    // Author bio.
		    if ( is_single() && get_the_author_meta( 'description' ) ) :
		      get_template_part( 'author-bio' );
		    endif;
		  ?>

		  <footer class="entry-footer">
		    <?php twentyfifteen_entry_meta(); ?>
		    <?php edit_post_link( __( 'Edit', 'twentyfifteen' ), '<span class="edit-link">', '</span>' ); ?>
		  </footer><!-- .entry-footer -->

		</article><!-- #post-## -->



<?php
  endwhile;
endif;
?>

			<div class="nav-previous alignleft"><?php next_posts_link( 'Older playlists' ); ?></div>
			<div class="nav-next alignright"><?php previous_posts_link( 'Newer playlists' ); ?></div>

    </main><!-- .site-main -->
  </section><!-- .content-area -->

<?php get_footer(); ?>