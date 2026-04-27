<?php
namespace MiMFa\Library\Payment;

use MiMFa\Library\Convert;

library("payment/Port");
class ZarinPalPort extends Port
{
    public $Image = "/asset/payment/image/ZarinPal-Port.ico";
    
    public $TerminalKey = null;
    public $AcceptorKey = "merchant_id";
    public $PasswordKey = null;
    public $TransactionKey = "ref_id";
    public $CallbackKey = "callback_url";
    public $ReferrerKey = "referrer_id";
    public $CardKey = "card_pan";
    public $TokenKey = "authority";
    public $OrderKey = "order_id";
    public $PhoneKey = "mobile";


    public function __construct(
        $acceptor,
        $name = "ZarinPal",
        $title = "درگاه پرداخت زرین پل (ZarinPal)",
        $paymentPath = "https://payment.zarinpal.com/pg/StartPay/{authority}",
        $initiatePath = "https://payment.zarinpal.com/pg/v4/payment/request.json",
        $validatePath = "https://payment.zarinpal.com/pg/v4/payment/verify.json",
        $reversePath = "https://payment.zarinpal.com/pg/v4/payment/reverse.json"
    ) {
        parent::__construct(null, $acceptor, null, $name, $title, $paymentPath, $initiatePath, $validatePath, $reversePath, ["IRR", "IRT"]);
    }

    public function InitiateParams($data)
    {
        return [
            ...($this->TerminalKey && $this->Terminal ? [$this->TerminalKey => $this->Terminal] : []),
            ...($this->AcceptorKey && $this->Acceptor ? [$this->AcceptorKey => $this->Acceptor] : []),
            ...($this->PasswordKey && $this->Password ? [$this->PasswordKey => $this->Password] : []),
            
            ...(($v = get($data, "Amount")) && $this->AmountKey ? [$this->AmountKey => $v] : []),
            ...(($v = get($data, "Currency")) && $this->CurrencyKey ? [$this->CurrencyKey => $v] : []),
            ...(($v = get($data, "ReferrerId")) && $this->ReferrerKey ? [$this->ReferrerKey => $v] : []),
            ...(($v = get($data, "Description")) && $this->DescriptionKey ? [$this->DescriptionKey => Convert::ToExcerpt($v, 0, 490)] : []),
            ...(($v = get($data, "MetaData", "Callback")) && $this->CallbackKey ? [$this->CallbackKey => $v] : []),
            "metadata" => [
                ...(($v = get($data, "RelationId")) ? [$this->OrderKey => "/".get($data, "Relation")."/".$v] : []),
                ...(($v = get($data, "MetaData", "Phone")) ? [$this->PhoneKey => preg_replace("/.*([0-9]{10}$)/","0$1", "$v")] : []),
                ...(($v = get($data, "MetaData", "Email")) ? [$this->EmailKey => $v] : [])
            ]
        ];
    }
    public function ValidateParams($data)
    {
        return [
            ...($this->TerminalKey && $this->Terminal ? [$this->TerminalKey => $this->Terminal] : []),
            ...($this->AcceptorKey && $this->Acceptor ? [$this->AcceptorKey => $this->Acceptor] : []),
            ...($this->PasswordKey && $this->Password ? [$this->PasswordKey => $this->Password] : []),
            
            ...(($v = get($data, "MetaData", "Token")) && $this->TokenKey ? [$this->TokenKey => $v] : []),
            ...(($v = get($data, "Amount")) && $this->AmountKey ? [$this->AmountKey => $v] : []),
            ...(($v = get($data, "Currency")) && $this->CurrencyKey ? [$this->CurrencyKey => $v] : []),
        ];
    }

    public function GetFromResult($result, ...$hierarchy)
    {
        return get($result, "Data", ...$hierarchy);
    }

    public function GetErrors($result)
    {
        return get($result, $this->ErrorsKey);
    }

    public function GetFee($result)
    {
        return parent::GetFee($result) * 10;
    }

    public function IsActiveFee($result)
    {
        $this->GetFromResult($result, "Fee_Type") === "Merchant";
    }
}