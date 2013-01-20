# Post Archives by Taxonomy Terms

Create an archive page of all your posts organized and grouped by a term they are in.

## Usage

Use this shortcode where you wish to render the archive:

	[taxonomy_archive]

It accepts the following arguments:

	'post_type' = 'post'
	'taxonomy' = 'category'
	'template_items' = '<div class="term-posts term-%term_id%"><h2>%term_name%</h2> <ul class="posts-in-term">%items%</ul></div>',
	'template_item' = '<li><a href="%the_permalink%" title="%the_title_attribute%">%the_title%</a></li>',
	'template_terms' = '<div class="taxonomy-archive">%terms%</div>'

