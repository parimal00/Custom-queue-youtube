<?php

use App\CustomJob\CustomJob;
use App\CustomJob\Jobs\DummyJob;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});


Route::get('test-job', function () {
    $job = new DummyJob();
    CustomJob::push($job);
});
