<?php

use MiMFa\Library\Finance\Account;
use MiMFa\Library\Struct;

if (!auth(\_::$User->UserAccess))
    return;
$card = \_::$User->GetMetaValue("CreditCard");
$iban = \_::$User->GetMetaValue("IBAN");
if(!$card || !$iban) return page(\_::$Joint->Finance->CardsUrlPath);

$getted = receiveGet();
$posted = receivePost();

library("finance/Account");
$ARAccount = new Account();
//if($ARAccount->GetPendingTransactions(\_::$User->Id, $u)) return deliverRedirect(Struct::Error("You have 'pended' 'transaction'!"),null, "/finance/wallet");

$u = get($posted, "Currency");
$a = get($posted, "Amount");
$d = get($posted, "Paid");
$balance = $ARAccount->GetBalanceAmount(\_::$User->Id, $u?:\_::$Joint->Finance->Currency);
$step = 50000;

if ($balance >= $a && $method = has($posted, "Submit")) {
    if($res = $ARAccount->SetTransaction(
        amount: $a,
        from: \_::$User->Id,
        to: -1 * \_::$User->Id,
        relation: null,
        relationId: null,
        description: join(PHP_EOL,[
            preg_replace("/(\d{4})(\d{4})(\d{4})(\d{4})/","CARD: $1 $2 $3 $4", $card),
            "IBAN: $iban",
            \_::$User->GetValue("FirstName")." ".\_::$User->GetValue("LastName")
        ]),
        status: Account::$WithdrawingStatus,
        currency: $u?:\_::$Joint->Finance->Currency
    )) return deliverRedirect(Struct::Success("Your 'transaction' 'set' successfully"), "/finance/wallet");
    return deliverError("Your 'transaction' is not 'successfull'");
} elseif (!$posted) {
    $pat = join(" ", [__("Pay"), Struct::Number($a), __($u ?: \_::$Joint->Finance->ShownCurrency), __("via") . ":"]);
    $pbt = join(" ", [__("Pay"), Struct::Number(($a - min($a, $balance))), __($u ?: \_::$Joint->Finance->ShownCurrency), __("via") . ":"]);

    module("PrePage");
    $module = new MiMFa\Module\PrePage();
    $module->Image = "money-bill-wave";
    $module->Title = "Withdraw";
    $aid = uniqid( "a_");
    response(
        [
            $module->ToString(),
            Struct::Style("
        .form.payment{
            display: flex;
            justify-content: space-between;
            align-content: stretch;
            flex-direction: column;
            align-items: stretch;
            gap: var(--size-0);
            width: auto;
            max-width: 700px;
        }
        .form.payment .field{
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: var(--size-0);
        }
                [
        "),
            Struct::Form(
                [
                    Struct::Field("span", title: __("Balance") . ":", value: " " . Struct::Number($ARAccount->GetTotalAmount(\_::$User->Id, $u ?: \_::$Joint->Finance->Currency)) . " " . __($u ?: \_::$Joint->Finance->ShownCurrency)),
                    Struct::Field("span", title: __("Pended") . ":", value: " " . Struct::Number($ARAccount->GetPendingWithdrawAmount(\_::$User->Id, $u ?: \_::$Joint->Finance->Currency)) . " " . __($u ?: \_::$Joint->Finance->ShownCurrency)),
                    Struct::Field("span", title: __("Leviable") . ":", value: " " . Struct::Number($balance < 0? 0 : ($balance - $balance%$step)) . " " . __($u ?: \_::$Joint->Finance->ShownCurrency), attribute: ["class" => "be bold", "onclick"=>"_('#$aid input').val(".($balance - $balance%$step).")"]),
                    Struct::Field("number", "Amount",  title: __("Amount") . ":", value: 0, description:$u?:\_::$Joint->Finance->ShownCurrency, options:[0, $balance], attributes: [
                        "class" => "be bold",
                        "step" => $step,
                        "Wrapper"=>["id"=>$aid]
                    ]),
                    Struct::Field("span", title: __("'Deposit' to") . ":", value: $iban),
                    Struct::SubmitButton("Submit", "Withdraw", ["class" => "main"])
                ],
                \_::$Address->Url,
                [
                    "Method" => "POST",
                    "Class" => "payment container",
                    "interaction" => true
                ]
            )
        ]
    );
} else
    return deliverWarning("Please try again");