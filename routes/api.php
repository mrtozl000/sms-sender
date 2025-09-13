<?php
/**
 * API route definitions.
 * @package     App
 * @subpackage  Routes
 * @author      Murat Özel <muratozel34@icloud.com>
 * @copyright   Murat
 */

use App\Http\Controllers\Api\MessageController;
use Illuminate\Support\Facades\Route;

Route::prefix('messages')->group(function () {
    Route::get('/sent', [MessageController::class, 'getSentMessages']);
    Route::post('/', [MessageController::class, 'store']);
});
