<!DOCTYPE html>
{{-- Fehlerdetails nur für das Team auf dem Testserver (bootstrap/app.php). Bewusst ohne Layout, damit sie auch erscheinen, wenn das Layout selbst fehlschlägt. --}}
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Fehler 500 · Details für das Team</title>
    <style>
        body { margin: 0; padding: 2rem 1rem; background: #f6f6f3; color: #0e1311; font: 15px/1.5 system-ui, sans-serif; }
        main { max-width: 60rem; margin: 0 auto; }
        h1 { font-size: 1.4rem; margin: 0 0 .25rem; }
        .box { background: #fff; border: 1px solid #e4e4de; border-left: 4px solid #e4572e; border-radius: 10px; padding: 1rem 1.25rem; margin: 1rem 0; }
        code, pre { font: 13px/1.5 ui-monospace, Consolas, monospace; }
        pre { white-space: pre-wrap; word-break: break-word; margin: .25rem 0 0; }
        .muted { color: #5a615d; }
    </style>
</head>
<body>
<main>
    <h1>Fehler 500 – Details für das Team</h1>
    <p class="muted">Nur sichtbar für angemeldete Redaktion/Admins auf dem Testserver. Bitte diesen Text kopieren und an Claude schicken.</p>

    <div class="box">
        <strong>{{ class_basename($exception) }}</strong>
        <pre>{{ $exception->getMessage() }}</pre>
    </div>

    <div class="box">
        <strong>Stelle</strong>
        <pre>{{ $file }}:{{ $exception->getLine() }}</pre>
        @if ($trace->isNotEmpty())
            <strong>Aufrufe im eigenen Code</strong>
            <pre>@foreach ($trace as $line){{ $line }}
@endforeach</pre>
        @endif
        @if ($exception->getPrevious())
            <strong>Ursprung</strong>
            <pre>{{ class_basename($exception->getPrevious()) }}: {{ $exception->getPrevious()->getMessage() }}</pre>
        @endif
    </div>

    <p class="muted">Adresse: <code>{{ $url }}</code> · {{ now()->format('d.m.Y H:i:s') }}</p>
    <p><a href="{{ url('/') }}">Zur Startseite</a></p>
</main>
</body>
</html>
