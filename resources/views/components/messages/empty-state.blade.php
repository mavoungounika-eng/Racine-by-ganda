@props(['icon', 'title', 'description'])

<div class="text-center py-16 bg-white rounded-2xl border border-dashed border-[#E5DDD3]">
    <div class="w-16 h-16 bg-gray-100 rounded-full flex items-center justify-content-center mx-auto mb-4 text-gray-400">
        <i class="{{ $icon }} text-3xl"></i>
    </div>
    <h3 class="text-lg font-semibold text-[#2C1810]">{{ $title }}</h3>
    <p class="text-[#8B7355] mt-2">{{ $description }}</p>
</div>
