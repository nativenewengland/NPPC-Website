@php
    $readable = $record->is_digitized && $record->file_url;
    $tag = $readable ? 'a' : 'article';
@endphp
<{{ $tag }} class="mp-card" @if($readable) href="{{ $record->file_url }}" target="_blank" rel="noopener" @endif>
    <div class="mp-cover">
        @if ($record->thumbnail_url)
            <img src="{{ $record->thumbnail_url }}" alt="Cover of {{ $record->title }}" loading="lazy">
        @else
            <div class="mp-cover-fallback" aria-hidden="true"><span>{{ $record->collection ?: 'NPPC Archive' }}</span><b>{{ $issueLabel($record) }}</b></div>
        @endif
        @unless ($readable)<span class="mp-scan">Scan wanted</span>@endunless
    </div>
    <p class="mp-card-kicker">{{ $issueLabel($record) }}</p>
    <h3>{{ $dateLabel($record) }}</h3>
    <p class="mp-card-title">{{ $record->title }}</p>
</{{ $tag }}>
