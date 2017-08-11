MONKEY.ComponentWrapper( 'FollowizeForm', function(FollowizeForm, utils, $) {

	FollowizeForm.fn.init = function() {
		this.addEventListener();
	};

	FollowizeForm.fn.addEventListener = function() {
		MONKEY.vars.main.on( 'ready', $.proxy( this.validateForm, this ) );
	};

	FollowizeForm.fn.validateForm = function() {
		if ( !$.fn.validate ) {
			return;
		}

		this.$el.validate({
			rules: {
				registrationNumber: {
					cpfBR: true
				},
				companyRegistrationNumber: {
					cnpjBR: true
				}
			}
		});
	};

} );