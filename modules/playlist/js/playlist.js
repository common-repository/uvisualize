/* Playlist function */

/* Displays a message to the user after receiving an AJAX response */
function UVNotify(msg) {

    var notify = jQuery("#uvis-notification");

    notify.html(msg);
    notify.show();
    notify.fadeOut(2000);

}

function initUVPlaylist($) {

      var UVPlaylist = { Views:{} },
          vispl  = window.UVisPlaylist;

      /**
       * Model
       */
      UVPlaylist.Model = Backbone.Model.extend({
        defaults : {
        },
        url : ajaxurl + '?action=uvis_save_playlist',
        toJSON : function(){
          var attrs = _.clone(this.attributes);
          attrs.post_id = vispl.post_id;
          return attrs;
        },
        initialize : function(){
        }
      });

      /**
       * Collection
       */
      UVPlaylist.Collection = Backbone.Collection.extend({
        model : UVPlaylist.Model,
        comparator: function(model) {
          return model.get('ordinal');
        },
        initialize : function() {
        }
      });

      /**
       * All Items
       */
      UVPlaylist.Views.Inputs = Backbone.View.extend({
        initialize : function () {
          this.collection.each(this.addInput, this);
          this.render(); // Render to update new order in hidden inputs
        },

        addInput : function(model, index){
          var input = new UVPlaylist.Views.Input({ model:model });
          this.$el.append(input.render().el);
        },

        events: {
          'update-sort': 'updateSort'
        },

        // Is called after an item was dragged
	      updateSort: function(event, model, position) {

	         this.collection.remove(model);

	         // Set the new sort order
	         this.collection.each(function (model, index) {
	             var ordinal = index;
	             if (index >= position)
	                 ordinal += 1;

	             model.set('ordinal', ordinal);
	         });

	         model.set('ordinal', position);
		       this.collection.add(model, {at: position});

	         this.render();
	      },

	      render: function() {

            $('#playlist-order').html('');

            // Get ids in order
            var ids = this.collection.pluck('playlistItemID');

            // Write order to hidden input
            $(ids).each(function(i, id) {
              $('#playlist-order').append('<input type="hidden" name="playlist_items[' + i + ']" value="' + id + '" />');
            });
	      }
      });

      /**
       * Single Item
       */
      UVPlaylist.Views.Input = Backbone.View.extend({
        tagName : 'li',
        className: 'playlist-item-handle',

        // Get the template from the DOM
        template :_.template($(vispl.inputTempl).html()),

        // When a model is saved, return the button to the disabled state
        initialize : function () {
          var _this = this;
          this.model.view = this;
          collection = this.model.collection;
        },

        // Attach events
        events : {
          'drop' : 'drop',
          'click .remove-item': 'removeItem'
        },

        drop: function(event, index) {

          this.$el.trigger('update-sort', [this.model, index]);

          var playlist_order = [];
          var playlist_id = parseInt(this.$el.parent("ul").attr("playlist_id"));

          if( isNaN(playlist_id) )
            playlist_id = parseInt($("#uvis-playlist-items").attr("playlist_id"));

          // Get the new item's order
          $('#playlist-order input[type="hidden"]').each( function(i) {
            playlist_order[i] = parseInt($(this).val());
          });

          // ...and save it
          $.post(
              ajaxurl,
              {
                  'action': 'uvis_save_playlist',
                  'playlist_order': playlist_order,
                  'playlist_id':  playlist_id
              },
              function(response) {
                var response = ( response !== "" )? "Sequence updated." : "Error updating sequence";
                UVNotify(response);
              }
          );


        },

        removeItem: function(event, index) {

          if (confirm("Remove item from " + uvis_playlist_post_type_name_singular + "?")) {
              this.model.destroy({
                  success: function(model, response) {
                    $(model.view.el).remove();

                    $('#playlist-order').html('');

                    // Get ids in order
                    var ids = this.collection.pluck('playlistItemID');

                    // Write order to hidden input
                    $(ids).each(function(i, id) {
                      $('#playlist-order').append('<input type="hidden" name="playlist_items[' + i + ']" value="' + id + '" />');
                    });

                    $(model.view).trigger('drop', $(model.view.el.index));

                  },
                  error: function(model, response) {
                     console.log(response);
                  }
              });
          }
        },


        // Render the single input - include an index.
        render : function () {
          this.model.set('index', this.model.collection.indexOf(this.model)+1);
          this.model.set('ordinal', this.model.collection.indexOf(this.model)+1);
          this.$el.html(this.template(this.model.toJSON()));
          return this;
        }
      });

      /**
       * Init
       */

      var playlistItems  = new UVPlaylist.Collection(vispl.playlistItems);
      var inputs     = new UVPlaylist.Views.Inputs({collection:playlistItems, el:vispl.outputTempl});

      // Make items sortable
	    $(vispl.outputTempl).sortable({
	      axis: "y",
	      placeholder: "sort-placeholder",
	      // Consider using update instead of stop
	      stop: function(event, ui) {
	        ui.item.trigger('drop', ui.item.index());
	      }
	    }).disableSelection();

}


/**
 *
 * Manage playlists in frontend
 *
 */

