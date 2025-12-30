@props([
    'value' => '0',
    'label' => 'Label',
    'icon' => '📊',
    'iconBg' => '#F0F0F5',
    'valueColor' => '#11001F'
])

<div class="card h-100">
    <div class="card-body">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <p class="mb-1" style="color: #6B7280; font-size: 0.85rem;">{{ $label }}</p>
                <h3 class="mb-0" style="color: {{ $valueColor }}; font-family: 'Playfair Display', serif; font-weight: 600;">
                    {{ $value }}
                </h3>
                {{ $slot }}
            </div>
            <div style="width: 56px; height: 56px; background: {{ $iconBg }}; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.5rem;">
                {{ $icon }}
            </div>
        </div>
    </div>
</div>

