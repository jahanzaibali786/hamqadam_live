{{-- Terms & conditions: each control directive occupies its own line. --}}
<section class="hqed-policy-hero">
    <div class="hqed-wrap">
        <span class="hqed-eyebrow">{{ $data['hero']['eyebrow'] }}</span>
        <h1>{{ $data['hero']['title'] }}</h1>
        <p>{{ $data['hero']['description'] }}</p>
        <div class="hqed-pills">
            @foreach ($data['hero']['badges'] as $badge)
                <span>{{ $badge['text'] }}</span>
            @endforeach
        </div>
    </div>
</section>

<section class="hqed-section hqed-policy-body">
    <div class="hqed-wrap hqed-document-grid">
        <aside class="hqed-document-aside">
            <div class="hqed-card hqed-toc">
                <div>
                    <strong>{{ $data['sidebar']['title'] }}</strong>
                    <small>{{ $data['sidebar']['count_label'] }}</small>
                </div>
                <nav aria-label="Terms provisions">
                    @foreach ($data['sections'] as $i => $section)
                        <a href="#clause-{{ $i + 1 }}">
                            <span>{{ $i + 1 }}</span>
                            {{ $section['nav'] }}
                            <i class="las la-angle-right"></i>
                        </a>
                    @endforeach
                </nav>
            </div>
            <div class="hqed-card hqed-aside-support">
                <strong>{{ $data['sidebar']['inquiry_title'] }}</strong>
                <p>{{ $data['sidebar']['inquiry_text'] }}</p>
                <a href="mailto:{{ $data['sidebar']['inquiry_email'] }}">
                    {{ $data['sidebar']['inquiry_email'] }}
                </a>
                <p>
                    {{ $data['sidebar']['inquiry_phone'] }}
                    <br>
                    {{ $data['sidebar']['inquiry_address'] }}
                </p>
            </div>
            <div class="hqed-notice">{{ $data['sidebar']['seal'] }}</div>
        </aside>

        <div class="hqed-document-content">
            @foreach ($data['sections'] as $i => $section)
                <section class="hqed-card hqed-clause" id="clause-{{ $i + 1 }}">
                    <h2>
                        <small>Clause {{ $section['num'] }}</small>
                        {{ $section['title'] }}
                    </h2>
                    <p>{{ $section['intro'] }}</p>

                    @if (!empty($section['cards']))
                        <div class="hqed-document-card-grid">
                            @foreach ($section['cards'] as $card)
                                <div class="hqed-mini-card">
                                    <h3>{{ $card['title'] }}</h3>
                                    <p>{{ $card['description'] }}</p>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    @if (!empty($section['bullets']))
                        <ul class="hqed-bullets">
                            @foreach ($section['bullets'] as $bullet)
                                <li>{{ $bullet }}</li>
                            @endforeach
                        </ul>
                    @endif

                    @if (!empty($section['table_rows']))
                        <div class="hqed-table-scroll">
                            <table>
                                <thead>
                                    <tr>
                                        @foreach ($section['table_headers'] as $heading)
                                            <th>{{ $heading }}</th>
                                        @endforeach
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($section['table_rows'] as $row)
                                        <tr>
                                            @foreach ($row as $cell)
                                                <td>{{ $cell }}</td>
                                            @endforeach
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif

                    @if (!empty($section['notice']))
                        <div class="hqed-notice">
                            @if (!empty($section['notice_title']))
                                <strong>{{ $section['notice_title'] }}</strong>
                            @endif
                            {{ $section['notice'] }}
                        </div>
                    @endif

                    @if (!empty($section['closing']))
                        <p>{{ $section['closing'] }}</p>
                    @endif
                </section>
            @endforeach
        </div>
    </div>
</section>

@include('frontend.editorial.sections.cta', ['cta' => $data['cta'], 'variant' => 'wide'])
