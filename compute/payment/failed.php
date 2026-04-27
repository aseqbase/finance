<?php

use MiMFa\Library\Struct;

$token = get($data, "Token");
if (!$token)
    return false;
$transaction = popSecret(getClientCode($token));
if ($transaction) {
    $transaction["Transaction"] = get($data, "Transaction") ?? get($transaction, "Transaction");
    $transaction["Description"] = get($data, "Description") ?? get($transaction, "Description");
    if ($action = get($transaction, "MetaData", "Error"))
        if(isScript($action)) return script($action);
        else return run($action, $transaction);
    module("PrePage");
    $prepage = new \MiMFa\Module\PrePage();
    $prepage->Router->Get()->Switch();
    $prepage->Image = "close";
    $prepage->Title = Struct::Span("Failed", null, ["class" => "be fore red"]);
    $prepage->Description = Struct::Error($this->CancelMessage);
    $prepage->Content = Struct::Container([
        Struct::Center(__(get($transaction, "MetaData", "Errors") ?? get($transaction, "Description") ?? "Please try again...")),
    ]);
    module("TimeCounter");
    $counter = new MiMFa\Module\TimeCounter(5, 0, $action);
    $counter->Router->Get()->Switch();
    $counter->Description = "Refer to complete the process";
    $counter->Class = "button be inline center red";
    $counter->TagName = "button";
    $counter["onclick"] = "load(" . MiMFa\Library\Script::Convert("/finance/$r?id=".urlencode($rid)) . ")";
    style(".{$prepage->MainClass} .image {color:var(--color-red);}");
    $prepage->Render();
    $counter->Render();
    return true;
}
return false;