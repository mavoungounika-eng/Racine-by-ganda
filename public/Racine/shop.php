<?php
include 'config.php'; 
$produits = $conn->query("SELECT * FROM produits ORDER BY id DESC");
?>


<!DOCTYPE html>
<html lang="en">
  <head>
    <title>Boutique - RACINE BY GANDA</title>
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
	          <li class="nav-item"><a href="index.php" class="nav-link">Accueil</a></li>
	          <li class="nav-item active"><a href="shop.html" class="nav-link">Boutique</a></li>
	          <li class="nav-item"><a href="showroom.html" class="nav-link">Showroom</a></li>
	          <li class="nav-item"><a href="atelier.html" class="nav-link">Atelier</a></li>
	          <li class="nav-item"><a href="contact.html" class="nav-link">Contact</a></li>
	          
			<li class="nav-item cta cta-colored">
  <a href="cart.php" class="nav-link">
    <span class="icon-shopping_cart"></span>
    [<span id="cart-count">0</span>]
  </a>
</li>

			</ul>
	      </div>
	    </div>
	  </nav>
    <!-- END nav -->

    <div class="hero-wrap hero-bread" style="background-image: url('images/bg_6.jpg');">
      <div class="container">
        <div class="row no-gutters slider-text align-items-center justify-content-center">
          <div class="col-md-9 ftco-animate text-center">
          	<p class="breadcrumbs"><span class="mr-2"><a href="index.html">Accueil</a></span> <span>Boutique</span></p>
            <h1 class="mb-0 bread">Boutique</h1>
          </div>
        </div>
      </div>
    </div>

    <section class="ftco-section bg-light">
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

                    <a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
                </p>
            </div>
        </div>
    </div>
<?php endwhile; ?>
</div>

		    			<div class="col-sm-12 col-md-12 col-lg-4 ftco-animate d-flex">
		    				<div class="product d-flex flex-column">
		    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-2.png" alt="Colorlib Template">
		    						<span class="status">50% Réduction</span>
		    						<div class="overlay"></div>
		    					</a>
		    					<div class="text py-3 pb-4 px-3">
		    						<div class="d-flex">
		    							<div class="cat">
				    						<span>Mode Africaine</span>
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
		    						<h3><a href="#">Ensemble Wax Premium</a></h3>
		  							<div class="pricing">
			    						<p class="price"><span class="mr-2 price-dc">90.000 F CFA</span><span class="price-sale">45.000 F CFA</span></p>
			    					</div>
			    					<p class="bottom-area d-flex px-3">
		    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
		    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
		    						</p>
		    					</div>
		    				</div>
		    			</div>
		    			<div class="col-sm-12 col-md-12 col-lg-4 ftco-animate d-flex">
		    				<div class="product">
		    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-3.png" alt="Colorlib Template">
			    					<div class="overlay"></div>
			    				</a>
		    					<div class="text py-3 pb-4 px-3">
		    						<div class="d-flex">
		    							<div class="cat">
				    						<span>Mode Africaine</span>
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
		    						<h3><a href="#">Chemise Brodée Élégante</a></h3>
		  							<div class="pricing">
			    						<p class="price"><span>65.000 F CFA</span></p>
			    					</div>
			    					<p class="bottom-area d-flex px-3">
		    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
		    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
		    						</p>
		    					</div>
		    				</div>
		    			</div>
		    			<div class="col-sm-12 col-md-12 col-lg-4 ftco-animate d-flex">
		    				<div class="product">
		    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-4.png" alt="Colorlib Template">
		    						<div class="overlay"></div>
		    					</a>
		    					<div class="text py-3 pb-4 px-3">
		    						<div class="d-flex">
		    							<div class="cat">
				    						<span>Mode Africaine</span>
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
		    						<h3><a href="#">Pantalon Wax Style</a></h3>
		  							<div class="pricing">
			    						<p class="price"><span>55.000 F CFA</span></p>
			    					</div>
			    					<p class="bottom-area d-flex px-3">
		    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
		    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
		    						</p>
		    					</div>
		    				</div>
		    			</div>

		    			<div class="col-sm-12 col-md-12 col-lg-4 ftco-animate d-flex">
		    				<div class="product d-flex flex-column">
		    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-5.png" alt="Colorlib Template">
		    						<div class="overlay"></div>
		    					</a>
		    					<div class="text py-3 pb-4 px-3">
		    						<div class="d-flex">
		    							<div class="cat">
				    						<span>Mode Africaine</span>
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
		    						<h3><a href="#">Dashiki Contemporain</a></h3>
		    						<div class="pricing">
			    						<p class="price"><span>48.000 F CFA</span></p>
			    					</div>
			    					<p class="bottom-area d-flex px-3">
		    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
		    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
		    						</p>
		    					</div>
		    				</div>
		    			</div>
		    			<div class="col-sm-12 col-md-12 col-lg-4 ftco-animate d-flex">
		    				<div class="product d-flex flex-column">
		    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-6.png" alt="Colorlib Template">
		    						<span class="status">50% Réduction</span>
		    						<div class="overlay"></div>
		    					</a>
		    					<div class="text py-3 pb-4 px-3">
		    						<div class="d-flex">
		    							<div class="cat">
				    						<span>Mode Africaine</span>
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
		    						<h3><a href="#">Boubou Grand Style</a></h3>
		  							<div class="pricing">
			    						<p class="price"><span class="mr-2 price-dc">80.000 F CFA</span><span class="price-sale">40.000 F CFA</span></p>
			    					</div>
			    					<p class="bottom-area d-flex px-3">
		    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
		    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
		    						</p>
		    					</div>
		    				</div>
		    			</div>
		    			<div class="col-sm-12 col-md-12 col-lg-4 ftco-animate d-flex">
		    				<div class="product">
		    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-7.png" alt="Colorlib Template">
			    					<div class="overlay"></div>
			    				</a>
		    					<div class="text py-3 pb-4 px-3">
		    						<div class="d-flex">
		    							<div class="cat">
				    						<span>Mode Africaine</span>
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
		    						<h3><a href="#">Veste Kente Royale</a></h3>
		  							<div class="pricing">
			    						<p class="price"><span>95.000 F CFA</span></p>
			    					</div>
			    					<p class="bottom-area d-flex px-3">
		    							
										<button type="button"
   class="add-to-cart text-center py-2 mr-1"
   data-id="<?= $produit['id'] ?>"
   data-name="<?= htmlspecialchars($produit['nom']) ?>"
   data-price="<?= $produit['prix'] ?>"
   data-image="<?= htmlspecialchars($produit['image']) ?>">
   Ajouter au panier <i class="ion-ios-add ml-1"></i>
