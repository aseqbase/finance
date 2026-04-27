<?php
namespace MiMFa\Library\Payment;
use MiMFa\Library\Convert;
use MiMFa\Library\Finance\MetaDataTable;
use MiMFa\Library\Finance\Account;
use MiMFa\Library\Struct;

class Port
{
    /**
     * The terminal Id
     * @var 
     */
    public $Terminal;
    public $TerminalKey = "terminal";
    /**
     * The acceptor Id
     * @var 
     */
    public $Acceptor;
    public $Password;
    public $Image = "credit-card";
    public $Name = "Port";
    public $Title = "PaymentPort";
    public $Description = "Select to pay by this port";

    /**
     * To register the data
     * @var 
     */
    public $InitiatePath;
    public $InitiateMethod = "POST";
    /**
     * The pay path pattern
     * @var 
     */
    public $PaymentPath;
    public $PaymentMethod = "GET";
    /**
     * The validate path
     * @var 
     */
    public $ValidatePath;
    public $ValidateMethod = "POST";
    /**
     * The path to reverse the payment
     * @var 
     */
    public $ReversePath;
    public $ReverseMethod = "POST";

    public $AcceptableCurrencies;
    public $AcceptableStatuses = ["OK", "ok", "True", "true", "1"];

    public $AcceptorKey = "acceptor";
    public $PasswordKey = "password";
    public $TokenKey = "token";
    public $StatusKey = "status";
    public $TransactionKey = "transaction";
    public $AmountKey = "amount";
    public $CurrencyKey = "currency";
    public $ReferrerKey = "referrer";
    public $CodeKey = "code";
    public $MessageKey = "message";
    public $ErrorsKey = "errors";
    public $CardKey = "card";
    public $FeeKey = "fee";
    public $FeeTypeKey = "fee_type";
    public $CallbackKey = "callback";
    public $DescriptionKey = "description";
    public $PhoneKey = "phone";
    public $EmailKey = "email";

    public $PaidMessage = "This 'invoice' is 'paid' successfully!";
    public $WaitMessage = "This 'invoice' is not 'ready' yet!";
    public $SuccessMessage = "Thanks, `your 'transaction' is 'successfully' 'completed'`";
    public $ErrorMessage = "Something went wrong!";
    public $CancelMessage = "It seams your payment is failed or canceled.";

    /**
     * To construct a new PaymentPort instance.
     * @param mixed $port The client identifier for the payment gateway.
     * @param mixed $title The title of the payment gateway.
     * @param mixed $payPath
     * @param mixed $setPath
     * @param mixed $getPath
     * @param mixed $popPath
     * @param mixed $currency
     */
    public function __construct(
        $terminal,
        $acceptor,
        $password,
        $name,
        $title,
        $paymentPath,
        $initiatePath,
        $validatePath,
        $reversePath,
        $acceptableCurrencies = null
    ) {
        $this->Terminal = $terminal;
        $this->Acceptor = $acceptor;
        $this->Password = $password;
        $this->Name = $name;
        $this->Title = $title;
        $this->PaymentPath = $paymentPath;
        $this->InitiatePath = $initiatePath;
        $this->ValidatePath = $validatePath;
        $this->ReversePath = $reversePath;
        $this->AcceptableCurrencies = $acceptableCurrencies ?? [];
    }

