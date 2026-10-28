<x-admin-layout title="Plätze">
    <div class="admin-head">
        <h1>Plätze</h1>
        <a class="button button--primary" href="{{ route('admin.courses.create') }}">+ Neuer Platz</a>
    </div>

    <table class="admin-table">
        <thead>
            <tr><th>Name</th><th>Ort</th><th>Bahnen</th><th>Status</th><th></th></tr>
        </thead>
        <tbody>
            @forelse ($courses as $course)
                <tr>
                    <td><a href="{{ route('admin.courses.edit', $course) }}"><strong>{{ $course->name }}</strong></a>
                        @if ($course->is_demo) <span class="badge">Demo</span> @endif</td>
                    <td>{{ $course->location() }}</td>
                    <td class="num">{{ $course->holes_count }}</td>
                    <td><span class="status status--{{ $course->status->value }}">{{ $course->status->label() }}</span></td>
                    <td class="admin-table__actions">
                        <a href="{{ route('admin.courses.edit', $course) }}">Bearbeiten</a>
                        @if ($course->isPublished())
                            <a href="{{ route('courses.show', $course) }}" target="_blank" rel="noopener">Ansehen ↗</a>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Noch keine Plätze.</td></tr>
            @endforelse
        </tbody>
    </table>

    <section class="admin-card admin-help">
        <h2>So legt ihr einen Platz an</h2>
        <ol>
            <li><strong>Neuer Platz:</strong> Name, Ort, Lochzahl, Abschlagfarben – Lage per Klick auf die Karte.</li>
            <li><strong>Jede Bahn öffnen:</strong> Abschläge, Grün, Bunker, Bäume und Wasser auf dem Satellitenbild setzen, Baumhöhen eintragen.</li>
            <li><strong>Medien hochladen:</strong> Überflug-Video, 360°-Fotos (DJI „Sphäre“) und optional die Bahngrafik des Clubs.</li>
            <li><strong>360°-Fotos ausrichten:</strong> „Ausrichten“ → im Panorama auf das Grün klicken.</li>
            <li><strong>Veröffentlichen</strong> (nur Rolle Admin) – vorher prüfen, dass keine Personen erkennbar sind.</li>
        </ol>
    </section>
</x-admin-layout>
