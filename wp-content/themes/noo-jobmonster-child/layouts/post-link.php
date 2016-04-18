<?php $link = noo_get_post_meta( get_the_id(), '_noo_wp_post_link',  '' ); ?>

<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
<?php 
$bg_url= '';
if(has_post_thumbnail()){
	// $bg_url = wp_get_attachment_url(get_post_thumbnail_id());
	$bg_url = wp_get_attachment_image_src( get_post_thumbnail_id(), 'noo-full');
	$bg_url = $bg_url[0];
} else {
	$bg_url = NOO_ASSETS_URI.'/images/default-bg.png';
}
?>
	<div class="content-featured" <?php if(!empty($bg_url)):?> style="background-image:url(<?php echo esc_url($bg_url)?>)"<?php endif;?>>
		<?php noo_featured_default(); ?>
		<?php if(is_sticky()): ?>
			<span class="sticky_post"><i class="fa fa-thumb-tack"></i></span>
		<?php endif;?>
		<header class="content-header">
			<?php if ( is_singular() ) : ?>
			<h1 class="content-title">
				<?php the_title(); ?>				
			</h1>
			<?php else : ?>
			<h2 class="content-title">
				<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permanent link to: "%s"', 'noo' ), the_title_attribute( 'echo=0' ) ) ); ?>"><?php the_title(); ?></a>
			</h2>
			<?php endif; ?>
			<?php if($link != '') : ?>
			<span class="content-sub-title content-link">
				<a href="<?php echo esc_url($link); ?>" title="<?php echo esc_attr( sprintf( __( 'Shared link from post: "%s"', 'noo' ), the_title_attribute( 'echo=0' ) ) ); ?>" target="_blank">
				<i class="fa fa-link"></i>
				<?php echo esc_url($link); ?>
				</a>
			</span>
			<?php endif; ?>
			<?php //noo_content_meta(); ?>
		</header>
	</div>
	<div class="content-wrap">
		
		<?php if ( is_singular() ) : ?>
			<div class="content">
				<?php the_content(); ?>
				<?php wp_link_pages(); ?>
			</div>
			<?php if(noo_get_option('noo_blog_post_show_post_tag', true) && has_tag()) : ?>
				<div class="entry-tags">
				<?php the_tags(sprintf('<span>%s</span>',__('<i class="fa fa-tag"></i>','noo')),'')?>
				</div>
			<?php endif;?>
		<?php endif; ?>
	</div>
	<?php noo_get_layout('post', 'footer'); ?>
</article> <!-- /#post- -->