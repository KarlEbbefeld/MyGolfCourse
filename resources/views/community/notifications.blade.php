<x-layout title="Mitteilungen">
    <section class="page-head">
        <div class="container">
            <p class="eyebrow">Mitteilungen</p>
            <h1 class="page-head__title">Was gibt’s Neues?</h1>
        </div>
    </section>
    <section class="section section--tight">
        <div class="container">
            @if ($notifications->isEmpty())
                <div class="empty-state"><p>Noch keine Mitteilungen.</p></div>
            @else
                <ul class="inbox">
                    @foreach ($notifications as $notification)
                        @php($data = $notification->data)
                        <li>
                            <a @class(['inbox__item', 'is-unread' => $notification->read_at === null])
                               href="{{ ! empty($data['username']) ? route('players.show', $data['username']) : route('community.index') }}">
                                <span class="avatar avatar--md avatar--club" aria-hidden="true"><x-icon name="user" :size="18" /></span>
                                <span class="inbox__text">
                                    <strong>{{ $data['text'] ?? 'Neue Mitteilung' }}</strong>
                                    <span class="inbox__preview">{{ $notification->created_at->timezone(config('app.display_timezone', 'Europe/Berlin'))->format('d.m.Y, H:i') }}</span>
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</x-layout>
