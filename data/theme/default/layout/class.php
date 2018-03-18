<?php 
use PhpApiDocMaker\Utils;
?>

<!-- Navigation -->
<div class="breadcrumbs">
    <?php $i = 1; ?>
    <?php foreach ($this->breadcrumbs as $name=>$url): ?>
    <a href="<?= $url ?>"><?= $name ?></a>
    <?php 
    if ($i<count($this->breadcrumbs)) echo '&rarr;';
    $i++;
    ?>
    <?php endforeach; ?>
</div>

<?php if ($this->projectProps['google_adsence']['enabled']): ?>
<?= $this->upperAdContent ?>
<?php endif; ?>

<!-- Content -->
<div id="chapter_content">
    <h1><?php if($this->classInfo['class']['type']=='class') echo 'Class'; else echo 'Interface'; ?> 
        <?php echo $this->className; ?></h1>        
</div>

<h2 id="inheritance">Summary</h2>

<table class="table">
    <tr>
        <td>
            Fully Qualified Name:
        </td>
        <td>
            <?= $this->fullyQualifiedClassName ?>
        </td>
    </tr>
    <?php if (count($this->classInfo['class']['extends'])!=0): ?>
    <tr>
        <td>
            Extends:
        </td>
        <td>
            <?php $i=0; ?>
            <?php foreach ($this->classInfo['class']['extends'] as $parentClass): ?>
            <a href="<?= $this->dirPrefix ?><?= $parentClass ?>.html"><?= Utils::getShortClassName($parentClass) ?></a><?php 
                if ($i<count($this->classInfo['class']['extends'])-1)
                    echo ', ';
                $i++;
            ?>
            <?php endforeach; ?>
        </td>
    </tr>
    <?php endif; ?>
    <?php if (count($this->classInfo['class']['implements'])!=0): ?>
    <tr>
        <td>
            Implements:
        </td>
        <td>
            <?php $i=0; ?>
            <?php foreach ($this->classInfo['class']['implements'] as $parentClass): ?>
            <a href="<?= $this->dirPrefix ?><?= $parentClass ?>.html"><?= Utils::getShortClassName($parentClass) ?></a><?php 
                if ($i<count($this->classInfo['class']['implements'])-1)
                    echo ', ';
                $i++;
            ?>
            <?php endforeach; ?>
        </td>
    </tr>
    <?php endif; ?>
</table>

<h2>Description</h2>

<div class="description">
    <p>
    <?= $this->classInfo['class']['summary'] ?>
    </p>
    <?= $this->classInfo['class']['description'] ?>
</div>

<?php if(count($this->fullMethods)!=0): ?>
<h2 id="methods">Methods</h2>

<table class="table">
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Defined By</th>
    </tr>
    <?php foreach ($this->fullMethods as $method): ?>
    <tr>
        <td>
            <a href="#<?= $method['name'] ?>()">
            <?= $method['name']?>()
            </a>
        </td>
        <td>
            <?= $method['summary']?>
        </td>
        <td>
            <a href="<?= $this->dirPrefix ?><?= $method['defined_by'] ?>.html">
            <?= Utils::getShortClassName($method['defined_by']) ?>
            </a>
        </td>
    </tr>
    <?php endforeach; ?>
</table>

<h2 id="method-details">Method Details</h2>

<?php foreach ($this->fullMethods as $method): ?>

<h3 id="<?= $method['name']?>()"><?= $method['name']?>()</h3>

<p>
<?= $method['summary'] ?>
</p>

<?= $method['description'] ?>

<?php if (count($method['params'])!=0): ?>
<table class="table">
    <tr>
        <th>Parameter Name</th>
        <th>Type</th>
        <th>Description</th>
    </tr>
    <?php foreach ($method['params'] as $param): ?>
    <tr>
        <td>
            <?= $param['name'] ?>
        </td>
        <td>
            <?= $param['type'] ?>
        </td>
        <td>
            <?= $param['description'] ?>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php endforeach; ?>

<?php endif; ?>


<?php if ($this->projectProps['google_adsence']['enabled']): ?>
<!-- Ads -->
<div id="ads-chapter-bottom">
<div>
<?= $this->lowerAdContent ?>
</div>
</div>
<?php endif; ?>
    
<div id="disqus_thread"></div>

<?php
$this->externalStylesheets[] = 'assets/css/prism.css';
$this->externalScripts[] = 'assets/js/prism.js';
$pageTitle = $this->pageTitle;

if ($this->projectProps['disqus']['enabled']) {
$disqusSrc = $this->projectProps['disqus']['src'];
$this->inlineScripts[] = <<<EOT
<script>
var disqus_config = function () {
this.page.url = window.location.href; // Replace PAGE_URL with your page's canonical URL variable
this.page.identifier = ''; // Replace PAGE_IDENTIFIER with your page's unique identifier variable
};

(function() { // DON'T EDIT BELOW THIS LINE
var d = document, s = d.createElement('script');

s.src = '$disqusSrc';

s.setAttribute('data-timestamp', +new Date());
(d.head || d.body).appendChild(s);
})();
</script>
<noscript>Please enable JavaScript to view the <a href="https://disqus.com/?ref_noscript" rel="nofollow">comments powered by Disqus.</a></noscript>
EOT;
}
?>
