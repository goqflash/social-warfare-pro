( function ( $, wp, swpmb ) {
	'use strict';

	var views = swpmb.views = swpmb.views || {},
		MediaField = views.MediaField,
		FileUploadField, UploadButton;

	FileUploadField = views.FileUploadField = MediaField.extend( {
		createAddButton: function () {
			this.addButton = new UploadButton( {controller: this.controller} );
		}
	} );

	UploadButton = views.UploadButton = Backbone.View.extend( {
		className: 'swpmb-upload-area',
		tagName: 'div',
		template: wp.template( 'swpmb-upload-area' ),
		render: function () {
			this.$el.html( this.template( {} ) );
			return this;
		},

		initialize: function ( options ) {
			this.controller = options.controller;
			this.el.id = _.uniqueId( 'swpmb-upload-area-' );
			this.render();

			// Auto hide if you reach the max number of media
			this.listenTo( this.controller, 'change:full', function () {
				this.$el.toggle( ! this.controller.get( 'full' ) );
			} );
		},

		// Initializes plupload using code from wp.Uploader (wp-includes/js/plupload/wp-plupload.js)
		initUploader: function ( $this ) {
			var self = this,
				extensions = this.getExtensions().join( ',' ),
				maxFileSize = this.controller.get( 'maxFileSize' ),
				options = {
					container: this.el,
					dropzone: this.el,
					browser: this.$( '.swpmb-browse-button' ),
					params: {
						post_id : $( '#post_ID' ).val()
					},
					added: function( attachment ) {
						self.controller.get( 'items' ).add( [attachment] );
					}
				};

			// Initialize the plupload instance.
			this.uploader = new wp.Uploader( options );

			var filters = this.uploader.uploader.getOption( 'filters' );
			if ( maxFileSize ) {
				filters.max_file_size = maxFileSize;
			}
			if ( extensions ) {
				filters.mime_types = [{title: i18nRwmbMedia.select, extensions: extensions}];
			}
			this.uploader.uploader.setOption( 'filters', filters );
			$this.data( 'uploader', this.uploader );
		},

		getExtensions: function () {
			var mimeTypes = this.controller.get( 'mimeType' ).split( ',' ),
				exts = [];

			_.each( mimeTypes, function ( current, index ) {
				if ( i18nRwmbMedia.extensions[current] ) {
					exts = exts.concat( i18nRwmbMedia.extensions[current] );
				}
			} );
			return exts;
		}
	} );

	function initFileUpload() {
		var $this = $( this ),
			view = $this.data( 'view' );

		if ( view ) {
			return;
		}
		view = new FileUploadField( { input: this } );

		$this.siblings( '.swpmb-media-view' ).remove();
		$this.after( view.el );

		// Init uploader after view is inserted to make wp.Uploader works.
		view.addButton.initUploader( $this );

		$this.data( 'view', view );
	}

	function init( e ) {
		$( e.target ).find( '.swpmb-file_upload' ).each( initFileUpload );
	}

	swpmb.$document
		.on( 'mb_ready', init )
		.on( 'clone', '.swpmb-file_upload', initFileUpload )
} )( jQuery, wp, swpmb );
