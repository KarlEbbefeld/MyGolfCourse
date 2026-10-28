<?php

namespace App\Console\Commands;

use App\Modules\Courses\Models\Course;
use App\Modules\Explorer\Models\Flyover;
use Database\Seeders\DemoCourseSeeder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnose auf dem Server (php artisan mgc:status): Pfade, Medien-Dateien, Tabellen, Überflüge.
 * Wird von scripts/hostinger/upload-media.cmd und status.cmd aufgerufen – wir testen nur auf dem Hosting.
 */
class StatusCommand extends Command
{
    protected $signature = 'mgc:status';

    protected $description = 'Zeigt den Zustand von My Golf Course auf diesem Server';

    public function handle(): int
    {
        $this->line('== Umgebung');
        $this->line('  APP_ENV: '.app()->environment().' · APP_URL: '.config('app.url'));
        $this->line('  DB: '.config('database.default'));

        $this->line('== E-Mail (ohne Passwort)');
        $mailer = (string) config('mail.default');
        $this->line('  Mailer: '.$mailer.' · Host: '.(config('mail.mailers.smtp.host') ?? '–').' · Port: '.(config('mail.mailers.smtp.port') ?? '–')
            .' · Absender: '.(config('mail.from.address') ?? '–'));
        $this->line('  Passwort gesetzt: '.(filled(config('mail.mailers.smtp.password')) ? 'ja' : 'NEIN'));
        if ($mailer === 'log') {
            $this->warn('  MAIL_MAILER=log – es werden KEINE Mails verschickt. Lösung: use-marketplace-mail.cmd ausführen.');
        }

        $this->line('== Letzte Fehler im Protokoll');
        $logs = glob(storage_path('logs/*.log')) ?: [];
        usort($logs, fn ($a, $b) => filemtime($b) <=> filemtime($a));
        $errors = [];
        foreach (array_slice($logs, 0, 2) as $log) {
            foreach (array_reverse(file($log, FILE_IGNORE_NEW_LINES) ?: []) as $line) {
                if (preg_match('/^\[[^\]]+\] \w+\.(ERROR|CRITICAL|ALERT|EMERGENCY): /', $line)) {
                    // Meldung + Stelle („at /pfad/Datei.php:123“), ohne den langen Stacktrace
                    $location = preg_match('/ at (\S+:\d+)\)/', $line, $m) ? '  → '.str_replace(base_path().'/', '', $m[1]) : '';
                    $message = preg_replace('/ \{"(exception|userId)".*$/', '', $line);
                    $errors[] = mb_strimwidth($message, 0, 400, ' …').$location;
                }
                if (count($errors) >= 5) {
                    break 2;
                }
            }
        }
        $errors === [] ? $this->line('  keine') : array_map(fn ($e) => $this->line('  '.$e), $errors);

        $this->line('== Öffentlicher Ordner');
        $public = base_path('public');
        $this->line('  '.$public.(is_link($public) ? ' → '.readlink($public) : ' (kein Verweis!)'));
        $this->line('  public_path(): '.public_path());

        $this->line('== Dateien in media/demo');
        $files = glob(public_path('media/demo').'/*') ?: [];
        if ($files === []) {
            $this->warn('  keine Dateien gefunden');
        }
        foreach ($files as $file) {
            $name = basename($file);
            $hint = preg_match('/^bahn-\d+\.(mp4|jpg)$/', $name) || $name === 'LIESMICH.txt' ? '' : '   ← Name passt nicht (erwartet bahn-<Nr>.mp4)';
            $this->line(sprintf('  %-28s %8.1f MB%s', $name, filesize($file) / 1048576, $hint));
        }

        $this->line('== Tabellen');
        foreach (['courses', 'holes', 'panoramas', 'points_of_interest', 'flyovers'] as $table) {
            $this->line('  '.str_pad($table, 20).(Schema::hasTable($table) ? 'vorhanden' : 'FEHLT – deploy.cmd ausführen (Migration)'));
        }

        if (! Schema::hasTable('flyovers')) {
            return self::FAILURE;
        }

        $this->line('== Demoplatz');
        $course = Course::query()->where('slug', DemoCourseSeeder::SLUG)->first();
        if (! $course) {
            $this->warn('  nicht vorhanden – DEMO_DATA=true in der .env? Dann deploy.cmd ausführen');

            return self::FAILURE;
        }
        $this->line('  '.$course->name.' · '.$course->holes()->count().' Bahnen');

        $this->line('== Überflüge');
        $flyovers = Flyover::query()->with('hole')->get();
        if ($flyovers->isEmpty()) {
            $this->warn('  keine – Video als public/media/demo/bahn-1.mp4 hochladen (upload-media.cmd)');
        }
        foreach ($flyovers as $flyover) {
            $exists = is_file(public_path($flyover->video_url)) ? 'Datei ok' : 'DATEI FEHLT';
            $this->line('  Bahn '.$flyover->hole?->number.': '.$flyover->video_url.' ('.$exists.')');
        }

        return self::SUCCESS;
    }
}
