<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::view('/login', 'login')->name('login');
Route::view('/register', 'register')->name('register');
Route::view('/dashboard', 'dashboard')->name('dashboard');
Route::view('/merchant', 'merchant')->name('merchant');
Route::view('/plans', 'plans')->name('plans');
Route::view('/customers', 'customers')->name('customers');
Route::view('/customers/{id}', 'customer-show')->name('customer.show');
