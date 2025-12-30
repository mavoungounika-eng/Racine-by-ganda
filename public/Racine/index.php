<?php
include('./config.php');
$produits = $conn->query("SELECT * FROM produits ORDER BY id DESC");
?>



<!DOCTYPE html>
<html lang="en">
  <head>
    <title>RACINE BY GANDA - Mode et Style Authentique</title>
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
    <link rel="stylesheet" href="css/testimony-enhancement.css">
  
  <style>
    body {
      background-image: url('images/bg-pattern-racine.png');
      background-repeat: repeat;
      background-size: contain;
      background-attachment: fixed;
    }
  </style>

</head>
  <body class="goto-here">
		<div class="py-1 bg-black">
    	<div class="container">
    		<div class="row no-gutters d-flex align-items-start align-items-center px-md-0">
	    		<div class="col-lg-12 d-block">
		    		<div class="row d-flex">					    <div class="col-md pr-4 d-flex topper align-items-center">
					    	<div class="icon mr-2 d-flex justify-content-center align-items-center"><span class="icon-phone2"></span></div>
						    <span class="text">+242 06 6XX XX XX</span>
					    </div>
					    <div class="col-md pr-4 d-flex topper align-items-center">
					    	<div class="icon mr-2 d-flex justify-content-center align-items-center"><span class="icon-paper-plane"></span></div>
						    <span class="text">contact@racinebyganda.com</span>
					    </div>
					    <div class="col-md-5 pr-4 d-flex topper align-items-center text-lg-right">
						    <span class="text">Livraison gratuite à Pointe-Noire &amp; Retours gratuits</span>
					    </div>
				    </div>
			    </div>
		    </div>
		  </div>
    </div>
    <nav class="navbar navbar-expand-lg navbar-dark ftco_navbar bg-dark ftco-navbar-light" id="ftco-navbar">
	    <div class="container">
	      <a class="navbar-brand" href="index.html">
	        <img src="images/logoo.png" alt="RACINE BY GANDA Logo">
	        <span class="navbar-brand-text">RACINE BY GANDA</span>
	      </a>
	      <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#ftco-nav" aria-controls="ftco-nav" aria-expanded="false" aria-label="Toggle navigation">
	        <span class="oi oi-menu"></span> Menu
	      </button>

	      <div class="collapse navbar-collapse" id="ftco-nav">
	        <ul class="navbar-nav ml-auto">
	          <li class="nav-item active"><a href="index.php" class="nav-link">Accueil</a></li>
	          <li class="nav-item"><a href="shop.php" class="nav-link">Boutique</a></li>
	          <li class="nav-item"><a href="showroom.html" class="nav-link">Showroom</a></li>
	          <li class="nav-item"><a href="atelier.html" class="nav-link">Atelier</a></li>
	          <li class="nav-item"><a href="contact.html" class="nav-link">Contact</a></li>
	          <li class="nav-item cta cta-colored"><a href="cart.php" class="nav-link">
			
			  
			<span class="icon-shopping_cart" id="cart-count">
    <?php
    session_start();
    echo isset($_SESSION['panier']) ? array_sum(array_column($_SESSION['panier'], 'quantity')) : 0;
    ?>
