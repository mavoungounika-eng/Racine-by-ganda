{{-- Navigation Croisée Pages Légales --}}
<div class="border-t border-gray-200 pt-8 mt-12">
    <h3 class="font-semibold text-primary mb-4 text-center">Pages Utiles</h3>
    <div class="flex flex-wrap justify-center gap-3">
        <a href="{{ route('frontend.help') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-full hover:border-accent hover:text-accent transition-all shadow-sm hover:shadow-md {{ request()->routeIs('frontend.help') ? 'border-accent text-accent' : 'text-gray-700' }}">
            <i class="fas fa-question-circle"></i>
            <span class="text-sm font-medium">Aide & Support</span>
        </a>

        <a href="{{ route('frontend.shipping') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-full hover:border-accent hover:text-accent transition-all shadow-sm hover:shadow-md {{ request()->routeIs('frontend.shipping') ? 'border-accent text-accent' : 'text-gray-700' }}">
            <i class="fas fa-truck"></i>
            <span class="text-sm font-medium">Livraison</span>
        </a>

        <a href="{{ route('frontend.returns') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-full hover:border-accent hover:text-accent transition-all shadow-sm hover:shadow-md {{ request()->routeIs('frontend.returns') ? 'border-accent text-accent' : 'text-gray-700' }}">
            <i class="fas fa-undo"></i>
            <span class="text-sm font-medium">Retours & Échanges</span>
        </a>

        <a href="{{ route('frontend.terms') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-full hover:border-accent hover:text-accent transition-all shadow-sm hover:shadow-md {{ request()->routeIs('frontend.terms') ? 'border-accent text-accent' : 'text-gray-700' }}">
            <i class="fas fa-file-contract"></i>
            <span class="text-sm font-medium">CGV</span>
        </a>

        <a href="{{ route('frontend.privacy') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-full hover:border-accent hover:text-accent transition-all shadow-sm hover:shadow-md {{ request()->routeIs('frontend.privacy') ? 'border-accent text-accent' : 'text-gray-700' }}">
            <i class="fas fa-shield-alt"></i>
            <span class="text-sm font-medium">Confidentialité</span>
        </a>

        <a href="{{ route('frontend.about') }}" class="inline-flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 rounded-full hover:border-accent hover:text-accent transition-all shadow-sm hover:shadow-md {{ request()->routeIs('frontend.about') ? 'border-accent text-accent' : 'text-gray-700' }}">
            <i class="fas fa-info-circle"></i>
            <span class="text-sm font-medium">À Propos</span>
        </a>
    </div>
</div>
