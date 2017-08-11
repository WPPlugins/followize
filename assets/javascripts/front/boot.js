jQuery(function() {
	var context = jQuery( 'body' )
	  , main    = jQuery( document )
	  , win     = jQuery( window )
	  , route   = context.data( 'route' )
	;

	MONKEY.vars = {
		body  : context,
		route : route,
		main  : main,
		win   : win
	};

	//set route in application
	MONKEY.dispatcher( MONKEY.Application, route, [context] );
});