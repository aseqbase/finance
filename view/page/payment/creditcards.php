<?php
use MiMFa\Library\Struct;
if (!auth(\_::$User->UserAccess, \_::$User->InHandlerPath))
    return;

module("PrePage");
$module = new MiMFa\Module\PrePage();
$module->Title = "'Banking' 'Card'";
$module->Image = "credit-card";

module("Form");
$CreditCard = \_::$User->GetMetaValue("CreditCard");
$IBAN  = \_::$User->GetMetaValue("IBAN");
$Contact = \_::$User->GetValue("Contact");
$id = uniqid("auth_");
$isActive = !$CreditCard || !$IBAN || !$Contact;

if ($isActive)
    $module->Description = "'Authenticate' your 'Account'";

style("
    .authentication-page{
        width: 500px;
        max-width: 85%;
        background-color:var(--back-color-special);
        color:var(--fore-color-special);
        padding:var(--size-2);
        gap:var(--size-0);
    }
    .authentication-page>.form{
        display: grid;
        gap: var(--size-0);
        justify-content: space-around;
        align-items: center;
        align-content: stretch;
    }
    .authentication-page>.form>.field{
        width: 100%;
    }
    .authentication-page>.form>.field>.input{
        width: 100%;
    }
");
$module->Render();
response(
    Struct::Container(
        Struct::Form(
            [
                Struct::HiddenInput("Target", "#$id"),
                Struct::Field(
                    type: "/^[^\d\-\+\.\)\(\[\]\}\{&\^%\$#@!~'\"\|:;\?><\*\/\\\]{2,}$/",
                    key: "FirstName",
                    title: "First Name",
                    value: $firstName = \_::$User->GetValue("FirstName"),
                    attributes: [
                        "required" => true,
                        ...($firstName ? ["disabled" => true]: []),
                        "placeholder" => "Put your 'first name'",
                        "class" => "be align center",
                    ]
                ),
                Struct::Field(
                    type: "/^[^\d\-\+\.\)\(\[\]\}\{&\^%\$#@!~'\"\|:;\?><\*\/\\\]{2,}$/",
                    key: "LastName",
                    title: "Last Name",
                    value: $lastName = \_::$User->GetValue("LastName"),
                    attributes: [
                        "required" => true,
                        ...($lastName ? ["disabled" => true]: []),
                        "placeholder" => "Put your 'last name'",
                        "class" => "be align center",
                    ]
                ),
                Struct::Field(
                    type: "/^09\d{9}$/",
                    key: "Contact",
                    title: "Phone",
                    value: preg_replace("/^\+\d+\s*/", "0", $Contact ?? ""),
                    attributes: [
                        "required" => true,
                        ...($isActive ? [] : ["disabled" => true]),
                        "placeholder" => "09123456789",
                        "class" => "be align center",
                        "inputmode" => "numeric",
                        "minlength" => "11",
                        "maxlength" => "11"
                    ]
                ),
                Struct::Field(
                    type: "/^\d{16}$/",
                    key: "CreditCard",
                    title: "'Banking' 'Card Number'",
                    value: $CreditCard,
                    attributes: [
                        "required" => true,
                        ...($isActive ? [] : ["disabled" => true]),
                        "placeholder" => "Enter your 'Credit Card Number'",
                        "inputmode" => "numeric",
                        "class" => "be align center",
                        "minlength" => "16",
                        "maxlength" => "16"
                    ]
                ),
                Struct::Field(
                    type: "/^\w\w\d{24}$/",
                    key: "IBAN",
                    title: "'Banking' 'IBAN'",
                    value: $IBAN??"IR",
                    attributes: [
                        "required" => true,
                        ...($isActive ? [] : ["disabled" => true]),
                        "placeholder" => "Enter your 'IBAN'",
                        "class" => "be align center",
                        "minlength" => "26",
                        "maxlength" => "26"
                    ]
                ),
                ...($isActive ? [Struct::SubmitButton("Submit", "Submit", ["class" => "main"])] : []),
            ],
            "/payment/creditcards",
            [
                "Id" => $id,
                "method" => "Post",
                "interaction" => true
            ]
        ),
        ["class" => "authentication-page"]
    )
);