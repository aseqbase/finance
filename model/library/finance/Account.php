<?php
namespace MiMFa\Library\Finance;

library("finance\MetaDataTable");

class Account extends MetaDataTable
{
    public static $BeginingStatus = "Trying";
    public static $WaitingStatus = "Waiting";
    public static $WithdrawingStatus = "Withdrawing";
    public static $DepositingStatus = "Depositing";
    public static $ReversingStatus = "Reversing";
    public static $TransferingStatus = "Transfering";
    public static $ContractedStatus = "Contracted";
    public static $ReversedStatus = "Reversed";
    public static $FailedStatus = "Failed";
    public static $TransferedStatus = "Transfered";
    public static $SucceedStatus = "Succeed";

    public static function PendingStatuses()
    {
        return [
            "Begining" => self::$BeginingStatus,
            "Withdrawing" => self::$WithdrawingStatus,
            "Depositing" => self::$DepositingStatus,
            "Reversing" => self::$ReversingStatus,
            "Transfering" => self::$TransferingStatus,
            "Waiting" => self::$WaitingStatus,
        ];
    }
    public static function PledgeStatuses()
    {
        return [
            "Contracted" => self::$ContractedStatus,
        ];
    }
    public static function ErrorStatuses()
    {
        return [
            "Reversed" => self::$ReversedStatus,
            "Failed" => self::$FailedStatus
        ];
    }
    public static function SuccessStatuses()
    {
        return [
            "Transfered" => self::$TransferedStatus,
            "Succeed" => self::$SucceedStatus
        ];
    }