</span> </li>

	        </ul>
	      </div>
	    </div>
	  </nav>
    <!-- END nav -->

    <section id="home-section" class="hero">
		  <div class="home-slider owl-carousel">
	      <div class="slider-item js-fullheight">
	      	<div class="overlay"></div>
	        <div class="container-fluid p-0">
	          <div class="row d-md-flex no-gutters slider-text align-items-center justify-content-end" data-scrollax-parent="true">
	          	<img class="one-third order-md-last img-fluid" src="images/Models/0000.png" alt="">
		          <div class="one-forth d-flex align-items-center ftco-animate" data-scrollax=" properties: { translateY: '70%' }">
		          	<div class="text">
		          		<span class="subheading">#Nouvelle Collection</span>
		          		<div class="horizontal">
				            <h1 class="mb-4 mt-3">Style Africain Contemporain</h1>
				            <p class="mb-4">Découvrez l'élégance de nos créations uniques qui célèbrent l'héritage africain avec une touche moderne. RACINE BY GANDA vous invite à exprimer votre authenticité.</p>
				            
				            <p><a href="shop.php" class="btn-custom">Découvrir Maintenant</a></p>
				          </div>
		            </div>
		          </div>
	        	</div>
	        </div>
	      </div>

	      <div class="slider-item js-fullheight">
	      	<div class="overlay"></div>
	        <div class="container-fluid p-0">
	          <div class="row d-flex no-gutters slider-text align-items-center justify-content-end" data-scrollax-parent="true">
	          	<img class="one-third order-md-last img-fluid" src="images/bg_2.png" alt="">
		          <div class="one-forth d-flex align-items-center ftco-animate" data-scrollax=" properties: { translateY: '70%' }">
		          	<div class="text">
		          		<span class="subheading">#Création Exclusive</span>
		          		<div class="horizontal">
				            <h1 class="mb-4 mt-3">Collection Hiver Afro-Chic</h1>
				            <p class="mb-4">Des pièces uniques qui racontent votre histoire, inspirées par la richesse culturelle africaine et adaptées aux tendances contemporaines.</p>
				            
				            <p><a href="#" class="btn-custom">Découvrir Maintenant</a></p>
				          </div>
		            </div>
		          </div>
	        	</div>
	        </div>
	      </div>
	    </div>
    </section>

    <section class="ftco-section ftco-no-pt ftco-no-pb">
			<div class="container">
				<div class="row no-gutters ftco-services">
          <div class="col-lg-4 text-center d-flex align-self-stretch ftco-animate">
            <div class="media block-6 services p-4 py-md-5">
              <div class="icon d-flex justify-content-center align-items-center mb-4">
            		<span class="flaticon-bag"></span>
              </div>
              <div class="media-body">
                <h3 class="heading">Livraison Gratuite</h3>
                <p>Profitez de la livraison gratuite à Pointe-Noire pour toutes vos commandes. Nous nous engageons à vous livrer rapidement et en toute sécurité.</p>
              </div>
            </div>      
          </div>
          <div class="col-lg-4 text-center d-flex align-self-stretch ftco-animate">
            <div class="media block-6 services p-4 py-md-5">
              <div class="icon d-flex justify-content-center align-items-center mb-4">
            		<span class="flaticon-customer-service"></span>
              </div>
              <div class="media-body">
                <h3 class="heading">Service Client</h3>
                <p>Notre équipe dédiée est là pour vous accompagner dans vos choix et répondre à toutes vos questions. Une expérience client exceptionnelle.</p>
              </div>
            </div>    
          </div>
          <div class="col-lg-4 text-center d-flex align-self-stretch ftco-animate">
            <div class="media block-6 services p-4 py-md-5">
              <div class="icon d-flex justify-content-center align-items-center mb-4">
            		<span class="flaticon-payment-security"></span>
              </div>
              <div class="media-body">
                <h3 class="heading">Paiement Sécurisé</h3>
                <p>Vos transactions sont protégées par nos systèmes de paiement sécurisés. Payez en toute confiance avec vos moyens de paiement préférés.</p>
              </div>
            </div>      
          </div>
        </div>
			</div>
		</section>

    <section class="ftco-section bg-light">
    	<div class="container">
				<div class="row justify-content-center mb-3 pb-3">
          <div class="col-md-12 heading-section text-center ftco-animate">
            <h2 class="mb-4">Nouvelles Créations RACINE</h2>
            <p>Découvrez nos dernières créations qui allient tradition africaine et modernité contemporaine pour un style unique et authentique</p>
          </div>
        </div>   		
    	</div>
    	<div class="container">
    		<div class="row">

			<?php while ($produit = $produits->fetch_assoc()): ?>
    <div class="col-sm-12 col-md-12 col-lg-4 ftco-animate d-flex">
        <div class="product d-flex flex-column">
            <a href="#" class="img-prod">
                <img class="img-fluid" src="images/<?= htmlspecialchars($produit['image']) ?>" alt="<?= htmlspecialchars($produit['nom']) ?>">
                <div class="overlay"></div>
            </a>
            <div class="text py-3 pb-4 px-3">
                <div class="d-flex">
                    <div class="cat"><span><?= htmlspecialchars($produit['categories'] ?? 'Mode Africaine') ?></span></div>
                    <div class="rating">
                        <p class="text-right mb-0">
                            <a href="#"><span class="ion-ios-star-outline"></span></a>
                            <a href="#"><span class="ion-ios-star-outline"></span></a>
                            <a href="#"><span class="ion-ios-star-outline"></span></a>
                            <a href="#"><span class="ion-ios-star-outline"></span></a>
                            <a href="#"><span class="ion-ios-star-outline"></span></a>
                        </p>
                    </div>
                </div>
                <h3><a href="#"><?= htmlspecialchars($produit['nom']) ?></a></h3>
                <div class="pricing">
                    <p class="price"><span><?= number_format($produit['prix'], 0, ',', ' ') ?> F CFA</span></p>
                </div>
                <p class="bottom-area d-flex px-3">
                    <a href="./ajouter_panier.php" class="add-to-cart text-center py-2 mr-1"
   
   data-id="<?= $produit['id'] ?>"
   data-name="<?= htmlspecialchars($produit['nom']) ?>"
   data-price="<?= $produit['prix'] ?>"
   data-image="<?= $produit['image'] ?>">
   Ajouter au panier
