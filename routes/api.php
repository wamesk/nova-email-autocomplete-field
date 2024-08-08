<?php

use Illuminate\Support\Facades\Route;

Route::post('email-check-exists', [\Wame\NovaEmailAutocompleteField\Http\Controllers\EmailController::class, 'checkEmailExists']);