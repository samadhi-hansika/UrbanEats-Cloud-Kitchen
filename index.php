<?php
include 'includes/header.php';
?>

<div class="card" style="text-align: center; padding: 4rem 2rem;">
<div class="home-hero">
    <div class="home-hero-copy">
        <span class="home-hero-eyebrow">FRESH · FAST · DELICIOUS</span>
        <h1>Welcome to <span>UrbanEats</span> Cloud Kitchen</h1>
        <p>Sri Lanka's premier cloud kitchen platform. Freshly prepared dishes delivered straight to your doorstep.</p>
        <div class="role-dashboard-actions">
            <a href="menu.php" class="role-dashboard-primary">Browse the menu</a>
            <a href="auth/register.php" class="role-dashboard-secondary">Get Started</a>
        </div>
    </div>
    <div class="home-hero-visual">
        <img class="home-hero-image" src="public/images/home-food.png" alt="A table filled with freshly prepared meals">
        <div class="home-glass-card">
            <span class="home-glass-brand"><span aria-hidden="true">✦</span> URBANEATS KITCHEN</span>
            <h2>Fresh flavors.<br>Made with care.</h2>
            <p>Delicious meals, prepared fresh and delivered to your door.</p>
            <div class="home-glass-footer">
                <span>FRESH · FAST · DELICIOUS</span>
                <span class="home-glass-status"><span aria-hidden="true"></span> Made fresh today</span>
            </div>
        </div>
    </div>
</div>
</div>

<section class="home-about" id="about-urbaneats" aria-labelledby="home-about-title">
    <div class="home-about-heading">
        <span class="home-hero-eyebrow">A LITTLE ABOUT US</span>
        <h2 id="home-about-title">Good food, made <span>easy.</span></h2>
        <p>
            UrbanEats is a Sri Lankan cloud kitchen bringing a variety of freshly prepared meals together in one easy-to-browse menu.
            Choose what you love, place your order online, and enjoy a delicious meal without the fuss.
        </p>
    </div>

    <div class="home-about-highlights">
        <article class="home-about-card">
            <span class="home-about-icon" aria-hidden="true">🍳</span>
            <h3>Prepared with care</h3>
            <p>Our kitchen team prepares each dish with attention to flavor and quality.</p>
        </article>
        <article class="home-about-card">
            <span class="home-about-icon" aria-hidden="true">🍽️</span>
            <h3>Plenty to choose from</h3>
            <p>Explore a range of dishes and find something to suit your appetite.</p>
        </article>
        <article class="home-about-card">
            <span class="home-about-icon" aria-hidden="true">🛵</span>
            <h3>Easy online ordering</h3>
            <p>Browse the menu and order your meal from the comfort of home.</p>
        </article>
    </div>

    <a class="home-about-link" href="menu.php">Explore our menu <span aria-hidden="true">→</span></a>
</section>

<?php
include 'includes/footer.php';
?>