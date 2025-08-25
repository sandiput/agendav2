<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\MeetingController;
use App\Http\Controllers\Api\ParticipantController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\ReviewController;
use App\Http\Controllers\Api\WhatsAppController;

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

// Health check
Route::get('/health', function () {
    return response()->json([
        'success' => true,
        'message' => 'Meeting Manager API is running',
        'timestamp' => now()->toISOString(),
        'version' => config('app.version', '1.0.0'),
    ]);
});

// Authentication routes
Route::prefix('auth')->group(function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    
    Route::middleware('jwt.auth')->group(function () {
        Route::put('profile', [AuthController::class, 'profile']);
        Route::post('change-password', [AuthController::class, 'changePassword']);
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me', [AuthController::class, 'me']);
        Route::post('refresh', [AuthController::class, 'refresh']);
    });
});

// WhatsApp webhook (no auth required)
Route::prefix('whatsapp')->group(function () {
    Route::post('webhook', [WhatsAppController::class, 'webhook']);
    Route::get('webhook', [WhatsAppController::class, 'verify']);
});

// Public routes (no authentication required)
Route::middleware('cors')->group(function () {
    // Dashboard (public access for viewing)
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [DashboardController::class, 'stats']);
        Route::get('upcoming-meetings', [DashboardController::class, 'upcomingMeetings']);
    });

    // Review (public access for viewing)
    Route::prefix('review')->group(function () {
        Route::get('stats', [ReviewController::class, 'stats']);
        Route::get('top-participants', [ReviewController::class, 'topParticipants']);
        Route::get('seksi-stats', [ReviewController::class, 'seksiStats']);
        Route::get('meeting-trends', [ReviewController::class, 'meetingTrends']);
    });

    // Meetings (read-only public access)
    Route::prefix('meetings')->group(function () {
        Route::get('/', [MeetingController::class, 'index']);
        Route::get('search', [MeetingController::class, 'search']);
        Route::get('{meeting}', [MeetingController::class, 'show']);
    });

    // Participants (read-only public access)
    Route::prefix('participants')->group(function () {
        Route::get('/', [ParticipantController::class, 'index']);
        Route::get('search', [ParticipantController::class, 'search']);
    });
});

// Protected routes (authentication required)
Route::middleware(['cors', 'jwt.auth'])->group(function () {
    // Meetings (full CRUD)
    Route::prefix('meetings')->group(function () {
        Route::post('/', [MeetingController::class, 'store']);
        Route::put('{meeting}', [MeetingController::class, 'update']);
        Route::delete('{meeting}', [MeetingController::class, 'destroy']);
        Route::post('{meeting}/send-reminder', [MeetingController::class, 'sendReminder']);
        Route::post('{meeting}/upload-attachment', [MeetingController::class, 'uploadAttachment']);
        Route::delete('{meeting}/attachments/{attachment}', [MeetingController::class, 'deleteAttachment']);
    });

    // Participants (full CRUD)
    Route::prefix('participants')->group(function () {
        Route::post('/', [ParticipantController::class, 'store']);
        Route::get('{participant}', [ParticipantController::class, 'show']);
        Route::put('{participant}', [ParticipantController::class, 'update']);
        Route::delete('{participant}', [ParticipantController::class, 'destroy']);
        Route::post('import', [ParticipantController::class, 'import']);
        Route::get('export', [ParticipantController::class, 'export']);
    });

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'show']);
        Route::put('/', [SettingsController::class, 'update']);
        Route::post('test-whatsapp', [SettingsController::class, 'testWhatsApp']);
        Route::get('preview-group-message', [SettingsController::class, 'previewGroupMessage']);
        Route::post('send-test-group-message', [SettingsController::class, 'sendTestGroupMessage']);
        Route::post('send-test-personal-message', [SettingsController::class, 'sendTestPersonalMessage']);
    });

    // Admin only routes
    Route::middleware('admin')->group(function () {
        // System management
        Route::prefix('system')->group(function () {
            Route::get('info', function () {
                return response()->json([
                    'success' => true,
                    'data' => [
                        'php_version' => PHP_VERSION,
                        'laravel_version' => app()->version(),
                        'database' => config('database.default'),
                        'cache' => config('cache.default'),
                        'queue' => config('queue.default'),
                        'timezone' => config('app.timezone'),
                        'debug' => config('app.debug'),
                    ]
                ]);
            });
            
            Route::post('cache-clear', function () {
                \Artisan::call('cache:clear');
                \Artisan::call('config:clear');
                \Artisan::call('route:clear');
                \Artisan::call('view:clear');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Cache cleared successfully'
                ]);
            });
            
            Route::post('optimize', function () {
                \Artisan::call('optimize');
                
                return response()->json([
                    'success' => true,
                    'message' => 'Application optimized successfully'
                ]);
            });
        });
    });
});

// Fallback route for API
Route::fallback(function () {
    return response()->json([
        'success' => false,
        'message' => 'API endpoint not found',
        'available_endpoints' => [
            'GET /api/health' => 'Health check',
            'POST /api/auth/login' => 'User login',
            'GET /api/dashboard/stats' => 'Dashboard statistics',
            'GET /api/meetings' => 'List meetings',
            'GET /api/participants' => 'List participants',
            'GET /api/review/stats' => 'Review statistics',
        ]
    ], 404);
});