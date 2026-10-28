<?php

namespace App\Modules\Community\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Community\Models\Conversation;
use App\Modules\Community\Models\Message;
use App\Modules\Community\Models\Report;
use App\Modules\Community\Support\Inbox;
use App\Modules\Courses\Models\Course;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

/**
 * Nachrichten: Posteingang, Chat (direkt oder Club), Senden, Abfragen neuer Nachrichten (Polling wie im Golf Marketplace), Melden.
 */
class ChatController extends Controller
{
    public const PAGE_SIZE = 80;

    public function index(Request $request): View
    {
        return view('community.inbox', ['rows' => Inbox::for($request->user()), 'me' => $request->user()]);
    }

    public function club(Request $request, Course $course): RedirectResponse
    {
        abort_unless($course->isPublished() && $request->user()->club_id === $course->id, 403);

        return redirect()->route('chats.show', Conversation::club($course));
    }

    public function show(Request $request, Conversation $conversation): View
    {
        $me = $request->user();
        abort_unless($conversation->canView($me), 404);
        $conversation->loadMissing(['course', 'userLow', 'userHigh']);

        $partner = $conversation->partnerFor($me);
        if ($partner && $me->isBlockedWith($partner)) {
            abort(404);
        }

        // Die neuesten Nachrichten laden (absteigend), dann für die Anzeige wieder aufsteigend sortieren
        $messages = $this->visibleMessages($conversation, $me)->reorder()->latest('id')->limit(self::PAGE_SIZE)->get()->reverse()->values();
        $conversation->markReadUpTo($me, (int) $messages->last()?->id);

        return view('community.chat', [
            'conversation' => $conversation,
            'me' => $me,
            'partner' => $partner,
            'messages' => $messages->map(fn (Message $m) => $m->toChatArray($me)),
            'canSend' => $this->canSend($conversation, $me),
            'partnerReadUpTo' => $partner ? $conversation->lastReadBy($partner) : 0,
            'reasons' => Report::REASONS,
        ]);
    }

    /** Neue Nachrichten nach ?after=<id> (JSON, nie zwischenspeichern). */
    public function poll(Request $request, Conversation $conversation): JsonResponse
    {
        $me = $request->user();
        abort_unless($conversation->canView($me), 404);

        $after = max(0, (int) $request->query('after', 0));
        $messages = $this->visibleMessages($conversation, $me)->where('id', '>', $after)->limit(self::PAGE_SIZE)->get();
        if ($messages->isNotEmpty()) {
            $conversation->markReadUpTo($me, (int) $messages->last()->id);
        }

        $partner = $conversation->partnerFor($me);

        return response()->json([
            'messages' => $messages->map(fn (Message $m) => $m->toChatArray($me))->values(),
            'partnerReadUpTo' => $partner ? $conversation->lastReadBy($partner) : 0,
        ])->header('Cache-Control', 'no-store, private');
    }

    public function store(Request $request, Conversation $conversation): JsonResponse|RedirectResponse
    {
        $me = $request->user();
        abort_unless($conversation->canView($me), 404);
        abort_unless($this->canSend($conversation, $me), 403, 'Du kannst in diesem Chat nicht schreiben.');

        $data = $request->validate(['body' => ['required', 'string', 'max:'.Message::MAX_LENGTH]]);
        $body = trim(preg_replace("/\n{3,}/", "\n\n", str_replace("\r\n", "\n", $data['body'])));
        abort_if($body === '', 422);

        $message = $conversation->messages()->forceCreate(['user_id' => $me->id, 'body' => $body]);
        $conversation->forceFill(['last_message_at' => now()])->save();
        $conversation->markReadUpTo($me, $message->id);

        if ($request->expectsJson()) {
            return response()->json(['message' => $message->load('sender')->toChatArray($me)], 201);
        }

        return redirect()->route('chats.show', $conversation);
    }

    public function report(Request $request, Conversation $conversation, Message $message): RedirectResponse
    {
        abort_unless($conversation->canView($request->user()) && $message->conversation_id === $conversation->id, 404);

        $data = $request->validate([
            'reason' => ['required', Rule::in(array_keys(Report::REASONS))],
            'note' => ['nullable', 'string', 'max:1000'],
        ]);
        Report::create($data + ['reporter_id' => $request->user()->id, 'reportable_type' => 'message', 'reportable_id' => $message->id]);

        return back()->with('status', 'Danke, die Nachricht wurde gemeldet.');
    }

    /** Nachrichten des Chats ohne die blockierter Personen. */
    private function visibleMessages(Conversation $conversation, $me)
    {
        return $conversation->messages()->reorder('id')->with('sender')
            ->where(fn ($q) => $q->whereNull('user_id')->orWhereNotIn('user_id', $me->blockedIds()));
    }

    private function canSend(Conversation $conversation, $me): bool
    {
        if (! $me->hasVerifiedEmail()) {
            return false;
        }

        if ($conversation->isClub()) {
            return $conversation->canView($me);
        }

        $partner = $conversation->partnerFor($me);

        return $partner !== null && ! $me->isBlockedWith($partner);
    }
}
