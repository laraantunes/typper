    <footer style="margin-top: 50px; border-top: 1px solid #eee; padding-top: 20px; text-align: center; color: #666;">
        <p>&copy; <?= date('Y') ?> <?= htmlspecialchars(config('siteTitle') ?: 'Typper Site') ?>. Todos os direitos reservados.</p>
        <p><small>Powered by <a href="https://github.com/laraantunes/typper" style="color: #666; text-decoration: none; font-weight: 600;">Typper CMS</a></small></p>
    </footer>

    <!-- Code Syntax Highlight JS -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/prism.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-php.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-javascript.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/prism/1.29.0/components/prism-css.min.js"></script>
</body>
</html>