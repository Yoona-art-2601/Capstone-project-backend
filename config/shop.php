<?php
define('DELIVERY_FEE', 10.00);
define('FREE_DELIVERY_THRESHOLD', 120.00);

function delivery_fee_for($subtotal) {
    return $subtotal >= FREE_DELIVERY_THRESHOLD ? 0.00 : DELIVERY_FEE;
}
?>
