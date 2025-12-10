<?php
session_start();
include('./config.php');


?>


<!DOCTYPE html>
<html lang="fr">
  <head>
    <title>Panier - RACINE BY GANDA</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    
    <link href="https://fonts.googleapis.com/css?family=Open+Sans:300,400,600,700,800" rel="stylesheet">

    <link rel="stylesheet" href="css/open-iconic-bootstrap.min.css">
    <link rel="stylesheet" href="css/animate.css">
    
    <link rel="stylesheet" href="css/owl.carousel.min.css">
    <link rel="stylesheet" href="css/owl.theme.default.min.css">
    <link rel="stylesheet" href="css/magnific-popup.css">

    <link rel="stylesheet" href="css/aos.css">

    <link rel="stylesheet" href="css/ionicons.min.css">

    <link rel="stylesheet" href="css/bootstrap-datepicker.css">
    <link rel="stylesheet" href="css/jquery.timepicker.css">

    
    <link rel="stylesheet" href="css/flaticon.css">
    <link rel="stylesheet" href="css/icomoon.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/racine-custom.css">
  
  <style>
    body {
      background-image: url('images/bg-pattern-racine.png');
      background-repeat: repeat;
      background-size: contain;
      background-attachment: fixed;
    }
    
    .payment-options {
      background: #f8f9fa;
      padding: 25px;
      border-radius: 12px;
      border: 1px solid #dee2e6;
      box-shadow: 0 2px 10px rgba(0,0,0,0.05);
    }
    
    .payment-method .form-check {
      background: white;
      padding: 18px 20px;
      border-radius: 8px;
      border: 2px solid #e9ecef;
      transition: all 0.3s ease;
      cursor: pointer;
    }
    
    .payment-method .form-check:hover {
      background: #f8f9fa;
      border-color: #82ae46;
      transform: translateY(-1px);
      box-shadow: 0 4px 12px rgba(130, 174, 70, 0.15);
    }
    
    .payment-method .form-check-input:checked + .form-check-label {
      color: #82ae46;
    }
    
    .payment-method .form-check-input:checked + .form-check-label strong {
      color: #82ae46;
    }
    
    .payment-method .form-check:has(.form-check-input:checked) {
      border-color: #82ae46;
      background: #f0f8e8;
      box-shadow: 0 4px 12px rgba(130, 174, 70, 0.2);
    }
    
    .payment-icon {
      flex-shrink: 0;
      transition: transform 0.3s ease;
    }
    
    .form-check:hover .payment-icon {
      transform: scale(1.1);
    }
  </style>
  </head>
  <body class="goto-here">
		<div class="py-1 bg-black">
    	<div class="container">
    		<div class="row no-gutters d-flex align-items-start align-items-center px-md-0">
	    		<div class="col-lg-12 d-block">
		    		<div class="row d-flex">
		    			<div class="col-md pr-4 d-flex topper align-items-center">
					    	<div class="icon mr-2 d-flex justify-content-center align-items-center"><span class="icon-phone2"></span></div>
						    <span class="text">+242 06 973 84 85</span>
					    </div>
					    <div class="col-md pr-4 d-flex topper align-items-center">
					    	<div class="icon mr-2 d-flex justify-content-center align-items-center"><span class="icon-paper-plane"></span></div>
						    <span class="text">contact@racinebyganda.com</span>
					    </div>
					    <div class="col-md-5 pr-4 d-flex topper align-items-center text-lg-right">
						    <span class="text">Livraison 2-3 jours &amp; Retours gratuits</span>
					    </div>
				    </div>
			    </div>
		    </div>
		  </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
	    <div class="container">
	      <a class="navbar-brand" href="index.html">RACINE BY GANDA</a>
	      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
	        <span class="oi oi-menu"></span> Menu
	      </button>

	      <div class="collapse navbar-collapse" id="ftco-nav">
	        <ul class="navbar-nav ml-auto">
	          <li class="nav-item"><a href="index.php" class="nav-link">Accueil</a></li>
	          <li class="nav-item dropdown active">
              <a class="nav-link dropdown-toggle" href="#" id="dropdown04" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">Boutique</a>
              <div class="dropdown-menu" aria-labelledby="dropdown04">
              	<a class="dropdown-item" href="shop.php">Boutique</a>
                <a class="dropdown-item" href="product-single.html">Produit</a>
                <a class="dropdown-item" href="cart.html">Panier</a>
                <a class="dropdown-item" href="checkout.html">Commande</a>
              </div>
            </li>
	          <li class="nav-item"><a href="showroom.html" class="nav-link">Showroom</a></li>
	          <li class="nav-item"><a href="atelier.html" class="nav-link">Atelier</a></li>
	          <li class="nav-item"><a href="contact.html" class="nav-link">Contact</a></li>
	          <li class="nav-item cta cta-colored"><a href="cart.html" class="nav-link"><span class="icon-shopping_cart"></span>[0]</a></li>

	        </ul>
	      </div>
	    </div>
	  </nav>
    <!-- END nav -->

    <div class="hero-wrap hero-bread" style="background-image: url('images/bg_6.jpg'); position: relative;">
      <div class="container">
        <div class="row no-gutters slider-text align-items-center justify-content-center">
          <div class="col-md-9 ftco-animate text-center">
            <!-- Logo RACINE BY GANDA -->
            <div class="mb-4">
              <img src="images/logo.png" alt="RACINE BY GANDA" style="max-height: 80px; width: auto; filter: brightness(0) invert(1); opacity: 0.9;">
            </div>
          	<p class="breadcrumbs"><span class="mr-2"><a href="index.html">Accueil</a></span> <span>Panier</span></p>
            <h1 class="mb-0 bread">Mon Panier</h1>
          </div>
        </div>
      </div>
    </div>

    <section class="ftco-section ftco-cart">
			<div class="container">
				<div class="row">
    			<div class="col-md-12 ftco-animate">
    				<div class="cart-list">
	    				<table class="table">
						    <thead class="thead-primary">
						      <tr class="text-center">
						        <th>&nbsp;</th>
						        <th>&nbsp;</th>
						        <th>Produit</th>
						        <th>Prix</th>
						        <th>Quantité</th>
						        <th>Total</th>
						      </tr>
						    </thead>

