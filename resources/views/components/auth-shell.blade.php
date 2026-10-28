@props(['title', 'asideTitle' => 'Jede Bahn kennen, bevor du abschlägst.', 'asideText' => 'Mit einem Konto merkst du dir Plätze, siehst deinen Verlauf und stellst Einheit und Lieblingsabschlag ein.'])
<x-layout :title="$title" :toast="false" no-footer>
    <div class="auth">
        <aside class="auth__aside">
            <div class="auth__aside-text">
                <p class="eyebrow">My Golf Course</p>
                <h2>{{ $asideTitle }}</h2>
                <p>{{ $asideText }}</p>
            </div>
        </aside>
        <div class="auth__main">
            <div class="auth__form">
                @if (session('status'))
                    <p class="status-box" role="status">{{ session('status') }}</p>
                @endif
                @if ($errors->any())
                    <div class="error-box" role="alert">
                        @foreach ($errors->all() as $error)
                            <p>{{ $error }}</p>
                        @endforeach
                    </div>
                @endif
                {{ $slot }}
            </div>
        </div>
    </div>
</x-layout>
