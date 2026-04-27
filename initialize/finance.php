<?php
plugin("Finance");
\_::$Joint->Finance = new MiMFa\Plugin\Finance();

$data = $data ?? [];

MiMFa\Library\Struct::$MaxDecimalPrecision = \_::$Joint->Finance->DecimalPercision;

if (\_::$User->HasAccess(\_::$User->AdminAccess) && isset(\_::$Front->AdminMenus["Administrator"])) {
    \_::$Front->AdminMenus = [
        ...array_slice(\_::$Front->AdminMenus, 0, count(\_::$Front->AdminMenus) - 1),
        "Administrator-Finance" => array(
            "Name" => "FINANCE",
            "Path" => "/administrator/finance/account",
            "Access" => \_::$User->AdminAccess,
            "Description" => "To manage the 'bank'",
            "Image" => "dollar",
            "Items" => [
                array("Name" => "ACCOUNT", "Path" => "/administrator/finance/account", "Access" => \_::$User->AdminAccess, "Description" => "To manage the 'bank'", "Image" => "sack-dollar"),
                array("Name" => "INVOICES", "Path" => "/administrator/finance/invoices", "Access" => \_::$User->AdminAccess, "Description" => "To manage all 'invoices'", "Image" => "money-check"),
                array("Name" => "PENDINGS", "Path" => "/administrator/finance/pendings", "Access" => \_::$User->AdminAccess, "Description" => "To manage all the `'pended' 'transactions'`", "Image" => "spinner"),
                array("Name" => "PLEDGES", "Path" => "/administrator/finance/pledges", "Access" => \_::$User->AdminAccess, "Description" => "To manage all the `'pledges' 'transactions'`", "Image" => "stamp"),
                array("Name" => "TRANSACTIONS", "Path" => "/administrator/finance/transactions", "Access" => \_::$User->AdminAccess, "Description" => "To manage all 'transactions'", "Image" => "landmark"),
                array("Name" => "CONFIGURATIONS", "Path" => "/administrator/finance/finance", "Access" => \_::$User->SuperAccess, "Description" => "To manage all the finance configurations", "Image" => "cog"),
            ]
        ),
        ...array_slice(\_::$Front->AdminMenus, count(\_::$Front->AdminMenus) - 1),
    ];
}
if (\_::$Joint->Finance->DefaultMenu) {
    $menus = [array("Access" => \_::$Joint->Finance->WalletAccess, "Name" => \_::$Joint->Finance->WalletTitle, "Path" => \_::$Joint->Finance->WalletUrlPath, "Description" => \_::$Joint->Finance->WalletDescription, "Icon" => \_::$Joint->Finance->WalletImage)];

    \_::$Front->MainMenus = [
        ...array_slice(\_::$Front->MainMenus, 0, count(\_::$Front->MainMenus) - 1),
        ...$menus,
        ...array_slice(\_::$Front->MainMenus, count(\_::$Front->MainMenus) - 1),
    ];
    \_::$Front->SideMenus = [
        ...array_slice(\_::$Front->SideMenus, 0, count(\_::$Front->SideMenus) - 1),
        ...$menus,
        ...array_slice(\_::$Front->SideMenus, count(\_::$Front->SideMenus) - 1),
    ];
}

\_::$Router->On("/payment/creditcards")
    ->Get(fn() => page("/payment/creditcards", $data))
    ->Default(fn() => compute("/payment/creditcards", $data));