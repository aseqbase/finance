<?php
namespace MiMFa\Library\Finance;

use MiMFa\Library\Convert;

library("MetaDataTable");
class MetaDataTable extends \MiMFa\Library\MetaDataTable
{
    public function __construct($dataBase = null, $name = null, $prefix = null, $nameConvertors = null)
    {
        parent::__construct($dataBase ?? \_::$Back->DataBase, $name ?? "Finance_Invoice", $prefix ?? \_::$Back->DataBasePrefix, $nameConvertors ?? \_::$Back->DataTableNameConvertors);
    }

    public function IsPaid($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance, $metaData);
        $pa = $this->GetPaidAmount($idOrInstance, $metaData);
        return is_null($pa) ? null : ($pa >= ($this->GetAmount($idOrInstance, $metaData) ?? 0));
    }
    public function GeneratePrice($amount = null, $currency = null, $data = null, $paid = 0)
    {
        return [
            "User" => \_::$User->Name,
            "UserId" => \_::$User->Id,
            "Amount" => $amount ? floatval($amount) : $amount,
            "Paid" => $paid ? floatval($paid) : 0,
            "Currency" => $currency,
            "Data" => $amount == $data ? null : $data,
            "Timestamp" => Convert::ToDateTime()->getTimestamp(),
        ];
    }
    public function Pay(&$idOrInstance, &$metaData = null, $paid = null, $currency = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        $price = $this->GetPrice($idOrInstance, $metaData);
        if ($price["Currency"] === $currency || !$price["Currency"] || !$currency)
            $price["Paid"] += $paid;
        else {
            $price["Currency"] = "{$price["Currency"]},$currency";
            $price["Paid"] = "{$price["Paid"]},$paid";
        }
        return $this->RepairPrice($idOrInstance, $metaData, $price);
    }
    public function Refund(&$idOrInstance, &$metaData = null, $refund = null, $currency = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        $price = $this->GetPrice($idOrInstance, $metaData);
        if ($price["Currency"] === $currency || !$price["Currency"] || !$currency)
            $price["Paid"] -= $refund;
        else {
            $price["Currency"] = "{$price["Currency"]},$currency";
            $price["Paid"] = "{$price["Paid"]},-$refund";
        }
        return $this->RepairPrice($idOrInstance, $metaData);
    }
    public function RepairPrice(&$idOrInstance, &$metaData = null, $price = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        $metaData["Seen"] = false;
        $price = $this->GeneratePrice(
            $price["Amount"] ?? ($idOrInstance["Amount"] ?? get($metaData, "Price", "Amount")),
            $price["Currency"] ?? (get($idOrInstance, "Currency") ?: get($metaData, "Price", "Currency")),
            $price["Data"] ?? null,
            $price["Paid"] ?? (get($idOrInstance, "Paid") ?: get($metaData, "Price", "Paid"))
        );
        $metaData["Price"] = $price;
        if ($a = has($idOrInstance, "Amount"))
            $idOrInstance["Amount"] = pop($metaData["Price"], "Amount");
        if ($p = has($idOrInstance, "Paid"))
            $idOrInstance["Paid"] = pop($metaData["Price"], "Paid");
        if (has($idOrInstance, "Currency"))
            if ($a && $p)
                $idOrInstance["Currency"] = pop($metaData["Price"], $price["Currency"]);
            else
                $idOrInstance["Currency"] = $price["Currency"];
        $idOrInstance["MetaData"] = $metaData;
        return $price;
    }
    public function GetAmount($idOrInstance, $metaData = null)
    {
        if ($price = $this->GetPrice($idOrInstance, $metaData))
            return $price["Amount"] ?? null;
        return null;
    }
    public function GetPaidAmount($idOrInstance, $metaData = null)
    {
        if ($price = $this->GetPrice($idOrInstance, $metaData))
            return str_contains($price["Paid"] . "", ",") ? null : $price["Paid"];
        return null;
    }
    public function GetUnpaidAmount($idOrInstance, $metaData = null)
    {
        $up = $this->GetPaidAmount($idOrInstance, $metaData);
        if ((!is_null($up)) && ($price = $this->GetPrice($idOrInstance, $metaData)))
            return ($price["Amount"] ?? 0) - $up;
        return null;
    }
    public function GetPrice($idOrInstance, $metaData = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $metaData = $this->MetaData($idOrInstance["Id"] ?? null, $metaData);
        $price = get($metaData, "Price") ?? [];
        return $this->GeneratePrice(
            $idOrInstance["Amount"] ?? ($price["Amount"] ?? null),
            get($idOrInstance, "Currency") ?: ($price["Currency"] ?? null),
            $price["Data"] ?? null,
            get($idOrInstance, "Paid") ?: ($price["Paid"] ?? null)
        );
    }
    public function SetPrice($idOrInstance, $metaData = null, $price = null)
    {
        $idOrInstance = $this->Data($idOrInstance);
        $this->RepairPrice($idOrInstance, $metaData, $price);
        return $this->Set($idOrInstance["Id"], $idOrInstance);
    }
}