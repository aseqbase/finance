<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Struct;

if (!auth(\_::$User->UserAccess))
    return;

module("PrePage");
$module = new MiMFa\Module\PrePage();
$module->Title = "Wallet";
$module->Image = "wallet";

library("finance/Account");
$account_MDT = new \MiMFa\Library\Finance\Account();

module("Table");
$table = new MiMFa\Module\Table(table("Finance_Account")->AS("A")
    ->Join(table("User")->AS("SU"), "SU.Id=ABS(SourceId)")
    ->Join(table("User")->AS("DU"), "DU.Id=ABS(DestinationId)")
    ->OrderBy("A.Id", false));
$table->KeyColumn = "A.Id";
$table->SelectCondition = "(SourceId=:Id OR DestinationId=:Id) AND (Currency IS NULL OR Currency=:Currency)";
$table->SelectParameters = [":Id" => \_::$User->Id, ":Currency" => \_::$Joint->Finance->Currency];
$table->IncludeColumns = ["UpdateTime" => "A.UpdateTime", "Tracking Code" => "Transaction", "Amount" => "A.Amount", "Currency" => "A.Currency", "IsDeposited" => "A.DestinationId=:Id", "Status" => "A.Status", "From" => "SU.Name", "To" => "DU.Name", "FromSignature" => "SU.Signature", "ToSignature" => "DU.Signature", "For" => "A.Relation", "Relation" => "A.Relation", "RelationId" => "A.RelationId", "Description" => "A.Description"];
$table->ExcludeColumns = ["Currency", "Relation", "RelationId", "IsDeposited", "FromSignature", "ToSignature"];
$unit = __(\_::$Joint->Finance->ShownCurrency);
$table->CellsValues = [
    "Amount" => function ($v, $k, $r) use ($unit) {
        return Struct::Number($v, ["class" => "be fore " . ($r["IsDeposited"] ? "green" : "red")]) . $unit; },
    "From" => function ($v, $k, $r) {
        return $r["FromSignature"] ? Struct::Link($v, \_::$Address->UserRootUrlPath . $r["FromSignature"]) : Struct::Link(\_::$Front->Name, \_::$Front->DirectPath); },
    "To" => function ($v, $k, $r) {
        return $r["ToSignature"] ? Struct::Link($v, \_::$Address->UserRootUrlPath . $r["ToSignature"]) : Struct::Link(\_::$Front->Name, \_::$Front->DirectPath); },
    "For" => function ($v, $k, $r) {
        return $v ? Struct::Link($v, "/finance/{$r["Relation"]}?id={$r["RelationId"]}") : ""; },
    "Status" => function ($v) {
        return Struct::Span($v, null, ["class" => startsWith($v, "Error", "Fail", "Re") ? "be fore red" : (startsWith($v, "Succe") ? "be fore green" : "be fore yellow")]); },
    "CreateTime" => fn($v) => Convert::ToShownDateTimeString($v)
];
$table->RefreshTimeout = \_::$Front->RefreshTimeout;
$table->AllowDataTranslation = true;
$table->Controlable =
    $table->Updatable = false;
$table->ViewAccess =
    $table->RemoveAccess =
    $table->ModifyAccess =
    $table->DuplicateAccess =
    $table->AddAccess = \_::$User->SuperAccess;
$deposit = $account_MDT->GetSuccessDepositAmount(\_::$User->Id, \_::$Joint->Finance->Currency);
$pending = $account_MDT->GetPendingAmount(\_::$User->Id, \_::$Joint->Finance->Currency);
$pledges = $account_MDT->GetPledgeAmount(\_::$User->Id, \_::$Joint->Finance->Currency);
$withdraw = $account_MDT->GetSuccessWithdrawAmount(\_::$User->Id, \_::$Joint->Finance->Currency);
$balance = $account_MDT->GetBalanceAmount(\_::$User->Id, \_::$Joint->Finance->Currency);
$module->Description =
    Struct::Division(__("Account Balance"), null, ["class" => "be center large"]) .
    Struct::Division(Struct::Number($balance) . $unit, null, ["class" => "be center xxlarge"]) .
    Struct::Table([
        ["Deposit", "Pending", "Pledges", Struct::Button(__("Withdraw") . " " . Struct::Icon("arrow-up"), \_::$Joint->Finance->WithdrawUrlPath, ["style" => "flex-direction: row;"])],
        [
            $deposit ? Struct::Division(Struct::Number($deposit) . $unit, ["class" => "be fore green large"]) : "-",
            $pending ? Struct::Division(Struct::Number($pending) . $unit, ["class" => "be fore yellow large"]) : "-",
            $pledges ? Struct::Division(Struct::Number($pledges) . $unit, ["class" => "be fore magenta large"]) : "-",
            $withdraw ? Struct::Division(Struct::Number($withdraw) . $unit, ["class" => "be fore red large"]) : "-"
        ]
    ], [], ["class" => "be align center", "style" => "background-color:transparent;"]);
$module->Render();
response(Struct::Container($table->Handle()));