<script type="text/javascript">
    jQuery(function () {

        jQuery("#place_order").on('click', function (e) {
            var payment_method = jQuery('form[name="checkout"] input[name="payment_method"]:checked').val();

            console.log(payment_method);
            if (payment_method == 'zipmoney') {

                Zip.Checkout.init({
                    redirect: <?php echo $is_iframe_checkout ? 0 : 1?>,
                    checkoutUri: '<?php echo WC_Zipmoney_Payment_Gateway_Util::get_checkout_endpoint_url();?>',
                    redirectUri: '<?php echo WC_Zipmoney_Payment_Gateway_Util::get_complete_endpoint_url()?>'
                });

                e.preventDefault();

                this.prop("disabled",true);
            }
        });
    });
</script>