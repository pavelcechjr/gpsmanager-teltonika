<?php

use App\Http\Controllers\AdminPageController;
use App\Http\Controllers\AlarmController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\MicrosoftAuthController;
use App\Http\Controllers\CalibrationController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DriverController;
use App\Http\Controllers\GroupController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\VehicleController;
use App\Http\Controllers\LocationController;
use App\Http\Controllers\MaintenanceController;
use App\Http\Controllers\MonitorController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\RefuelingController;
use App\Http\Controllers\StatsController;
use App\Http\Controllers\TripController;
use App\Http\Controllers\TripExportController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'store']);

    // Microsoft 365 SSO (Azure AD)
    Route::get('/auth/microsoft',          [MicrosoftAuthController::class, 'redirect'])->name('auth.microsoft');
    Route::get('/auth/microsoft/callback', [MicrosoftAuthController::class, 'callback']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');
    Route::redirect('/', '/dashboard');
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Kniha jízd (Pavel Sr hlavní view — soupis cest, filtr vozidlo + období, default 24 h)
    Route::get('/kniha-jizd/statistiky', StatsController::class)->name('kniha-jizd.statistiky');
    Route::get('/kniha-jizd/export', TripExportController::class)->name('kniha-jizd.export');
    Route::get('/kniha-jizd',             [TripController::class, 'index'])->name('kniha-jizd.index');
    Route::get('/kniha-jizd/{trip}/edit',        [TripController::class, 'edit'])->name('kniha-jizd.edit');
    Route::put('/kniha-jizd/{trip}',             [TripController::class, 'update'])->name('kniha-jizd.update');
    Route::post('/kniha-jizd/{trip}/toggle-type', [TripController::class, 'toggleType'])->name('kniha-jizd.toggle-type');
    Route::get('/kniha-jizd/{trip}',             [TripController::class, 'show'])->name('kniha-jizd.show');

    Route::resource('tankovani', RefuelingController::class)->except(['show']);
    Route::resource('udrzba',    MaintenanceController::class)->except(['show']);

    Route::get('/vozidla/skupiny',                    [GroupController::class, 'vehicleIndex'])->name('vozidla.skupiny');
    Route::get('/vozidla/skupiny/create',             [GroupController::class, 'vehicleCreate'])->name('vozidla.skupiny.create');
    Route::post('/vozidla/skupiny',                   [GroupController::class, 'vehicleStore'])->name('vozidla.skupiny.store');
    Route::get('/vozidla/skupiny/{group}/edit',       [GroupController::class, 'vehicleEdit'])->name('vozidla.skupiny.edit');
    Route::put('/vozidla/skupiny/{group}',            [GroupController::class, 'vehicleUpdate'])->name('vozidla.skupiny.update');
    Route::delete('/vozidla/skupiny/{group}',         [GroupController::class, 'vehicleDestroy'])->name('vozidla.skupiny.destroy');
    // Vozidlo — kalibrace tachometru (per vozidlo)
    Route::get('/vozidla/{vozidla}/kalibrace/create',              [CalibrationController::class, 'create'])->name('vozidla.kalibrace.create');
    Route::post('/vozidla/{vozidla}/kalibrace',                    [CalibrationController::class, 'store'])->name('vozidla.kalibrace.store');
    Route::get('/vozidla/{vozidla}/kalibrace/{calibration}/edit',  [CalibrationController::class, 'edit'])->name('vozidla.kalibrace.edit');
    Route::put('/vozidla/{vozidla}/kalibrace/{calibration}',       [CalibrationController::class, 'update'])->name('vozidla.kalibrace.update');
    Route::delete('/vozidla/{vozidla}/kalibrace/{calibration}',    [CalibrationController::class, 'destroy'])->name('vozidla.kalibrace.destroy');

    Route::resource('vozidla', VehicleController::class);

    Route::get('/zarizeni/skupiny',                   [GroupController::class, 'deviceIndex'])->name('zarizeni.skupiny');
    Route::get('/zarizeni/skupiny/create',            [GroupController::class, 'deviceCreate'])->name('zarizeni.skupiny.create');
    Route::post('/zarizeni/skupiny',                  [GroupController::class, 'deviceStore'])->name('zarizeni.skupiny.store');
    Route::get('/zarizeni/skupiny/{group}/edit',      [GroupController::class, 'deviceEdit'])->name('zarizeni.skupiny.edit');
    Route::put('/zarizeni/skupiny/{group}',           [GroupController::class, 'deviceUpdate'])->name('zarizeni.skupiny.update');
    Route::delete('/zarizeni/skupiny/{group}',        [GroupController::class, 'deviceDestroy'])->name('zarizeni.skupiny.destroy');
    Route::view('/zarizeni/typy',    'placeholder', ['title' => 'Typy zařízení',    'desc' => 'Konfigurační šablony pro modely Teltonika (IO mapping, posílání pozic).'])->name('zarizeni.typy');
    Route::resource('zarizeni', DeviceController::class)->except(['show']);

    // Alarmy — order matters: literal paths BEFORE dynamic {event}
    Route::get('/alarmy/pravidla',                  [AlarmController::class, 'rules'])->name('alarmy.pravidla');
    Route::get('/alarmy/pravidla/create',           [AlarmController::class, 'ruleCreate'])->name('alarmy.pravidla.create');
    Route::post('/alarmy/pravidla',                 [AlarmController::class, 'ruleStore'])->name('alarmy.pravidla.store');
    Route::get('/alarmy/pravidla/{rule}/edit',      [AlarmController::class, 'ruleEdit'])->name('alarmy.pravidla.edit');
    Route::put('/alarmy/pravidla/{rule}',           [AlarmController::class, 'ruleUpdate'])->name('alarmy.pravidla.update');
    Route::delete('/alarmy/pravidla/{rule}',        [AlarmController::class, 'ruleDestroy'])->name('alarmy.pravidla.destroy');
    Route::get('/alarmy/historie',                  [AlarmController::class, 'historie'])->name('alarmy.historie');
    Route::post('/alarmy/{event}/resolve',          [AlarmController::class, 'resolve'])->name('alarmy.resolve');
    Route::get('/alarmy',                           [AlarmController::class, 'aktivni'])->name('alarmy');

    Route::resource('mista', LocationController::class)->except(['show']);
    Route::get('/monitor', [MonitorController::class, 'index'])->name('monitor');
    Route::get('/api/monitor/latest', [MonitorController::class, 'latest'])->name('api.monitor.latest');

    Route::resource('uzivatele', UserController::class)->except(['show']);
    Route::resource('ridici', DriverController::class);

    Route::get('/servery',     [AdminPageController::class, 'servery'])->name('servery');
    Route::get('/konfigurace', [AdminPageController::class, 'konfigurace'])->name('konfigurace');
    Route::get('/casove-zony', [AdminPageController::class, 'casoveZony'])->name('casove-zony');

    Route::get('/profil', [ProfileController::class, 'show'])->name('profil');
    Route::patch('/profil', [ProfileController::class, 'update'])->name('profil.update');
    Route::patch('/profil/heslo', [ProfileController::class, 'password'])->name('profil.password');
});
