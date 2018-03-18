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
<!-- Ads -->
<?= $this->upperAdContent ?>
<?php endif; ?>

<!-- Content -->
<div id="chapter_content">
 
    <p><?= $this->component['description']?></p>
    
    <h1>Classes</h1>
    
<div class="file-tree">
<?php echo $this->component['file_tree']; ?>
</div>
</div>
    
<div id="disqus_thread"></div>

<?php
$this->externalScripts[] = 'assets/js/main.js';

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
