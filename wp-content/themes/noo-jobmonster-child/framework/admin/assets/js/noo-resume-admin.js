
function toggle_disable_noo_resume_custom_field(el) {
	$this = jQuery(el);
	var disabledEl = $this.siblings('input[type="hidden"]');
	var parentEl = $this.closest('tr');
	if( disabledEl.val() == 'yes' ) {
		disabledEl.val('no');
		$this.prop('value', nooresumeL10n.disable_text);
		parentEl.removeClass('noo-disable-field');
	} else {
		disabledEl.val('yes');
		$this.prop('value', nooresumeL10n.enable_text);
		parentEl.addClass('noo-disable-field');
	}
	
}

function delete_noo_resume_custom_field(el){
	jQuery(el).closest('tr').remove();
	return false;
}

jQuery( document ).ready( function ( $ ) {
	// Clone Education, Experience and Skill
	$(".noo-clone-fields").on("click", function() {
		var $this = $(this);
		var $template = $( $this.data('template') );
		$template.find(".noo-remove-fields").on("click", remove );
		$this.parents('.noo-addable-fields').find('tbody').append( $template );
	});

	function remove() {
		$(this).parents('tr').remove();
	}

	$(".noo-remove-fields").on("click", remove);

	// Custom field for resume
	$(".noo_resume_custom_field_table").sortable({
			'items': 'tbody tr',
			'axis': 'y',
			 placeholder: "noo-resume-state-highlight"
		});
		
		$('#add_noo_resume_custom_field').click(function(){
			var table = $('.noo_resume_custom_field_table'),
				n = 0,
				num = table.data('num');
			
			n = num + 1;
			var tmpl = nooresumeL10n.custom_field_tmpl.replace( /__i__|%i%/g, n );
			table.append(tmpl);
			table.data('num',n);
			
			$(".noo_resume_custom_field_table").sortable({
				'items': 'tbody tr',
				'axis': 'y',
				 placeholder: "noo-resume-state-highlight"
			});
		});
} );

