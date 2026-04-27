<?php
library("payment/IranKishPort");
return new MiMFa\Library\Payment\IranKishPort(
    "TERMINAL",
    "ACCEPTOR-CODE",
    "ACCEPTOR-PASSWORD"
);