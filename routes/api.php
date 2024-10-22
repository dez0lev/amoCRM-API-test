<?php

use App\Http\Controllers\DataController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;


Route::post('/data', [Datacontroller::class, 'store']);
