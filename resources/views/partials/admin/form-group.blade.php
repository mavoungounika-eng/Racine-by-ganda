{{-- Composant Groupe de Formulaire --}}
@props(['label', 'name', 'type' => 'text', 'required' => false, 'help' => null, 'col' => 12])

<div class="col-md-{{ $col }} mb-4">
    <label for="{{ $name }}" class="form-label fw-semibold mb-2">
        {{ $label }}
        @if($required)
            <span class="text-danger">*</span>
        @endif
    </label>
    
    @if($type === 'textarea')
        <textarea name="{{ $name }}" 
                  id="{{ $name }}" 
                  class="form-control @error($name) is-invalid @enderror"
                  rows="{{ $attributes->get('rows', 4) }}"
                  {{ $required ? 'required' : '' }}
                  {{ $attributes->except(['rows', 'type', 'label', 'name', 'required', 'help', 'col']) }}>
            {{ old($name, $attributes->get('value')) }}
        </textarea>
    @elseif($type === 'select')
        <select name="{{ $name }}" 
                id="{{ $name }}" 
                class="form-select @error($name) is-invalid @enderror"
                {{ $required ? 'required' : '' }}
                {{ $attributes->except(['type', 'label', 'name', 'required', 'help', 'col']) }}>
            {{ $slot }}
        </select>
    @elseif($type === 'file')
        <input type="file" 
               name="{{ $name }}" 
               id="{{ $name }}"
               class="form-control @error($name) is-invalid @enderror"
               {{ $required ? 'required' : '' }}
               {{ $attributes->except(['type', 'label', 'name', 'required', 'help', 'col']) }}>
    @elseif($type === 'checkbox')
        <div class="form-check form-switch">
            <input type="checkbox" 
                   name="{{ $name }}" 
                   id="{{ $name }}"
                   class="form-check-input @error($name) is-invalid @enderror"
                   value="1"
                   {{ old($name, $attributes->get('checked', false)) ? 'checked' : '' }}
                   {{ $attributes->except(['type', 'label', 'name', 'required', 'help', 'col', 'checked']) }}>
            <label class="form-check-label" for="{{ $name }}">
                {{ $attributes->get('checkLabel', 'Activer') }}
            </label>
        </div>
    @else
        <input type="{{ $type }}" 
               name="{{ $name }}" 
               id="{{ $name }}"
               value="{{ old($name, $attributes->get('value')) }}"
               class="form-control @error($name) is-invalid @enderror"
               {{ $required ? 'required' : '' }}
               {{ $attributes->except(['type', 'label', 'name', 'required', 'help', 'col', 'value']) }}>
    @endif
    
    @if($help)
        <div class="form-text text-muted small mt-1">
            <i class="fas fa-info-circle me-1"></i>
            {{ $help }}
        </div>
    @endif
    
    @error($name)
        <div class="invalid-feedback d-block">
            <i class="fas fa-exclamation-circle me-1"></i>
            {{ $message }}
        </div>
    @enderror
</div>

