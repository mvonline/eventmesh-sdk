<?php

use Illuminate\Support\Facades\Route;
use EventMesh\LaravelSdk\Http\Controllers\WebhookController;

Route::post('/', [WebhookController::class, 'handle'])
    ->name('eventmesh.webhook'); 