;(function($){
	"use strict";
	$(document).ready(function(){
		
		$(document).on('click','.add-new-location-btn',function(e){
			e.stopPropagation();
			e.preventDefault();
			$('.add-new-location-content').toggle();

			return false;
		});
		
		$(document).on('click','.add-new-location-submit',function(e){
			e.stopPropagation();
			e.preventDefault();
			var _this = $(this);
			var _location =  _this.closest('.add-new-location-content').find('input').val();
			if($.trim(_location) !=''){
				$.post(nooMemberL10n.ajax_url,{
					action: 'add_new_job_location',
					location: _location,
					security: nooMemberL10n.ajax_security
				},function(res){
					if(res.success == true){
						_this.closest('.add-new-location-content').find('input').val('');
						var option = $('<option>');
						option.text(res.location_title).val(res.location_value);
						option.prop('selected', true).attr('selected', 'selected');
						_this.closest('.form-group').find('.form-control-chosen').append(option);
						_this.closest('.form-group').find('.form-control-chosen').trigger('liszt:updated');
						_this.closest('.form-group').find('.form-control-chosen').trigger('chosen:updated'); 
					}
				},'json');
			}
			return false;
		});
		
		
		$(document).on('click','.member-register-link',function(e){
			e.stopPropagation();
			e.preventDefault();
			$('.memberModalLogin').modal('hide');
			$('.memberModalRegister').modal('show');
			return false;
		});

		$(document).on('click','.member-login-link',function(e){
			e.stopPropagation();
			e.preventDefault();
			$('.memberModalLogin').modal('show');
			$('.memberModalRegister').modal('hide');
			return false;
		});
		
		$('form.noo-ajax-login-form').on('submit', function (e) {
			e.stopPropagation();
			e.preventDefault();
			var _this = $(this);
			_this.find('.noo-ajax-result').show().html(nooMemberL10n.loadingmessage);
			$.ajax({
                type: 'POST',
                dataType: 'json',
                url: nooMemberL10n.ajax_url,
                data: {
                    action: 'noo_ajax_login',
                    log: _this.find('.log').val(),
                    pwd: _this.find('.pwd').val(),
                    remember: (_this.find('.rememberme').is(':checked') ? true : false),
                    security: _this.find('.security').val(),
                    redirect_to: _this.find('.redirect_to').length ? _this.find('.redirect_to').val() : ''
                },
                success: function (data) {
                	if(typeof data =='object') {
                		_this.find('.noo-ajax-result').show().html(data.message);
                		if (data.loggedin == true) {
                			if( $('body').hasClass( 'interim-login' ) ) {
                				$('body').addClass( 'interim-login-success' );
                				$('.wp-auth-check-close', window.parent.document).click();
                			} else {
	                			if (data.redirecturl == null) {
	                				document.location.reload();
	                			}
	                			else {
	                				document.location.href = data.redirecturl;
	                			}
                			}
                		}
					} else {
						document.location.reload();
					}
                },
                complete: function () {

                },
                error: function () {
                	_this.off('submit');
                	_this.submit();
                }
			});

			return false;
		});
		
		$('.job-manage-action.action-delete').click(function(){
			return confirm(nooMemberL10n.confirm_delete);
		});
		$('form.noo-ajax-register-form').on('submit', function (e) {
			e.stopPropagation();
			e.preventDefault();
			var _this = $(this);
			if(_this.find(".account_reg_term").length > 0 && !_this.find(".account_reg_term").is(':checked')){
				_this.find('.noo-ajax-result').hide();
				alert(nooMemberL10n.confirm_not_agree_term);
				return false;
			}else{
				_this.find('.noo-ajax-result').show().html(nooMemberL10n.loadingmessage);
				var formData = _this.serializeArray();
				formData.push({name: "security_code", value: _this.find('.security_code').data('security-code')});
				$.ajax({
	                type: 'POST',
	                dataType: 'json',
	                url: nooMemberL10n.ajax_url,
	                data: formData,
	                success: function (data) {
		                if(typeof data =='object') {
	                		_this.find('.noo-ajax-result').show().html(data.message);
	                		if (data.success == true) {
	                			if (typeof data.redirecturl !== "undefined" && data.redirecturl != null) {
	                				document.location.href = data.redirecturl;
	                			} else {
	                				document.location.reload();
	                			}
	                		}
						} else {
							document.location.reload();
						}
	                },
	                complete: function () {
	
	                },
	                error: function () {
	                	_this.off('submit');
	                	_this.submit();
	                }
				});
			}

			return false;
		});
		
		// Init validate
		$('form#candidate_profile_form').validate({
			onkeyup: false,
			errorClass: "jform-error",
			validClass: "jform-valid",
			errorElement: "span",
			ignore: ":hidden:not(.ignore-valid)",
			errorPlacement: function(error, element) {
				if ( element.is( ':radio' ) || element.is( ':checkbox' ) || element.is( ':file' ) )
					error.appendTo( element.parent().parent() );
				else
					error.appendTo( element.parent());
			}
		});

		// Init validate
		$('form#noo-ajax-update-password').validate({
			onkeyup: false,
			errorClass: "jform-error",
			validClass: "jform-valid",
			errorElement: "span",
			ignore: ":hidden:not(.ignore-valid)",
			errorPlacement: function(error, element) {
				if ( element.is( ':radio' ) || element.is( ':checkbox' ) || element.is( ':file' ) )
					error.appendTo( element.parent().parent() );
				else
					error.appendTo( element.parent());
			},
			submitHandler: function(form) {
				var _form = $(form);
				_form.find('.noo-ajax-result').show().html(nooMemberL10n.loadingmessage);
				$.ajax({
	                type: 'POST',
	                dataType: 'json',
	                url: nooMemberL10n.ajax_url,
	                data: _form.serializeArray(),
	                success: function (data) {
	                	_form.find('.noo-ajax-result').show().html(data.message);
	                    if (data.success == true) {
	                        if (data.redirecturl == null) {
	                            document.location.reload();
	                        }
	                        else {
	                            document.location.href = data.redirecturl;
	                        }
	                    }
	                },
	                complete: function () {

	                },
	                error: function () {
	                	_form.off('submit');
	                	_form.submit();
	                }
				});
			}
		});

		$('form#noo-ajax-update-password').on('submit', function (e) {
			e.stopPropagation();
			e.preventDefault();
			$(this).validate();
		});
		
		// Init validate
		$('form#post_resume_form').validate({
			onkeyup: false,
			errorClass: "jform-error",
			validClass: "jform-valid",
			errorElement: "span",
			ignore: ":hidden:not(.ignore-valid)",
			errorPlacement: function(error, element) {
				if ( element.is( ':radio' ) || element.is( ':checkbox' ) || element.is( ':file' ) )
					error.appendTo( element.parent().parent() );
				else
					error.appendTo( element.parent());
			}
		});
		
		// Init validate
		$('form#add_job_alert_form').validate({
			onkeyup: false,
			errorClass: "jform-error",
			validClass: "jform-valid",
			errorElement: "span",
			ignore: ":hidden:not(.ignore-valid)",
			errorPlacement: function(error, element) {
				if ( element.is( ':radio' ) || element.is( ':checkbox' ) || element.is( ':file' ) )
					error.appendTo( element.parent().parent() );
				else
					error.appendTo( element.parent());
			}
		});

		$(".noo-clone-fields").on("click", function() {
			var $this = $(this);
			var $template = $( $this.data('template') );
			$this.closest('.noo-metabox-addable').find('.noo-addable-fields').append( $template );
			$template.find('.form-control-editor').wysihtml5({
				"font-styles": true,
				"blockquote": true,
				"emphasis": true,
				"lists": true,
				"html": true,
				"link": true,
				"image": true,
				"stylesheets": [wysihtml5L10n.stylesheet_rtl]
			});
			$template.find('input:first-child').focus();
			return false;
		});

		$(".noo-remove-fields").on("click", function(){
			var $lastFields = $(this).closest('.noo-metabox-addable').find('.noo-addable-fields .fields-group:last');
			if( $lastFields.length ) $lastFields.remove();
			return false;
		});
		
		
		$(document).on('click','.member-manage-action.approve-reject-action',function(e){
			e.preventDefault();
			e.stopPropagation();
			var $this = $(this);
			$this.closest('.member-manage-table').block({ message: null,overlayCSS:  { 
		        backgroundColor: '#fff', 
		        opacity:         0.5, 
		        cursor:          'wait' 
		    }}); 
			$.post(nooMemberL10n.ajax_url,{
				action: 'noo_approve_reject_application_modal',
				application_id: $this.data('application-id'),
				hander:  $this.data('hander'),
				security: nooMemberL10n.ajax_security
			},function(respon){
				$this.closest('.member-manage-table').unblock();
				if(respon){
					var $modal = $(respon);
					$('body').append($modal);
					$modal.modal('show');
					$('form#noo-ajax-approve-reject-application-form').validate({
						onkeyup: false,
						onfocusout: false,
						onclick: false,
						errorClass: "jform-error",
						validClass: "jform-valid",
						errorElement: "span",
						ignore: ":hidden:not(.ignore-valid)",
						errorPlacement: function(error, element) {
							if ( element.is( ':radio' ) || element.is( ':checkbox' ) || element.is( ':file' ) )
								error.appendTo( element.parent().parent() );
							else
								error.appendTo( element.parent());
						}
					});
					$modal.on('hidden.bs.modal',function(){
						$modal.remove();
					});
				}
			});

			return false;
		});

		$("a.bookmark-job").on("click", function(){
			var $this = $(this);
			var bookmarked = $this.hasClass('bookmarked');
			$.ajax({
                type: 'POST',
                dataType: 'json',
                url: nooMemberL10n.ajax_url,
                data: {
                	action: $this.attr('data-action'),
                	security: $this.attr('data-security'),
                	job_id: $this.attr('data-job-id')
                },
                success: function (data) {
                	$('.noo-ajax-result').show().html(data.message);
                    if (data.success == true) {
                    	if( bookmarked )
                    		$this.removeClass('bookmarked');
                    	else
                    		$this.addClass('bookmarked');

                    	$this.closest('.job-action').find('.noo-ajax-result').show().html(data.message);
                        if (data.redirecturl == null) {
                            // document.location.reload();
                        }
                        else {
                            document.location.href = data.redirecturl;
                        }
                    }
                },
                complete: function () {

                },
                error: function () {
                }
			});

			return false;
		});
		
		$(document).on('click','.member-manage-action.view-employer-message',function(e){
			e.preventDefault();
			e.stopPropagation();
			var $this = $(this);
			$this.closest('.member-manage-table').block({ message: null,overlayCSS:  { 
		        backgroundColor: '#fff', 
		        opacity:         0.5, 
		        cursor:          'wait' 
		    }}); 
			$.post(nooMemberL10n.ajax_url,{
				action: 'noo_employer_message_application_modal',
				application_id: $this.data('application-id'),
				security: nooMemberL10n.ajax_security,
				mode: $this.data('mode') || 0
			},function(respon){
				$this.closest('.member-manage-table').unblock();
				if(respon){
					var $modal = $(respon);
					$('body').append($modal);
					$modal.modal('show');
					$modal.on('hidden.bs.modal',function(){
						$modal.remove();
					});
				}
			});
		});
		$('.job-preview').find('[type="submit"]').addClass('disabled');
		// -- Check for submit jobs
		$(document).on('click','.job-preview',function(event){
			var $check = $('input[name=agreement]:checked');
			if ( $check.length == 1 )
				$(this).find('[type="submit"]').removeClass('disabled');
			else 
				$(this).find('[type="submit"]').addClass('disabled');
		});

		$(document).on('click', '.view_applications', function(event) {
			event.preventDefault();
			var application_id = $(this).data('application-id');
			var url = $(this).attr('href');
			$.post(nooMemberL10n.ajax_url, {
   				action: 'check_view_application',
    			application_id: application_id,
   			}, function(data, status, xhr) {
   				window.location.href = url;
   			});
		});

		// -- check candidate view
			// $(document).on('click', '.view-employer-message', function(e) {
			// 	event.preventDefault();
			// 	var application_id = $(this).data('application-id');
			// 	var url = $(this).attr('href');
			// 	$.post(nooMemberL10n.ajax_url, {
	  //  				action: 'check_view_application',
	  //   			application_id: application_id,
	  //  			}, function(data, status, xhr) {
	  //  				window.location.href = url;
	  //  			});
			// });



		// -- create order free package
		$(document).on('click', '.auto_create_order_free', function(event) {
			event.preventDefault();
			var user_id = $(this).data('id');
			var package_id = $(this).data('package');
			var security = $(this).data('security');
			var url_package = $(this).data('url-package');
			$.post(nooMemberL10n.ajax_url, {
   				action: 'auto_create_order',
    			user_id: user_id,
    			package_id: package_id,
    			security: security
   			}, function(data) {
   				window.location.href = url_package;
   			});
		});


	});
})(jQuery);
