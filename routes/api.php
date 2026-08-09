<?php


use App\Domains\Contact\ManageContact\Api\Controllers\ContactController;
use App\Domains\Settings\ManageUsers\Api\Controllers\UserController;
use App\Domains\Vault\ManageVault\Api\Controllers\VaultController;
use Illuminate\Support\Facades\Route;



Route::middleware('auth:sanctum')->name('api.')->group(function () {

    // users
    Route::get('user', [UserController::class, 'user']);
    Route::apiResource('users', UserController::class)->only(['index', 'show']);

    // vaults
    Route::apiResource('vaults', VaultController::class);

    Route::post('import', [ContactController::class, 'import'])
        ->name('vaults.contacts.import');

    Route::get('import/{id}', [ContactController::class, 'show'])
        ->name('vaults.contacts.show');

    Route::post('import/contacts/cancel', [ContactController::class, 'cancel'])->name('import.contacts.cancel');
});
