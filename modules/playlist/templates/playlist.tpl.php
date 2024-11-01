<!-- Playlist Item -->
<?php

  global $pagenow;

?>

<h4 id="uvis-playlist-title"><?php echo $post->post_title; ?></h4>

<script type="text/template" id="inputTemplate">
<div class="playlist-item clearfix">
  <div class="playlist-item-title"><%= playlistItemTitle %></div>
  <div class="playlist-item-id">ID <%= playlistItemID %></div>
  <div class="playlist-item-post-date"><%= playlistItemPostDate %></div>
  <div class="playlist-item-options"><a href="<?php bloginfo( "url" ); ?>/wp-admin/post.php?post=<%= playlistItemID %>&action=edit" target="_blank"><div class="button"><span class="dashicons dashicons-edit" alt="Edit post" title="Edit post"></span></div></a> <div class="button"><span class="dashicons dashicons-trash remove-item" alt="Remove item" title="Remove item"></span></div></div>
</div>
</script>

<ul id="uvis-playlist-items" playlist_id="<?php echo $post->ID; ?>"></ul>

<div id="playlist-order"></div>

<script>
   window.UVisPlaylist = {};
   var vispl = window.UVisPlaylist;
   vispl.outputTempl = '#uvis-playlist-items';
   vispl.inputTempl = '#inputTemplate';
   vispl.playlistItems = <?php echo $playlistItems; ?>;
   vispl.post_id = <?php echo $post->ID ?>;

   (function($) {
     // Render the view
     initUVPlaylist($);
   })(jQuery);
</script>
<!-- /Playlist Item -->