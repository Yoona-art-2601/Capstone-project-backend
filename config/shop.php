<?php
define('DELIVERY_FEE', 10.00);
define('FREE_DELIVERY_THRESHOLD', 120.00);
define('WHATSAPP_NUMBER', '60163334371');

function delivery_fee_for($subtotal) {
    return $subtotal >= FREE_DELIVERY_THRESHOLD ? 0.00 : DELIVERY_FEE;
}

function effective_price($price, $sale_price) {
    if ($sale_price !== null && $sale_price !== '' && (float)$sale_price < (float)$price) {
        return (float)$sale_price;
    }
    return (float)$price;
}
?>
