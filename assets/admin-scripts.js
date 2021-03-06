jQuery( document ).ready( function() {

	jQuery( '.icspresso-log-table td.expand' ).click( function() {

		var td = jQuery( this );

		if ( td.hasClass( 'expanded' ) ) {

			td.removeClass( 'expanded' ).html( '+' ).closest( 'tr' ).removeClass( 'expanded' );
		} else {
			td.addClass( 'expanded').html( '-' ).closest( 'tr' ).addClass( 'expanded' );
		}

	} );

	jQuery( '.icspresso-reindex-submit' ).click( function( e ) {

		e.preventDefault();

		var self = jQuery( this );

		IcspressoIndexTypeManager.reindex( self.attr( 'data-type-name' ) );
	} );

	jQuery( '.icspresso-resync-submit' ).click( function( e ) {

		e.preventDefault();

		var self = jQuery( this );

		IcspressoIndexTypeManager.resync( self.attr( 'data-type-name' ) );
	} );

	jQuery( '.icspresso-status' ).each( function() {

		var name = jQuery( this ).attr( 'data-type-name' );

		IcspressoIndexTypeManager.updateStatus( name );

	} );

} );

IcspressoIndexTypeManager = new function() {

	var self = this;

	self.getNonce = function() {

		return jQuery( '#icspresso_settings' ).val();
	};

	self.reindex = function( type, callback ) {

		jQuery.post( ajaxurl, { action: 'icspresso_init_index', type_name: type, nonce: self.getNonce() }, function( data ) {

		} ).done( function() {

			var recurse = function( data ) {

				if ( ! data.is_doing_full_index) {
					return;
				}

				setTimeout( function() {
					self.updateStatus( type, function( data ) { recurse( data ) } );
				}, 5000 )
			};

			self.updateStatus( type, function( data ) {
				recurse( data );
			} )

		} );

	};

	self.resync = function( type ) {

		jQuery.post( ajaxurl, { action: 'icspresso_resync_index', type_name: type, nonce: self.getNonce() }, function( data ) {

		} ).done( function() {

			var recurse = function( data ) {

				if ( ! data.is_doing_full_index ) {
					return;
				}

				setTimeout( function() {
					self.updateStatus( type, function( data ) { recurse( data ) } );
				}, 5000 )
			};

			self.updateStatus( type, function( data ) {
				recurse( data );
			} )

		} );
	};

	self.updateStatus = function( type, callback ) {

		var element = jQuery( '.icspresso-status-' + type );
		var messageElement = jQuery( '.icspresso-status-message-' + type );

		messageElement.html( 'Fetching...' );

		element.removeClass( 'status-warning' ).removeClass( 'status-error').removeClass( 'status-ok' ).addClass( 'status-warning' );

		self.getStatus( type, function( data ) {

			data = jQuery.parseJSON( data );

			var newClass, string = '';

			if ( data.error ) {

				string = data.error;
				newClass = 'status-error';

			} else if ( data.is_doing_full_index ) {

				string = 'Indexing ' + '(' + data.indexed_count + ' of ' + data.database_count + ' indexed)';
				newClass = 'status-warning';

			} else {

				string = 'Ready ' + '(' + data.indexed_count + ' of ' + data.database_count + ' indexed)';
				newClass = 'status-ok';
			}

			if ( ! data.error ) {

				var width = ( ( data.indexed_count / data.database_count ) * 100 );

				element.css( 'width', ( ( width <= 100 ) ? width : 100 ) + '%' );
			}

			messageElement.html( string );

			element.removeClass( 'status-warning' ).removeClass( 'status-error').removeClass( 'status-ok' ).addClass( newClass );

			if ( typeof( callback ) == 'function' ) {

				callback( data );
			}

		} );
	};

	self.getStatus = function( type, callback ) {

		jQuery.post( ajaxurl, { action: 'icspresso_get_type_status', type_name: type, nonce: self.getNonce() }, function( data ) {

			callback( data );

		} );
	};
}