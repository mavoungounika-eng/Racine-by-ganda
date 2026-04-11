@extends('layouts.frontend')

@section('title', 'Questions Fréquentes')

@section('content')
<div class="container py-5">
    <h1 class="display-4 mb-4">Questions Fréquentes</h1>

    @if($categories->count() > 0)
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="list-group">
                    <a href="{{ route('cms.faq.public') }}" 
                       class="list-group-item list-group-item-action {{ !$categoryId ? 'active' : '' }}">
                        Toutes les catégories
                    </a>
                    @foreach($categories as $category)
                        @if($category->active_faqs_count > 0)
                            <a href="{{ route('cms.faq.public', ['category' => $category->id]) }}" 
                               class="list-group-item list-group-item-action {{ $categoryId == $category->id ? 'active' : '' }}">
                                @if($category->icon)
                                    <i class="{{ $category->icon }} me-2"></i>
                                @endif
                                {{ $category->name }}
                                <span class="badge bg-secondary float-end">{{ $category->active_faqs_count }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
            <div class="col-md-9">
                @if($faqs->count() > 0)
                    <div class="accordion" id="faqAccordion">
                        @foreach($faqs as $index => $faq)
                            <div class="accordion-item">
                                <h2 class="accordion-header">
                                    <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" 
                                            type="button" 
                                            data-bs-toggle="collapse" 
                                            data-bs-target="#faq{{ $faq->id }}">
                                        {{ $faq->question }}
                                    </button>
                                </h2>
                                <div id="faq{{ $faq->id }}" 
                                     class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                                     data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        {!! nl2br(e($faq->answer)) !!}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">
                        Aucune question fréquente disponible pour cette catégorie.
                    </div>
                @endif
            </div>
        </div>
    @else
        @if($faqs->count() > 0)
            <div class="accordion" id="faqAccordion">
                @foreach($faqs as $index => $faq)
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button {{ $index > 0 ? 'collapsed' : '' }}" 
                                    type="button" 
                                    data-bs-toggle="collapse" 
                                    data-bs-target="#faq{{ $faq->id }}">
                                {{ $faq->question }}
                            </button>
                        </h2>
                        <div id="faq{{ $faq->id }}" 
                             class="accordion-collapse collapse {{ $index === 0 ? 'show' : '' }}" 
                             data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                {!! nl2br(e($faq->answer)) !!}
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @else
            <div class="alert alert-info">
                Aucune question fréquente disponible pour le moment.
            </div>
        @endif
    @endif
</div>
@endsection

