@props(['title' => 'Panduan Singkat'])

@php
    $guideId = 'page-guide-'.str_replace(['.', '/'], '-', request()->route()?->getName() ?? md5(request()->path()));
@endphp

<div id="{{ $guideId }}" class="alert alert-info d-flex align-items-start gap-2 border-0 shadow-sm mb-4" role="note">
    <i class="bi bi-info-circle-fill fs-5 mt-1"></i>
    <div class="flex-grow-1 small">
        <span class="fw-semibold">{{ $title }}:</span>
        {{ $slot }}
    </div>
    <button type="button" class="btn-close flex-shrink-0" aria-label="Tutup panduan"
        onclick="var el=document.getElementById('{{ $guideId }}'); if (el) { el.style.display='none'; } try { localStorage.setItem('{{ $guideId }}', '1'); } catch (e) {}"
    ></button>
</div>
<script>
    try {
        if (localStorage.getItem('{{ $guideId }}') === '1') {
            var el = document.getElementById('{{ $guideId }}');
            if (el) { el.style.display = 'none'; }
        }
    } catch (e) {}
</script>