</a>

</a>

                   <a href="ajouter_panier.php?id=<?= $produit['id'] ?>&name=<?= urlencode($produit['nom']) ?>&price=<?= $produit['prix'] ?>&image=<?= urlencode($produit['image']) ?>" class="buy-now text-center py-2">
    Acheter <span><i class="ion-ios-cart ml-1"></i></span>
</a>

				</p>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>


    			<div class="col-sm-12 col-md-6 col-lg-3 ftco-animate d-flex">
    				<div class="product d-flex flex-column">
    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-1.png" alt="Robe Wax">
    						<div class="overlay"></div>
    					</a>
    					<div class="text py-3 pb-4 px-3">
    						<div class="d-flex">
    							<div class="cat">
		    						<span>Vêtements</span>
		    					</div>
		    					<div class="rating">
	    							<p class="text-right mb-0">
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    							</p>
	    						</div>
	    					</div>
    						<h3><a href="#">Robe Wax Élégante</a></h3>
    						<div class="pricing">
	    						<p class="price"><span>52 000 F CFA</span></p>
	    					</div>
	    					<p class="bottom-area d-flex px-3">
    							<a href="./ajouter_panier.php" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
    						</p>
    					</div>
    				</div>
    			</div>
    			<div class="col-sm-12 col-md-6 col-lg-3 ftco-animate d-flex">
    				<div class="product d-flex flex-column">
    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-2.png" alt="Dashiki Modern">
    						<span class="status">30% Réduction</span>
    						<div class="overlay"></div>
    					</a>
    					<div class="text py-3 pb-4 px-3">
    						<div class="d-flex">
    							<div class="cat">
		    						<span>Hommes</span>
		    					</div>
		    					<div class="rating">
	    							<p class="text-right mb-0">
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    							</p>
	    						</div>
	    					</div>
    						<h3><a href="#">Dashiki Moderne</a></h3>
  							<div class="pricing">
	    						<p class="price"><span class="mr-2 price-dc">56 000 F CFA</span><span class="price-sale">39 000 F CFA</span></p>
	    					</div>
	    					<p class="bottom-area d-flex px-3">
    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
    						</p>
    					</div>
    				</div>
    			</div>
    			<div class="col-sm-12 col-md-6 col-lg-3 ftco-animate d-flex">
    				<div class="product">
    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-3.png" alt="Accessoire Traditionnel">
	    					<div class="overlay"></div>
	    				</a>
    					<div class="text py-3 pb-4 px-3">
    						<div class="d-flex">
    							<div class="cat">
		    						<span>Accessoires</span>
		    					</div>
		    					<div class="rating">
	    							<p class="text-right mb-0">
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    							</p>
	    						</div>
	    					</div>
    						<h3><a href="#">Sac Bogolan Authentique</a></h3>
  							<div class="pricing">
	    						<p class="price"><span>26 500 F CFA</span></p>
	    					</div>
	    					<p class="bottom-area d-flex px-3">
    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
    						</p>
    					</div>
    				</div>
    			</div>
    			<div class="col-sm-12 col-md-6 col-lg-3 ftco-animate d-flex">
    				<div class="product">
    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-4.png" alt="Bijou Africain">
    						<div class="overlay"></div>
    					</a>
    					<div class="text py-3 pb-4 px-3">
    						<div class="d-flex">
    							<div class="cat">
		    						<span>Bijoux</span>
		    					</div>
		    					<div class="rating">
	    							<p class="text-right mb-0">
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    							</p>
	    						</div>
	    					</div>
    						<h3><a href="#">Collier Cauris Tradition</a></h3>
  							<div class="pricing">
	    						<p class="price"><span>17 000 F CFA</span></p>
	    					</div>
	    					<p class="bottom-area d-flex px-3">
    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
    						</p>
    					</div>
    				</div>
    			</div>

    			<div class="col-sm-12 col-md-6 col-lg-3 ftco-animate d-flex">
    				<div class="product d-flex flex-column">
    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-5.png" alt="Boubou Élégant">
    						<div class="overlay"></div>
    					</a>
    					<div class="text py-3 pb-4 px-3">
    						<div class="d-flex">
    							<div class="cat">
		    						<span>Hommes</span>
		    					</div>
		    					<div class="rating">
	    							<p class="text-right mb-0">
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    							</p>
	    						</div>
	    					</div>
    						<h3><a href="#">Boubou Grand Boubou</a></h3>
    						<div class="pricing">
	    						<p class="price"><span>73 500 F CFA</span></p>
	    					</div>
	    					<p class="bottom-area d-flex px-3">
    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
    						</p>
    					</div>
    				</div>
    			</div>
    			<div class="col-sm-12 col-md-6 col-lg-3 ftco-animate d-flex">
    				<div class="product d-flex flex-column">
    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-6.png" alt="Ensemble Kente">
    						<span class="status">25% Réduction</span>
    						<div class="overlay"></div>
    					</a>
    					<div class="text py-3 pb-4 px-3">
    						<div class="d-flex">
    							<div class="cat">
		    						<span>Femmes</span>
		    					</div>
		    					<div class="rating">
	    							<p class="text-right mb-0">
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    							</p>
	    						</div>
	    					</div>
    						<h3><a href="#">Ensemble Kente Royal</a></h3>
  							<div class="pricing">
	    						<p class="price"><span class="mr-2 price-dc">94 000 F CFA</span><span class="price-sale">70 500 F CFA</span></p>
	    					</div>
	    					<p class="bottom-area d-flex px-3">
    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
    						</p>
    					</div>
    				</div>
    			</div>
    			<div class="col-sm-12 col-md-6 col-lg-3 ftco-animate d-flex">
    				<div class="product">
    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-7.png" alt="Headwrap Coloré">
	    					<div class="overlay"></div>
	    				</a>
    					<div class="text py-3 pb-4 px-3">
    						<div class="d-flex">
    							<div class="cat">
		    						<span>Accessoires</span>
		    					</div>
		    					<div class="rating">
	    							<p class="text-right mb-0">
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    							</p>
	    						</div>
	    					</div>
    						<h3><a href="#">Headwrap Wax Premium</a></h3>
  							<div class="pricing">
	    						<p class="price"><span>20 500 F CFA</span></p>
	    					</div>
	    					<p class="bottom-area d-flex px-3">
    							
							<a href="php/ajouter_panier.php" class="add-to-cart text-center py-2 mr-1"
   data-id="1"
   data-name="Robe Wax Élégante"
   data-price="52000">
   <span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span>
