<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Struct;
use MiMFa\Module\Table;
$data = $data ?? [];
$routeHandler = function ($data) {
    auth(\_::$User->AdminAccess);
    module("Table");
    $module = new Table(table("Finance_Invoice")->OrderBy("CreateTime", false));
    $module->KeyColumns = ["Title"];
    $module->IncludeColumns = ["Code" => "Name", "Title", "User" => "UserId", "Source", "Amount", "Currency", "Status", "Destination", "Description", "UpdateTime", "MetaData"];
    $module->ExcludeColumns = ["Currency", "MetaData"];
    $module->AllowDataTranslation = false;
    $module->AllowServerSide = true;
    $module->ViewAccess = false;
    $module->Updatable = true;
    $module->UpdateAccess = \_::$User->AdminAccess;
    $module->PrependControls = fn($v, $row) => [
        Struct::Icon("eye", \_::$Joint->Finance->InvoiceUrlPath."?id={$v}", ["tooltip" => "To see the 'invoice'"]),
    ];
    $users = [0 => "Public"];
    foreach (table("User")->SelectPairs("Id", "Signature") as $k => $v)
        $users[$k] = $v;
    $statuses = ["Pending" => "yellow", "Paid" => "green", "Cancelled" => "red", "Failed" => "red"];
    $module->CellsValues = [
        "Code" => function ($v, $k, $r) use ($statuses) {
            return Struct::Link("\${{$v}}", \_::$Joint->Finance->InvoiceUrlPath."?id=" . ($v ?: $r["Id"]), ["target" => "blank", "class" => "be fore " . ($statuses[$r["Status"] ?: "Created"] ?? "gray")]);
        },
        "User" => fn($v) => Struct::Span("\${" . ($users[$v] ?? "") . "}", \_::$Address->UserRootUrlPath . $v),
        "Amount" => fn($v, $k, $r) => \_::$Joint->Finance->AmountStruct($v, __($r["Currency"] ?: \_::$Joint->Finance->ShownCurrency),["class" => "be fore " . ($statuses[$r["Status"] ?: "Created"] ?? "gray")]),
        "Status" => function ($v, $k, $r) use ($statuses) {
            return Struct::Span($v ?: "Created", \_::$Joint->Finance->InvoiceUrlPath."?id=" . ($r["Code"] ?: $r["Id"]), ["target" => "blank", "class" => "be fore " . ($statuses[$r["Status"] ?: "Created"] ?? "gray")]);
        },
        "CreateTime" => fn($v) => Convert::ToShownDateTimeString($v),
        "UpdateTime" => fn($v) => Convert::ToShownDateTimeString($v)
    ];
    $module->CellsTypes = [
        "Id" => "number",
        "Name" => function () {
            $std = new stdClass();
            $std->Title = "Code";
            $std->Description = "The manual tracking code";
            $std->Type = "text";
            return $std;
        },
        "UserId" => function ($t, $v) use ($users) {
            $std = new stdClass();
            $std->Title = "User";
            $std->Type = \_::$User->HasAccess(\_::$User->SuperAccess) ? "select" : "hidden";
            $std->Options = $users;
            if (!isValid($v))
                $std->Value = \_::$User->Id;
            return $std;
        },
        "Title" => "text",
        "Description" => "texts",
        "Content" => "content",
        "Amount" => "number",
        "Currency" => function ($t, $v) {
            $std = new stdClass();
            $std->Type = "select";
            $std->Options = \_::$Joint->Finance->GetAllCurrencyOptions();
            return $std;
        },
        "Platform" => function () {
            $std = new stdClass();
            $std->Type = "text";
            $std->Description = "The custom payment platform for this invoice
            You can customize it by your link or js codes, using bellow keys:
            - {table}     If you want to send the name of the invoice table,
            - {id}        If you want to send the id of the invoice record on the table,
            - {callback}  If you want to send the callback url,
            - {success}   If you want to send the success callback url,
            - {error}     If you want to send the error callback url
            - {platform}  If you want to send the platform
            ";
            return $std;
        },
        "Access" => "int",
        "Source" => "text",
        "SourceData" => "json",
        "Destination" => "text",
        "DestinationData" => "json",
        "Relation" => "path",
        "Status" => ["Unpaid" => "Unpaid", "Pending" => "Pending", "Installing" => "Installing", "Cancelled" => "Cancelled", "Failed" => "Failed", "Paid" => "Paid"],
        "Transactions" => "json",
        "CreateTime" => function ($t, $v) {
            return \_::$User->HasAccess(\_::$User->SuperAccess) ? "calendar" : (isValid($v) ? "hidden" : false);
        },
        "UpdateTime" => function ($t, $v) {
            $std = new stdClass();
            $std->Type = \_::$User->HasAccess(\_::$User->SuperAccess) ? "calendar" : "hidden";
            $std->Value = Convert::ToDateTimeString();
            return $std;
        },
        "MetaData" => "json"
    ];
    pod($module, $data);
    return $module->ToString();
};

(new Router())
    ->if(\_::$User->HasAccess(\_::$User->AdminAccess))
    ->Get(function () use ($routeHandler) {
        (\_::$Front->AdminView)($routeHandler, [
            "Image" => "money-check",
            "Title" => "Invoices Management"
        ]);
    })
    ->Default(fn() => response($routeHandler($data)))
    ->Handle();