    public function Pay(
        $amount = null,
        $currency = null,
        $relation = null,
        $relationId = null,
        $sourceId = null,
        $destinationId = null,
        $referrerId = null,
        $callbackUrl = null,
        $description = null,
        $phone = null,
        $email = null,
        $onSuccess = null,
        $onError = null
    ) {
        if ($token = $this->Token())
            return $this->ReceivePayment($token);
        else
            return $this->SendPayment(
                $amount,
                $currency,
                $relation,
                $relationId,
                $sourceId,
                $destinationId,
                $referrerId,
                $callbackUrl,
                $description,
                $phone,
                $email,
                $onSuccess,
                $onError
            );
    }
    public function SendPayment($amount = null, $currency = null, $relation = null, $relationId = null, $sourceId = null, $destinationId = null, $referrerId = null, $callbackUrl = null, $description = null, $phone = null, $email = null, $onSuccess = null, $onError = null)
    {
        if (!$this->Acceptable($amount, $currency))
            return deliverError("'$this->Title' could not exchange the '$amount$currency'!");
        $callbackUrl = $callbackUrl ?: (
            path(\_::$Address->PageRootDirectory . "payment" . DIRECTORY_SEPARATOR . "callback" . DIRECTORY_SEPARATOR . $this->Name, null) ?
            \_::$Address->UrlOrigin . "/payment/callback/" . $this->Name :
            \_::$Address->UrlBase
        );
        $data = [
            "Transaction" => uniqid("P" . \_::$User->Id . "U"),
            "Status" => Account::$BeginingStatus,
            "Amount" => $amount,
            "Currency" => $currency,
            "Relation" => $relation,
            "RelationId" => $relationId,
            "SourceId" => $sourceId,
            "DestinationId" => $destinationId,
            "Description" => $description,
            "Platform" => $this->Name,
            "MetaData" => [
                "Phone" => $phone,
                "Email" => $email,
                ...($relation === "Invoice" ? ["InvoiceId" => $relationId] : []),
                "ReferrerId" => $referrerId,
                "Callback" => $callbackUrl,
                "Success" => $onSuccess,
                "Error" => $onError
            ]
        ];

        if (!$this->Invoice($data))
            return false;
        
        if (isEmpty($amount)) 
            return deliverRedirect(
                Struct::Warning($this->WaitMessage),
                "/finance/".($relation??"invoice")."?Id=$relationId"
            );
        elseif ($amount <= 0) {
            $token = $data["MetaData"]["Token"] = $data["MetaData"]["Token"] ?? getId(true);
            $data["Status"] = Account::$SucceedStatus;
            setSecret(getClientCode($token), $data);
            if ($id = $this->Succeed($data)) {
                $data["Id"] = $data["Id"] ?? $id;
                setSecret(getClientCode($token), $data);
                return $this->RenderSucceed($data);
            }
            return deliverRedirect(
                Struct::Warning($this->PaidMessage),
                "/finance/".($relation??"invoice")."?Id=$relationId"
            );
        } else {
            $result = $this->Initiate($data);
            $data["MetaData"]["Errors"] = $this->GetErrors($result) ?? [];
            if ($token = $this->GetToken($result)) {
                $data["MetaData"]["Token"] = $token;
                $data["MetaData"]["Code"] = $this->GetCode($result);
                $data["MetaData"]["Message"] = $this->GetMessage($result);
                $data["MetaData"]["Fee"] = [
                    "Amount" => $this->GetFee($result),
                    "Active" => $this->IsActiveFee($result)
                ];
                setSecret(getClientCode($token), $data);
                return $this->Payment($data);
            } else
                return deliverError(["Could not create a token!", ...$data["MetaData"]["Errors"]]);
        }
    }
    public function ReceivePayment($token = null)
    {
        $token = $token ?? $this->Token();
        if (!$token)
            return false;
        $data = getSecret(getClientCode($token));
        $data["Status"] = Account::$WaitingStatus;

        if ($this->HasAccess() && get($data, "MetaData", "Token") == $token) {
            $data["MetaData"]["Token"] = $token;
            $a = get($data, "Amount");
            if (isEmpty($a))
                $data["Status"] = Account::$BeginingStatus;
            elseif (($a = floatVal($a)) <= 0)
                $data["Status"] = Account::$SucceedStatus;
            else {
                $result = $this->Validate($data);
                $data["MetaData"]["Errors"] = [...($data["MetaData"]["Errors"] ?? []), ...(get($result, "Errors") ?? [])];
                $data["MetaData"]["Code"] = $this->GetCode($result);
                $data["MetaData"]["Message"] = $this->GetMessage($result) ?? "";
                if ($this->IsSucceed($result)) {
                    $data["Status"] = Account::$SucceedStatus;
                    if ($data["MetaData"]["Errors"])
                        error($data["MetaData"]["Errors"]);

                    $data["Transaction"] = $data["Platform"] . "-" . $this->GetTransaction($result);
                    $data["MetaData"]["Details"] = trim(join(
                        PHP_EOL,
                        [
                            get($data, "Description"),
                            __("Card") . ": " . $this->GetCard($result),
                            __("Fee") . ": " . $this->GetFee($result),
                            __("Payer") . ": " . $this->GetFeeType($result)
                        ]
                    ));

                    $data["MetaData"]["Fee"] = [
                        "Amount" => $this->GetFee($result),
                        "Active" => $this->IsActiveFee($result)
                    ];
                } else
                    $data["Status"] = Account::$FailedStatus;
                setSecret(getClientCode($token), $data);
            }

            if ($id = $this->Succeed($data)) {
                $data["Id"] = $data["Id"] ?? $id;
                setSecret(getClientCode($token), $data);
                $this->RenderSucceed($data);
                return true;
            } else { // Reverse the Transaction
                $data["Status"] = Account::$ReversingStatus;
                if ($a <= 0)
                    $data["Status"] = Account::$ReversedStatus;
                else {
                    $data["MetaData"]["Token"] = $token;
                    $result = $this->Reverse($data);
                    $data["MetaData"]["Errors"] = [...($data["MetaData"]["Errors"] ?? []), ...($this->GetErrors($result) ?? [])];
                    $data["MetaData"]["Code"] = $this->GetCode($result);
                    $data["MetaData"]["Message"] = $this->GetMessage($result) ?? "";
                    if ($this->IsSucceed($result))
                        $data["Status"] = Account::$ReversedStatus;
                }
            }
            $data["Description"] = join(PHP_EOL, [...(($data["MetaData"]["Errors"] ?? null) ? $data["MetaData"]["Errors"] : []), ...(($data["Description"] ?? null) ? [$data["Description"]] : [])]);
        } else
            $data["Status"] = Account::$FailedStatus;
        if ($id = $this->Failed($data)) {
            $data["Id"] = $data["Id"] ?? $id;
            setSecret(getClientCode($token), $data);
            $this->RenderFailed($data);
        } else
            setSecret(getClientCode($token), $data);
        return false;
    }

