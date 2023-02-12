<?php $view = get_view(); ?>

<h2><?php echo __('Browse Collections'); ?></h2>

<p>
	<b><?php echo __('Please note'); ?></b>: <?php echo __('in most cases, you will want to add whichever field you select here to the list of sort links in your Theme\'s %s file; check the %s file for an example.', '<em>collections/browse.php</em>', '<em>README.md</em>'); ?>
</p>

<div class="field">
	<div class="two columns alpha">
		<label><?php echo __('Collections Sort Field');?></label>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('Choose the field all Collections will be sorted by.'); ?>
		</p>
		<?php echo $view->formSelect('collectionsplus_collections_sort_field', get_option('collectionsplus_collections_sort_field'), null, $formElementOptions) ?>
	</div>
</div>

<div class="field">
	<div class="two columns alpha">
		<label><?php echo __('Collections Sort Direction');?></label>
	</div>
	<div class="inputs five columns omega">
		<p class="explanation">
			<?php echo __('Choose the sorting direction for all Collections.'); ?>
		</p>
		<?php echo $view->formRadio('collectionsplus_collections_sort_dir', get_option('collectionsplus_collections_sort_dir'), null, array('a' => __('Ascending'), 'd' => __('Descending'))); ?>
	</div>
</div>
