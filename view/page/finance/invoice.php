<?php
use MiMFa\Library\Convert;
use MiMFa\Library\Script;
use MiMFa\Library\Struct;

$table = strToProper(\_::$Address->UrlResource);
$id = received("Id") ?? received("Code");
if (!$id)
    return deliverError("There is no '$table'!");
$invoice = table($table)->SelectRow("*", ["(Id=:Id OR Name=:Id)", authCondition(checkStatus: false)], [":Id" => $id]);
if (!$invoice)
    return deliverError("There is not find any '$table'!");
$id = get($invoice, "Id");
$userId = get($invoice, "UserId");
$user = $userId ? table("User")->Get($userId) : [];
$amount = get($invoice, "Amount");
$status = get($invoice, "Status") ?? "";
$state = strtolower($status);
$state = $state === "paid" ? 1 : (in_array($state, ["cancelled", "failed"]) ? -1 : ($state === "pending" ? 0 : null));
$stateColor = $state > 0 ? "var(--color-green)" : ($state < 0 ? "var(--color-red)" : ($state === 0 ? "var(--color-yellow)" : "inherit"));
$platform = $p = get($invoice, "Platform");
$metadata = Convert::FromJson(get($invoice, "MetaData"));
$content = get($invoice, "Content");
if (($si = get($metadata, "Run", $status)) || ($si = get($metadata, "Run")))
    $content .= (($n = get($si, "Name")) ? run($n, get($si, "Data")) : null) ?? $si;
if ($platform && preg_match("/^[\w\s\-\d]+$/", $platform))
    $platform = null;
else
    $p = null;
$callback = \_::$Address->UrlOrigin . "/finance/$table?id=$id";
$currency = __(get($invoice, "Currency") ?: \_::$Joint->Finance->ShownCurrency);
$priceParams = get($metadata, "PriceParams");
style("
    .invoice-details :is(.button,.icon){
        display: inline-block;
    }
    .invoice-details .invoice-abstract{
        width:100%;
        height:fit-content;
        max-width:720px;
        margin-bottom: var(--size-max);
        padding: var(--size-0);
        border: var(--border-1) $stateColor;
        box-shadow: var(--shadow-1);
        border-radius: var(--radius-2);
    }
    .invoice-details .invoice-abstract .list .heading{
        padding: 0px var(--size-0);
        margin: 0px;
        font-weight: normal;
    }
");
response(
    Struct::Container([
        [
            Struct::Heading1(
                Struct::Box(__(getBetween($invoice, "Title", "Name"))) .
                Struct::Small(Convert::ToShownDateTime(get($invoice, "CreateTime"))),
                getFullUrl(\_::$Joint->Finance->InvoiceUrlPath . "?id=$id", false)
            )
        ],
        Struct::Slot(
            Struct::Division(
                [
                    ...(($val = get($user, "Name")) ? [Struct::Division(Struct::Span("User: ") . Struct::Span($val), ["class" => "be flex justify middle"])] : []),
                    ...(($val = get($user, "Signature")) ? [Struct::Division(Struct::Span("Account: ") . Struct::Span($val, \_::$Address->UserRootUrlPath . $userId), ["class" => "be flex justify middle"])] : []),
                    Struct::Big(Struct::Span($status, null, ["Animation" => "fade-in"]) . ($state > 0 ? (" " . Struct::Icon("check-circle")) : ($state < 0 ? (" " . Struct::Icon("times-circle")) : "")), null, ["class" => "be bold flex middle center", "style" => "color:$stateColor;", "Animation" => "zoom-out"]),
                    Struct::Small(Convert::ToShownDateTime(get($invoice, "UpdateTime"))),
                    Struct::Paragraph(get($invoice, "Description")),
                    ...(($val = get($invoice, "Source")) ? [Struct::Division(Struct::Span("From: ") . Struct::Convert($val), ["class" => "be flex justify middle invoice-special-line"])] : []),
                    ...($p ? [Struct::Division(Struct::Span("By: ") . Struct::Span($p), ["class" => "be flex justify middle invoice-special-line"])] : []),
                    ...(($val = get($invoice, "Destination")) ? [Struct::Division(Struct::Span("To: ") . Struct::Convert($val), ["class" => "be flex justify middle invoice-special-line"])] : []),
                    ...(($val = get($invoice, "Transactions")) ? [Struct::Division(Struct::Span("Tracking Code: ") . Struct::Division(Struct::List(loop(Convert::FromJson($val), fn($v, $k) => Struct::Span($k, null, ["Tooltip" => Struct::Items($v, ["class" => "be align start"])]))), ["class" => "be small"]), ["class" => "be flex justify middle invoice-special-line"])] : []),
                    Struct::$BreakLine,
                    ...($priceParams ? loop($priceParams, fn($val, $key) => Struct::Division(Struct::Span($key) . Struct::Span(\_::$Joint->Finance->AmountStruct($val, $currency)), ["class" => "be flex justify middle"])) : []),
                    Struct::Division(
                        Struct::Division(
                            Struct::Super("Amount:", null, ["class" => "be xsmall"]) . Struct::$Break .
                            Struct::Bold(
                                \_::$Joint->Finance->AmountStruct($amount, $currency),
                                null,
                                ["style" => "color:$stateColor;"]
                            ),
                            ["class" => "be start align"]
                        ) .
                        Struct::Division(
                            ($content ? Struct::Icon("eye-slash", "_('.invoice-content').toggle();", ["class" => "view unprintable be square flex middle center"]) : "") .
                            Struct::Icon("print", Script::Print(), ["class" => "view unprintable be square flex middle center"]) .
                            (($val = get($invoice, "Relation")) ? Struct::Icon("chevron-right", $val, ["class" => "be square flex middle center"]) : "")
                            ,
                            ["class" => "be flex justify"]
                        ),
                        ["class" => "be flex justify middle gap-0"]
                    ),
                    ...(
                        !is_null($state) || isEmpty($amount) ? [] : [
                            Struct::Button(
                                Struct::Icon("credit-card") .
                                __(" 'Pay'")
                                /*. Struct::Bold(
                                    \_::$Joint->Finance->AmountStruct($amount, $currency)
                                )*/ ,
                                str_replace(
                                    ["{table}", "{id}", "{success}", "{error}", "{callback}", "{platform}"],
                                    [$table, $id, urlencode($callback), urlencode($callback), urlencode($callback), $p],
                                    $platform ?: "/payment/bill?" .
                                    \_::$Joint->Finance->RelationRequestKey . "={table}&" .
                                    \_::$Joint->Finance->RelationIdRequestKey . "={id}&" .
                                    \_::$Joint->Finance->CallbackRequestKey . "={callback}&" .
                                    \_::$Joint->Finance->PlatformRequestKey . "={platform}"
                                ),
                                ["class" => "be wide main", "style" => "margin-top:var(--size-0);"]
                            )
                        ])
                ],
                ["class" => "be sticky invoice-abstract"]
            ),
            ["class" => "be flex center"]
        ) .
        ($content ? Struct::LargeSlot(
            isJson($content) ?
            Struct::Table(Convert::FromJson($content)) :
            Struct::Convert($content),
            ["class" => "col-lg-8 invoice-content"]
        ) : "")
    ], ["class" => "invoice-details"])
);