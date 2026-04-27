<?php
if (!auth(\_::$User->UserAccess))
    return;

$received = receivePost();
$Contact = get($received, "Contact");
$CreditCard = get($received, "CreditCard");
$IBAN = preg_find("/^[a-z]{2}\d{24}$/i", get($received, "IBAN") ?? "");
if ($CreditCard && $IBAN && $Contact && \_::$User->Id) {
    //$dbContact = $Contact;
    $pdo = \_::$Back->DataBase->Connection();
    //if (table("User")->Exists("Id!=:Id AND (Contact=:Contact OR JSON_UNQUOTE(JSON_EXTRACT(MetaData, '$.CreditCard'))=:CreditCard)", [":Id" => \_::$User->Id, ":Contact" => $dbContact, ":CreditCard" => $CreditCard]))
    if (table("User")->Exists("Id!=:Id AND (Contact LIKE :Contact OR (JSON_VALID(MetaData) AND JSON_UNQUOTE(JSON_EXTRACT(MetaData, '$.CreditCard'))=" . $pdo->quote($CreditCard) . "))", [":Id" => \_::$User->Id, ":Contact" => "%$Contact"]))
        return deliverError("You can not 'submit a request' by this 'information'!");
    else {
        $fn = get($received, "FirstName");
        $ln = get($received, "LastName");
        if (
            \_::$User->SetMetaValue("CreditCard", "$CreditCard") &&
            \_::$User->SetMetaValue("IBAN", strtoupper("$IBAN")) &&
            \_::$User->Set([
                "Contact" => $Contact,
                ...((!\_::$User->Name) && $fn && $ln? ["Name" => "$fn $ln"] : []),
                ...($fn ? ["FirstName" => $fn] : []),
                ...($ln ? ["LastName" => $ln] : [])
            ])
        )
            return deliverRedirect(MiMFa\Library\Struct::Success("Your 'information' are set successfully!"));
        else
            return deliverError("Incorrect value");
    }
} else
    return deliverError("Please complete all fields correctly!");