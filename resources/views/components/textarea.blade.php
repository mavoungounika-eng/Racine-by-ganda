{{--
    Composant Textarea Premium
    
    Usage:
    <x-textarea 
        name="description" 
        label="Description" 
        :value="old('description', $product->description ?? '')" 
        rows="5" 
        required 
    />
    
    Props:
    - name: string (required)
    - label: string (optional)
    - value: string (optional)
    - rows: integer (optional, default: 4)
    - required: boolean (optional)
    - placeholder: string (optional)
--}}

@props([
    'name',
    'label' => null,
    'value' => '',
    'rows' => 4,
    'required' => false,
    'placeholder' => ''
])

<div class="form-group">
    @if($label)
    <label for="{{ $name }}" class="form-label {{ $required ? 'required' : '' }}">
        {{ $label }}
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    @endif

    <textarea 
        name="{{ $name }}" 
        id="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-textarea w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition resize-y']) }}
    >{{ old($name, $value) }}</textarea>

    @error($name)
        <p class="form-error text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
