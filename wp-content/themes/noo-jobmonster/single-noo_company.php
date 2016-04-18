<?php get_header(); ?>
<div class="container-boxed max offset main-content">
	<div class="row">
		<div class="noo-main noo-company" role="main">
			<div class="col-md-8">
				<div class="job-listing" data-agent-id="<?php the_ID()?>">
				<?php 
					$paged = (get_query_var('paged')) ? get_query_var('paged') : 1;
					$employer = Noo_Company::get_employer_id( get_the_ID() );
					$args = array(
							'paged'=> $paged,
							'post_type'=>'noo_job',
							'author__in' => array( $employer ),
							'post_status' => 'publish'
					);

					$r = new WP_Query($args);
					Noo_Job::loop_display(array(
						'query'=>$r,
						'title'=>sprintf(__('%s has posted %s jobs', 'noo'), get_the_title(), '<span class="text-primary">'.$r->found_posts.'</span>'),
						'no_content' => 'none'
					));
					?>
				</div>
			</div>
			<div class="col-md-4">
				<?php Noo_Company::display_sidebar(get_the_ID());
					wp_reset_postdata();
					// wp_reset_query();
				?>
			</div>
			
		</div>		
	</div> <!-- /.row -->
</div> <!-- /.container-boxed.max.offset -->
<?php get_footer(); ?>