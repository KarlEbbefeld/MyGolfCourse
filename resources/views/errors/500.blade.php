<!DOCTYPE html>
{{-- Ohne Layout und ohne Datenbankzugriffe, damit die Seite auch erscheint, wenn genau dort der Fehler liegt --}}
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="robots" content="noindex">
    <title>Da ist etwas schiefgelaufen · My Golf Course</title>
    <style>
        body { margin: 0; min-height: 100vh; display: grid; place-items: center; padding: 1rem; background: #0b2219; color: #f6f6f3; font: 16px/1.6 system-ui, sans-serif; text-align: center; }
        h1 { font-size: 1.75rem; margin: 0 0 .5rem; letter-spacing: -.02em; }
        p { color: rgb(246 246 243 / .75); max-width: 30rem; margin: 0 auto 1.5rem; }
        a { display: inline-block; padding: .75rem 1.25rem; border-radius: 10px; background: #f6f6f3; color: #0e1311; font-weight: 600; text-decoration: none; }
    </style>
</head>
<body>
<main>
    <h1>Da ist etwas schiefgelaufen.</h1>
    <p>Die Seite konnte gerade nicht geladen werden. Bitte versuch es in einem Moment noch einmal.</p>
    <a href="{{ url('/') }}">Zur Startseite</a>
</main>
</body>
</html>
