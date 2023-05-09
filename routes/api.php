<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\KmtController;
use App\Http\Controllers\QrcodeController;
use App\Http\Controllers\MemberController;
use App\Http\Controllers\MemberLiveController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group([
    'middleware' => 'api',
    'prefix' => 'auth'
], function ($router) {
    Route::post('/login', [AuthController::class, 'login']);
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/refresh', [AuthController::class, 'refresh']);
    Route::get('/user-profile', [AuthController::class, 'userProfile']);    
});

Route::group(['prefix' => 'member'], function () {
    Route::get('/', [MemberController::class, 'index']);
    Route::get('/{id}', [MemberController::class, 'show']);
    Route::post('/', [MemberController::class, 'store']);
});

Route::group(['prefix' => 'memberlive'], function () {
    Route::get('/', [MemberLiveController::class, 'index']);
    Route::get('/{id}', [MemberLiveController::class, 'show']);
});

Route::post('/callback', [KmtController::class, 'callback']);
Route::get('/publickey', function(){
    return env('PUBLIC_KEY', false);
});
Route::get('/billerid', function(){
    return env('BILLER_ID', false);
});
Route::get('/bizmchid', function(){
    return env('BIZMCH_ID', false);
});
Route::get('/channal', function(){
    return env('CHANNEL', false);
});
Route::get('/uuid', function(){
    return Str::uuid();
});
Route::get('/qrcode', [KmtController::class, 'qrCode']);
Route::get('/qrcodes', [KmtController::class, 'qrCodes']);
Route::post('/readqrcode', [KmtController::class, 'readQrCode']);
Route::post('/readqrcode2', [KmtController::class, 'readQrCode2']);
Route::get('/transection/{id}', [KmtController::class, 'transection']);
Route::get('/transection', [KmtController::class, 'transectionList']);
Route::get('/settle', [KmtController::class, 'settleList']);
Route::get('/settles', [KmtController::class, 'settles']);
Route::get('/verify', [KmtController::class, 'verify']);

Route::get('/sign', [KmtController::class, 'getSign']);

// Route::middleware('api')->group( function () {
    Route::resource('qrcodes', QrcodeController::class);
// });
Route::get('/test', function () {
    return 'test';
});