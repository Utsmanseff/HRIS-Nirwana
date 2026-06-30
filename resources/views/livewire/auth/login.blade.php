<div class="card card-pad" style="background:var(--panel-glow),var(--panel-grad);border:0;color:var(--on-panel)">
    <div class="flex items-center gap-3 mb-6">
        <span class="grid place-items-center w-11 h-11 rounded-xl bg-white"><x-logo :size="26" /></span>
        <div>
            <div class="font-extrabold text-lg tracking-tight">Nirwana<span class="text-brand-300">HRIS</span></div>
            <div class="text-xs" style="color:var(--on-panel-muted)">RSU Nirwana</div>
        </div>
    </div>
    <form wire:submit="login" class="space-y-4">
        <div>
            <label class="field-label" style="color:var(--on-panel)">NIP</label>
            <input wire:model="nip" class="input font-mono @error('nip') input-error @enderror" placeholder="1990.04.21.001" autofocus>
            @error('nip') <p class="field-hint" style="color:var(--danger-500)">{{ $message }}</p> @enderror
        </div>
        <div>
            <label class="field-label" style="color:var(--on-panel)">Kata sandi</label>
            <input wire:model="password" type="password" class="input" placeholder="••••••••">
        </div>
        <label class="flex items-center gap-2 text-sm" style="color:var(--on-panel-muted)">
            <input wire:model="remember" type="checkbox"> <span>Ingat saya</span>
        </label>
        <button type="submit" class="btn btn-primary w-full">Masuk</button>
    </form>
    <div class="flex items-center gap-3 my-5" style="color:var(--on-panel-muted)">
        <div class="flex-1 h-px" style="background:rgba(255,255,255,.15)"></div><span class="text-xs">atau</span><div class="flex-1 h-px" style="background:rgba(255,255,255,.15)"></div>
    </div>
    <a href="{{ route('auth.google') }}" class="btn btn-secondary w-full">Masuk dengan Google</a>
</div>
