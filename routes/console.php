<?php
use Illuminate\Support\Facades\Artisan;
Artisan::command('getdone:info', function () { $this->info('GetDone Laravel API; jalankan php artisan migrate --force.'); });
