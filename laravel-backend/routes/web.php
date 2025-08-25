<?php

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
        'message' => 'Meeting Manager Laravel Backend',
        'version' => config('app.version', '1.0.0'),
        'status' => 'running',
        'timestamp' => now()->toISOString(),
        'api_url' => url('/api'),
        'health_check' => url('/api/health'),
    ]);
});

// Health check route
Route::get('/health', function () {
    return response()->json([
        'status' => 'healthy',
        'timestamp' => now()->toISOString(),
        'services' => [
            'database' => 'connected',
            'cache' => 'active',
            'queue' => 'running',
        ]
    ]);
});

// Redirect to API documentation or frontend
Route::get('/api', function () {
    return response()->json([
        'message' => 'Meeting Manager API',
        'version' => config('app.version', '1.0.0'),
        'endpoints' => [
            'health' => '/api/health',
            'auth' => '/api/auth/*',
            'dashboard' => '/api/dashboard/*',
            'meetings' => '/api/meetings/*',
            'participants' => '/api/participants/*',
            'settings' => '/api/settings/*',
            'review' => '/api/review/*',
        ],
        'documentation' => 'See API_ENDPOINTS.md for complete documentation'
    ]);
});