    public function Invoice(&$transaction, $paid = null, $currency = null)
    {
        library("finance/MetaDataTable");
        $MDT = new MetaDataTable(\_::$Back->DataBase, "Invoice", \_::$Back->DataBasePrefix, \_::$Back->DataTableNameConvertors);
        $invoice = [];
        if ($invoiceId = $transaction["MetaData"]["InvoiceId"] ?? null) {
            $invoice = $MDT->Get($invoiceId);
            $invoice["MetaData"] = $invoice["MetaData"]?:$transaction["MetaData"];
            $invoice["Name"] = $invoice["Name"] ?: $transaction["Transaction"];
            $invoice["Description"] = $invoice["Description"] ?: $transaction["Description"];
            $invoice["Relation"] = $invoice["Relation"] ?: ("/" . $transaction["Relation"] . "/" . $transaction["RelationId"]);
            $invoice["Source"] = $transaction["SourceId"]??$invoice["Source"];
            $invoice["SourceData"] = Convert::FromJson($invoice["SourceData"]) ?? [];
            $invoice["SourceData"]["Phone"] = $transaction["MetaData"]['Phone'] = $invoice["SourceData"]["Phone"] ?? $transaction["MetaData"]['Phone'];
            $invoice["SourceData"]["Email"] = $transaction["MetaData"]['Email'] = $invoice["SourceData"]["Email"] ?? $transaction["MetaData"]['Email'];
            $invoice["SourceData"]["Referrer"] = $transaction["MetaData"]["ReferrerId"] = $invoice["SourceData"]["Referrer"] ?? $transaction["MetaData"]["ReferrerId"];
            $invoice["Amount"] = $invoice["Amount"] ?: $transaction["Amount"];
            $invoice["Currency"] = $invoice["Currency"] ?: $transaction["Currency"];
            $invoice["Platform"] = $invoice["Platform"] ?: $transaction["Platform"];
            $invoice["Destination"] = $transaction["DestinationId"]??$invoice["Destination"];
            $invoice["DestinationData"] = Convert::FromJson($invoice["DestinationData"]) ?? [];
            $invoice["DestinationData"]["Callback"] = ($transaction["MetaData"]["Callback"] = ($transaction["MetaData"]["Callback"] ?? null ?: ($invoice["DestinationData"]["Callback"] ?? null)));
            $invoice["DestinationData"]["Success"] = ($transaction["MetaData"]["Success"] = ($transaction["MetaData"]["Success"] ?? null ?: ($invoice["DestinationData"]["Success"] ?? null)));
            $invoice["DestinationData"]["Error"] = ($transaction["MetaData"]["Error"] = ($transaction["MetaData"]["Error"] ?? null ?: ($invoice["DestinationData"]["Error"] ?? null)));
        } else {
            $invoice = [
                "UserId" => \_::$User->Id,
                "Name" => $transaction["Transaction"],
                "Description" => $transaction["Description"],
                "Relation" => "/" . $transaction["Relation"] . "/" . $transaction["RelationId"],
                "Source" => $transaction["SourceId"],
                "SourceData" => [
                    "Phone" => $transaction["MetaData"]['Phone'],
                    "Email" => $transaction["MetaData"]['Email'],
                    "Referrer" => $transaction["MetaData"]["ReferrerId"],
                ],
                "Amount" => $transaction["Amount"],
                "Currency" => $transaction["Currency"],
                "Platform" => $transaction["Platform"],
                "Destination" => $transaction["DestinationId"],
                "DestinationData" => [
                    "Callback" => $transaction["MetaData"]["Callback"],
                    "Success" => $transaction["MetaData"]["Success"],
                    "Error" => $transaction["MetaData"]["Error"]
                ],
                "MetaData" => $transaction["MetaData"]
            ];
        }

        if (!is_null($paid)) {
            if (is_numeric($paid)) {
                if (!$invoice["Transactions"])
                    $invoice["Transactions"] = [];
                $invoice["Transactions"][$transaction["Transaction"]] = $MDT->Pay($invoice, $invoice["MetaData"], $paid, $currency);
            }
            switch ($MDT->IsPaid($invoice, $invoice["MetaData"])) {
                case null:
                    if (is_numeric($paid) && $paid > 0)
                        $invoice["Status"] = "Installing";
                    else
                        $invoice["Status"] = "Pending";
                    break;
                case true:
                    $invoice["Status"] = "Paid";
                    break;
                case false:
                    if (!$paid)
                        $invoice["Status"] = "Failed";
                    break;
            }
        }

        pop($invoice["MetaData"],"Email");
        pop($invoice["MetaData"],"Phone");
        pop($invoice["MetaData"],"ReferrerId");
        pop($invoice["MetaData"],"Callback");
        pop($invoice["MetaData"],"Success");
        pop($invoice["MetaData"],"Error");

        if ($invoiceId)
            if ($MDT->Set($invoiceId, $invoice))
                return true;
            else
                error("Could not update the invoice!");
        elseif ($invoiceId = $transaction["MetaData"]["InvoiceId"] = $MDT->Insert($invoice))
            return true;
        else
            error("Could not create the invoice!");
        return false;
    }

