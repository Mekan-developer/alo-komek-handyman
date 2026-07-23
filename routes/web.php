<?php

use App\Http\Controllers\BannerController;
use App\Http\Controllers\CategoryContentController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CityController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\MasterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OblastController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RegionController;
use App\Http\Controllers\SettingController;
use App\Http\Controllers\SystemStatusController;
use App\Http\Controllers\TilesController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/tiles/{file}', [TilesController::class, 'serve'])->where('file', '.+\.pmtiles');

Route::get('/tiles/{z}/{x}/{y}.pbf', function (int $z, int $x, int $y) {
    $db = new PDO('sqlite:'.config('services.mbtiles.path'), null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    // mbtiles хранит Y в TMS-схеме (перевёрнутая ось)
    $tmsY = (1 << $z) - 1 - $y;

    $stmt = $db->prepare(
        'SELECT tile_data FROM tiles
         WHERE zoom_level = ? AND tile_column = ? AND tile_row = ?'
    );
    $stmt->execute([$z, $x, $tmsY]);
    $tile = $stmt->fetchColumn();

    if ($tile === false) {
        return response('', 204); // пустой тайл — норм для моря/пустых зон
    }

    return response($tile)
        ->header('Content-Type', 'application/x-protobuf')
        ->header('Content-Encoding', 'gzip')  // тайлы в mbtiles уже gzip-сжаты
        ->header('Cache-Control', 'public, max-age=86400');
})->where(['z' => '\d{1,2}', 'x' => '\d+', 'y' => '\d+']);

Route::view('/map', 'map');

Route::post('/locale/{locale}', function (string $locale) {
    $supported = ['ru', 'tk'];
    if (in_array($locale, $supported, strict: true)) {
        session(['locale' => $locale]);
    }

    return back();
})->name('locale.set');

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/system-status', SystemStatusController::class)->name('system.status');

    // Profile — accessible to all authenticated users (all roles)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Dashboard & all other admin sections — administrator and manager only
    Route::middleware('role:administrator,manager')->group(function () {
        Route::get('/dashboard', [DashboardController::class, 'index'])
            ->middleware('verified')
            ->name('dashboard');

        Route::resource('oblasts', OblastController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('regions', RegionController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('cities', CityController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
        Route::post('categories/{category}/content', [CategoryContentController::class, 'upsert'])->name('categories.content.upsert');
        Route::get('masters/map', [MasterController::class, 'map'])->name('masters.map');
        Route::get('masters/{master}/trajectory', [MasterController::class, 'trajectory'])->name('masters.trajectory');
        Route::post('masters/{master}/reset-balance', [MasterController::class, 'resetBalance'])->name('masters.reset-balance');
        Route::resource('masters', MasterController::class)->only(['index', 'store', 'destroy']);
        Route::post('masters/{master}', [MasterController::class, 'update'])->name('masters.update');

        Route::resource('banners', BannerController::class)->only(['index', 'store', 'destroy']);
        Route::post('banners/{banner}', [BannerController::class, 'update'])->name('banners.update');
        Route::post('banners/{banner}/toggle', [BannerController::class, 'toggle'])->name('banners.toggle');

        Route::post('clients/{client}/toggle-block', [ClientController::class, 'toggleBlock'])->name('clients.toggle-block');
        Route::resource('clients', ClientController::class)->only(['index', 'store', 'update', 'destroy']);

        Route::resource('orders', OrderController::class)->only(['index', 'show', 'store', 'update', 'destroy']);
        Route::post('orders/{order}/assign', [OrderController::class, 'assign'])->name('orders.assign');
        Route::post('orders/{order}/price', [OrderController::class, 'setPrice'])->name('orders.set-price');
        Route::post('orders/{order}/status', [OrderController::class, 'updateStatus'])->name('orders.update-status');
        Route::get('orders/{order}/master-trajectory', [OrderController::class, 'masterTrajectoryForOrder'])->name('orders.master-trajectory');

        Route::prefix('notifications')->name('notifications.')->group(function () {
            Route::get('/', [NotificationController::class, 'index'])->name('index');
            Route::post('{id}/read', [NotificationController::class, 'markRead'])->name('read');
            Route::post('read-all', [NotificationController::class, 'markAllRead'])->name('read-all');
            Route::delete('{id}', [NotificationController::class, 'destroy'])->name('destroy');
            Route::delete('/', [NotificationController::class, 'destroyAll'])->name('destroy-all');
        });

        // Administrators only — managers have no access to these sections
        Route::middleware('role:administrator')->group(function () {
            Route::resource('users', UserController::class)->only(['index', 'store', 'update', 'destroy']);

            Route::get('payments', [PaymentController::class, 'index'])->name('payments.index');
            Route::post('payments/{master}/payout', [PaymentController::class, 'payout'])->name('payments.payout');
        });

        Route::get('settings', [SettingController::class, 'index'])->name('settings.index');
        Route::put('settings', [SettingController::class, 'update'])->name('settings.update');
    });
});

require __DIR__.'/auth.php';
