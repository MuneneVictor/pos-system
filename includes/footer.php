<?php
$pageScripts = $pageScripts ?? [];
?>
</div>

<div class="sidebar-backdrop" data-sidebar-backdrop></div>

<script src="<?= e(asset_url('js/theme.js')) ?>"></script>
<script src="<?= e(asset_url('js/main.js')) ?>"></script>
<?php foreach ($pageScripts as $script): ?>
    <script src="<?= e(asset_url('js/' . ltrim((string) $script, '/'))) ?>"></script>
<?php endforeach; ?>
</body>
</html>
