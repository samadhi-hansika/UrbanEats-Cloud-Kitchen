</div>
    <footer id="site-footer" class="site-footer site-footer-modern">
        <div class="site-footer-cta">
            <div>
                <span class="site-footer-cta-eyebrow">FRESH FROM OUR KITCHEN</span>
                <p>Make your next meal a little more delicious.</p>
            </div>
            <a href="<?= e(url('menu.php')) ?>">Explore the menu <span aria-hidden="true">→</span></a>
        </div>
        <div class="site-footer-content">
            <div class="site-footer-brand">
                <a href="<?= e(url('index.php')) ?>" class="site-footer-logo">
                    <img class="site-footer-brand-icon" src="<?= e(url('public/images/urbaneats-logo.png')) ?>" alt="">
                    <span>Urban<span class="site-footer-logo-accent">Eats</span></span>
                </a>
                <p>Fresh flavors, prepared with care.<br>Good food, made easy. Made for sharing.</p>
            </div>
            <nav class="site-footer-links" aria-label="Footer navigation">
                <h2>Explore</h2>
                <a href="<?= e(url('menu.php')) ?>"><span aria-hidden="true">↗</span> Menu</a>
                <a href="<?= e(url('index.php#about-urbaneats')) ?>"><span aria-hidden="true">↗</span> About Us</a>
                <a href="<?= e(url('index.php#contact-urbaneats')) ?>"><span aria-hidden="true">↗</span> Contact Us</a>
            </nav>
            <div class="site-footer-contact">
                <h2>Get in touch</h2>
                <a href="mailto:info@urbaneatscolombo.lk">
                    <span class="site-footer-contact-icon" aria-hidden="true">@</span>
                    <span><span class="site-footer-contact-label">Email us</span>info@urbaneatscolombo.lk</span>
                </a>
                <a href="tel:071234567">
                    <span class="site-footer-contact-icon" aria-hidden="true">☎</span>
                    <span><span class="site-footer-contact-label">Call us</span>071234567</span>
                </a>
            </div>
        </div>
        <div class="site-footer-bottom">
            <p>&copy; <?= date('Y') ?> UrbanEats Cloud Kitchen. Made with care.</p>
            <a href="<?= e(url('index.php')) ?>">Good Food <span aria-hidden="true">·</span> Better Mood</a>
        </div>
    </footer>
</body>
</html>