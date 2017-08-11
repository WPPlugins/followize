MONKEY( 'Application', function(Application, utils, $) {

	Application['followize_page_followize-page-form'] = function(container) {
		if ( ! $.fn.sortable ) {
			return;
		}

		$( '#the-list' ).sortable();

		MONKEY.OrderFields.init( container );
		MONKEY.CustomFields.init();
	};

}, {} );