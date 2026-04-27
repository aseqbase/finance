<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Finance\Account;
use MiMFa\Library\Struct;
library("finance/Account");

$data = $data ?? [];
$routeHandler = function ($data) {
    auth(\_::$User->AdminAccess);

    module("Table");
    $table = new MiMFa\Module\Table(table("Account")->OrderBy("UpdateTime", false));
    $table->SelectCondition = "Status IN ('" . join("', '", Account::PledgeStatuses()) . "')";
    $table->IncludeColumns = [
        "UpdateTime",
        "Amount",
        "Currency",
        "IsDeposited" => Account::DepositCondition(true),
        "Status",
        "From" => "SourceId",
        "To" => "DestinationId",
        "For" => "Relation",
        "RelationId",
        "Description"
    ];
    $table->ExcludeColumns = ["Currency", "RelationId", "IsDeposited"];
    $table->FilterColumns = ["Status"];
    $unit = __(\_::$Joint->Finance->ShownCurrency);
    $users = table("User")->SelectPairs("Id", "Signature");
    $table->CellsValues = [
        "Amount" => function ($v, $k, $r) use ($unit) {
            return \_::$Joint->Finance->AmountStruct($v, $unit, ["class" => "be fore " . ($r["IsDeposited"] ? "green" : "red")]);
        },
        "From" => function ($v, $k, $r) use ($users) {
            return $r["From"] ? Struct::Link($v = get($users, abs($r["From"])) ?? $v, \_::$Address->UserRootUrlPath . $v) : Struct::Link(\_::$Front->Name, \_::$Front->DirectPath);
        },
        "To" => function ($v, $k, $r) use ($users) {
            return $r["To"] ? Struct::Link($v = get($users, abs($r["To"])) ?? $v, \_::$Address->UserRootUrlPath . $v) : Struct::Link(\_::$Front->Name, \_::$Front->DirectPath);
        },
        "For" => function ($v, $k, $r) {
            return $v ? Struct::Link($v, "/finance/$v?id=" . $r["RelationId"]) : "";
        },
        "Status" => function ($v) {
            return Struct::Span($v, null, ["style" => "color:" . Account::GetStatusColor($v)]);
        },
        "UpdateTime" => fn($v) => Convert::ToShownDateTimeString($v)
    ];
    $table->CellsTypes = [
        "Status" => Account::GetStatuses(),
        "Transaction" => "text",
        "Description" => "texts",
        "Amount" => "disabled",
        "Currency" => "disabled",
        "UpdateTime" => function ($t, $v) {
            $std = new stdClass();
            $std->Type = \_::$User->HasAccess(\_::$User->SuperAccess) ? "calendar" : "hidden";
            $std->Value = Convert::ToDateTimeString();
            return $std;
        },
        "CreateTime" => "disabled"
    ];
    $table->FormFilter = !\_::$User->HasAccess(\_::$User->SuperAccess);
    $table->RefreshTimeout = \_::$Front->RefreshTimeout;
    $table->AllowDataTranslation = true;
    $table->Controlable = true;
    $table->Updatable = true;
    $table->ModifyAccess =
        $table->ViewAccess = \_::$User->AdminAccess;
    $table->RemoveAccess =
        $table->DuplicateAccess =
        $table->AddAccess = \_::$User->SuperAccess;
    return $table->ToString();
};

(new Router())
    ->if(\_::$User->HasAccess(\_::$User->AdminAccess))
    ->Get(function () use ($routeHandler) {
        (\_::$Front->AdminView)($routeHandler, [
            "Image" => "stamp",
            "Title" => "'Pledges' 'Management'"
        ]);
    })
    ->Default(fn() => response($routeHandler($data)))
    ->Handle();