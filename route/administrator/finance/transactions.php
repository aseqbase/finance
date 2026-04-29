<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Finance\Account;
use MiMFa\Library\Struct;
library("finance/Account");

$data = $data??[];
$routeHandler = function ($data) {
    auth(\_::$User->AdminAccess);

    $account_MDT = new Account();

    module("Table");
    $table = new MiMFa\Module\Table(table("Finance_Account")->OrderBy("Id", false));
    $table->IncludeColumns = ["UpdateTime", "Tracking Code" => "Transaction", "Amount", "Currency", "IsDeposited" => Account::DepositCondition(), "Status", "From" => "SourceId", "To" => "DestinationId", "For" => "Relation", "RelationId", "Description"];
    $table->ExcludeColumns = ["Currency", "RelationId", "IsDeposited"];
    $table->FilterColumns = ["Status"];
    $unit = __(\_::$Joint->Finance->ShownCurrency);
    $users = table("User")->SelectPairs("Id", "Signature");
    $table->CellsValues = [
        "Amount" => function ($v, $k, $r) use ($unit) {
            return Struct::Number($v,["class" => "be fore " . ($r["IsDeposited"] ? "green" : "red")]).$unit; 
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
            return Struct::Span($v, null, ["style" => "color:".Account::GetStatusColor($v)]);
        },
        "UpdateTime" => fn($v) => Convert::ToShownDateTimeString($v)
    ];
    $table->CellsTypes = [
        "Status" => Account::GetStatuses(),
        "Transaction" => "text",
        "Description" => "texts",
        "Amount" => "disabled",
        "Currency" => function ($t, $v) {
            $std = new stdClass();
            $std->Type = "select";
            $std->Options = \_::$Joint->Finance->GetAllCurrencyOptions();
            return $std;
        },
        "UpdateTime" => function ($t, $v) {
            $std = new stdClass();
            $std->Type = \_::$User->HasAccess(\_::$User->SuperAccess) ? "calendar" : "hidden";
            $std->Value = Convert::ToDateTimeString();
            return $std;
        },
        "CreateTime" => "disabled"
    ];
    $table->RefreshTimeout = \_::$Front->RefreshTimeout;
    $table->AllowDataTranslation = true;
    $table->Controlable = true;
    $table->Updatable = true;
    $table->ViewAccess = \_::$User->AdminAccess;
    $table->ModifyAccess =
        $table->RemoveAccess =
        $table->DuplicateAccess =
        $table->AddAccess = \_::$User->SuperAccess;
    $deposit = $account_MDT->GetDepositAmount(false, \_::$Joint->Finance->Currency);
    $withdraw = $account_MDT->GetWithdrawAmount(false, \_::$Joint->Finance->Currency);
    $total = $deposit - $withdraw;
    return (getMethodIndex() === 1 ?
        Struct::Division(__("Account Dispute"), null, ["class" => "be center large"]) .
        Struct::Division(Struct::Number($total) . $unit, null, ["class" => "be center xxlarge"]) .
        Struct::Table([
            ["Deposit", "Withdraw"],
            [
                $deposit ? Struct::Division(Struct::Number($deposit) . $unit, ["class" => "be fore green large"]) : "-",
                $withdraw ? Struct::Division(Struct::Number($withdraw) . $unit, ["class" => "be fore red large"]) : "-"
            ]
        ], [], ["class" => "be align center", "style" => "background-color:transparent;"]) .
        Struct::$BreakLine : "") .
        $table->ToString();
};

(new Router())
    ->if(\_::$User->HasAccess(\_::$User->AdminAccess))
    ->Get(function () use ($routeHandler) {
        (\_::$Front->AdminView)($routeHandler, [
            "Image" => "scale-balanced",
            "Title" => "'Transactions' 'Management'"
        ]);
    })
    ->Default(fn() => response($routeHandler($data)))
    ->Handle();