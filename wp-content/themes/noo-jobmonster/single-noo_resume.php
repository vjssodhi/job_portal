<?php get_header(); ?>
<div class="container-boxed max offset main-content">
	<div class="row">
		<div class="<?php noo_main_class(); ?>" role="main">
				<?php Noo_Resume::display_detail()?>
			</div>
	</div> <!-- /.row -->
</div> <!-- /.container-boxed.max.offset -->
<?php get_footer(); ?>