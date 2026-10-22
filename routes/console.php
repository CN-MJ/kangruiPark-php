<?php

use App\Support\PublicStorage;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote')->hourly();

Artisan::command('storage:sync-web {--path=uploads : 相对 public disk 的子目录}', function () {
    $subdir = (string) $this->option('path');
    $diskRoot = Storage::disk('public')->path('');
    $webRoot = public_path('storage');
    $this->info('disk: '.$diskRoot);
    $this->info('web:  '.$webRoot);

    if (is_link($webRoot) || (function_exists('is_link') && @readlink($webRoot))) {
        $this->comment('public/storage 已是链接，一般无需同步。仍会检查缺失文件…');
    }

    $stats = PublicStorage::syncDiskToWeb($subdir !== '' ? $subdir : null);
    $this->info("copied={$stats['copied']} skipped={$stats['skipped']} missing={$stats['missing_source']}");
})->purpose('把 storage/app/public 中的上传文件同步到网站可读的 public/storage');