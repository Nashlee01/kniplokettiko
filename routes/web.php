<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\KlantController;

// Homepagina.
Route::get('/', [HomeController::class, 'index'])->name('home');

// Authenticatie.
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

Route::get('/registreren', [AuthController::class, 'showRegister'])->name('register');
Route::post('/registreren', [AuthController::class, 'register'])->name('register.post');

Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// Klantbeheer (CRUD).
Route::get('/klanten', [KlantController::class, 'index'])->name('klanten.index');
Route::get('/klanten/nieuw', [KlantController::class, 'create'])->name('klanten.create');
Route::post('/klanten', [KlantController::class, 'store'])->name('klanten.store');
Route::get('/klanten/{klant}/bewerken', [KlantController::class, 'edit'])->name('klanten.edit');
Route::put('/klanten/{klant}', [KlantController::class, 'update'])->name('klanten.update');
Route::delete('/klanten/{klant}', [KlantController::class, 'destroy'])->name('klanten.destroy');
