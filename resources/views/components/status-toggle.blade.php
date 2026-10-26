{{-- Render the selected status and its submitted form value. --}}
@props([
    'name',
    'id',
    'value',
    'submittedValue' => null,
    'activeValue' => '1',
    'inactiveValue' => '0',
    'required' => true,
    'disabled' => false,
])

@php
    $isActive = (string) $value === (string) $activeValue;
@endphp

<div class="status-toggle-control">
    <input
        type="hidden"
        name="{{ $name }}"
        value="{{ $submittedValue ?? ($isActive ? $activeValue : $inactiveValue) }}"
        data-status-value
    >
    <button
        type="button"
        id="{{ $id }}"
        class="status-toggle-button {{ $isActive ? 'is-active' : 'is-inactive' }}"
        role="switch"
        aria-checked="{{ $isActive ? 'true' : 'false' }}"
        aria-required="{{ $required ? 'true' : 'false' }}"
        data-status-toggle
        data-active-value="{{ $activeValue }}"
        data-inactive-value="{{ $inactiveValue }}"
        @disabled($disabled)
    >
        <span class="status-toggle-track" aria-hidden="true">
            <span class="status-toggle-thumb"></span>
        </span>
        <span data-status-label>{{ $isActive ? 'Active' : 'Inactive' }}</span>
    </button>
</div>
