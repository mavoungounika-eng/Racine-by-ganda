<footer class="ftco-footer ftco-section">
  <div class="container">
    <div class="row">
      <div class="mouse">
        <a href="#" class="mouse-icon">
          <div class="mouse-wheel"><span class="ion-ios-arrow-up"></span></div>
        </a>
      </div>
    </div>

    <div class="row mb-5">
      <div class="col-md">
        <div class="ftco-footer-widget mb-4">
          <h2 class="ftco-heading-2">
            <img src="{{ asset('racine/images/logoo.png') }}" alt="RACINE BY GANDA Logo"
                 style="height: 30px; margin-right: 10px; vertical-align: middle;">
            RACINE BY GANDA
          </h2>
          <p>Célébrons ensemble l'héritage africain à travers une mode raffinée, moderne et engagée.</p>
          <ul class="ftco-footer-social list-unstyled float-md-left float-lft mt-5">
            <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
            <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
            <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
          </ul>
        </div>
      </div>

      <div class="col-md">
        <div class="ftco-footer-widget mb-4 ms-md-5">
          <h2 class="ftco-heading-2">Menu</h2>
          <ul class="list-unstyled">
            <li><a href="{{ route('frontend.shop') }}" class="py-2 d-block">Boutique</a></li>
            <li><a href="{{ route('frontend.showroom') }}" class="py-2 d-block">Showroom</a></li>
            <li><a href="{{ route('frontend.atelier') }}" class="py-2 d-block">Atelier</a></li>
            <li><a href="{{ route('frontend.contact') }}" class="py-2 d-block">Contact</a></li>
            <li><a href="{{ route('login') }}" class="py-2 d-block text-muted"><small>🔐 Espace équipe</small></a></li>
          </ul>
        </div>
      </div>

      <div class="col-md">
        <div class="ftco-footer-widget mb-4">
          <h2 class="ftco-heading-2">Aide</h2>
          <div class="d-flex">
            <ul class="list-unstyled mr-l-5 pr-l-3 me-4">
              <li><a href="{{ route('frontend.shipping') }}" class="py-2 d-block">Livraison</a></li>
              <li><a href="{{ route('frontend.returns') }}" class="py-2 d-block">Retours &amp; échanges</a></li>
              <li><a href="{{ route('frontend.terms') }}" class="py-2 d-block">Conditions générales</a></li>
              <li><a href="{{ route('frontend.privacy') }}" class="py-2 d-block">Confidentialité</a></li>
            </ul>
          </div>
        </div>
      </div>

      <div class="col-md">
        <div class="ftco-footer-widget mb-4">
          <h2 class="ftco-heading-2">Vous avez une question ?</h2>
          <div class="block-23 mb-3">
            <ul>
              <li><span class="icon icon-map-marker"></span><span class="text">Centre ville, Galerie NF, Pointe-Noire</span></li>
              <li><a href="tel:{{ str_replace(' ', '', config('company.phone')) }}"><span class="icon icon-phone"></span><span class="text">{{ config('company.phone') }}</span></a></li>
              <li><a href="mailto:{{ config('company.email') }}"><span class="icon icon-envelope"></span><span class="text">{{ config('company.email') }}</span></a></li>
            </ul>
          </div>
        </div>
      </div>

    </div>

    <div class="row">
      <div class="col-md-12 text-center">
        <p>
          &copy; <script nonce="{{ csp_nonce() }}">document.write(new Date().getFullYear());</script> RACINE BY GANDA. Tous droits réservés.
        </p>
      </div>
    </div>
  </div>
</footer>

<!-- Loader -->
<div id="ftco-loader" class="show fullscreen">
  <svg class="circular" width="48px" height="48px">
    <circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4"></circle>
    <circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4"></circle>
  </svg>
</div>
