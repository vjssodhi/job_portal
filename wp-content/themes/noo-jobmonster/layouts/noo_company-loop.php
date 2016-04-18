<?php if ( $style == 'slider' ) : ?>
	<?php $id = noo_vc_elements_id_increment(); ?>
	<div class="wpb_wrapper">
		<div class="noo-text-block">
			<?php if( !empty($title) ) : ?>
				<h3 style="text-align: center;">
					<strong>
						<?php echo $title; ?>
					</strong>
				</h3>
			<?php endif; ?>
			<p style="text-align: center;">
				<?php echo $featured_content; ?>
			</p>
		</div>
	</div>
	<?php noo_caroufredsel_slider( $wp_query ) ?>
<?php else : ?>
	<?php if( !empty($title) ) : ?>
		<div class="form-title">
			<h3><?php echo ($title); ?></h3>
		</div>
	<?php endif; ?>
	<div class="company-letters">
		<?php foreach ( range( 'A', 'Z' ) as $letter ) {
			echo '<a href="#' . $letter . '">' . $letter . '</a>';
		} ?>
	</div>
	<?php
		if($wp_query->have_posts()):
			$current_letter = '';
			$letter_range = range( __('A', 'noo' ), __('Z', 'noo' ) );
			wp_enqueue_script('vendor-isotope');
	?>
		<div class="masonry">
			<ul class="companies-overview masonry-container ">
				<?php while ( $wp_query->have_posts() ) : $wp_query->the_post(); global $post; ?>
					<?php
						$company_name		= $post->post_title;

						if( !$company_name ) continue;

						$company_letter		= strtoupper(substr($company_name, 0, 1));
						$count				= Noo_Company::count_jobs( $post->ID );
						
						if( !Noo_Company::get_setting('show_no_jobs', 1) && $count <= 0 ) continue;

						if( $company_letter != $current_letter ) {
							if( $current_letter != '' ) {
								echo '</ul>';
								echo '</li>';
							}
							$current_letter = $company_letter;

							echo '<li class="company-group masonry-item"><div id="' . $current_letter . '" class="company-letter text-primary">' . $current_letter . '</div>';
							echo '<ul>';
						}

						echo '<li class="company-name"><a href="' . get_permalink() . '">' . esc_attr( $company_name ) . ' (' . $count . ')</a></li>';
					?>
				<?php endwhile; ?>
					</ul>
				</li>
			</ul>
		</div>
	<?php endif; ?>
<?php endif; ?>