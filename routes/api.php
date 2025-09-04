<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ConversationController;
use App\Http\Controllers\TicketController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);

    // Ticket
    Route::get('/tickets', [TicketController::class, 'index']);
    Route::post('/tickets', [TicketController::class, 'store']);
    Route::get('/tickets/{id}', [TicketController::class, 'show']);
    Route::get('/tickets/users/assign', [TicketController::class, 'getSupportStaff']);
    Route::put('/tickets/{id}/assign', [TicketController::class, 'assign']);
    Route::put('/tickets/{id}/status', [TicketController::class, 'updateStatus']);
    Route::delete('/tickets/{id}', [TicketController::class, 'destroy']);


    // Conversation
    Route::get('/tickets/{id}/conversations', [ConversationController::class, 'index']);
    Route::post('/tickets/{id}/conversations', [ConversationController::class, 'store']);
});
