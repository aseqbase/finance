<?php
library("payment/ZarinPalPort");
return new MiMFa\Library\Payment\ZarinPalPort(
    "a3e1ashmklkdvndslvndlsdjlksdsddlgdf6",
    "ZarinPal-Test",
    "ZarinPal Test",
    "https://sandbox.zarinpal.com/pg/StartPay/{authority}",
    "https://sandbox.zarinpal.com/pg/v4/payment/request.json",
    "https://sandbox.zarinpal.com/pg/v4/payment/verify.json",
    "https://sandbox.zarinpal.com/pg/v4/payment/reverse.json"
);