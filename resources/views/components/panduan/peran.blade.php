@props(['peran' => []])
<div class="flex flex-wrap gap-1.5">
    @foreach ($peran as $p)
        <span @class(['badge', $p === 'Semua' ? 'badge-success' : 'badge-brand'])>{{ $p }}</span>
    @endforeach
</div>
