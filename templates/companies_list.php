<?php foreach ($companies as $company): ?>
<div>
    <a href="/company/<?= $company['id'] ?>"><?= $company['name'] ?></a>
</div>
<?php endforeach; ?>

<?php for ($i=1; $i <= $paging['total']; $i++): ?>
    <span class="<?php if ($i == $paging['current']):?>current-page <?php endif; ?>some-class">
        <a href="/companies?page=<?= $i ?>"><?= $i ?></a>
    </span>
<?php endfor; ?>
