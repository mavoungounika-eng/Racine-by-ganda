@component('mail::layout')
{{-- Header --}}
@slot('header')
    @component('mail::header', ['url' => config('app.url')])
        RACINE BY GANDA
    @endcomponent
@endslot

{{-- Body --}}
<div style="font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif; line-height: 1.6; color: #333;">

    {{-- Title --}}
    <div style="margin-bottom: 2rem;">
        <h2 style="font-family: 'Cormorant Garamond', serif; color: #160D0C; font-size: 1.8rem; margin: 0;">
            Nouveau message de contact
        </h2>
    </div>

    {{-- Content Box --}}
    <div style="background: #f9f9f9; border-left: 4px solid #FFB800; padding: 1.5rem; border-radius: 4px; margin-bottom: 2rem;">
        
        {{-- Sender Info --}}
        <div style="margin-bottom: 1.5rem;">
            <p style="margin: 0; font-weight: 600; color: #160D0C;">
                <strong>De :</strong> {{ $firstName }} {{ $lastName }}
            </p>
            <p style="margin: 0.5rem 0 0; color: #666;">
                <strong>Email :</strong> 
                <a href="mailto:{{ $email }}" style="color: #ED5F1E; text-decoration: none;">
                    {{ $email }}
                </a>
            </p>
            @if ($phone)
                <p style="margin: 0.5rem 0 0; color: #666;">
                    <strong>Téléphone :</strong> {{ $phone }}
                </p>
            @endif
        </div>

        {{-- Subject --}}
        <div style="margin-bottom: 1.5rem; padding: 1rem; background: white; border-radius: 4px;">
            <p style="margin: 0; font-weight: 600; color: #160D0C;">
                <strong>Sujet :</strong>
            </p>
            <p style="margin: 0.5rem 0 0; color: #555;">
                @switch($subject)
                    @case('order')
                        Question sur une commande
                        @break
                    @case('product')
                        Question sur un produit
                        @break
                    @case('return')
                        Retour ou échange
                        @break
                    @case('partnership')
                        Partenariat
                        @break
                    @case('press')
                        Presse
                        @break
                    @case('other')
                        Autre
                        @break
                    @default
                        {{ $subject }}
                @endswitch
            </p>
        </div>

        {{-- Message --}}
        <div>
            <p style="margin: 0; font-weight: 600; color: #160D0C;">
                <strong>Message :</strong>
            </p>
            <div style="margin: 0.5rem 0 0; padding: 1rem; background: white; border-radius: 4px; border-left: 3px solid #ED5F1E;">
                <p style="margin: 0; color: #333; white-space: pre-wrap; word-wrap: break-word;">
                    {{ $message }}
                </p>
            </div>
        </div>

    </div>

    {{-- Action Button --}}
    <div style="text-align: center; margin: 2rem 0;">
        @component('mail::button', ['url' => '#', 'color' => 'primary'])
            Accéder à votre compte
        @endcomponent
    </div>

    {{-- Footer note --}}
    <div style="border-top: 1px solid #ddd; padding-top: 1rem; margin-top: 2rem; font-size: 0.9rem; color: #666;">
        <p style="margin: 0;">
            Ce message a été envoyé via le formulaire de contact sur {{ config('app.url') }}
        </p>
        <p style="margin: 0.5rem 0 0;">
            <strong>Date :</strong> {{ now()->format('d/m/Y à H:i') }}
        </p>
    </div>

</div>

{{-- Footer --}}
@slot('footer')
    @component('mail::footer')
        © {{ date('Y') }} RACINE BY GANDA. Tous droits réservés.
    @endcomponent
@endslot

@endcomponent