</a>


								<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
    						</p>
    					</div>
    				</div>
    			</div>
    			<div class="col-sm-12 col-md-6 col-lg-3 ftco-animate d-flex">
    				<div class="product">
    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-8.png" alt="Chaussures Traditionnelles">
    						<div class="overlay"></div>
    					</a>
    					<div class="text py-3 pb-4 px-3">
    						<div class="d-flex">
    							<div class="cat">
		    						<span>Chaussures</span>
		    					</div>
		    					<div class="rating">
	    							<p class="text-right mb-0">
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    								<a href="#"><span class="ion-ios-star-outline"></span></a>
	    							</p>
	    						</div>
	    					</div>
    						<h3><a href="#">Babouches Artisanales</a></h3>
  							<div class="pricing">
	    						<p class="price"><span>38 000 F CFA</span></p>
	    					</div>
	    					<p class="bottom-area d-flex px-3">
    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
    						</p>
    					</div>
    				</div>
    			</div>
    		</div>
    	</div>
    </section>



    <section class="ftco-section ftco-choose ftco-no-pb ftco-no-pt">
    	<div class="container">
				<div class="row no-gutters">
					<div class="col-lg-4">
						<div class="choose-wrap divider-one img p-5 d-flex align-items-end" style="background-image: url(images/choose-1.jpg);">

    					<div class="text text-center text-white px-2">
								<span class="subheading">Collection Homme</span>
    						<h2>Mode Masculine Africaine</h2>
    						<p>Des pièces sophistiquées qui célèbrent l'élégance masculine africaine avec des coupes modernes et des tissus authentiques.</p>
    						<p><a href="collections/masculine.php" class="btn btn-black px-3 py-2">Découvrir</a></p>
    					</div>
    				</div>
					</div>
					<div class="col-lg-8">
    				<div class="row no-gutters choose-wrap divider-two align-items-stretch">
    					<div class="col-md-12">
	    					<div class="choose-wrap full-wrap img align-self-stretch d-flex align-item-center justify-content-end" style="background-image: url(images/choose-2.jpg);">
	    						<div class="col-md-7 d-flex align-items-center">
	    							<div class="text text-white px-5">
	    								<span class="subheading">Collection Femme</span>
			    						<h2>Élégance Féminine</h2>
			    						<p>Découvrez notre collection exclusive de vêtements féminins qui allient grâce africaine et style contemporain.</p>
			    						<p><a href="collections/feminine.php" class="btn btn-black px-3 py-2">Découvrir</a></p>
			    					</div>
	    						</div>
	    					</div>
	    				</div>
    					<div class="col-md-12">
    						<div class="row no-gutters">
    							<div class="col-md-6">
		    						<div class="choose-wrap wrap img align-self-stretch bg-light d-flex align-items-center">
		    							<div class="text text-center px-5">
		    								<span class="subheading">Soldes d'Hiver</span>
				    						<h2>Jusqu'à 50% de Réduction</h2>
				    						<p>Profitez de nos offres exceptionnelles sur une sélection de nos plus belles créations traditionnelles et contemporaines.</p>
				    						<p><a href="shop.php" class="btn btn-black px-3 py-2">Découvrir</a></p>
				    					</div>
		    						</div>
	    						</div>
	    						<div class="col-md-6">
		    						<div class="choose-wrap wrap img align-self-stretch d-flex align-items-center" style="background-image: url(images/choose-3.jpg);">
		    							<div class="text text-center text-white px-5">
		    								<span class="subheading">Accessoires</span>
				    						<h2>Meilleures Ventes</h2>
				    						<p>Découvrez nos accessoires les plus populaires : bijoux, sacs et headwraps qui complètent parfaitement vos tenues.</p>
				    						<p><a href="collections/accessoirs.php" class="btn btn-black px-3 py-2">Découvrir</a></p>
				    					</div>
		    						</div>
	    						</div>
	    					</div>
    					</div>
    				</div>
    			</div>
  			</div>
    	</div>
    </section>

    <section class="ftco-section ftco-deal bg-primary">
    	<div class="container">
    		<div class="row">
    			<div class="col-md-6">
    				<img src="images/prod-1.png" class="img-fluid" alt="">
    			</div>
    			<div class="col-md-6">
    				<div class="heading-section heading-section-white">
    					<span class="subheading">Offre du mois</span>
	            <h2 class="mb-3">Promotion Exceptionnelle</h2>
	          </div>
    				<div id="timer" class="d-flex mb-4">
						  <div class="time" id="days"></div>
						  <div class="time pl-4" id="hours"></div>
						  <div class="time pl-4" id="minutes"></div>
						  <div class="time pl-4" id="seconds"></div>
						</div>
						<div class="text-deal">
							<h2><a href="#">Ensemble Wax Premium</a></h2>
							<p class="price"><span class="mr-2 price-dc">105 500 F CFA</span><span class="price-sale">73 500 F CFA</span></p>
							<ul class="thumb-deal d-flex mt-4">
								<li class="img" style="background-image: url(images/product-6.png);"></li>
								<li class="img" style="background-image: url(images/product-2.png);"></li>
								<li class="img" style="background-image: url(images/product-4.png);"></li>
							</ul>
						</div>
    			</div>
    		</div>
    	</div>
    </section>

    <section class="ftco-section testimony-section">
      <div class="container">
        <div class="row">
        	<div class="col-lg-5">
        		<div class="services-flow">
        			<div class="services-2 p-4 d-flex ftco-animate">
        				<div class="icon">
        					<span class="flaticon-bag"></span>
        				</div>
        				<div class="text">
	        				<h3>Livraison Gratuite</h3>
	        				<p class="mb-0">Profitez de la livraison gratuite à Pointe-Noire pour toutes vos commandes RACINE BY GANDA. Votre style, livré à domicile.</p>
        				</div>
        			</div>
        			<div class="services-2 p-4 d-flex ftco-animate">
        				<div class="icon">
        					<span class="flaticon-heart-box"></span>
        				</div>
        				<div class="text">
	        				<h3>Créations Uniques</h3>
	        				<p class="mb-0">Chaque pièce raconte une histoire, inspirée des traditions africaines et adaptée au style moderne. Authenticité garantie.</p>
	        			</div>
        			</div>
        			<div class="services-2 p-4 d-flex ftco-animate">
        				<div class="icon">
        					<span class="flaticon-payment-security"></span>
        				</div>
        				<div class="text">
	        				<h3>Qualité Artisanale</h3>
	        				<p class="mb-0">Nos artisans expérimentés utilisent des techniques traditionnelles pour créer des pièces d'exception qui durent dans le temps.</p>
	        			</div>
        			</div>
        			<div class="services-2 p-4 d-flex ftco-animate">
        				<div class="icon">
        					<span class="flaticon-customer-service"></span>
        				</div>
        				<div class="text">
	        				<h3>Service Personnalisé</h3>
	        				<p class="mb-0">Notre équipe vous accompagne pour trouver les pièces qui correspondent parfaitement à votre style et personnalité.</p>
	        			</div>
        			</div>
        		</div>
        	</div>
          <div class="col-lg-7">
          	<div class="heading-section ftco-animate mb-5">
	            <h2 class="mb-4">Nos clients satisfaits témoignent</h2>
	            <p>Découvrez ce que pensent nos clients de leurs expériences avec RACINE BY GANDA. Leur satisfaction fait notre fierté et nous motive à créer toujours plus de beauté authentique.</p>
	          </div>
            <div class="carousel-testimony owl-carousel">
              <div class="item">
                <div class="testimony-wrap">
                  <div class="trust-badge">Client Vérifié</div>
                  <div class="user-img mb-4" style="background-image: url(images/person_1.jpg)">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text">
                    <div class="star-rating">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                    </div>
                    <p class="mb-4 pl-4 line">J'ai découvert RACINE BY GANDA lors d'un événement à Pointe-Noire et je suis tombée amoureuse de leurs créations. Chaque pièce que j'ai achetée reflète parfaitement mon style et ma personnalité. La qualité est exceptionnelle !</p>
                    <p class="name">Dan Daryus RICHNIQUE</p>
                    <span class="position">Entrepreneur</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap">
                  <div class="trust-badge">Client Vérifié</div>
                  <div class="user-img mb-4" style="background-image: url(images/person_2.jpg)">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text">
                    <div class="star-rating">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                    </div>
                    <p class="mb-4 pl-4 line">Le service client est remarquable ! L'équipe m'a aidée à choisir des pièces qui correspondent parfaitement à mon style. Les créations sont magnifiques et la livraison a été rapide. Je recommande vivement RACINE BY GANDA !</p>
                    <p class="name">Dina RICHNIQUE</p>
                    <span class="position">Avocate</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap">
                  <div class="trust-badge">Client Vérifié</div>
                  <div class="user-img mb-4" style="background-image: url(images/person_3.jpg)">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text">
                    <div class="star-rating">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                    </div>
                    <p class="mb-4 pl-4 line">RACINE BY GANDA a transformé ma garde-robe ! Leurs pièces allient tradition et modernité de façon magistrale. Je reçois toujours des compliments quand je porte leurs créations. Bravo pour ce travail d'artiste !</p>
                    <p class="name">Nik MAVOUNGOU</p>
                    <span class="position">Directeur Marketing</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap">
                  <div class="trust-badge">Client Vérifié</div>
                  <div class="user-img mb-4" style="background-image: url(images/person_4.jpg)">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text">
                    <div class="star-rating">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                    </div>
                    <p class="mb-4 pl-4 line">La qualité artisanale est exceptionnelle. Chaque détail est soigné et les finitions sont parfaites. C'est un plaisir de porter des créations qui racontent notre histoire et célèbrent notre héritage africain.</p>
                    <p class="name">Antony EBENGUE</p>
                    <span class="position">Influenceur Mode</span>
                  </div>
                </div>
              </div>
              <div class="item">
                <div class="testimony-wrap">
                  <div class="trust-badge">Client Vérifié</div>
                  <div class="user-img mb-4" style="background-image: url(images/person_5.jpg)">
                    <span class="quote d-flex align-items-center justify-content-center">
                      <i class="icon-quote-left"></i>
                    </span>
                  </div>
                  <div class="text">
                    <div class="star-rating">
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                      <i class="fas fa-star"></i>
                    </div>
                    <p class="mb-4 pl-4 line">J'ai offert une de leurs créations à ma sœur et elle était aux anges ! Le packaging était soigné et la pièce absolument magnifique. RACINE BY GANDA sait comment faire plaisir à ses clients.</p>
                    <p class="name">Jack ANGAT HOFA</p>
                    <span class="position">Gendarme</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="ftco-gallery">
    	<div class="container">
    		<div class="row justify-content-center">
    			<div class="col-md-8 heading-section text-center mb-4 ftco-animate">
            <h2 class="mb-4">Suivez-nous sur Instagram</h2>
            <p>Découvrez nos dernières créations, les coulisses de nos ateliers et l'inspiration derrière chaque pièce RACINE BY GANDA sur notre compte Instagram.</p>
          </div>
    		</div>
    	</div>
    	<div class="container-fluid px-0">
    		<div class="row no-gutters">
					<div class="col-md-4 col-lg-2 ftco-animate">
						<a href="images/gallery-1.jpg" class="gallery image-popup img d-flex align-items-center" style="background-image: url(images/gallery-1.jpg);">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-instagram"></span>
    					</div>
						</a>
					</div>
					<div class="col-md-4 col-lg-2 ftco-animate">
						<a href="images/gallery-2.jpg" class="gallery image-popup img d-flex align-items-center" style="background-image: url(images/gallery-2.jpg);">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-instagram"></span>
    					</div>
						</a>
					</div>
					<div class="col-md-4 col-lg-2 ftco-animate">
						<a href="images/gallery-3.jpg" class="gallery image-popup img d-flex align-items-center" style="background-image: url(images/gallery-3.jpg);">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-instagram"></span>
    					</div>
						</a>
					</div>
					<div class="col-md-4 col-lg-2 ftco-animate">
						<a href="images/gallery-4.jpg" class="gallery image-popup img d-flex align-items-center" style="background-image: url(images/gallery-4.jpg);">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-instagram"></span>
    					</div>
						</a>
					</div>
					<div class="col-md-4 col-lg-2 ftco-animate">
						<a href="images/gallery-5.jpg" class="gallery image-popup img d-flex align-items-center" style="background-image: url(images/gallery-5.jpg);">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-instagram"></span>
    					</div>
						</a>
					</div>
					<div class="col-md-4 col-lg-2 ftco-animate">
						<a href="images/gallery-6.jpg" class="gallery image-popup img d-flex align-items-center" style="background-image: url(images/gallery-6.jpg);">
							<div class="icon mb-4 d-flex align-items-center justify-content-center">
    						<span class="icon-instagram"></span>
    					</div>
						</a>
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
              <h2 class="ftco-heading-2">
                <img src="images/logoo.png" alt="RACINE BY GANDA Logo" style="height: 30px; margin-right: 10px; vertical-align: middle;">
                RACINE BY GANDA
              </h2>
              <p>Célébrons ensemble l'héritage africain à travers des créations uniques qui allient tradition et modernité. Chaque pièce raconte une histoire, votre histoire.</p>
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
                <li><a href="index.php" class="py-2 d-block">Accueil</a></li>
                <li><a href="shop.php" class="py-2 d-block">Boutique</a></li>
                <li><a href="showroom.html" class="py-2 d-block">Showroom</a></li>
                <li><a href="atelier.php" class="py-2 d-block">Atelier</a></li>
                <li><a href="contact.html" class="py-2 d-block">Contact</a></li>
              </ul>
            </div>
          </div>
          <div class="col-md-4">
             <div class="ftco-footer-widget mb-4">
              <h2 class="ftco-heading-2">Aide</h2>
              <div class="d-flex">
	              <ul class="list-unstyled mr-l-5 pr-l-3 mr-4">
	                <li><a href="#" class="py-2 d-block">Informations de livraison</a></li>
	                <li><a href="#" class="py-2 d-block">Retours &amp; Échanges</a></li>
	                <li><a href="#" class="py-2 d-block">Conditions générales</a></li>
	                <li><a href="#" class="py-2 d-block">Politique de confidentialité</a></li>
	              </ul>
	              <ul class="list-unstyled">
	                <li><a href="#" class="py-2 d-block">FAQ</a></li>
	                <li><a href="#" class="py-2 d-block">Guide des tailles</a></li>
	              </ul>
	            </div>
            </div>
          </div>
          <div class="col-md">
            <div class="ftco-footer-widget mb-4">
            	<h2 class="ftco-heading-2">Nous Contacter</h2>
            	<div class="block-23 mb-3">
	              <ul>
	                <li><span class="icon icon-map-marker"></span><span class="text">Pointe-Noire, République du Congo</span></li>
	                <li><a href="#"><span class="icon icon-phone"></span><span class="text">+242 06 6XX XX XX</span></a></li>
	                <li><a href="#"><span class="icon icon-envelope"></span><span class="text">contact@racinebyganda.com</span></a></li>
	              </ul>
	            </div>
            </div>
          </div>
        </div>
        <div class="row">
          <div class="col-md-12 text-center">

            <p>&copy; 2024 RACINE BY GANDA. Tous droits réservés. | Créé avec passion pour célébrer la beauté africaine.</p>
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
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".add-to-cart").forEach(button => {
        button.addEventListener("click", function (e) {
            e.preventDefault();

           

			const product = {
    id: this.getAttribute("data-id"),
    name: this.getAttribute("data-name"),
    price: this.getAttribute("data-price"),
    image: this.getAttribute("data-image") 
};

            fetch("ajouter_panier.php", {
    method: "POST",
    headers: {
        "Content-Type": "application/json"
    },
    body: JSON.stringify(product)
})
.then(res => res.json())
.then(data => {
    if (data.success) {
        const compteur = document.getElementById("cart-count");
        if (compteur) {
            compteur.textContent = data.count; // ✅ Mise à jour ici
        }
    }
})

            .catch(error => console.error("Erreur JS:", error));
        });
    });
});
</script>


  </body>
</html>