<select name="quick-filter" class="quick-filter" aria-label="<?php echo __('Quick Filter'); ?>">
    <option><span class="quick-filter-heading"><?php echo __('Quick Filter') ?></span></option>
    <option value="<?php echo url('collections'); ?>"><?php echo __('View All') ?></option>
    <option value="<?php echo url('collections', array('public' => 1)); ?>"><?php echo __('Public'); ?></option>
    <option value="<?php echo url('collections', array('public' => 0)); ?>"><?php echo __('Private'); ?></option>
    <option value="<?php echo url('collections', array('featured' => 1)); ?>"><?php echo __('Featured'); ?></option>
    <option value="<?php echo url('collections', array('empty' => 1)); ?>"><?php echo __('Empty'); ?></option>
</select>