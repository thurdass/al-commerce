<?php

use App\Actions\DeleteAccount;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;
use Illuminate\Support\Facades\Storage;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('accounts:recover-deletions', function (DeleteAccount $deleteAccount): int {
    $failed = false;

    foreach (Storage::disk(DeleteAccount::BACKUP_DISK)->directories() as $directory) {
        if (! ctype_digit($directory)) {
            continue;
        }

        try {
            $deleteAccount->recover((int) $directory);
        } catch (Throwable $exception) {
            report($exception);
            $failed = true;
        }
    }

    return $failed ? 1 : 0;
})->purpose('Restore interrupted account deletions and clean up completed backups');

Schedule::command('accounts:recover-deletions')->everyFiveMinutes()->withoutOverlapping();
