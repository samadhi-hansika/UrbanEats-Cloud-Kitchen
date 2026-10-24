</div>
    <footer class="site-footer">
        <div class="site-footer-content">
            <div class="site-footer-brand">
                <a href="<?= e(url('index.php')) ?>" class="site-footer-logo">
                    <span class="site-footer-brand-icon" aria-hidden="true">🍔</span>
                    <span>Urban<span class="site-footer-logo-accent">Eats</span></span>
                </a>
                <p>Fresh flavors, prepared with care.<br>Good food, made easy.</p>
            </div>
            <nav class="site-footer-links" aria-label="Footer navigation">
                <h2>Explore</h2>
                <a href="<?= e(url('menu.php')) ?>">Menu</a>
                <a href="<?= e(url('index.php#about-urbaneats')) ?>">About Us</a>
                <a href="<?= e(url('index.php#contact-urbaneats')) ?>">Contact Us</a>
            </nav>
            <div class="site-footer-contact">
                <h2>Get in touch</h2>
                <a href="mailto:info@urbaneatscolombo.lk">
                    <span class="site-footer-contact-label">Email</span>
                    <span>info@urbaneatscolombo.lk</span>
                </a>
                <a href="tel:071234567">
                    <span class="site-footer-contact-label">Phone</span>
                    <span>071234567</span>
                </a>
            </div>
        </div>
        <div class="site-footer-bottom">
            <p>&copy; <?= date('Y') ?> UrbanEats Cloud Kitchen. All rights reserved.</p>
            <a href="<?= e(url('index.php')) ?>">Made for good food.</a>
        </div>
    </footer>
</body>
</html>