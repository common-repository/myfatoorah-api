<?php
if (isset($error)) {
    echo ('<div style="color:#b81c23"><b>' . $error . '</b></div>');
} else {
    ?>
    <fieldset id="wc-myfatoorah_direct-cc-form" class="wc-credit-card-form wc-payment-form">

        <!-- Select a Card -->
        <?php
        if ($this->count > 1) {
            parent::payment_fields();
            ?>

            <p class="form-row form-row-first woocommerce-validated">
                <label for="cctype">
                    <?php _e('Credit Card Type', 'myfatoorah-woocommerce'); ?>
                    <span class="required">*</span>
                </label>
                <select name="cctype" id="cctype" class="input-text wc-credit-card-form-card-number" style="border-color: #c7c1c6 !important;">
                    <?php foreach ($this->gateways as $gw) { ?>
                        <option value="<?php echo $gw->PaymentMethodId; ?>">
                            <?php echo ($this->lang == 'ar') ? $gw->PaymentMethodAr : $gw->PaymentMethodEn; ?>
                        </option>
                    <?php } ?>
                </select>
            </p>
            <div class="clear"></div>
            <!-- ------------------------------------------------------------------------------------------------ -->
        <?php } ?>


        <!-- Card Holder Name -->
        <p class="form-row form-row-first woocommerce-validated">
            <label for="cardHolderName">
                <?php _e('Card Holder Name', 'myfatoorah-woocommerce'); ?>
                <span class="required">*</span>
            </label>
            <input type="text" name="cardHolderName" id="cardHolderName" class="input-text wc-credit-card-form-card-number" placeholder="<?php _e('Card Holder Name', 'myfatoorah-woocommerce'); ?>" />
        </p>


        <!-- Card Number -->
        <p class="form-row form-row-last woocommerce-validated">
            <label for="ccnum">
                <?php _e('Credit Card Number', 'myfatoorah-woocommerce'); ?>
                <span class="required">*</span>
            </label>
            <input type="tel" name="ccnum" id="ccnum" class="input-text wc-credit-card-form-card-number" placeholder="•••• •••• •••• ••••" />
        </p>
        <div class="clear"></div>
        <!-- ------------------------------------------------------------------------------------------------ -->

        <!-- Expiration Date -->
        <p class="form-row form-row-first woocommerce-validated">
            <label for="expmonth">
                <?php _e('Expiration Date', 'myfatoorah-woocommerce'); ?>
                <span class="required">*</span>
            </label>
            <select name="expmonth" id="expmonth" class="input-select wc-credit-card-form-card-expiry" style="border-color: #c7c1c6 !important;">
                <?php
                for ($i = 1; $i <= 12; $i++) {
                    printf('<option value="%02d">%02d</option>', $i, $i);
                }
                ?>
            </select>

            <select name="expyear" id="expyear" class="input-text wc-credit-card-form-card-expiry" style="border-color: #c7c1c6 !important;">
                <?php
                for ($i = date('y'); $i <= date('y') + 15; $i++) {
                    printf('<option value="20%u">20%u</option>', $i, $i);
                }
                ?>
            </select>
        </p>


        <!-- Card CVV -->
        <p class="form-row form-row-last woocommerce-validated" title="<?php _e('3 or 4 digits usually found on the signature strip.', 'myfatoorah-woocommerce'); ?>">
            <label for="cvv">
                <?php _e('Card Security Code', 'myfatoorah-woocommerce'); ?>
                <span class="required">*</span>
            </label>
            <input type="tel" name="cvv" id="cvv" class="input-text wc-credit-card-form-card-cvc" maxlength="4" placeholder="CVC" style="width:100px;" />

        </p>					
        <div class="clear"></div>
        <!-- ------------------------------------------------------------------------------------------------ -->

    </fieldset>
    <?php
}    