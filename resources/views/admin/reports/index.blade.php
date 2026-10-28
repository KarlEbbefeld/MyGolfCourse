<x-admin-layout title="Meldungen">
    <div class="admin-head">
        <h1>Meldungen</h1>
        <div class="admin-head__actions">
            <a class="button {{ $showDone ? 'button--secondary' : 'button--primary' }}" href="{{ route('admin.reports.index') }}">Offen</a>
            <a class="button {{ $showDone ? 'button--primary' : 'button--secondary' }}" href="{{ route('admin.reports.index', ['status' => 'erledigt']) }}">Erledigt</a>
        </div>
    </div>

    @forelse ($reports as $report)
        @php($target = $report->target())
        <article class="admin-card report-card">
            <p class="muted">{{ $report->created_at->timezone(config('app.display_timezone', 'Europe/Berlin'))->format('d.m.Y H:i') }}
                · gemeldet von {{ $report->reporter?->name ?? 'gelöschtem Konto' }}
                · <strong>{{ $report->reasonLabel() }}</strong></p>
            @if ($report->note)
                <p>„{{ $report->note }}“</p>
            @endif

            @if ($report->reportable_type === 'message')
                @if ($target)
                    <blockquote class="report-card__quote">
                        <strong>{{ $target->sender?->name ?? 'Gelöschtes Konto' }}</strong>
                        @if ($target->conversation?->isClub()) im Club-Chat {{ $target->conversation->course?->name }} @else in einer Direktnachricht @endif:
                        <br>{{ $target->body }}
                        @if ($target->isHidden()) <br><em class="muted">bereits ausgeblendet</em> @endif
                    </blockquote>
                @else
                    <p class="muted">Nachricht existiert nicht mehr.</p>
                @endif
            @else
                <p>Person:
                    @if ($target?->avatar_path)
                        <img src="{{ route('players.avatar', ['user' => $target->username, 'v' => substr(md5($target->avatar_path), 0, 8)]) }}" alt="Profilbild" class="avatar avatar--xl avatar--image" style="vertical-align: middle; margin-right: .75rem">
                    @endif
                    @if ($target?->username)
                        <a href="{{ route('players.show', $target->username) }}" target="_blank" rel="noopener">{{ $target->name }} ({{ '@'.$target->username }}) ↗</a>
                    @else
                        <span class="muted">Konto gelöscht</span>
                    @endif
                </p>
            @endif

            @if ($report->status === 'open')
                <div class="admin-head__actions">
                    @if ($report->reportable_type === 'user' && $target?->avatar_path)
                        <form method="post" action="{{ route('admin.reports.update', $report) }}">
                            @csrf @method('put')
                            <input type="hidden" name="action" value="remove_avatar">
                            <button class="button button--primary" type="submit">Profilbild entfernen</button>
                        </form>
                    @endif
                    @if ($report->reportable_type === 'message' && $target && ! $target->isHidden())
                        <form method="post" action="{{ route('admin.reports.update', $report) }}">
                            @csrf @method('put')
                            <input type="hidden" name="action" value="hide">
                            <button class="button button--primary" type="submit">Nachricht ausblenden</button>
                        </form>
                    @endif
                    <form method="post" action="{{ route('admin.reports.update', $report) }}">
                        @csrf @method('put')
                        <input type="hidden" name="action" value="resolve">
                        <button class="button button--secondary" type="submit">Erledigt</button>
                    </form>
                    <form method="post" action="{{ route('admin.reports.update', $report) }}">
                        @csrf @method('put')
                        <input type="hidden" name="action" value="dismiss">
                        <button class="button button--secondary" type="submit">Verwerfen</button>
                    </form>
                </div>
            @else
                <p class="muted">{{ $report->status === 'dismissed' ? 'Verworfen' : 'Erledigt' }} am {{ $report->resolved_at?->format('d.m.Y') }}</p>
            @endif
        </article>
    @empty
        <p class="muted">{{ $showDone ? 'Keine erledigten Meldungen.' : 'Keine offenen Meldungen.' }}</p>
    @endforelse
</x-admin-layout>
