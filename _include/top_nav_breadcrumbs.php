<div>
    <ol class="breadcrumb">
        <?php foreach ($breadcrumbs as $crumb): ?>
        <li class="breadcrumb-item <?php echo $crumb['url'] === '#' ? 'active' : '' ?>">
            <?php if ($crumb['url'] !== '#'): ?>
            <a href="<?php echo htmlspecialchars($crumb['url']) ?>">
                <span><?php echo htmlspecialchars($crumb['label']) ?></span>
            </a>
            <?php else: ?>
            <span><?php echo htmlspecialchars($crumb['label']) ?></span>
            <?php endif; ?>
        </li>
        <?php endforeach; ?>
    </ol>

    <h1 class="h2"><?php echo htmlspecialchars($pageTitle) ?></h1>
</div>