(function($) {

  /* Load existing playlists into menu when clicking on dropdown */
  $('[data-toggle="dropdown"]').live("click", function() {

    var item_id = parseInt($(this).attr("post_id"));

    /* TO DO: EITHER USE LOCAL STORAGE OR PREVENT AJAX REQUEST TO BE CALLED EVERYTIME OTHERWISE! */

    $("li.uvis-add-to-playlist-after").html('<img src="' + uvis_url + '/modules/playlist/images/loading.gif" class="uvis-loading" alt="Loading..." title="Loading..." />');

    $.post(
        ajaxurl,
        {
            'action': 'uvis_get_playlists',
            'item_id': item_id
        },
        function(response){
          $("li.uvis-add-to-playlist-after").html(response);
        }
    );
  });


  /**
   * Add item to playlist
   */

  $('a.uvis-add-to-playlist').live("click", function() {

    var item_id = parseInt($(this).attr("post_id"));
    var playlist_id = parseInt($(this).attr("playlist_id"));

    $.post(
        ajaxurl,
        {
            'action': 'uvis_add_to_playlist',
            'playlist_id': playlist_id, // The playlist the item will be added to
            'item_id': item_id // The item to add
        },
        function(response){
            UVNotify(response);
        }
    );

  });


  /**
   * Create Playlist Dialog
   */

  $('.uvis-create-playlist').live("click", function() {

    var item_id = parseInt($(this).attr("post_id"));

    $( "#uvis-dialog-create-playlist" ).dialog({
      modal: false,
      closeText: '',
      buttons: [
              {
                  text: "Create",
                  "class": 'btn btn-sm btn-success',
                  click: function() {

					          $.post(
					              ajaxurl,
					              {
					                  'action': 'uvis_add_playlist',
					                  'title': $('#uvis-playlist-title').val().toString()
					              },
					              function(response){
					                  UVNotify(response);
					              }
					          );

					          $(this).dialog( "close" );

					        },
					    },
					    {
                  text: "Cancel",
                  "class": 'button btn btn-sm btn-default',
                  click: function() {
                    $(this).dialog( "close" );
                  }
              }
      ]
    });

  });


  /**
   * Manage a playlist
   */

  $('.uvis-btn-manage-playlist').live("click", function() {

    var playlist_id = parseInt($(this).attr("playlist_id"));

    $("#uvis-manage-items").html('<img src="' + uvis_url + '/modules/playlist/images/loading.gif" class="uvis-loading-center" alt="Loading..." title="Loading..." />');

    // Load the playlist into the dialog window
    $.post(
        ajaxurl,
        {
            'action': 'uvis_get_playlist',
            'playlist_id': playlist_id
        },
        function(response){
            $("#uvis-manage-items").empty().append(response);
        }
    );

    // Display the dialog
    $( "#uvis-dialog-manage-playlist" ).dialog({
      position: 'center',
      closeText: '',
      resizable: true,
      height:500,
      width:700,
      modal: false,
			buttons: [
			        {
			            text: "Save",
			            "class": 'btn btn-sm btn-success',
			            click: function() {
					          var self = $(this);
					          var playlist_order = [];

					          // Get the new item's order
					          $('#playlist-order input[type="hidden"]').each( function(i) {
					            playlist_order[i] = parseInt($(this).val());
					          });

					          $("#uvis-manage-items").html('<img src="' + uvis_url + '/modules/playlist/images/loading.gif" class="uvis-loading-center" alt="Loading..." title="Loading..." />');

					          // ...and save it
					          $.post(
					              ajaxurl,
					              {
					                  'action': 'uvis_save_playlist',
					                  'playlist_order': playlist_order,
					                  'playlist_id':  playlist_id
					              },
					              function(response) {
                              UVNotify(response);
					                    self.dialog("close")
					              }
					          );
			            }
			        },
			        {
			            text: "Delete",
			            "class": 'btn btn-sm btn-danger',
			            click: function() {

					          var self = $(this);

					          /* Delete confirmation dialog */
					          $( "#uvis-dialog-delete-playlist" ).dialog({
					            resizable: false,
                      closeText: '',
					            modal: false,
										  buttons: [
										              {
										                  text: "Delete",
										                  "class": 'btn btn-sm btn-danger',
										                  click: function() {

							                          var that = $(this);

							                          /* Post a delete request */
							                          $.post(
							                              ajaxurl,
							                              {
							                                  'action': 'uvis_delete_playlist',
							                                  'playlist_id': playlist_id
							                              },
							                              function(response){
							                                  UVNotify(response);
							                                  that.dialog( "close" );
							                                  self.dialog( "close" );
							                              }
							                          );

                                      }
                                  },
                                  {
                                      text: "Cancel",
                                      "class": 'btn btn-sm btn-default',
                                      click: function() {
                                          $(this).dialog( "close" );
                                      }
                                  }
                               ]
					          });

			            }
			        },
              {
                  text: "Cancel",
                  "class": 'btn btn-sm btn-default',
                  click: function() {
                    $(this).dialog( "close" );
                  }
              }
			    ]
			});

  });

})(jQuery);

jQuery(document).ready(function(){

    jQuery('#uvis-playlist-items').sortable({
      axis: "y",
      placeholder: "sort-placeholder",
      // consider using update instead of stop?
      stop: function(event, ui) {
        ui.item.trigger('drop', ui.item.index());
      }
    }).disableSelection();

});