<tbody>
<?php if (!empty($_SESSION['panier'])): ?>
    <?php foreach ($_SESSION['panier'] as $id => $item): ?>
    <tr class="text-center">
        <td class="product-remove">
           <a href="#"
   class="remove-from-cart"
   data-id="<?= $id ?>">
   <span class="ion-ios-close"></span>
</a>

        </td>
        <td class="image-prod">
<img src="images/<?= htmlspecialchars($item['image'] ?? 'default.png') ?>" width="80">



        </td>
        <td class="product-name">
            <h3><?= htmlspecialchars($item['name'] ?? '') ?></h3>

        </td>
        <td class="price"><?= number_format($item['price'] ?? 0, 0, ',', ' ') ?> F CFA</td>

        <td class="quantity"><?= $item['quantity'] ?></td>
       <td class="total"><?= number_format(($item['price'] ?? 0) * ($item['quantity'] ?? 1), 0, ',', ' ') ?> F CFA</td>

    </tr>
    <?php endforeach; ?>
<?php else: ?>
    <tr><td colspan="6" class="text-center">🛒 Votre panier est vide</td></tr>
<?php endif; ?>
</tbody>

<?php


// Initialiser les totaux
$sous_total = 0;

// Parcourir le panier et calculer les totaux
if (!empty($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        $prix = $item['price'] ?? 0;
        $quantite = $item['quantity'] ?? 1;
        $sous_total += $prix * $quantite;
    }
}

$livraison = 2000;
$remise = 3000;

// Total final
$total = max(0, $sous_total + $livraison - $remise); // max pour éviter négatif
?>

						  </table>
					  </div>
    			</div>
    		</div>
    		<div class="row justify-content-start">
    			<div class="col col-lg-5 col-md-6 mt-5 cart-wrap ftco-animate">
    				<div class="cart-total mb-3" id="cart-total-section">
  <h3>Total du Panier</h3>
  <p class="d-flex">
    <span>Sous-total</span>
    <span id="subtotal"><?= number_format($sous_total, 0, ',', ' ') ?> F CFA</span>
  </p>
  <p class="d-flex">
    <span>Livraison</span>
    <span id="shipping"><?= number_format($livraison, 0, ',', ' ') ?> F CFA</span>
  </p>
  <p class="d-flex">
    <span>Remise</span>
    <span id="discount">-<?= number_format($remise, 0, ',', ' ') ?> F CFA</span>
  </p>
  <hr>
  <p class="d-flex total-price">
    <span>Total</span>
    <span id="total"><?= number_format($total, 0, ',', ' ') ?> F CFA</span>
  </p>