</button>


		    							 <a href="ajouter_panier.php?id=<?= $produit['id'] ?>&name=<?= urlencode($produit['nom']) ?>&price=<?= $produit['prix'] ?>&image=<?= urlencode($produit['image']) ?>" class="buy-now text-center py-2">
    Acheter <span><i class="ion-ios-cart ml-1"></i></span>
</a>
									</p>
		    					</div>
		    				</div>
		    			</div>
		    			<div class="col-sm-12 col-md-12 col-lg-4 ftco-animate d-flex">
		    				<div class="product">
		    					<a href="#" class="img-prod"><img class="img-fluid" src="images/product-8.png" alt="Colorlib Template">
		    						<div class="overlay"></div>
		    					</a>
		    					<div class="text py-3 pb-4 px-3">
		    						<div class="d-flex">
		    							<div class="cat">
				    						<span>Mode Africaine</span>
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
		    						<h3><a href="#">Ensemble Cérémonie</a></h3>
		  							<div class="pricing">
			    						<p class="price"><span>110.000 F CFA</span></p>
			    					</div>
			    					<p class="bottom-area d-flex px-3">
		    							<a href="#" class="add-to-cart text-center py-2 mr-1"><span>Ajouter au panier <i class="ion-ios-add ml-1"></i></span></a>
		    							<a href="#" class="buy-now text-center py-2">Acheter<span><i class="ion-ios-cart ml-1"></i></span></a>
		    						</p>
		    					</div>
		    				</div>
		    			</div>
		    		</div>
		    		<div class="row mt-5">
		          <div class="col text-center">
		            <div class="block-27">
		              <ul>
		                <li><a href="#">&lt;</a></li>
		                <li class="active"><span>1</span></li>
		                <li><a href="#">2</a></li>
		                <li><a href="#">3</a></li>
		                <li><a href="#">4</a></li>
		                <li><a href="#">5</a></li>
		                <li><a href="#">&gt;</a></li>
		              </ul>
		            </div>
		          </div>
		        </div>
		    	</div>

		    	<div class="col-md-4 col-lg-2">
		    		<div class="sidebar">							<div class="sidebar-box-2">
								<h2 class="heading">Catégories</h2>
								<div class="fancy-collapse-panel">
                  <div class="panel-group" id="accordion" role="tablist" aria-multiselectable="true">
                     <div class="panel panel-default">
                         <div class="panel-heading" role="tab" id="headingOne">
                             <h4 class="panel-title">
                                 <a data-toggle="collapse" data-parent="#accordion" href="#collapseOne" aria-expanded="true" aria-controls="collapseOne">Vêtements Hommes
                                 </a>
                             </h4>
                         </div>
                         <div id="collapseOne" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingOne">
                             <div class="panel-body">
                                 <ul>
                                 	<li><a href="#">Dashiki</a></li>
                                 	<li><a href="#">Boubou</a></li>
                                 	<li><a href="#">Chemises Wax</a></li>
                                 	<li><a href="#">Pantalons</a></li>
                                 	<li><a href="#">Vestes</a></li>
                                 	<li><a href="#">Accessoires</a></li>
                                 	<li><a href="#">Cérémonie</a></li>
                                 </ul>
                             </div>
                         </div>
                     </div>
                     <div class="panel panel-default">
                         <div class="panel-heading" role="tab" id="headingTwo">
                             <h4 class="panel-title">
                                 <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">Vêtements Femmes
                                 </a>
                             </h4>
                         </div>
                         <div id="collapseTwo" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingTwo">
                             <div class="panel-body">
                                <ul>
                                 	<li><a href="#">Robes Wax</a></li>
                                 	<li><a href="#">Ensembles</a></li>
                                 	<li><a href="#">Jupes</a></li>
                                 	<li><a href="#">Hauts</a></li>
                                 	<li><a href="#">Pagnes</a></li>
                                 	<li><a href="#">Bijoux</a></li>
                                 	<li><a href="#">Mariage</a></li>
                                </ul>
                             </div>
                         </div>
                     </div>
                     <div class="panel panel-default">
                         <div class="panel-heading" role="tab" id="headingThree">
                             <h4 class="panel-title">
                                 <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseThree" aria-expanded="false" aria-controls="collapseThree">Accessoires
                                 </a>
                             </h4>
                         </div>
                         <div id="collapseThree" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingThree">
                             <div class="panel-body">
                                <ul>
                                 	<li><a href="#">Sacs</a></li>
                                 	<li><a href="#">Chaussures</a></li>
                                 	<li><a href="#">Bijoux</a></li>
                                 	<li><a href="#">Foulards</a></li>
                                </ul>
                             </div>
                         </div>
                     </div>
                     <div class="panel panel-default">
                         <div class="panel-heading" role="tab" id="headingFour">
                             <h4 class="panel-title">
                                 <a class="collapsed" data-toggle="collapse" data-parent="#accordion" href="#collapseFour" aria-expanded="false" aria-controls="collapseThree">Collections
                                 </a>
                             </h4>
                         </div>
                         <div id="collapseFour" class="panel-collapse collapse" role="tabpanel" aria-labelledby="headingFour">
                             <div class="panel-body">
                                <ul>
                                 	<li><a href="#">Traditionnelle</a></li>
                                 	<li><a href="#">Moderne</a></li>
                                 	<li><a href="#">Cérémonie</a></li>
                                 	<li><a href="#">Casual</a></li>
                                </ul>
                             </div>
                         </div>
                     </div>
                  </div>
               </div>
							</div>
							<div class="sidebar-box-2">
								<h2 class="heading">Gamme de Prix</h2>
								<form method="post" class="colorlib-form-2">
		              <div class="row">
		                <div class="col-md-12">
		                  <div class="form-group">
		                    <label for="guests">Prix minimum :</label>
		                    <div class="form-field">
		                      <i class="icon icon-arrow-down3"></i>
		                      <select name="people" id="people" class="form-control">
		                        <option value="#">5.000 F CFA</option>
		                        <option value="#">10.000 F CFA</option>
		                        <option value="#">20.000 F CFA</option>
		                        <option value="#">30.000 F CFA</option>
		                        <option value="#">50.000 F CFA</option>
		                      </select>
		                    </div>
		                  </div>
		                </div>
		                <div class="col-md-12">
		                  <div class="form-group">
		                    <label for="guests">Prix maximum :</label>
		                    <div class="form-field">
		                      <i class="icon icon-arrow-down3"></i>
		                      <select name="people" id="people" class="form-control">
		                        <option value="#">100.000 F CFA</option>
		                        <option value="#">150.000 F CFA</option>
		                        <option value="#">200.000 F CFA</option>
		                        <option value="#">300.000 F CFA</option>
		                        <option value="#">500.000 F CFA</option>
		                      </select>
		                    </div>
		                  </div>
		                </div>
		              </div>
		            </form>
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
            <p>Découvrez nos dernières créations et l'univers RACINE BY GANDA à travers nos photos. Inspirez-vous de nos modèles portés par nos clients satisfaits.</p>
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