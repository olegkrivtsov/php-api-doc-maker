<h1>Components</h1>
    
<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Description</th>
            <th>Version</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($this->components as $component): ?>
    <tr>
        <td>
            <a href="components/<?= $component['name'].'.html' ?>">
                <?= $component['namespace'] ?>
            </a>
        </td>
        <td>
            <?= $component['description'] ?>
        </td>
        <td>
            <?= $component['version'] ?>
        </td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>