</div>

    				
    				
    				<!-- Options de paiement mobile -->
    				<div class="payment-options mb-4">
    					<h4 class="mb-3">Moyens de Paiement</h4>
    					<div class="payment-method">
    						<div class="form-check mb-2">
    							<input class="form-check-input" type="radio" name="payment" id="mtn" value="mtn" checked>
    							<label class="form-check-label d-flex align-items-center" for="mtn">
    								<div class="payment-icon mr-3" style="background: linear-gradient(45deg, #FFCC00, #FFD700); color: #000; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 16px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
    									MTN
    								</div>
    								<div>
    									<strong>MTN Mobile Money</strong>
    									<small class="d-block text-muted">Paiement sécurisé via MTN</small>
    								</div>
    							</label>
    						</div>
    						<div class="form-check mb-2">
    							<input class="form-check-input" type="radio" name="payment" id="airtel" value="airtel">
    							<label class="form-check-label d-flex align-items-center" for="airtel">
    								<div class="payment-icon mr-3" style="background: linear-gradient(45deg, #FF0000, #FF4444); color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 14px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
    									AIR
    								</div>
    								<div>
    									<strong>Airtel Money</strong>
    									<small class="d-block text-muted">Paiement rapide via Airtel</small>
    								</div>
    							</label>
    						</div>
    						<div class="form-check mb-2">
    							<input class="form-check-input" type="radio" name="payment" id="cash" value="cash">
    							<label class="form-check-label d-flex align-items-center" for="cash">
    								<div class="payment-icon mr-3" style="background: linear-gradient(45deg, #28a745, #34ce57); color: white; width: 35px; height: 35px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: bold; font-size: 18px; box-shadow: 0 2px 5px rgba(0,0,0,0.2);">
    									💰
    								</div>
    								<div>
    									<strong>Paiement à la livraison</strong>
    									<small class="d-block text-muted">Payez en espèces à réception</small>
    								</div>
    							</label>
    						</div>
    					</div>
    				</div>
    				
    				<!-- Bon bouton de redirection -->
