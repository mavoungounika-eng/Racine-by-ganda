{{--
    Composant Select Premium
    
    Usage:
    <x-select 
        name="category_id" 
        label="Catégorie" 
        :options="$categories" 
        :value="old('category_id')" 
        required 
    />
    
    Props:
    - name: string (required)
    - label: string (optional)
    - options: array (required) - Format: [['value' => 1, 'label' => 'Option 1'], ...]
    - value: mixed (optional)
    - required: boolean (optional)
    - placeholder: string (optional)
--}}

@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => null,
    'required' => false,
    'placeholder' => 'Sélectionner...'
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

    <select 
        name="{{ $name }}" 
        id="{{ $name }}"
        {{ $required ? 'required' : '' }}
        {{ $attributes->merge(['class' => 'form-select w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-accent focus:border-accent transition']) }}
    >
        <option value="">{{ $placeholder }}</option>
        @foreach($options as $option)
            <option 
                value="{{ is_array($option) ? $option['value'] : $option->id }}" 
                {{ (is_array($option) ? $option['value'] : $option->id) == $value ? 'selected' : '' }}
            >
                {{ is_array($option) ? $option['label'] : $option->name }}
            </option>
        @endforeach
    </select>

    @error($name)
        <p class="form-error text-red-500 text-sm mt-1">{{ $message }}</p>
    @enderror
</div>
