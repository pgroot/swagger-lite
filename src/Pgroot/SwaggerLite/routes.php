<?php
/**
 * Date: 2017-11-12 15:26
 * @author: GROOT (pzyme@outlook.com)
 */

Route::get(Config::get('swagger-lite.doc-route'), function () {
    return view('swagger-lite::index', [
        "docJson" => url(Config::get('swagger-lite.api-docs-route'))
    ]);
});

Route::get(Config::get('swagger-lite.api-docs-route'), function () {
    $json = (new \Pgroot\SwaggerLite\Generator)->make(true);
    return Response::make($json, 200,
        ['Content-Type' => 'application/json', 'Content-Length' => strlen($json)]);
});