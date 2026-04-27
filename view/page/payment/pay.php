<?php
use \MiMFa\Library\Convert;
module("PrePage");
$module = new MiMFa\Module\PrePage();
$module->Title = "Payment";
$module->Render();
if (!isValid(\_::$Front->Payment))
    return;
$jsd = is_string(\_::$Front->Payment) ? Convert::FromJson(mb_convert_encoding(\_::$Front->Payment ?? "{}", "UTF-8")) : Convert::ToSequence(\_::$Front->Payment);
if (isEmpty($jsd))
    return;
module("payment\Form");
$ts = array();
foreach ((is_array(first($jsd)) ? $jsd : [$jsd]) as $key => $value) {
    $t = new MiMFa\Module\Transaction();
    foreach ($value as $k => $v)
        $t->$k = $v;
    $ts[] = $t;
}
$module = new MiMFa\Module\Payment\Form(...$ts);
pod($module, $data);
$module->Render();