    protected function Succeed($transaction)
    {
        if (!get($transaction, "MetaData", "Token"))
            return false;
        if ($transaction["Relation"] && $transaction["RelationId"]) {
            library(name: "finance\MetaDataTable");
            $MDT = new MetaDataTable(\_::$Back->DataBase, $transaction["Relation"], \_::$Back->DataBasePrefix, \_::$Back->DataTableNameConvertors);
            $invoice = $MDT->Data($transaction["RelationId"]);
            if (!$invoice)
                return false;

            $metaData = $MDT->MetaData($invoice["Id"]);
            $price = $MDT->GetPrice($invoice, $metaData);
            if ((get($price, "Amount")?:0)>0 && $MDT->IsPaid($invoice, $metaData))
                return false;

            unset($transaction["MetaData"]["Callback"]);
            unset($transaction["MetaData"]["Success"]);
            unset($transaction["MetaData"]["Error"]);
            if (table("Account")->Insert($transaction)) {
                $fee = get($transaction, "MetaData", "Fee");
                $feeAmount = get($fee, "Amount");
                if ($feeAmount && get($fee, "Active"))
                    table("Account")->Insert([
                        "Transaction" => uniqid("F" . \_::$User->Id . "U"),
                        "Amount" => $feeAmount,
                        "Currency" => \_::$Joint->Finance->Currency,
                        "Relation" => "Account",
                        "RelationId" => table("Account")->Last("Id", "Transaction=:TR", [":TR" => get($transaction, "Transaction")]),
                        "SourceId" => \_::$User->Id,
                        "DestinationId" => \_::$Joint->Finance->PlatformAccount,
                        "Description" => "Fee",
                        "Platform" => get($transaction, "Platform")
                    ]);
                $transaction["Transaction"] = uniqid("B" . \_::$User->Id . "U");
                $transaction["Amount"] = \_::$Joint->Finance->StandardCurrency($invoice["Amount"], $invoice["Currency"]);
                $transaction["Currency"] = \_::$Joint->Finance->Currency;
                $transaction["SourceId"] = \_::$User->Id;
                $transaction["DestinationId"] = \_::$Joint->Finance->PlatformAccount;
                $transaction["Platform"] = \_::$Front->Name;
                if ($id = table("Account")->Insert($transaction)) {
                    $this->Invoice($transaction, $invoice["Amount"], $invoice["Currency"]);
                    return $id;
                }
            }
        }
        return false;
    }
    protected function Failed($transaction)
    {
        unset($transaction["MetaData"]["Callback"]);
        unset($transaction["MetaData"]["Success"]);
        unset($transaction["MetaData"]["Error"]);
        if ($id = table("Account")->Insert($transaction)) {
            if (strtolower($transaction["Relation"] ?? "") !== "invoice")
                $this->Invoice($transaction, false);
            return $id;
        } else
            return false;
    }

