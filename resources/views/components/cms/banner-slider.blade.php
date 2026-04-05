@props(['banners', 'autoplay' => true, 'interval' => 5000])

@if(count($banners) > 0)
<div class="relative overflow-hidden group cms-banner-slider" 
     id="banner-slider-{{ uniqid() }}"
     data-autoplay="{{ $autoplay ? 'true' : 'false' }}"
     data-interval="{{ $interval }}">
    
    <div class="flex transition-transform duration-700 ease-in-out slider-container" style="transform: translateX(0%)">
        @foreach($banners as $banner)
            <div class="min-w-full relative h-[400px] md:h-[600px] flex-shrink-0">
                <a href="{{ $banner->link_url ?? '#' }}" 
                   target="{{ $banner->link_target }}"
                   class="block h-full"
                   onclick="trackBannerClick({{ $banner->id }})">
                    
                    <picture class="absolute inset-0 w-full h-full">
                        @if($banner->mobile_image_path)
                            <source media="(max-width: 768px)" srcset="{{ asset($banner->mobile_image_path) }}">
                        @endif
                        <img src="{{ asset($banner->image_path) }}" 
                             alt="{{ $banner->title }}" 
                             class="w-full h-full object-cover">
                    </picture>

                    @if($banner->title || $banner->subtitle || $banner->link_text)
                        <div class="absolute inset-0 bg-black/30 flex items-center">
                            <div class="container mx-auto px-6 md:px-12">
                                <div class="max-w-2xl text-white transform transition-all duration-1000 translate-y-4 opacity-0 slide-content">
                                    <h2 class="text-4xl md:text-6xl font-black mb-4 leading-tight">
                                        {{ $banner->title }}
                                    </h2>
                                    @if($banner->subtitle)
                                        <p class="text-xl md:text-2xl mb-8 font-light text-gray-100">
                                            {{ $banner->subtitle }}
                                        </p>
                                    @endif
                                    @if($banner->link_text)
                                        <span class="inline-block bg-white text-gray-900 px-8 py-4 rounded-full font-bold uppercase tracking-widest hover:bg-primary hover:text-white transition-all transform hover:scale-105 shadow-xl">
                                            {{ $banner->link_text }}
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </div>
                    @endif
                </a>
            </div>
        @endforeach
    </div>

    @if(count($banners) > 1)
        <button class="absolute left-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 backdrop-blur-md p-3 rounded-full text-white transition-all prev-btn">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
        </button>
        <button class="absolute right-4 top-1/2 -translate-y-1/2 bg-white/20 hover:bg-white/40 backdrop-blur-md p-3 rounded-full text-white transition-all next-btn">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
        </button>

        <div class="absolute bottom-6 left-1/2 -translate-x-1/2 flex gap-2 dots-container">
            @foreach($banners as $index => $banner)
                <button class="w-2.5 h-2.5 rounded-full bg-white/50 transition-all dot {{ $index === 0 ? 'w-8 bg-white' : '' }}" data-index="{{ $index }}"></button>
            @endforeach
        </div>
    @endif
</div>

<script>
function trackBannerClick(id) {
    fetch('/banner/' + id + '/click', {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content,
            'Accept': 'application/json'
        }
    });
}

document.addEventListener('DOMContentLoaded', () => {
    const slider = document.querySelector('.cms-banner-slider');
    if (!slider) return;

    const container = slider.querySelector('.slider-container');
    const slides = slider.querySelectorAll('.min-w-full');
    const dots = slider.querySelectorAll('.dot');
    const prevBtn = slider.querySelector('.prev-btn');
    const nextBtn = slider.querySelector('.next-btn');
    
    let currentIndex = 0;
    const interval = parseInt(slider.dataset.interval) || 5000;
    const isAutoplay = slider.dataset.autoplay === 'true';

    function updateSlider() {
        container.style.transform = `translateX(-${currentIndex * 100}%)`;
        
        // Update dots
        dots.forEach((dot, idx) => {
            if (idx === currentIndex) {
                dot.classList.add('w-8', 'bg-white');
                dot.classList.remove('bg-white/50');
            } else {
                dot.classList.remove('w-8', 'bg-white');
                dot.classList.add('bg-white/50');
            }
        });

        // Animate content
        slides.forEach((slide, idx) => {
            const content = slide.querySelector('.slide-content');
            if (content) {
                if (idx === currentIndex) {
                    setTimeout(() => {
                        content.classList.remove('translate-y-4', 'opacity-0');
                    }, 300);
                } else {
                    content.classList.add('translate-y-4', 'opacity-0');
                }
            }
        });
    }

    function nextSlide() {
        currentIndex = (currentIndex + 1) % slides.length;
        updateSlider();
    }

    if (nextBtn) {
        nextBtn.addEventListener('click', (e) => {
            e.preventDefault();
            nextSlide();
        });
    }

    if (prevBtn) {
        prevBtn.addEventListener('click', (e) => {
            e.preventDefault();
            currentIndex = (currentIndex - 1 + slides.length) % slides.length;
            updateSlider();
        });
    }

    dots.forEach(dot => {
        dot.addEventListener('click', () => {
            currentIndex = parseInt(dot.dataset.index);
            updateSlider();
        });
    });

    if (isAutoplay) {
        setInterval(nextSlide, interval);
    }

    // Initial animation
    updateSlider();
});
</script>
@endif
