<?php if (!function_exists('e')) { http_response_code(403); exit; } ?>
</main>
<footer class="site-footer">
    <div class="footer-inner">
        <div class="footer-brand">UNSAID</div>
        <p class="footer-text">A quiet place to say what you can't say out loud.</p>
        <p class="footer-copy">&copy; <?php echo date('Y'); ?> UNSAID. All rights reserved. Compliment, confession, o panggugulo. Ikaw bahala.</p>
    </div>
</footer>
<?php if (!empty($extraScripts) && is_array($extraScripts)): ?>
<?php foreach ($extraScripts as $extraScript): ?>
<script src="<?php echo e(app_base() . $extraScript); ?>"></script>
<?php endforeach; ?>
<?php endif; ?>
<script src="<?php echo app_base(); ?>assets/js/script.js"></script>
</body>
</html>
