</main>
<!--<footer class="footer mt-auto py-3 bg-light">-->

<footer class="horizontal-nav  mt-auto py-3 bg-light">
    <div class="container">
        <hr>
        <div class="row">
            <div class="col-md-5">
                <h5><?= SITE_NAME ?></h5>
                <p><?= $lang->get('footer_description') ?></p>
            </div>
            <div class="col-md-4">
                <h5><?= $lang->get('quick_links') ?></h5>
                <ul class="list-unstyled horizontal-footer-nav">
                    <li><a href="/ticket/"><?= $lang->get('home') ?></a></li>
                    <li><a href="/ticket/events.php"><?= $lang->get('events') ?></a></li>
                    <li><a href="/ticket/about.php"><?= $lang->get('about') ?></a></li>
                    <li><a href="/ticket/contact.php"><?= $lang->get('contact') ?></a></li>
                </ul>
            </div>
            <div class="col-md-3">
                <h5><?= $lang->get('legal') ?></h5>
                <ul class="list-unstyled horizontal-footer-nav">
                    <li><a href="/ticket/terms.php"><?= $lang->get('terms') ?></a></li>
                    <li><a href="/ticket/privacy.php"><?= $lang->get('privacy') ?></a></li>
                    <li><a href="/ticket/refund.php"><?= $lang->get('refund_policy') ?></a></li>
                </ul>
            </div>
        </div>
        <hr>
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0">&copy; <?= date('Y') ?> <?= SITE_NAME ?>. <?= $lang->get('all_rights_reserved') ?></p>
            </div>
            <div class="col-md-6">
                <ul class="list-inline horizontal-footer-nav text-md-end mb-0">
                    <li class="list-inline-item"><a href="#"><i class="bi bi-facebook"></i></a></li>
                    <li class="list-inline-item"><a href="#"><i class="bi bi-twitter"></i></a></li>
                    <li class="list-inline-item"><a href="#"><i class="bi bi-instagram"></i></a></li>
                </ul>
            </div>
        </div>
    </div>
</footer>

<script src="/ticket/assets/js/main.js"></script>
<!--<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>-->
</body>
</html>