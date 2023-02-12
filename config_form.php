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
		<select name="collectionsplus_collections_sort_field">
			<option value=""><?php echo __('Default'); ?></option>
			<?php $currentCollectionSort =  get_option('collectionsplus_collections_sort_field'); ?>

			<option value="added" <?php if($currentCollectionSort == 'added') { echo 'selected'; }?>><?php echo __('Date Added'); ?></option>
			<?php
				foreach ($elements as $element) :
					$val = $allElements[]=$element->set_name . ',' . $element->name;
					$checked = ($val == $currentCollectionSort ? 'selected' : '');
			?>
			<option value="<?php echo $val;?>" <?php echo $checked; ?>><?php echo __($element->set_name) . ', ' . __($element->name);?></option>
			<?php endforeach; ?>
		</select>
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
		<?php $currentCollectionSortDir = get_option('collectionsplus_collections_sort_dir'); ?>
		<input type="radio" name="collectionsplus_collections_sort_dir" value="a" <?php if($currentCollectionSortDir == 'a') {echo 'checked';}  ?>> <?php echo __('Ascending'); ?> <br />
		<input type="radio" name="collectionsplus_collections_sort_dir" value="d" <?php if($currentCollectionSortDir == 'd') {echo 'checked';}  ?>> <?php echo __('Descending'); ?>
	</div>
</div>