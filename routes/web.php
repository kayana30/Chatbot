<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ChatController;

Route::get('/', function () {
    return view('chatbot.index'); // Make sure index.blade.php exists in resources/views
});

Route::post('/chat', [ChatController::class, 'chat'])
    ->name('chat');
