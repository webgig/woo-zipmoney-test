<h3><?php _e($this->method_title, 'woocommerce');?></h3>
<p><?php _e($this->method_description, 'woocommerce');?></p>
<table class="form-table">
    <?php $this->generate_settings_html();?>
</table>
<script src="<?php echo $admin_option_js;?>"></script>