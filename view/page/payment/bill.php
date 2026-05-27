<?php
use MiMFa\Library\finance\MetaDataTable;
use MiMFa\Library\Convert;
use MiMFa\Library\Struct;
use MiMFa\Library\Script;
$getted = receiveGet();
$posted = receive(\_::$Joint->Finance->PeymentMethod);
$r = get($getted, \_::$Joint->Finance->RelationRequestKey);
$rid = get($getted, \_::$Joint->Finance->RelationIdRequestKey);
$t = get($getted, \_::$Joint->Finance->TokenRequestKey);
$s = get($getted, \_::$Joint->Finance->SuccessRequestKey);
$e = get($getted, \_::$Joint->Finance->ErrorRequestKey);
$c = get($getted, \_::$Joint->Finance->CallbackRequestKey);
if (!$r || !$rid) {
    if ($t) {
        $bill = getSecret($t);
        if ($action = get($bill, "Action")) {
            $r = run(get($action, "Name"), get($action, "Data"));
            if ($r && is_array($r))
                set($bill, $r);
        }
        $n = get($bill, "Name");
        $r = get($bill, "Table") ?? "Finance_Invoice";
        $rid = table($r)->SelectValue("Id", "Id=:Id OR Name=:Name", [":Id" => get($bill, "Id") ?? $t, ":Name" => $n ?? $t]);
        if ($r === "Finance_Invoice" && $bill)
            $rid = table("Finance_Invoice")->Replace([
                ...($rid ? ["Id" => $rid] : []),
                "UserId" => get($bill, "UserId") ?: \_::$User->Id,
                "Name" => $nt = $n ?: $t,
                "Title" => (get($bill, "Title") ?: $n) ?: $rid,
                "Description" => get($bill, "Description"),
                "Content" => get($bill, "Content"),
                "Relation" => get($bill, "Relation") ?: "/finance/$r?id=" . ($rid ?: $nt),
                "Source" => get($bill, "Source") ?: \_::$User->Signature,
                "SourceData" => Convert::ToJson(get($bill, "SourceData") ?: [
                    ...(($v = \_::$User->GetValue("Contact")) ? ["Phone" => $v] : []),
                    ...(($v = \_::$User->Email) ? ["Email" => $v] : [])
                ]),
                "Amount" => get($bill, "Amount"),
                "Currency" => get($bill, "Currency"),
                "Platform" => get($bill, "Platform"),
                "Destination" => get($bill, "Destination"),
                "DestinationData" => Convert::ToJson(get($bill, "DestinationData") ?: [
                    ...(($c = get($bill, "Callback") ?: $c) ? ["Callback" => $c] : []),
                    ...(($s = get($bill, "Success") ?: $s) ? ["Success" => $s] : []),
                    ...(($e = get($bill, "Error") ?: $e) ? ["Error" => $e] : []),
                ]),
                "MetaData" => get($bill, "MetaData")
            ]);
    }
    if (!$r || !$rid)
        return $posted ? deliverError("Something went wrong!") : error("Something went wrong!");
    //elseif($t) popSecret($t);
}
library("finance/MetaDataTable");
$MDT = new MetaDataTable(\_::$Back->DataBase, $r, \_::$Back->DataBasePrefix, \_::$Back->DataTableNameConvertors);
$instance = $MDT->Data($rid);
$metaData = $MDT->MetaData($instance);

$p = $MDT->GetPrice($instance, $metaData);
$u = get($p, "Currency");
$a = get($p, "Amount");
$d = get($p, "Paid");

if ($a && $MDT->IsPaid($instance, $metaData))
    return $posted ? deliverError("This $r paid before!") : error("This $r paid before!");

