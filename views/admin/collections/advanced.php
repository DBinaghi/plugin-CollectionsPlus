<?php $title = __('Advanced Settings') . ": " . strip_formatting(metadata('collection', array('Dublin Core', 'Title'))); ?>

<?php
	echo head(array('title' => $title, 'bodyclass' => "collections"));
	echo flash();
?>

<form method="post">
	<section class="seven columns alpha">
		<div class="field">
			<div class="two columns alpha">
				<?php echo $this->formLabel('theme', __('Theme')); ?>
			</div>
			<div class="inputs five columns omega">
				<p class="explanation">
					<?php echo __('%sOmeka Theme%s that will be applied to this Collection.', '<a href="../../themes/browse">', '</a>'); ?>
				</p>
				<?php echo $this->formSelect('theme', $this->settings['theme'], null, $this->themes); ?>
			</div>
		</div>

		<div class="field">
			<div class="two columns alpha">
				<?php echo $this->formLabel('per_page', __('Items Per Page')); ?>
			</div>
			<div class="inputs five columns omega">
				<p class="explanation">
					<?php echo __('Number of Items displayed when browsing this Collection (Public interface).'); ?>
				</p>
				<?php echo $this->formText('per_page', $this->settings['per_page']); ?>
			</div>
		</div>

		<div class="field">
			<div class="two columns alpha">
				<?php echo $this->formLabel('items_sort_field', __('Items Sort Field')); ?>
			</div>
			<div class="inputs five columns omega">
				<p class="explanation">
					<?php echo __('The field all Items in this Collection will be sorted by.'); ?>
				</p>
			
				<?php echo $this->formSelect('items_sort_field', $this->settings['items_sort_field'], null, $this->elements) ?>
			</div>
		</div>
		
		<div class="field">
			<div class="two columns alpha">
				<?php echo $this->formLabel('items_sort_dir', __('Items Sort Direction')); ?>
			</div>
			<div class="inputs five columns omega">
				<p class="explanation">
					<?php echo __('The direction all Items in this Collection will be sorted by.'); ?>
				</p>
				<?php echo $this->formRadio('items_sort_dir', $this->settings['items_sort_dir'], null, array('a' => __('Ascending'), 'd' => __('Descending'))); ?>
			</div>
		</div>

		<div class="field">
			<div class="two columns alpha">
				<?php echo $this->formLabel('tracking_id', __('Google Analytics Tracking ID')); ?>
			</div>
			<div class="inputs five columns omega">
				<p class="explanation">
					<?php echo __('Unique identifier useful to identify and collect this Collection\'s user traffic and behavior data.'); ?>
				</p>
				<?php echo $this->formText('tracking_id', $this->settings['tracking_id']); ?>
			</div>
		</div>
		
		<?php fire_plugin_hook('admin_settings_form', array('view' => $this)); ?>
	</section>
	<section class="three columns omega">
		<div id="save" class="panel">
			<?php echo $this->formSubmit('submit', __('Save Changes'), array('class'=>'submit big green button')); ?>
		</div>
	</section>
</form>

<?php echo foot(); ?>