<a href="checkout.php" class="btn btn-primary py-3 px-4">Passer la commande</a>




    			</div>
    		</div>
			</div>
		</section>
		

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
              <h2 class="ftco-heading-2">RACINE BY GANDA</h2>
              <p>Créateur de mode congolaise, alliant traditions et modernité pour sublimer la beauté africaine.</p>
              <ul class="ftco-footer-social list-unstyled float-md-left float-lft mt-5">
                <li class="ftco-animate"><a href="#"><span class="icon-twitter"></span></a></li>
                <li class="ftco-animate"><a href="#"><span class="icon-facebook"></span></a></li>
                <li class="ftco-animate"><a href="#"><span class="icon-instagram"></span></a></li>
              </ul>
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4 ml-md-5">
              <h2 class="ftco-heading-2">Menu</h2>
              <ul class="list-unstyled">
                <li><a href="index.html" class="py-2 d-block">Accueil</a></li>
                <li><a href="shop.html" class="py-2 d-block">Boutique</a></li>
                <li><a href="showroom.html" class="py-2 d-block">Showroom</a></li>
                <li><a href="atelier.html" class="py-2 d-block">Atelier</a></li>
                <li><a href="contact.html" class="py-2 d-block">Contact</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-4">
             <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Services</h2>
              <div class="d-flex">
	              <ul class="list-unstyled mr-l-5 pr-l-3 mr-4">
	                <li><a href="#" class="py-2 d-block">Création sur mesure</a></li>
	                <li><a href="#" class="py-2 d-block">Retouches</a></li>
	                <li><a href="#" class="py-2 d-block">Conseils mode</a></li>
	                <li><a href="#" class="py-2 d-block">Livraison</a></li>
	              </ul>
	              <ul class="list-unstyled">
	                <li><a href="#" class="py-2 d-block">Questions</a></li>
	                <li><a href="contact.html" class="py-2 d-block">Contact</a></li>
	              </ul>
	            </div>
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
            	<h2 class="ftco-heading-2">Nous contacter</h2>
            	<div class="block-23 mb-3">
	              <ul>
	                <li><span class="icon icon-map-marker"></span><span class="text">Pointe-Noire, Congo</span></li>
	                <li><a href="#"><span class="icon icon-phone"></span><span class="text">+242 06 973 84 85</span></a></li>
	                <li><a href="#"><span class="icon icon-envelope"></span><span class="text">contact@racinebyganda.com</span></a></li>
	              </ul>
	            </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 text-center">

            <p>Copyright &copy;<script>document.write(new Date().getFullYear());</script> RACINE BY GANDA. Tous droits réservés.</p>
          </div>
        </div>
      </div>
    </footer>
    
  

  <!-- loader -->
  <div id="ftco-loader" class="show fullscreen"><svg class="circular" width="48px" height="48px"><circle class="path-bg" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke="#eeeeee"/><circle class="path" cx="24" cy="24" r="22" fill="none" stroke-width="4" stroke-miterlimit="10" stroke="#F96D00"/></svg></div>


  <script src="js/jquery.min.js"></script>
  <script src="js/jquery-migrate-3.0.1.min.js"></script>
  <script src="js/popper.min.js"></script>
  <script src="js/bootstrap.min.js"></script>
  <script src="js/jquery.easing.1.3.js"></script>
  <script src="js/jquery.waypoints.min.js"></script>
  <script src="js/jquery.stellar.min.js"></script>
  <script src="js/owl.carousel.min.js"></script>
  <script src="js/jquery.magnific-popup.min.js"></script>
  <script src="js/aos.js"></script>
  <script src="js/jquery.animateNumber.min.js"></script>
  <script src="js/bootstrap-datepicker.js"></script>
  <script src="js/scrollax.min.js"></script>
  <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyBVWaKrjvy3MaE7SQ74_uJiULgl1JY0H2s&sensor=false"></script>
  <script src="js/google-map.js"></script>
  <script src="js/main.js"></script>

  <script>
		$(document).ready(function(){

		var quantitiy=0;
		   $('.quantity-right-plus').click(function(e){
		        
		        // Stop acting like a button
		        e.preventDefault();
		        // Get the field name
		        var quantity = parseInt($('#quantity').val());
		        
		        // If is not undefined
		            
		            $('#quantity').val(quantity + 1);

		          
		            // Increment
		        
		    });

		     $('.quantity-left-minus').click(function(e){
		        // Stop acting like a button
		        e.preventDefault();
		        // Get the field name
		        var quantity = parseInt($('#quantity').val());
		        
		        // If is not undefined
		      
		            // Increment
		            if(quantity>0){
		            $('#quantity').val(quantity - 1);
		            }
		    });
		    
		});
	</script>
    
	<script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".remove-from-cart").forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault();

            const productId = this.getAttribute("data-id");

            fetch("retirer.php?id=" + productId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Supprimer la ligne du tableau
                        this.closest("tr").remove();

                        // Mettre à jour le compteur du panier
                        const compteur = document.getElementById("cart-count");
                        if (compteur) {
                            compteur.textContent = data.count;
                        }

                        // Optionnel : recharger les totaux
                        location.reload(); // ou tu recalcules manuellement
                    }
                })
                .catch(err => console.error("Erreur suppression:", err));
        });
    });
});
</script>

<script>
function formatPrix(montant) {
    return new Intl.NumberFormat('fr-FR', {
        style: 'decimal',
        minimumFractionDigits: 0
    }).format(montant) + ' F CFA';
}

function mettreAJourTotauxPanier() {
    fetch('get_totaux_panier.php')
        .then(res => res.json())
        .then(data => {
            document.getElementById("subtotal").textContent = formatPrix(data.sous_total);
            document.getElementById("shipping").textContent = formatPrix(data.livraison);
            document.getElementById("discount").textContent = '-' + formatPrix(data.remise);
            document.getElementById("total").textContent = formatPrix(data.total);
        })
        .catch(err => console.error("Erreur lors de la mise à jour des totaux :", err));
}

// Appel initial
mettreAJourTotauxPanier();
</script>


  </body>
</html>