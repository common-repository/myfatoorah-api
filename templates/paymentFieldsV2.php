<?php
if (isset($error)) {
    echo ('<div style="color:#b81c23"><b>' . $error . '</b></div>');
} else {
    parent::payment_fields();

    if ($this->listOptions === 'multigateways' && $this->count > 1) {
        $key = 0;
        foreach ($this->gateways as $gateway) {

            $checked = ($key == 0) ? 'checked' : '';
            $key++;

            $label = ($this->lang == 'ar') ? $gateway->PaymentMethodAr : $gateway->PaymentMethodEn;
            ?>
            <span class="mf-div" style="margin: 20px; display: inline-flex;">
                <input class="mf-radio" <?php echo $checked; ?> type="radio" id="mf-radio-<?php echo $gateway->PaymentMethodId; ?>" name="mf_gateway" value="<?php echo $gateway->PaymentMethodId; ?>" style="margin: 5px; vertical-align: top;"/>
                <label for="mf-radio-<?php echo $gateway->PaymentMethodId; ?>">
                    <?php echo $label; ?>
                    <img class="mf-img" id="mf-img-<?php echo $gateway->PaymentMethodId; ?>" src="<?php echo $gateway->ImageUrl; ?>" alt="<?php echo $label; ?>" style="margin: 0px; width: 50px; height: 30px;"/>
                </label>
            </span>
            <?php
        }
    }
}