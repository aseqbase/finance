<?php
namespace MiMFa\Library\Payment;

library("payment/Port");
class IranKishPort extends Port
{
    public $Image = "/asset/payment/image/IranKish-Port.ico";

    public $TerminalKey = "terminalId";
    public $AcceptorKey = "acceptorId";
    public $PasswordKey = "authenticationEnvelope";
    public $PaymentMethod = "POST";
    public $TokenKey = "tokenIdentity";
    public $CallbackKey = "revertUri";
    public $TransactionKey = "systemTraceAuditNumber";
    public $OrderKey = "requestId";
    public $AmountKey = "amount";
    public $CodeKey = "responseCode";
    public $ErrorsKey = "description";
    public $MessageKey = "description";
    public $DescriptionKey = "description";
    public $StatusKey = "status";

    public $CurrencyKey = "currency";
    public $CardKey = "card";
    public $FeeKey = "fee";
    public $FeeTypeKey = "fee_type";
    public $PhoneKey = "mobile";
    public $EmailKey = "email";


    public function __construct(
        $terminal,
        $acceptor,
        $password,
        $name = "IranKish",
        $title = "درگاه پرداخت ایران کیش (IranKish)",
        $paymentPath = "https://ikc.shaparak.ir/iuiv3/IPG/Index",
        $initiatePath = "https://ikc.shaparak.ir/api/v3/tokenization/make",
        $validatePath = "https://ikc.shaparak.ir/api/v3/confirmation/purchase",
        $reversePath = "https://ikc.shaparak.ir/api/v3/confirmation/reversePurchase"
    ) {
        parent::__construct($terminal, $acceptor, $password, $name, $title, $paymentPath, $initiatePath, $validatePath, $reversePath, ["IRR"]);
    }

    public function InitiateParams($data)
    {
        $a = get($data, "Amount");
        return [
            ...($this->TerminalKey && $this->Terminal ? [$this->TerminalKey => $this->Terminal] : []),
            ...($this->AcceptorKey && $this->Acceptor ? [$this->AcceptorKey => $this->Acceptor] : []),
            ...($this->PasswordKey && $this->Password ? [$this->PasswordKey => $this->HashPassword("-----BEGIN PUBLIC KEY-----
MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQC3YHBB32iBTKwxRmxe2gGSxFyT
PmSqqU0S74xXe2yFLtpR6SRVkL3o2c6jE1z01ojyO5kAb0OVi7ZFIKD4w42EfQ/c
CFbu6/HJDT5rq/H4hjfzCicb03mVr5s8nPJ573Lxc86HloqnJnWuTKlRi9nmpl27
kTcK/7O5TJJEN9mwKwIDAQAB
-----END PUBLIC KEY-----", $a)] : []),
            "transactionType" => "Purchase",
            "billInfo"=> null,
            ...($a && $this->AmountKey ? [$this->AmountKey => $a] : []),
            ...(($v = get($data, "Transaction")) ? ["paymentId" => $v] : []),
            ...(($v = get($data, "RelationId")) ? [$this->OrderKey => "/".get($data, "Relation")."/".$v] : []),
            "requestTimestamp" => time(),
            ...(($v = get($data, "MetaData", "Callback")) && $this->CallbackKey ? [$this->CallbackKey => $v] : []),
            ...(($v = (get($data, "MetaData", "Phone")??get($data, "MetaData", "Email"))) ? ["cmsPreservationId" => preg_replace("/^0/","98", "$v")] : []),
            
            //..(($v = get($data, "Description")) && $this->DescriptionKey ? [$this->DescriptionKey => Convert::ToExcerpt($v, 0, 490)] : []),
            //...(($v = get($data, "Currency")) && $this->CurrencyKey ? [$this->CurrencyKey => $v] : []),
        ];
    }
    public function PaymentParams($data)
    {
        return [
            ...(($v = get($data, "MetaData", "Token")) && $this->TokenKey ? [$this->TokenKey => $v] : []),
        ];
    }
    public function ValidateParams($data)
    {
        return [
            ...($this->TerminalKey && $this->Terminal ? [$this->TerminalKey => $this->Terminal] : []),
            ...($this->TransactionKey ? [$this->TransactionKey => receivePost($this->TransactionKey)] : []),
            "retrievalReferenceNumber" => receivePost('retrievalReferenceNumber'),
            
            ...(($v = get($data, "MetaData", "Token")) && $this->TokenKey ? [$this->TokenKey => $v] : []),
        ];
    }
    public function ReverseParams($data)
    {
        return [
            ...($this->TerminalKey && $this->Terminal ? [$this->TerminalKey => $this->Terminal] : []),
            ...($this->TransactionKey ? [$this->TransactionKey => receivePost($this->TransactionKey)] : []),
            "retrievalReferenceNumber" => receivePost('retrievalReferenceNumber'),
            
            ...(($v = get($data, "MetaData", "Token")) && $this->TokenKey ? [$this->TokenKey => $v] : []),
        ];
    }

    public function Token()
    {
        return receivePost('token');
    }
    public function HasAccess()
    {
        return receivePost($this->CodeKey) == "00";
    }
    public function IsSucceed($result)
    {
        return $this->GetCode($result) == "00";
    }

    public function GetFromResult($result, ...$hierarchy)
    {
        return get($result, "Result", ...$hierarchy);
    }

    public function GetToken($result)
    {
        return $this->GetFromResult($result, 'Token');
    }
    public function GetCode($result)
    {
        return get($result, $this->CodeKey);
    }
    public function GetErrors($result)
    {
        if (!$this->IsSucceed($result))
            return [get($result, "responseCode"),get($result, $this->ErrorsKey)];
    }

    public function HashPassword($pub_key, $amount)
    {
        $data = $this->Terminal . $this->Password . str_pad($amount, 12, '0', STR_PAD_LEFT) . '00';
        $data = hex2bin($data);
        $AESSecretKey = openssl_random_pseudo_bytes(16);
        $ivlen = openssl_cipher_iv_length($cipher = "AES-128-CBC");
        $iv = openssl_random_pseudo_bytes($ivlen);
        $ciphertext_raw = openssl_encrypt($data, $cipher, $AESSecretKey, OPENSSL_RAW_DATA, $iv);
        $hmac = hash('sha256', $ciphertext_raw, true);
        $crypttext = '';

        openssl_public_encrypt($AESSecretKey . $hmac, $crypttext, $pub_key);

        return array(
            "data" => bin2hex($crypttext),
            "iv" => bin2hex($iv),
        );
    }
}