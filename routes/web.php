<?php

use App\Http\Controllers\ScheduleDispatchDashboardController;
use App\Http\Controllers\Api\V1\Invoices\InvoiceController;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/', function () {
    return response()->json([
        'success' => true,
        'message' => 'FixlyHandy API is running 🚀'
    ]);
    //return view('welcome');
});

Route::get('/test-mail', function () {
    Mail::raw('This is a test email from FixlyHandy.', function ($message) {
        $message->to('fixlyhandyofficial@gmail.com')
                ->subject('FixlyHandy SMTP Test');
    });
    return 'Mail Sent Successfully';
});

Route::get('/schedule-dispatch', ScheduleDispatchDashboardController::class)
    ->name('schedule-dispatch.dashboard');

Route::get('/invoice/public/{token}', [InvoiceController::class, 'publicView'])
    ->name('invoice.public.show');
