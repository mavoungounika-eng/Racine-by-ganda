@props(['banners', 'autoplay' => true, 'interval' => 5000])

@if(count($banners) > 0 and $banners->first() and $banners->first()->image_path)
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

<script nonce="{{ csp_nonce() }}">
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
@else
<section class="hero">
    <div class="hero-bg-pattern"></div>
    <div class="container">
        <div class="hero-content">
            <div class="hero-text">
                <span class="hero-badge">Nouvelle Collection 2025</span>
                <h1 class="hero-title">L'Elegance<br><span class="highlight">Africaine</span><br>Reinventee</h1>
                <p class="hero-description">Decouvrez des creations uniques qui celebrent notre heritage.</p>
                <div class="hero-cta">
                    <a href="/boutique" class="btn-primary-custom"><i class="fas fa-shopping-bag"></i> Explorer la boutique</a>
                    <a href="/ateliers" class="btn-outline-custom"><i class="fas fa-palette"></i> Nos createurs</a>
                </div>
            </div>
            <div class="hero-image">
                <div class="hero-slider" id="heroSlider">
                    <div class="hero-slider-track">
                        @foreach(range(1, 7) as $i)
                        <div class="hero-slide {{ $i === 1 ? 'active' : '' }}">
                            <img src="{{ asset('storage/hero/hero-' . sprintf('%02d', $i) . '.jpeg') }}" alt="Look {{ $i }}" loading="{{ $i === 1 ? 'eager' : 'lazy' }}">
                        </div>
                        @endforeach
                    </div>
                    <div class="hero-slider-dots">
                        @foreach(range(1, 7) as $i)
                        <button class="hero-dot {{ $i === 1 ? 'active' : '' }}" data-index="{{ $i - 1 }}" aria-label="Slide {{ $i }}"></button>
                        @endforeach
                    </div>
                    <button class="hero-slider-prev"><i class="fas fa-chevron-left"></i></button>
                    <button class="hero-slider-next"><i class="fas fa-chevron-right"></i></button>
                </div>
                <div class="hero-image-float hero-float-1">
                    <div class="hero-float-content">
                        <div class="hero-float-icon"><i class="fas fa-truck"></i></div>
                        <div class="hero-float-text"><h4>Livraison Express</h4><span>Partout en France</span></div>
                    </div>
                </div>
                <div class="hero-image-float hero-float-2">
                    <div class="hero-float-content">
                        <div class="hero-float-icon"><i class="fas fa-award"></i></div>
                        <div class="hero-float-text"><h4>100% Authentique</h4><span>Fait main en Afrique</span></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endif
