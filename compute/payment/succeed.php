<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Struct;
use MiMFa\Library\Script;

$data = $data??[];
$token = get($data, "Token");
if (!$token)
    return false;
$transaction = popSecret(getClientCode($token));
$track = get($data, "Transaction") ?? get($transaction, "Transaction");
if ($transaction && $transaction["Relation"] && $transaction["RelationId"] && (!$transaction["Transaction"] || $track == $transaction["Transaction"])) {
    if ($action = get($transaction, "MetaData", "Success")) 
        if(isScript($action)) return script($action);
        else return run($action, $transaction);
    module("PrePage");
    $prepage = new \MiMFa\Module\PrePage();
    $prepage->Router->Get()->Switch();
    $prepage->Image = "check";
    $prepage->Title = Struct::Span("Successful", null, ["class" => "be fore green"]);
    $prepage->Description = get($data, "Description");
    $prepage->Content = Struct::Container([
        Struct::Center(get($transaction, "Description") ?? "Your 'transaction' 'verified' 'successfully'!"),
        Struct::$Break,
        Struct::Center(
            Struct::Table([
                [__("Tracking code") . ":", Struct::Bold($transaction["Transaction"]) . Struct::Icon("copy", "copy(" . Script::Convert($transaction["Transaction"]) . ")") . Struct::Button(Struct::Big(Struct::Icon("print")), "window.print()")],
                [__("From") . ":", $transaction["SourceId"] ? get(table("User")->Get(abs($transaction["SourceId"])), "Name") : __("Unknown")] ?? __(\_::$Front->Name),
                [__("To") . ":", $transaction["DestinationId"] ? get(table("User")->Get(abs($transaction["DestinationId"])), "Name") : __("Unknown")] ?? __(\_::$Front->Name),
                [__("Amount") . ":", Struct::Number($transaction["Amount"]) . " " . __($transaction["Currency"])],
                [__("Platform") . ":", __($transaction["Platform"])],
                [__("Time") . ":", Convert::ToShownDateTimeString()],
            ], ["RowHeaders" => [], "ColHeaders" => []])
        )
    ]);
    module("TimeCounter");
    $counter = new MiMFa\Module\TimeCounter(10, 0, $action);
    $counter->Router->Get()->Switch();
    $counter->Description = "Refer to complete the process";
    $counter->Class = "button be inline center green";
    $counter->TagName = "button";
    $counter["onclick"] = "load(" . MiMFa\Library\Script::Convert(\_::$Joint->Finance->RootUrlPath. strtolower($transaction["Relation"])."?id=".urlencode($transaction["RelationId"])) . ")";
    style(".{$prepage->MainClass} :is(.image,.icon) {color:var(--color-green) !important;}");
    $prepage->Render();
    $counter->Render();
    return true;
}
return false;