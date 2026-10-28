<x-layout :title="$conversation->title($me)" body-class="page-chat" no-footer>
    <div class="chat" data-chat
         data-poll-url="{{ route('chats.poll', $conversation) }}"
         data-send-url="{{ route('chats.store', $conversation) }}"
         data-report-url="{{ route('chats.report', [$conversation, '__ID__']) }}"
         data-last-id="{{ $messages->last()['id'] ?? 0 }}"
         data-partner-read="{{ $partnerReadUpTo }}"
         data-club="{{ $conversation->isClub() ? '1' : '0' }}">

        <header class="chat__head">
            <a class="chat__back" href="{{ route('chats.index') }}" aria-label="Zurück zu den Nachrichten"><x-icon name="arrow-left" :size="20" /></a>
            @if ($conversation->isClub())
                <span class="avatar avatar--md avatar--club" aria-hidden="true"><x-icon name="flag" :size="18" /></span>
                <div class="chat__title">
                    <strong>Club-Chat</strong>
                    <a class="muted" href="{{ route('clubs.show', $conversation->course) }}">{{ $conversation->course->name }} · Mitglieder</a>
                </div>
            @elseif ($partner)
                <x-avatar :user="$partner" />
                <div class="chat__title">
                    <strong>{{ $partner->name }}</strong>
                    <a class="muted" href="{{ route('players.show', $partner->username) }}">{{ '@'.$partner->username }} · Profil</a>
                </div>
            @else
                <div class="chat__title"><strong>Gelöschtes Konto</strong></div>
            @endif
        </header>

        <ol class="chat__messages" data-chat-messages aria-live="polite">
            @forelse ($messages as $message)
                {{-- Erstanzeige serverseitig; neue Nachrichten fügt public/js/chat.js per textContent hinzu (gleiches Aussehen) --}}
                <li @class(['chat-msg', 'chat-msg--mine' => $message['mine'], 'chat-msg--hidden' => $message['hidden']])
                    data-message-id="{{ $message['id'] }}" data-day="{{ $message['day'] }}" data-sender="{{ $message['sender'] }}">
                    @if (! $message['mine'] && $conversation->isClub())
                        <span class="chat-msg__sender">
                            @if ($message['senderUrl'])<a href="{{ $message['senderUrl'] }}">{{ $message['sender'] }}</a>@else{{ $message['sender'] }}@endif
                        </span>
                    @endif
                    <div class="chat-msg__bubble">
                        <p class="chat-msg__body">{{ $message['body'] }}</p>
                        <span class="chat-msg__meta"><time>{{ substr($message['time'], -5) }}</time></span>
                    </div>
                    @unless ($message['mine'] || $message['hidden'])
                        <button type="button" class="chat-msg__report" data-report="{{ $message['id'] }}" aria-label="Nachricht melden">Melden</button>
                    @endunless
                </li>
            @empty
                <li class="chat__empty" data-chat-empty>
                    {{ $conversation->isClub() ? 'Noch ist es ruhig im Club-Chat. Schreib die erste Nachricht!' : 'Noch keine Nachrichten. Sag Hallo!' }}
                </li>
            @endforelse
        </ol>

        @if ($canSend)
            <form class="chat__form" method="post" action="{{ route('chats.store', $conversation) }}" data-chat-form>
                @csrf
                <label class="visually-hidden" for="chat-body">Nachricht</label>
                <textarea id="chat-body" name="body" rows="1" maxlength="2000" required placeholder="{{ $conversation->isClub() ? 'Nachricht an alle Mitglieder …' : 'Nachricht schreiben …' }}" data-chat-input></textarea>
                <button class="button button--primary" type="submit">Senden</button>
            </form>
            <p class="chat__hint">Enter sendet · Umschalt + Enter für neue Zeile</p>
        @else
            <p class="chat__locked">
                @if (! $me->hasVerifiedEmail())
                    Bitte bestätige zuerst deine E-Mail-Adresse, um zu schreiben.
                @else
                    In diesem Chat kannst du nicht mehr schreiben.
                @endif
            </p>
        @endif
    </div>

    <dialog class="report-dialog" data-report-dialog>
        <form method="post" class="form-stack" data-report-form>
            @csrf
            <h2>Nachricht melden</h2>
            <label class="field"><span>Grund</span>
                <select name="reason" required>
                    @foreach ($reasons as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                    @endforeach
                </select></label>
            <label class="field"><span>Hinweis (optional)</span>
                <textarea name="note" rows="3" maxlength="1000"></textarea></label>
            <div class="member-cta__actions">
                <button class="button button--primary" type="submit">Melden</button>
                <button class="button button--secondary" type="button" data-report-cancel>Abbrechen</button>
            </div>
        </form>
    </dialog>

    <x-slot:scripts>
        <script src="{{ asset_v('js/chat.js') }}" defer></script>
    </x-slot:scripts>
</x-layout>
