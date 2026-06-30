{{-- Dark/light toggle button. Icon filled by theme-init script.
     Pass class via attributes, e.g. <x-theme-toggle class="btn btn-ghost btn-icon" /> --}}
<button type="button" onclick="toggleTheme()" aria-label="Ganti tema" {{ $attributes->merge(['class' => 'btn btn-ghost btn-icon']) }}>
    <span data-theme-icon style="display:grid"></span>
</button>
