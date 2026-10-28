<x-layout title="So funktioniert’s" description="Wie My Golf Course Golfplätze aufnimmt, vermisst und als Überflug, 360°-Ansicht und Plan zeigt.">
    <section class="page-head">
        <div class="container">
            <p class="eyebrow">So funktioniert’s</p>
            <h1 class="page-head__title">Vom Drohnenflug zur Rundenstrategie</h1>
            <p class="page-head__lead">Wir nehmen jede Bahn mit der Drohne auf, vermessen wichtige Hindernisse vor Ort und verbinden alles zu einer digitalen Platzbegehung.</p>
        </div>
    </section>

    <section class="section section--tight">
        <div class="container">
            <div class="modes">
                <article class="mode-card">
                    <span class="mode-card__visual"><x-icon name="video" :size="26" /></span>
                    <h3>Überflug mit Haltepunkten</h3>
                    <p>Die Drohne fliegt vom Abschlag zum Grün. An den wichtigen Stellen hält der Film an und zeigt, was dich dort erwartet – Bunker, Bäume, Wasser und Distanzen.</p>
                    <span class="mode-card__number">01</span>
                </article>
                <article class="mode-card">
                    <span class="mode-card__visual"><x-icon name="sphere" :size="26" /></span>
                    <h3>360° wie Street View</h3>
                    <p>An mehreren Positionen jeder Bahn kannst du dich frei umsehen und per Pfeil zur nächsten Position weitergehen. Marker zeigen Hindernisse mit Höhe und Entfernung.</p>
                    <span class="mode-card__number">02</span>
                </article>
                <article class="mode-card">
                    <span class="mode-card__visual"><x-icon name="plan" :size="26" /></span>
                    <h3>Plan und Distanzen</h3>
                    <p>Die Bahngrafik mit allen Hindernissen und das Distanzband vom gewählten Abschlag – wie ein digitales Yardage Book.</p>
                    <span class="mode-card__number">03</span>
                </article>
            </div>
        </div>
    </section>

    <section class="section section--white">
        <div class="container split">
            <div>
                <p class="eyebrow">Messdaten</p>
                <h2 class="section__title">Woher kommen die Zahlen?</h2>
                <ul class="checklist">
                    <li><x-icon name="check" /><span><strong>Baumhöhen</strong> messen wir vor Ort mit einem Laser-Entfernungsmesser. Angegeben sind immer Genauigkeit und Messdatum.</span></li>
                    <li><x-icon name="check" /><span><strong>Distanzen</strong> berechnen wir aus den Positionen von Abschlag, Hindernis und Grün. Sie sind als Orientierung gedacht, nicht als offizielle Platzvermessung.</span></li>
                    <li><x-icon name="check" /><span><strong>Aufnahmen</strong> tragen ihr Datum – Plätze verändern sich, wir fliegen regelmäßig nach.</span></li>
                </ul>
            </div>
            <div class="sample-card">
                <p class="sample-card__type">Hinweis</p>
                <p class="sample-card__title">Ohne Konto nutzbar</p>
                <p style="color: var(--color-on-dark-muted)">Alle Plätze und Bahnen kannst du ohne Anmeldung erkunden. Mit einem kostenlosen Konto merkst du dir Plätze und wählst Meter oder Yards.</p>
                <a class="button button--light" href="{{ route('courses.index') }}">Plätze entdecken</a>
            </div>
        </div>
    </section>
</x-layout>
