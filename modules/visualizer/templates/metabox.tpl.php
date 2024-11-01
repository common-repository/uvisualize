<!-- Visualization Metabox -->
<script>
    window.uvisPLID = <?php echo $playlist_id; ?>;
</script>

<div class="uvisWrap" ng-controller="uvisAppController">
  <div class="uvisLoading">
    <div class="spinner"></div>
  </div>
  <div class="button uvisAdd" ng-cloak class="ng-cloak" ng-click="add(<?php echo $playlist_id; ?>)">{{ 'Create visualization'|translate }}</div><hr />
  <div class="uvisList" ng-model="uvisList" ng-cloak>
    <div class="uvisRow clearfix" ng-repeat="uvis in uvisList">
        <div class="button uvisGetJSON" ng-click="open(uvis.ID)" uvis-tooltip="{{ 'Click to edit'|translate }}">{{ uvis.post_title }}</div><br />
        <div class="postStatus">{{ 'Status'|translate }}: {{ uvis.post_status }}</div>
      <div class="button button-secondary delete" ng-click="delete(uvis.ID)" uvis-tooltip="{{ 'Delete visualization'|translate }}"><span class="dashicons dashicons-trash"></span></div>
      <div class="button button-secondary view" ng-click="copyShortcodeToClipboard(uvis.ID)" uvis-tooltip="{{ 'Copy shortcode to clipboard'|translate }}"><span class="dashicons dashicons-editor-code"></span></div>
      <div class="button button-secondary view" ng-click="copyPermalinkToClipboard(uvis.post_permalink, uvis.ID)" uvis-tooltip="{{ 'Copy permalink to clipboard'|translate }}"><span class="dashicons dashicons-admin-links"></span></div>
      <div class="button button-secondary view" ng-click="view(uvis.ID)" uvis-tooltip="{{ 'View visualization as visitor'|translate }}"><span class="dashicons dashicons-visibility"></span></div>
    </div>

  </div>
</div>
<!-- /Visualization Metabox -->
