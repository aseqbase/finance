<?php
if (\_::$Joint->Finance->TestPayment) {
    library("payment/TestPort");
    return new MiMFa\Library\Payment\TestPort();
} else
    return null;