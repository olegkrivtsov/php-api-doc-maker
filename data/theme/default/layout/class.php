<!-- Navigation -->
<div class="navigation">
    <div class="contents">
        <a href="<?= $this->langDirPrefix ?>toc.html">
            <img alt="Contents" src="<?= $this->dirPrefix ?>assets/images/book.png"><span class="nav-btn-text">Contents</span>
        </a>
    </div>
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
            <?php foreach ($this->classInfo['class']['extends'] as $parentClass): ?>
            <?= $parentClass ?>,
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
            <?php foreach ($this->classInfo['class']['implements'] as $parentClass): ?>
            <?= $parentClass ?>,
            <?php endforeach; ?>
        </td>
    </tr>
    <?php endif; ?>
</table>

<?php if(count($this->classInfo['class']['properties'])!=0): ?>
<h2 id="properties">Properties</h2>

<table class="table">
    <tr>
        <th>Name</th>
        <th>Description</th>
    </tr>
    <?php foreach ($this->classInfo['class']['properties'] as $property): ?>
    <tr>
        <td>
            <?= $property['name']?>()
        </td>
        <td>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>

<?php if(count($this->classInfo['class']['methods'])!=0): ?>
<h2 id="methods">Methods</h2>

<table class="table">
    <tr>
        <th>Name</th>
        <th>Description</th>
    </tr>
    <?php foreach ($this->classInfo['class']['methods'] as $method): ?>
    <tr>
        <td>
            <?= $method['name']?>()
        </td>
        <td>
        </td>
    </tr>
    <?php endforeach; ?>
</table>
<?php endif; ?>


<?php if ($this->projectProps['google_adsence']['enabled']): ?>
<!-- Ads -->
<div id="ads-chapter-bottom">
<div>
<?= $this->lowerAdContent ?>
</div>
</div>
<?php endif; ?>
    
<!-- Navigation -->
<div class="navigation">
    <div class="contents">
        <a href="<?= $this->langDirPrefix ?>toc.html">
            <img alt="Contents" src="<?= $this->dirPrefix ?>assets/images/book.png"><span class="nav-btn-text">Contents</span>
        </a>
    </div>
</div>

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
