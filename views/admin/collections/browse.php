<?php
	$pageTitle = __('Browse Collections') . ' ' .  __('(%s total)', $total_results);
	$totalItemsWithoutCollection = get_db()->getTable('Item')->count(array('collection' => 0));
	echo head(
		array(
			'title' => $pageTitle,
			'bodyclass' => 'collections browse'
		)
	);
	echo flash();
	echo collection_search_filters();
?>

<?php if ($total_results): ?>
	<?php echo pagination_links(); ?>
	<?php if (is_allowed('Collections', 'add')): ?>
		<a href="<?php echo html_escape(url('collections/add')); ?>" class="green button full-width-mobile">
			<?php echo __('Add a Collection'); ?>
		</a>
	<?php endif; ?>
	<?php echo common('quick-filters', array(), 'collections'); ?>

	<p class="not-in-collections">
		<?php if ($totalItemsWithoutCollection):
			$withoutCollectionMessage = __(plural('%s%d item%s has no collection.', "%s%d items%s aren't in a collection.",
				$totalItemsWithoutCollection), '<a href="' . html_escape(url('items/browse?collection=0')) . '">', $totalItemsWithoutCollection, '</a>');
		else:
			$withoutCollectionMessage = __('All items are in a collection.');
		endif; ?>
		<?php echo $withoutCollectionMessage; ?>
	</p>
	
	<div class="table-responsive">
		<table id="collections">
			<thead>
				<tr>
					<?php
					$sortLinks = array(
						__('Title') => 'Dublin Core,Title',
						__('Contributors') => null,
						__('Date Added') => 'added',
						__('Total Number of Items') => null
					);
					?>
					<?php echo browse_sort_links($sortLinks, array('link_tag' => 'th scope="col"', 'list_tag' => '')); ?>
				</tr>
			</thead>
			<tbody>
				<?php $key = 0; ?>
				<?php foreach (loop('Collection') as $collection): ?>
				<tr class="collection<?php if(++$key%2==1) echo ' odd'; else echo ' even'; ?>">
					<td class="collection-info" style="width:50%">
						<?php if ($collectionImage = record_image('collection', 'square_thumbnail')): ?>
							<?php echo link_to_collection($collectionImage, array('class' => 'image')); ?>
						<?php endif; ?>
						
						<span class="title">
							<?php echo link_to_collection(); ?>
							<?php if ($collection->featured): ?><span class="featured" aria-label="<?php echo __('Featured'); ?>" title="<?php echo __('Featured'); ?>"></span><?php endif; ?>
	
							<?php if(!$collection->public): ?>
								<small><?php echo __('(Private)'); ?></small>
							<?php endif; ?>
						</span>

						<ul class="action-links group">
							<?php if (is_allowed($collection, 'edit')): ?>
								<li><?php echo link_to_collection(__('Edit'), array('class'=>'edit'), 'edit'); ?></li>
								<li><?php echo link_to_collection(__('Advanced Settings'), array('class'=>'advanced'), 'advanced'); ?></li>
							<?php endif; ?>
							
							<?php if (is_allowed($collection, 'delete')): ?>
								<li><?php echo link_to_collection(__('Delete'), array('class' => 'delete-confirm'), 'delete-confirm'); ?></li>
							<?php endif; ?>
						</ul>

						<div class="details">
							<?php $collectionDescription = snippet_by_word_count(metadata('collection', array('Dublin Core', 'Description')), 40); ?>
							<?php if ($collectionDescription !== ''): ?>
								<p class="description"><?php echo $collectionDescription; ?></p>
							<?php endif; ?>
							<p>
								<strong><?php echo __('Theme'); ?>:</strong>
								<?php echo collection_theme(); ?>
							</p>
							<p>
								<strong><?php echo __('Items Per Page'); ?>:</strong>
								<?php echo collection_per_page(); ?>
							</p>
							<p>
								<strong><?php echo __('Google Analytics Tracking ID'); ?>:</strong>
								<?php echo collection_google_analytics_id(); ?>
							</p>
						</div>
					</td>
					<td>
						<?php if ($collection->hasContributor()): ?>
							<?php echo metadata('collection', array('Dublin Core', 'Contributor'), array('all'=>true, 'delimiter'=>'<br>')); ?>
						<?php else: ?>
							<?php echo __('No contributors'); ?>
						<?php endif; ?>
					</td>
					<td>
						<?php if($time = metadata('collection', 'Added')):?>
							<?php echo format_date($time); ?>
						<?php endif; ?>
					</td>
					<td><?php echo link_to_items_in_collection(); ?></td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
	</div>

	<?php echo pagination_links(); ?>
	<?php if (is_allowed('Collections', 'add')): ?>
		<a href="<?php echo html_escape(url('collections/add')); ?>" class="green button"><?php echo __('Add a Collection'); ?></a>
		<?php echo common('quick-filters', array(), 'collections'); ?>
	<?php endif; ?>
	<p class="not-in-collections"><?php echo $withoutCollectionMessage; ?></p>

    <script type="text/javascript">
		Omeka.addReadyCallback(Omeka.CollectionsBrowse.setupDetails, [
			<?php echo js_escape(__('Details')); ?>,
			<?php echo js_escape(__('Show Details')); ?>,
			<?php echo js_escape(__('Hide Details')); ?>
		]);
		Omeka.addReadyCallback(Omeka.quickFilter);
    </script>

<?php else: ?>
	<?php $total_collections = total_records('Collection'); ?>
	<?php if ($total_collections === 0): ?>
		<h2><?php echo __('You have no collections.'); ?></h2>
		<?php if(is_allowed('Collections', 'add')): ?>
			<p><?php echo __('Get started by adding your first collection.'); ?></p>
			<a href="<?php echo html_escape(url('collections/add')); ?>" class="add green button"><?php echo __('Add a Collection'); ?></a>
		<?php endif; ?>
    <?php else: ?>
        <p>
            <?php echo __(plural('The query searched 1 collection and returned no results.', 'The query searched %s collections and returned no results.', $total_collections), $total_collections); ?>
            <?php echo link_to('collections', null, __('View All Collections')); ?>
        </p>
    <?php endif; ?>
<?php endif; ?>

<?php fire_plugin_hook('admin_collections_browse', array('collections' => $collections, 'view' => $this)); ?>

<?php echo foot(); ?>
