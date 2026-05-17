<?php
namespace MiMFa\Plugin;

use MiMFa\Library\Finance\Account;
use MiMFa\Library\Struct;

class Finance extends \MiMFa\Library\Revise
{
    /**
     * @field text
     */
    public $Title = "Finance";
    /**
     * @field texts
     */
    public $Description = null;
    /**
     * @field path
     */
    public $Image = "coins";
    public $DefaultMenu = true;

    /**
     * @category Currency
     * @field int
     */
    public $DecimalPercision = 2;
    /**
     * @category Currency
     * @field text
     */
    public $Currency = "USD";
    /**
     * @category Currency
     * @field text
     */
    public $ShownCurrency = "<small>\$</small>";
    /**
     * The free price indicator
     * @category Price
     * @field text
     */
    public $ShownFreePrice = "<strong>Free</strong>";
    /**
     * The unspecific price indicator
     * @category Price
     * @field text
     */
    public $ShownUnknownPrice = "Post payment";
    /**
     * A multiple ratios of each other currencies to the main carrency 
     * @category Currency
     * @field pairs
     * @example [
     *  "USD" => 1,
     *  "EURO" => 1.2,
     *  "POUND" => 1.5
     * ]
     */
    public $CurrenciesRatio = [];

    /**
     * @category Payment
     * @field text
     */
    public $PaymentTitle = "Payment";
    /**
     * @category Payment
     * @field text
     */
    public $PaymentDescription = null;
    /**
     * @category Payment
     * @field text
     */
    public $PaymentUrlPath = "/payment/bill";
    /**
     * @category Payment
     * @field text
     */
    public $PeymentMethod = "__PAYMENT";
    /**
     * @category Payment
     * @field value
     */
    public $PlatformRequestKey = "Platform";
    /**
     * @category Payment
     * @field value
     */
    public $WalletRequestKey = "Wallet";
    /**
     * @category Payment
     * @field value
     */
    public $TokenRequestKey = "T";
    /**
     * @category Payment
     * @field value
     */
    public $RelationRequestKey = "R";
    /**
     * @category Payment
     * @field value
     */
    public $RelationIdRequestKey = "RId";
    /**
     * @category Payment
     * @field value
     */
    public $SuccessRequestKey = "S";
    /**
     * @category Payment
     * @field value
     */
    public $ErrorRequestKey = "E";
    /**
     * @category Payment
     * @field value
     */
    public $CallbackRequestKey = "C";
    /**
     * @category Payment
     * @field value
     */
    public $SubmitRequestKey = "Submit";

    /**
     * The platform user id (account number)
     * @category Account
     * @field int
     */
    public $PlatformAccount = 0;

    /**
     * @category Account
     * @field text
     */
    public $WalletTitle = "Wallet";
    /**
     * @category Account
     * @field texts
     */
    public $WalletDescription = "Deposit or withdraw to your wallet";
    /**
     * @category Account
     * @field path
     */
    public $WalletImage = "wallet";
    /**
     * @category Account
     * @field path
     */
    public $WalletUrlPath = "/finance/wallet";
    /**
     * @category Payment
     * @field text
     */
    public $InvoiceUrlPath = "/finance/invoice";
    /**
     * @category Account
     * @field path
     */
    public $WithdrawUrlPath = "/payment/withdraw";
    /**
     * @category Account
     * @field path
     */
    public $CardsUrlPath = "/payment/creditcards";
    /**
     * @category Account
     * @field int
     */
    public $WalletAccess = 1;

    /**
     * @category Admin
     * @field text
     */
    public $AdminTitle = "Finance";
    /**
     * @category Admin
     * @field texts
     */
    public $AdminDescription = null;
    /**
     * @category Admin
     * @field path
     */
    public $AdminImage = "coins";

    public function GetPlatform($name = null, $amount = null, $currency = null)
    {
        return $this->GetPlatforms($amount, $currency)[$name] ?? null;
    }
    public function GetPlatforms($amount = null, $currency = null)
    {
        $currency = $currency ?: $this->Currency;
        return loop(
            \MiMFa\Library\Storage::GetDirectory("compute/payment/platforms/"),
            function ($path) use ($amount, $currency) {
                if (!$path)
                    return null;
                $name = preg_find("/(?<=[\\/\\\])[^-\\/\\\][^\\/\\\]+(?=" . preg_quote(\_::$Extension) . "$)/i", $path);
                if ($name && ($p = compute("payment/platforms/$name")) && $p->Acceptable($amount, $currency))
                    return [$name => $p];
                else
                    return null;
            },
            false,
            true
        );
    }
    public function GetPlatformPairs($amount = null, $currency = null)
    {
        return loop(
            $this->GetPlatforms($amount, $currency),
            fn($p, $k) => [$k => $p->Title ?? $k],
            false,
            true
        );
    }

    public function GetTransaction($id, $succeed = null)
    {
        return table("Finance_Account")->SelectRow("*", ($succeed?"Status IN ('".join("','", Account::SuccessStatuses())."') AND ":($succeed === false?"Status NOT IN ('".join("','", Account::SuccessStatuses())."') AND ":""))."Id=:Id", [":Id" => $id]);
    }

    public function StandardCurrency(float|null $amount = 0, $fromCurrency = null)
    {
        $fromCurrency = trim(strtoupper($fromCurrency ?? ""));
        $tocurrency = trim(strtoupper(\_::$Joint->Finance->Currency ?? ""));
        if (!$amount || !$fromCurrency || !$tocurrency || $fromCurrency === $tocurrency)
            return $amount;
        if ($m = get($this->CurrenciesRatio, $fromCurrency))
            return $m * $amount;
        return $amount;
    }

    public function GetAllCurrencyOptions()
    {
        $options = [\_::$Joint->Finance->Currency => \_::$Joint->Finance->Currency];
        foreach (\_::$Joint->Finance->CurrenciesRatio as $key => $value)
            $options[$key] = "$key ($value " . \_::$Joint->Finance->Currency . ")";
        return $options;
    }

    
    public function AmountStruct($amount, $currency = null, $attributes = [])
    {
        if(is_null($amount) || $amount === '') return $this->ShownUnknownPrice;
        elseif(!$amount) return $this->ShownFreePrice;
        else return Struct::Number(round($amount, $this->DecimalPercision), $attributes) . ($currency??$this->ShownCurrency);
    }

}