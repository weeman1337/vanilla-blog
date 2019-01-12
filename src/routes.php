<?php

$app->get('/', \Blog\Controllers\HomeController::class);
$app->get('/thumbs/{url}', \Blog\Controllers\ThumbnailController::class);
