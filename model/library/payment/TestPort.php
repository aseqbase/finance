<?php
namespace MiMFa\Library\Payment;

use MiMFa\Library\Finance\Account;
use MiMFa\Library\Struct;

library("payment/Port");
class TestPort extends Port
{
    public $Title = "Test Port";
    public $Description = "A test payment port";
    public $FinalStatus = "Succeed";


    public function __construct(
        $terminal = "Test",
        $acceptor = "Test",
        $password = "Test",
        $name = "TestPort",
        $title = "Test Port",
        $paymentPath = true,
        $initiatePath = true,
        $validatePath = true,
        $reversePath = true
    ) {
        parent::__construct($terminal, $acceptor, $password, $name, $title, $paymentPath, $initiatePath, $validatePath, $reversePath);
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
                ...($relation === "Finance_Invoice" ? ["InvoiceId" => $relationId] : []),
                "ReferrerId" => $referrerId,
                "Callback" => $callbackUrl,
                "Success" => $onSuccess,
                "Error" => $onError
            ]
        ];

        if (!$this->Invoice($data))
            return false;

        $token = $data["MetaData"]["Token"] = $data["MetaData"]["Token"] ?? uniqid($this->Name, true);
        setSecret(getClientCode($token), $data);
        
        if (isEmpty($amount)) {
            return deliverRedirect(
                Struct::Warning($this->WaitMessage),
                "/finance/".($relation??"invoice")."?Id=$relationId"
            );
        } else
            switch ($data["Status"] = $this->FinalStatus) {
                case Account::$SucceedStatus:
                    if ($id = $this->Succeed($data)) {
                        $data["Id"] = $data["Id"] ?? $id;
                        setSecret(getClientCode($token), $data);
                        return $this->RenderSucceed($data);
                    }
                    break;
                case Account::$FailedStatus:
                    if ($id = $this->Failed($data)) {
                        $data["Id"] = $data["Id"] ?? $id;
                        setSecret(getClientCode($token), $data);
                        return $this->RenderFailed($data);
                    }
                    break;
                default:
                    return deliverError("There were a problem!");
            }
    }
}