    public function RenderSucceed($transaction)
    {
        return compute("payment/succeed", [
            "Token" => get($transaction, "MetaData", "Token"),
            "Transaction" => get($transaction, "Transaction"),
            "Description" => Struct::Success($this->SuccessMessage)
        ]);
    }
    public function RenderFailed($transaction)
    {
        return compute("payment/failed", [
            "Token" => get($transaction, "MetaData", "Token"),
            "Transaction" => get($transaction, "Transaction")
        ]);
    }

    public function Connect($method, $url, $params = [], $message = "")
    {
        if (!$url || !$method)
            return null;
        if (strtolower($method) === "get") {
            foreach ($params as $k => $v)
                $url = str_replace("{{$k}}", $v, $url);
            return deliverRedirect($message, $url);
        } else
            return Convert::FromJson(send(
                $method,
                $url,
                $data_string = Convert::ToJson($params, JSON_ERROR_NONE | JSON_OBJECT_AS_ARRAY | JSON_PRESERVE_ZERO_FRACTION),
                [
                    CURLOPT_ENCODING => '',
                    CURLOPT_MAXREDIRS => 10,
                    CURLOPT_FOLLOWLOCATION => true,
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1
                ],
                [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($data_string),
                    'Accept: application/json'
                ],
                timeout: 0
            ));
    }
    public function Initiate($data)
    {
        return $this->Connect(
            $this->InitiateMethod,
            $this->InitiatePath,
            $this->InitiateParams($data),
            (
                ($data["MetaData"]["Errors"] ?? null) ?
                Struct::Error($data["MetaData"]["Errors"]) : ""
            ) .
            Struct::Message("Waiting to 'initiate' the 'terminal'") . "..."
        );
    }
    public function InitiateParams($data)
    {
        return [
            ...($this->TerminalKey && $this->Terminal ? [$this->TerminalKey => $this->Terminal] : []),
            ...($this->AcceptorKey && $this->Acceptor ? [$this->AcceptorKey => $this->Acceptor] : []),
            ...($this->PasswordKey && $this->Password ? [$this->PasswordKey => $this->Password] : []),

            ...(($v = get($data, "Amount")) && $this->AmountKey ? [$this->AmountKey => $v] : []),
            ...(($v = get($data, "Currency")) && $this->CurrencyKey ? [$this->CurrencyKey => $v] : []),
            ...(($v = get($data, "MetaData", "ReferrerId")) && $this->ReferrerKey ? [$this->ReferrerKey => $v] : []),
            ...(($v = get($data, "Description")) && $this->DescriptionKey ? [$this->DescriptionKey => Convert::ToExcerpt($v, 0, 490)] : []),
            ...(($v = get($data, "MetaData", "Callback")) && $this->CallbackKey ? [$this->CallbackKey => $v] : []),
            ...(($v = get($data, "MetaData", "Phone")) ? [$this->PhoneKey => "$v"] : []),
            ...(($v = get($data, "MetaData", "Email")) ? [$this->EmailKey => $v] : [])
        ];
    }
    public function Payment($data)
    {
        return $this->Connect(
            $this->PaymentMethod,
            $this->PaymentPath,
            $this->PaymentParams($data),
            (($data["MetaData"]["Errors"] ?? null) ? Struct::Error($data["MetaData"]["Errors"]) : "") . Struct::Message("Waiting to 'transfer' to the 'terminal'") . "...",
        );
    }
    public function PaymentParams($data)
    {
        return [
            ...(($v = get($data, "MetaData", "Token")) && $this->TokenKey ? [$this->TokenKey => $v] : []),
        ];
    }
    public function Validate($data)
    {
        return $this->Connect(
            $this->ValidateMethod,
            $this->ValidatePath,
            $this->ValidateParams($data),
            (($data["MetaData"]["Errors"] ?? null) ? Struct::Error($data["MetaData"]["Errors"]) : "") . Struct::Message("Waiting to 'verify' the 'transaction'") . "...",
        );
    }
    public function ValidateParams($data)
    {
        return [
            ...($this->TerminalKey && $this->Terminal ? [$this->TerminalKey => $this->Terminal] : []),
            ...($this->AcceptorKey && $this->Acceptor ? [$this->AcceptorKey => $this->Acceptor] : []),
            ...($this->PasswordKey && $this->Password ? [$this->PasswordKey => $this->Password] : []),

            ...(($v = get($data, "MetaData", "Token")) && $this->TokenKey ? [$this->TokenKey => $v] : []),
        ];
    }
    public function Reverse($data)
    {
        return $this->Connect(
            $this->ReverseMethod,
            $this->ReversePath,
            $this->ReverseParams($data),
            (($data["MetaData"]["Errors"] ?? null) ? Struct::Error($data["MetaData"]["Errors"]) : "") . Struct::Message("Waiting to 'reverse' the 'transaction'") . "...",
        );
    }
    public function ReverseParams($data)
    {
        return [
            ...($this->TerminalKey && $this->Terminal ? [$this->TerminalKey => $this->Terminal] : []),
            ...($this->AcceptorKey && $this->Acceptor ? [$this->AcceptorKey => $this->Acceptor] : []),
            ...($this->PasswordKey && $this->Password ? [$this->PasswordKey => $this->Password] : []),

            ...(($v = get($data, "MetaData", "Token")) && $this->TokenKey ? [$this->TokenKey => $v] : []),
        ];
    }

