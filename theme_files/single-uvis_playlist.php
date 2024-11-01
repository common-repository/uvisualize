<?php
/**
 * UVisualize!
 * The template for displaying a playlist and all of its visualizations
 */

get_header(); ?>

<div id="main">
  <div id="content">

			<?php while ( have_posts() ) : the_post(); ?>

				<?php get_template_part( 'content', get_post_format() ); ?>

        <div class="uvis-the-visualizations">
             <?php if( function_exists("uvis_the_visualizations") ) uvis_the_visualizations( $post->ID ); ?>
        </div>

        <div class="uvis-the-playlist-items">
             <?php if( function_exists("uvis_the_playlist_items") ) uvis_the_playlist_items(); ?>
        </div>

				<?php comments_template( '', true ); ?>

			<?php endwhile; // end of the loop. ?>

		</div><!-- /#content -->
	</div><!-- /#main -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>