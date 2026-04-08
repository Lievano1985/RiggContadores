<?php

use Livewire\Volt\Component;

new class extends Component {
    //
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Apariencia')" :subheading=" __('Actualiza el tema visual de tu cuenta.')">
        <flux:radio.group x-data="{ appTheme: document.documentElement.dataset.theme || 'dark' }"
            x-on:app-theme-changed.window="appTheme = $event.detail.theme"
            variant="segmented" x-model="appTheme" x-on:change="window.AppTheme?.apply(appTheme)">
            <flux:radio value="light" icon="sun">{{ __('Claro') }}</flux:radio>
            <flux:radio value="dark" icon="moon">{{ __('Oscuro') }}</flux:radio>
            <flux:radio value="rigg" icon="swatch">{{ __('Rigg') }}</flux:radio>
        </flux:radio.group>
    </x-settings.layout>
</section>