library("finance/Account");
$account_MDT = new \MiMFa\Library\Finance\Account();
$balance = $account_MDT->GetBalanceAmount(\_::$User->Id, $u ?: \_::$Joint->Finance->Currency);
if (has($posted, \_::$Joint->Finance->SubmitRequestKey)) {
    $destId = \_::$User->Id ?? get($instance, "UserId");
    if ($a)
        if ($balance < 0 || get($posted, \_::$Joint->Finance->WalletRequestKey))
            $a -= min($a, $balance);
    if ($payment = \_::$Joint->Finance->GetPlatform(get($posted, \_::$Joint->Finance->PlatformRequestKey)))
        return $payment->Pay(
            $a,
            $u ?: \_::$Joint->Finance->Currency,
            $r,
            $rid,
            -1 * \_::$User->Id,
            $destId,
            null,
            null,
            get($p, "Data") ?? __("'$r':$rid"),
            \_::$User->GetValue("Contact"),
            \_::$User->Email,
            $s ?? $c ?? get($metaData, "Success"),
            $e ?? $c ?? get($metaData, "Error")
        );
} elseif (!$posted) {
    $pat = join(" ", [__("Pay"), Struct::Number($a), __($u ?? \_::$Joint->Finance->ShownCurrency), __("via") . ":"]);
    $pbt = join(" ", [__("Pay"), Struct::Number(($a - min($a, $balance))), __($u ?? \_::$Joint->Finance->ShownCurrency), __("via") . ":"]);

    module("PrePage");
    $module = new MiMFa\Module\PrePage();
    $module->Image = "credit-card";
    $module->Title = \_::$Joint->Finance->PaymentTitle;
    $module->Description = \_::$Joint->Finance->PaymentDescription;
    $pairs = \_::$Joint->Finance->GetPlatformPairs($a, $u ?: \_::$Joint->Finance->Currency);

    style("
        :is(.bill,.form.payment){
            display: flex;
            justify-content: space-between;
            align-content: stretch;
            flex-direction: column;
            align-items: stretch;
            gap: var(--size-0);
            max-width: 700px;
        }
        :is(.bill,.form.payment) .field{
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--size-0);
        }
    ");
    response(
        [
            $module->ToString(),
            Struct::Container(
                [
                    ...($balance < 0 ? [
                        Struct::Field("span", title: __("Debt") . ":", value: " " . Struct::Number(-1 * $balance) . __($u ?: \_::$Joint->Finance->ShownCurrency)),
                        Struct::Field("span", title: __($r) . ":", value: " " . Struct::Number($a) . __($u ?: \_::$Joint->Finance->ShownCurrency))
                    ] : []),
                    Struct::Field("span", title: __("Total") . ":", value: " " . Struct::Number($balance < 0 ? $a - $balance : $a) . __($u ?: \_::$Joint->Finance->ShownCurrency), attributes: ["class" => "be bold"]),
                    Struct::Field("span", title: __("Paid") . ":", value: " " . Struct::Number($d) . __($u ?: \_::$Joint->Finance->ShownCurrency)),
                ],
                [
                    "Class" => "bill"
                ]
            ) .
            Struct::$BreakLine .
            Struct::Form(
                [
                    Struct::Field("switch", \_::$Joint->Finance->WalletRequestKey, false, title: join(" ", [__("Pay "), Struct::Number(min($a, $balance)) . __($u ?: \_::$Joint->Finance->ShownCurrency), __("by wallet")]), attributes: [
                        "Class" => "fa-2x",
                        "onchange" => "document.querySelector('.form.payment .field:has(.input[name=\"Platform\"]) .title').innerHTML = this.checked?" . Script::Convert($pbt) . ":" . Script::Convert($pat) . ";",
                    ]),
                    Struct::Field(
                        "select",
                        \_::$Joint->Finance->PlatformRequestKey,
                        get($getted, \_::$Joint->Finance->PlatformRequestKey),
                        title: $pat,
                        options: $pairs,
                        attributes: [
                        ]
                    ),
                    ...((isEmpty($a) || !$pairs)?[]:[Struct::SubmitButton(\_::$Joint->Finance->SubmitRequestKey, "Pay", ["class" => "main"])])
                ],
                rtrim(\_::$Joint->Finance->PaymentUrlPath, "\/\\") . "?" .
                join("&", loop([
                    \_::$Joint->Finance->TokenRequestKey => $t,
                    \_::$Joint->Finance->RelationRequestKey => $r,
                    \_::$Joint->Finance->RelationIdRequestKey => $rid,
                    \_::$Joint->Finance->SuccessRequestKey => $s,
                    \_::$Joint->Finance->ErrorRequestKey => $e,
                    \_::$Joint->Finance->CallbackRequestKey => $c
                ], fn($v, $k) => isValid($v) ? "$k=" . urlencode($v) : null)),
                [
                    "Method" => \_::$Joint->Finance->PeymentMethod,
                    "Class" => "payment container",
                    "Interaction" => true
                ]
            )
        ]
    );
} else
    return deliverWarning("Please try again");