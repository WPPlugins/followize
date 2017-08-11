MONKEY( 'OrderFields', function(OrderFields, utils, $) {

	OrderFields.init = function(container) {
		this.container = container;

		this.addEventListener();
	};

	OrderFields.addEventListener = function() {
		MONKEY.vars.body.on( 'submit', this._onSubmitForm.bind( this ) );
	};

	OrderFields._onSubmitForm = function(event) {
		this.container
			.find( '[name*=field_order]' )
				.each( this.setIterateRows.bind( this ) )
		;
	};

	OrderFields.setIterateRows = function(index, element) {
		$( element ).each( $.proxy( this, 'setIterateItems', index ) );
	};


	OrderFields.setIterateItems = function(origin, index, element) {
		var value = element.value;

		if ( !~value.indexOf( '#' ) ) {
			return;
		}

		element.value = value.replace( '#', origin );
	};

}, {} );