    public function Token()
    {
        return received($this->TokenKey);
    }
    public function HasAccess()
    {
        return in_array(received($this->StatusKey) ?? "", $this->AcceptableStatuses);
    }
    /**
     * To check if the payment was successful based on the gateway response.
     * @param mixed $result The response received from the payment gateway.
     * @return bool True if the payment was successful, false otherwise.
     */
    public function IsSucceed($result)
    {
        $code = $this->GetFromResult($result, "Code");
        return $code === 100 || $code === 101;
    }

    public function Acceptable($amount = null, $currency = null)
    {
        if (!$amount || !$currency || !$this->AcceptableCurrencies || in_array($currency, $this->AcceptableCurrencies))
            return true;
        return false;
    }

    /**
     * To get a specific result from the payment gateway response.
     * @param mixed $result The response received from the payment gateway.
     * @param mixed $hierarchy The key to extract the desired value from the response.
     * @return mixed The extracted value corresponding to the provided key.
     */
    public function GetFromResult($result, ...$hierarchy)
    {
        return get($result, ...$hierarchy);
    }
    public function GetToken($result)
    {
        return $this->GetFromResult($result, $this->TokenKey);
    }
    /**
     * To get the transaction ID from the payment gateway response.
     * @param mixed $result The response received from the payment gateway.
     * @return mixed The transaction ID extracted from the response.
     */
    public function GetTransaction($result)
    {
        return $this->GetFromResult($result, $this->TransactionKey);
    }
    public function GetCode($result)
    {
        return $this->GetFromResult($result, $this->CodeKey);
    }
    public function GetMessage($result)
    {
        return $this->GetFromResult($result, $this->MessageKey);
    }
    public function GetErrors($result)
    {
        return $this->GetFromResult($result, $this->ErrorsKey);
    }
    public function GetCard($result)
    {
        return $this->GetFromResult($result, $this->CardKey);
    }
    public function GetFee($result)
    {
        return $this->GetFromResult($result, $this->FeeKey);
    }
    public function GetFeeType($result)
    {
        return $this->GetFromResult($result, $this->FeeTypeKey);
    }
    public function IsActiveFee($result)
    {
        return $this->GetFromResult($result, "Fee_Type") === "Port";
    }
}