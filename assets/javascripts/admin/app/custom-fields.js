MONKEY( 'CustomFields', function(CustomFields, utils, $) {

	CustomFields.init = function() {
		this.list = $( '#the-list' );

		this.addEventListener();
	};

	CustomFields.addEventListener = function() {
		MONKEY.vars.body.on( 'click', '#add-custom-field', this._onClickAddCustomField.bind( this ) );
	};

	CustomFields._onClickAddCustomField = function(event) {
		event.preventDefault();
		this.duplicateRow();
	};

	CustomFields.duplicateRow = function() {
		var clone   = $( '#the-list tr:last' ).clone( true );
		var oldName = clone.find( 'input[name*="field_name"]' ).val();
		var name    = this.getFieldName();

		clone.find( '[name*="' + oldName + '"]' ).map(function() {
			this.name = this.name.replace( new RegExp( oldName ), name );
		});

		clone.find( 'strong' ).html( name );
		clone.find( 'input[name*="field_name"]' ).val( name );
		clone.find( 'input:text' ).val( '' );
		clone.find( 'input:checkbox' ).prop( 'checked', false );
		clone.find( '.column-description' ).html( 'Campo customizado.' );

		this.list.prepend( clone );

		clone.find( 'input:text:first' ).focus();
	};

	CustomFields.getFieldName = function() {
		var customFields = this.list.find( '[value^="customField"]' );
		return 'customField' + ( customFields.length + 1 );
	};

}, {} );