</main>

<footer>
    <div class="container">
        <div class="footer-section">
            <h3><?= $language->get('site_name') ?></h3>
            <p>&copy; <?= date('Y') ?> <?= $language->get('site_name') ?>. <?= $language->get('all_rights_reserved') ?></p>
        </div>
        <div class="footer-section">
            <h3><?= $language->get('quick_links') ?></h3>
            <ul>
                <li><a href="<?= SITE_URL ?>"><?= $language->get('home') ?></a></li>
                <li><a href="<?= SITE_URL ?>/event.php"><?= $language->get('events') ?></a></li>
                <li><a href="<?= SITE_URL ?>/contact.php"><?= $language->get('contact') ?></a></li>
            </ul>
        </div>
        <div class="footer-section">
            <h3><?= $language->get('contact_us') ?></h3>
            <p>Email: <?= ADMIN_EMAIL ?></p>
            <p>Phone: +65 1234 5678</p>
        </div>
    </div>
</footer>

<!--<script src="--><?php //= SITE_URL ?><!--/assets/js/main.js"></script>-->
<?php //if (isset($includeQRCode) : ?>
<!--    <script src="--><?php //= SITE_URL ?><!--/assets/js/qrcode.min.js"></script>-->
<?php //endif; ?>
</body>
</html>