<x-layout title="Nachrichten">
    <section class="page-head">
        <div class="container">
            <p class="eyebrow">Nachrichten</p>
            <h1 class="page-head__title">Deine Chats</h1>
            @unless ($me->hasVerifiedEmail())
                <p class="notice">Zum Schreiben bitte zuerst deine E-Mail-Adresse bestätigen (siehe <a href="{{ route('account.show') }}">Mein Konto</a>).</p>
            @endunless
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            @if ($rows->isEmpty())
                <div class="empty-state">
                    <p>Noch keine Chats. Schreib jemandem über sein Profil oder tritt einem Club bei, um im Club-Chat mitzureden.</p>
                    <a class="button button--primary" href="{{ route('community.index') }}">Zur Community</a>
                </div>
            @else
                <ul class="inbox">
                    @foreach ($rows as $row)
                        @php
                            $conversation = $row['conversation'];
                            $partner = $conversation->partnerFor($me);
                        @endphp
                        <li>
                            <a @class(['inbox__item', 'is-unread' => $row['unread'] > 0]) href="{{ route('chats.show', $conversation) }}">
                                @if ($conversation->isClub())
                                    <span class="avatar avatar--md avatar--club" aria-hidden="true"><x-icon name="flag" :size="18" /></span>
                                @else
                                    <x-avatar :user="$partner" />
                                @endif
                                <span class="inbox__text">
                                    <strong>{{ $conversation->title($me) }}</strong>
                                    <span class="inbox__preview">
                                        @if ($row['last'])
                                            @if ($row['last']->user_id === $me->id) Du: @elseif ($conversation->isClub()) {{ $row['last']->sender?->name ?? 'Gelöscht' }}: @endif
                                            {{ $row['last']->isHidden() ? 'Nachricht ausgeblendet' : \Illuminate\Support\Str::limit($row['last']->body, 80) }}
                                        @else
                                            Noch keine Nachrichten – sag Hallo!
                                        @endif
                                    </span>
                                </span>
                                <span class="inbox__side">
                                    @if ($row['last'])
                                        <span class="muted num">{{ $row['last']->created_at->timezone(config('app.display_timezone', 'Europe/Berlin'))->isToday() ? $row['last']->created_at->timezone(config('app.display_timezone', 'Europe/Berlin'))->format('H:i') : $row['last']->created_at->format('d.m.') }}</span>
                                    @endif
                                    @if ($row['unread'] > 0)
                                        <span class="count-badge">{{ $row['unread'] > 99 ? '99+' : $row['unread'] }}</span>
                                    @endif
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            @endif
        </div>
    </section>
</x-layout>