    public static function GetStatuses()
    {
        return [
            ...self::PendingStatuses(),
            ...self::PledgeStatuses(),
            ...self::ErrorStatuses(),
            ...self::SuccessStatuses()
        ];
    }
    public static function GetStatusColor($status)
    {
        switch ($status) {
            case self::$BeginingStatus:
            case self::$WaitingStatus:
            case self::$TransferingStatus:
            case self::$ReversingStatus:
            case self::$WithdrawingStatus:
            case self::$DepositingStatus:
                return "var(--color-yellow)";
            case self::$ContractedStatus:
                return "var(--color-magenta)";
            case self::$ReversedStatus:
            case self::$FailedStatus:
                return "var(--color-red)";
            case self::$TransferedStatus:
            case self::$SucceedStatus:
                return "var(--color-green)";
        }
        return "inherit";
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public static function Condition($userId = null, &$params = [])
    {
        if ($userId === true)
            return "(SourceId=" . \_::$Joint->Finance->PlatformAccount . " OR DestinationId=" . \_::$Joint->Finance->PlatformAccount . ")";
        elseif ($userId) {
            $params[":UserId"]=$userId;
            return "(SourceId=:UserId OR DestinationId=:UserId)";
        } else
            return null;
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public static function DepositCondition($userId = null, &$params = [])
    {
        if ($userId === true)
            return "(DestinationId=" . \_::$Joint->Finance->PlatformAccount . ")";
        elseif ($userId) {
            $params[":UserId"]=$userId;
            return "(DestinationId=:UserId)";
        } else
            return "(SourceId IS NULL OR SourceId='' OR SourceId<0)";
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public static function WithdrawCondition($userId = null, &$params = [])
    {
        if ($userId === true)
            return "(SourceId=" . \_::$Joint->Finance->PlatformAccount . ")";
        elseif ($userId) {
            $params[":UserId"]=$userId;
            return "(SourceId=:UserId)";
        } else
            return "(DestinationId IS NULL OR DestinationId='' OR DestinationId<0)";
    }

    public function __construct($dataBase = null, $name = null, $prefix = null, $nameConvertors = null)
    {
        parent::__construct($dataBase ?? \_::$Back->DataBase, $name ?? "Finance_Account", $prefix ?? \_::$Back->DataBasePrefix, $nameConvertors ?? \_::$Back->DataTableNameConvertors);
    }

    public function IsPlatform($userId)
    {
        return $userId ? $userId === \_::$Joint->Finance->PlatformAccount : true;
    }

    public function GenerateTransaction($amount, $from, $to, $relation = null, $relationId = null, $description = null, $status = null, $currency = null)
    {
        return $amount ? [
            ":Relation" => $relation,
            ":RelationId" => $relationId,
            ":SourceId" => $from,
            ":DestinationId" => $to,
            ":Amount" => $amount,
            ":Currency" => $currency ? strtoupper($currency) : $currency,
            ":Status" => $status ?? self::$SucceedStatus,
            ":Description" => $description,
            ":Transaction" => uniqid("A")
        ] : null;
    }

    public function SetTransaction($amount, $from, $to, $relation = null, $relationId = null, $description = null, $status = null, $currency = null)
    {
        $params = $this->GenerateTransaction($amount, $from, $to, $relation, $relationId, $description, $status, $currency);
        if ($params)
            return $this->Insert($params);
        return null;
    }
    public function GetTransaction($trackId)
    {
        return $this->SelectRow("*", "Transaction=:Track", [":Track" => $trackId]);
    }

    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        if (is_null($userId))
            return [];
        $suffix = null;
        if ($currency) {
            $currency = strtoupper($currency);
            $suffix = "(Currency IS NULL OR Currency=:Currency)";
        }
        return $this->Select($column, [self::Condition($userId, $params), $conditions, $suffix], [...$params, ...($currency ? [":Currency" => $currency] : [])]);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindDepositTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        if (is_null($userId))
            return [];
        $suffix = null;
        if ($currency) {
            $currency = strtoupper($currency);
            $suffix = "(Currency IS NULL OR Currency=:Currency)";
        }
        return $this->Select($column, [self::DepositCondition($userId, $params), $conditions, $suffix], [...$params, ...($currency ? [":Currency" => $currency] : [])]);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindWithdrawTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        if (is_null($userId))
            return [];
        $suffix = null;
        if ($currency) {
            $currency = strtoupper($currency);
            $suffix = "(Currency IS NULL OR Currency=:Currency)";
        }
        return $this->Select($column, [self::WithdrawCondition($userId, $params), $conditions, $suffix], [...$params, ...($currency ? [":Currency" => $currency] : [])]);
    }

    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindPendingTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindTransactions($column, [$conditions, "Status IN ('" . join("', '", self::PendingStatuses()) . "')"], $params, $userId, $currency);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindPendingDepositTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindDepositTransactions($column, [$conditions, "Status IN ('" . join("', '", self::PendingStatuses()) . "')"], $params, $userId, $currency);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindPendingWithdrawTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindWithdrawTransactions($column, [$conditions, "Status IN ('" . join("', '", self::PendingStatuses()) . "')"], $params, $userId, $currency);
    }

    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindPledgeTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindTransactions($column, [$conditions, "Status IN ('" . join("', '", self::PledgeStatuses()) . "')"], $params, $userId, $currency);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindPledgeDepositTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindDepositTransactions($column, [$conditions, "Status IN ('" . join("', '", self::PledgeStatuses()) . "')"], $params, $userId, $currency);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindPledgeWithdrawTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindWithdrawTransactions($column, [$conditions, "Status IN ('" . join("', '", self::PledgeStatuses()) . "')"], $params, $userId, $currency);
    }

    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindErrorTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindTransactions($column, [$conditions, "Status IN ('" . join("', '", self::ErrorStatuses()) . "', '" . self::$ReversedStatus . "')"], $params, $userId, $currency);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindErrorDepositTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindDepositTransactions($column, [$conditions, "Status IN ('" . join("', '", self::ErrorStatuses()) . "', '" . self::$ReversedStatus . "')"], $params, $userId, $currency);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindErrorWithdrawTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindWithdrawTransactions($column, [$conditions, "Status IN ('" . join("', '", self::ErrorStatuses()) . "', '" . self::$ReversedStatus . "')"], $params, $userId, $currency);
    }

    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindSuccessTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindTransactions($column, [$conditions, "Status IN ('" . join("', '", self::SuccessStatuses()) . "', '" . self::$TransferedStatus . "')"], $params, $userId, $currency);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindSuccessDepositTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindDepositTransactions($column, [$conditions, "Status IN ('" . join("', '", self::SuccessStatuses()) . "', '" . self::$TransferedStatus . "')"], $params, $userId, $currency);
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function FindSuccessWithdrawTransactions($column = "*", $conditions = null, $params = [], $userId = null, $currency = null)
    {
        return $this->FindWithdrawTransactions($column, [$conditions, "Status IN ('" . join("', '", self::SuccessStatuses()) . "', '" . self::$TransferedStatus . "')"], $params, $userId, $currency);
    }


    public function GetSumAmounts($transactions = [])
    {
        $amount = 0;
        foreach ($transactions as $key => $value)
            $amount += $value["Amount"] ?? 0;
        return $amount;
    }

    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetDepositAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindDepositTransactions("Amount", null, [], $userId, $currency));
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetWithdrawAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindWithdrawTransactions("Amount", null, [], $userId, $currency));
    }

    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetPendingDepositAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindPendingDepositTransactions("Amount", null, [], $userId, $currency));
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetPendingWithdrawAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindPendingWithdrawTransactions("Amount", null, [], $userId, $currency));
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetPledgeDepositAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindPledgeDepositTransactions("Amount", null, [], $userId, $currency));
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetPledgeWithdrawAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindPledgeWithdrawTransactions("Amount", null, [], $userId, $currency));
    }

    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetErrorDepositAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindErrorDepositTransactions("Amount", null, [], $userId, $currency));
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetErrorWithdrawAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindErrorWithdrawTransactions("Amount", null, [], $userId, $currency));
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetSuccessDepositAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindSuccessDepositTransactions("Amount", null, [], $userId, $currency));
    }
    /**
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetSuccessWithdrawAmount($userId = null, $currency = null)
    {
        return $this->GetSumAmounts($this->FindSuccessWithdrawTransactions("Amount", null, [], $userId, $currency));
    }


    /**
     * Total Pending balance amount
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetPendingAmount($userId = null, $currency = null)
    {
        return $this->GetPendingDepositAmount($userId, $currency) + $this->GetPendingWithdrawAmount($userId, $currency);
    }
    /**
     * Total Pending and all Pledge balance amount
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetPledgeAmount($userId = null, $currency = null)
    {
        return $this->GetPledgeDepositAmount($userId, $currency) + $this->GetPledgeWithdrawAmount($userId, $currency);
    }

    /**
     * Get expected total amount
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetExpectedAmount($userId = null, $currency = null)
    {
        return $this->GetTotalAmount($userId, $currency)
            + self::GetPledgeDepositAmount($userId, $currency)
            + self::GetPendingDepositAmount($userId, $currency)
            - self::GetPledgeWithdrawAmount($userId, $currency)
            - self::GetPendingWithdrawAmount($userId, $currency);
    }
    /**
     * Total balance amount
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetTotalAmount($userId = null, $currency = null)
    {
        return $this->GetSuccessDepositAmount($userId, $currency)
            - $this->GetSuccessWithdrawAmount($userId, $currency);
    }
    /**
     * Flexible balance amount
     * @param mixed $userId Send true to get only platforms, false to get all, id otherwise
     */
    public function GetBalanceAmount($userId = null, $currency = null)
    {
        return $this->GetTotalAmount($userId, $currency)
            - $this->GetPledgeWithdrawAmount($userId, $currency)
            - $this->GetPendingWithdrawAmount($userId, $currency);
    }
}