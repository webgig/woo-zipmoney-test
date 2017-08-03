<?php
if ($order->post_status == WC_Zipmoney_Payment_Gateway_Config::ZIP_ORDER_STATUS_AUTHORIZED_KEY) {
    ?>
    <button type="button" class="button zip-capture-btn">Capture zip charge</button>
    <button type="button" class="button zip-cancel-btn">Cancel charge</button>
    <input type="hidden" name="zip_order_id" value="<?php echo $order->id; ?>">
    <!-- Add the capture zip charge and hide the refund button -->
    <script>
        jQuery(function () {
            //hide the refund button
            jQuery('button.refund-items').hide();

            //Set the action of capture button
            jQuery('button.zip-capture-btn').click(function () {
                var confirmResult = confirm('Are you sure?');
                if (confirmResult == true) {
                    var post_form = jQuery('#post');
                    post_form.prop('action', '<?php echo WC_Zipmoney_Payment_Gateway_Util::get_capture_charge_url() ?>');
                    post_form.submit();
                }
            });

            //Set the action of cancel button
            jQuery('button.zip-cancel-btn').click(function(){
                var confirmResult = confirm('Are you sure?');
                if(confirmResult == true){
                    var post_form = jQuery('#post');
                    post_form.prop('action', '<?php echo WC_Zipmoney_Payment_Gateway_Util::get_cancel_charge_url()?>');
                    post_form.submit();
                }
            });
        });
    </script>
<?php } ?>