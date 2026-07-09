<div>
    <div class="card rise">
        <div class="px-4 flex flex-wrap items-center justify-between gap-2 border-b border-neutral-100">
            <div class="flex gap-1">
                <button type="button" class="tab-btn @if($tab==='shift') on @endif" wire:click="gantiTab('shift')">Shift Unit</button>
                <button type="button" class="tab-btn @if($tab==='template') on @endif" wire:click="gantiTab('template')">Template Pola</button>
                <button type="button" class="tab-btn @if($tab==='jadwal') on @endif" wire:click="gantiTab('jadwal')">Jadwal Bulanan</button>
            </div>
            @if($unitList->count() > 1)
                <select class="select w-auto" wire:change="gantiUnit($event.target.value)">
                    @foreach($unitList as $u)
                        <option value="{{ $u->id }}" @selected($unitId===$u->id)>{{ $u->nama }}</option>
                    @endforeach
                </select>
            @elseif($unit)
                <span class="text-sm font-semibold text-brand-600">{{ $unit->nama }}</span>
            @endif
        </div>

        @if(! $unit)
            <div class="p-8 text-center text-sm text-neutral-400">Anda belum memimpin unit mana pun.</div>
        @else
            <div @class(['p-4' => true, 'hidden' => $tab !== 'shift'])>
                @include('livewire.absensi.partials.tab-shift')
            </div>
            <div @class(['p-4' => true, 'hidden' => $tab !== 'template'])>
                @include('livewire.absensi.partials.tab-template')
            </div>
            <div @class(['p-4' => true, 'hidden' => $tab !== 'jadwal'])>
                @include('livewire.absensi.partials.tab-jadwal')
            </div>
        @endif
    </div>
</div>
