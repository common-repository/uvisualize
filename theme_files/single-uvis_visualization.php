<?php
/**
 * UVisualize!
 * The template for displaying a single visualization
 */

get_header();

?>

<style>
#wpadminbar {
  display:none;
}
html {
  margin:0 !important;
  overflow-y:auto;
}
.uvisWrapInclude {
  left:0;
}
</style>

  <div id="main">
    <div id="content">

    <?php while ( have_posts() ) : the_post(); ?>

			<div ng-controller="uvisAppController" class="uvisWrap">
			  <div class="uvisHeader"><h1><?php the_title(); ?></h1> <?php uvis_sharebuttons( $post->ID ); ?></div>
			  <div class="uvisWrapInclude" ng-include="includeView(<?php echo $post->ID; ?>)"></div>
			</div>

      <h2><?php the_title(); ?></h2>
      <p><?php the_content(); ?></p>

    <?php endwhile; ?>

    </div> <!-- /#content -->
  </div> <!-- /#main -->

<?php get_footer(); ?>