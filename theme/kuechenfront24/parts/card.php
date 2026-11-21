<article <?php post_class( 'post-card' ); ?>>
	<a class="pic" href="<?php the_permalink(); ?>" tabindex="-1" aria-hidden="true">
		<?php if ( has_post_thumbnail() ) {
			the_post_thumbnail( 'kf24-card', [ 'loading' => 'lazy', 'alt' => '' ] );
		} else {
			echo '<span data-scene="metod" data-mat="eiche-wild" style="display:block;width:100%;height:100%"></span>';
		} ?>
	</a>
	<div class="tx">
		<span class="small"><?php echo esc_html( get_the_date() ); ?></span>
		<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
		<p class="small" style="margin:0"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 24 ) ); ?></p>
		<a href="<?php the_permalink(); ?>" style="margin-top:auto;font-weight:600">Weiterlesen</a>
	</div>
</article>
