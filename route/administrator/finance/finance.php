<?php
auth(\_::$User->SuperAccess);
$data = $data ?? [];
$routeHandler = function ($data) {
    return \MiMFa\Library\Revise::ToString(\_::$Joint->Finance);
};
(new Router())
    ->Get(function () use ($routeHandler) {
        (\_::$Front->AdminView)($routeHandler, [
            "Image" => "coins",
            "Title" => "'Finance' Managements"
        ]);
    })
    ->Default(fn() => response($routeHandler($data)))
    ->Handle();