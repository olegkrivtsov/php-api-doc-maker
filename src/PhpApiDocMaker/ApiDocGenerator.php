<?php
namespace PhpApiDocMaker;

use PhpApiDocMaker\PhpRenderer;
use PhpApiDocMaker\ClassInfoExtractor;
use PhpApiDocMaker\ComponentInfoExtractor;

class ApiDocGenerator
{
    private $srcDir;
    
    private $outDir;
    
    private $componentInfoExtractor;
    
    private $classInfoExtractor;
    
    private $phpRenderer;
    
    private $projectProps;
    
    private $filesToCopy = [];
    
    private $siteUrls = [];
    
    private $warnings = [];
    
    private $components = [];
    
    private $classIndex = [];
    
    /**
     * Logger.
     * @var type 
     */
    private $logger;
    
    /**
     * Constructor.
     */
    public function __construct($verbose = false)
    {
        $this->logger = new Logger($verbose);
        $this->componentInfoExtractor = new ComponentInfoExtractor($this->logger);
        $this->classInfoExtractor = new ClassInfoExtractor($this->logger);
        $this->phpRenderer = new PhpRenderer();
    }
    
    /**
     * Returns the list of warnings.
     */
    public function getWarnings() 
    {
        return $this->warnings;
    }
    
    /**
     * Generates the API documentation.
     */
    public function generate($srcDir)
    {
        $this->srcDir = $srcDir;
        
        // Append slash to dir name, if not exists.
        if(substr($srcDir, -1)!='/')
            $srcDir .= '/';
            
        // Check if directory exists.
        if(!is_dir($srcDir))
            throw new \Exception("Passed argument is not a directory: $srcDir\n");
        
        $this->logger->log("Starting API doc generation\n");
        
        // Save directory name.
        $this->srcDir = $srcDir;
        
        $this->outDir = $srcDir . 'html/';
        
        // Read 'php-api-doc-maker.json' file.
        $this->getProjectProps();

        // Process components.
        $this->processComponents();
        
        // Generate index.html
        $this->generateIndex();
        
        // Generate sitemap.xml.
        $this->generateSiteMap();
        
        // Get list of asset files
        $themeAssetsDir = 'data/theme/default/assets';
        $assetFiles = Utils::getDirContents($themeAssetsDir); 
        
        foreach ($assetFiles as $fileName) {
            $dstFileName = substr($fileName, strlen($themeAssetsDir));
            $dstFileName = $this->outDir . 'assets/' . $dstFileName;
            $this->filesToCopy[$fileName] = $dstFileName;
        }
        
        $faviconImage = $this->srcDir . 'favicon.ico';
        if (is_readable($faviconImage)) {
            $this->filesToCopy[$faviconImage] = $this->outDir . 'favicon.ico';
        }
        
        // Copy asset files to output directory
        $this->copyFiles();
    }
    
    /**
     * Extracts book properties from php-api-doc-maker.json file.     
     */
    protected function getProjectProps() 
    {
        $this->logger->log("Reading " . $this->srcDir . "php-api-doc-maker.json file\n");
        
        $fileName = $this->srcDir . 'php-api-doc-maker.json';
        
        if(!is_readable($fileName))
            throw new \Exception("The file $fileName doesn't exist or is not readable.");
        
        $json = file_get_contents($fileName);
        
        // Remove UTF-8 BOM
        $json = str_replace("\xEF\xBB\xBF",'', $json);
        
        $projectProps = json_decode($json, true);
        
        if(!is_array($projectProps))
            throw new \Exception("The file '$fileName' is not in JSON format.");
        
        $defaults = [
            "title" => "Unnamed API Doc",
            "copyright" => "Put Your Name Here",
            "license" => null,
            "book_website" => null,
            "keywords" => [],
            "links" => [],
            "components" => [],
            "google_analytics" => [
                "enabled" => false,
		"account_id" => null
            ],
            "google_adsence" => [
                "enabled" => false, 
                "contents_ad" => null,
		"chapter_upper_ad" => null,
		"chapter_bottom_ad" => null,
            ],
            "disqus" => [
                "enabled" => false,
		"src" => null
            ]
        ];
        
        $this->projectProps = array_merge($defaults, $projectProps);
    }
            
    /**
     * Collects information about each component and extracts PHP class information from
     * source files.
     */
    private function processComponents()
    {
        $this->logger->log("Processing components...\n");
        
        foreach ($this->projectProps['components'] as $componentDir) {
            
            $this->logger->log("Processing component " . $componentDir . "\n");
            
            $componentInfo = $this->componentInfoExtractor->getComponentInfo($this->srcDir, $componentDir);
            
            $classes = $this->classInfoExtractor->extractPhpClassesForComponent($componentInfo['src_dir']);
            
            $componentInfo['classes'] = $classes;
            
            $fileTree = $this->componentInfoExtractor->getComponentFileTree($componentInfo['namespace'], $componentInfo['src_dir'], $componentInfo['src_dir']);
            
            $componentInfo['file_tree'] = $fileTree;
            
            $this->components[$componentInfo['name']] = $componentInfo;
            
            $this->generateComponentHtml($componentInfo);
            
            foreach ($componentInfo['classes'] as $className=>$classInfo) {
                $this->generateClassHtml($componentInfo['name'], $className, $classInfo);
            }
        }
    }
    
    /**
     * Generates index.html file.
     */
    protected function generateIndex()
    {
        // Generate index.html
        
        $vars = [
            'components' => $this->components,
            'projectProps' => $this->projectProps
        ];
        
        $this->phpRenderer->clearVars();
        $content = $this->phpRenderer->render("data/theme/default/layout/index.php", $vars);
        
        $html = $this->renderMainLayout($content, null);
        
        $outFile = $this->outDir . "index.html";
        
        $this->logger->log("Generating index file: $outFile\n");
        
        file_put_contents($outFile, $html);
        
        array_unshift($this->siteUrls, [$this->projectProps['website'] . '/index.html', 1.0]);
    }
    
    /**
     * Generates component HTML file.
     */
    protected function generateComponentHtml($componentInfo)
    {
        $vars = [
            'breadcrumbs' => [
                'All Components' => '../../index.html',
                $componentInfo['name'] => '../../components/' . $componentInfo['name'] . '.html',
            ],
            'component' => $componentInfo,
            'projectProps' => $this->projectProps,
            'dirPrefix' => '../../',
        ];
        
        $this->phpRenderer->clearVars();
        $content = $this->phpRenderer->render("data/theme/default/layout/component.php", $vars);
        
        $html = $this->renderMainLayout($content, 'Component ' . $componentInfo['name'], '../../');
        
        $outFile = $this->outDir . "components/" . $componentInfo['name'] . ".html";
        
        $this->logger->log("Generating component HTML file: $outFile\n");
        
        if(!is_dir(dirname($outFile))) {
            mkdir(dirname($outFile), 0775, true);
        }
        
        file_put_contents($outFile, $html);
        
        array_unshift($this->siteUrls, [$this->projectProps['website'] . '/index.html', 1.0]);
    }
    
    protected function generateClassHtml($componentName, $className, $classInfo)
    {
        $outFile = $this->outDir . 'classes/' . str_replace('\\', '/', $className) . '.html';
        
        $this->logger->log("Generating class HTML file: $outFile\n");
        
        if (!isset($classInfo['class']['type'])) {
            $this->logger->log("No class info found; skipping.\n");
            return;
        }
        
        $nameParts = explode('\\', $className);
        
        $shortClassName = $nameParts[count($nameParts)-1];
        
        $dirPrefix = str_repeat('../', count($nameParts));
        
        $vars = [
            'breadcrumbs' => [
                'All Components' => $dirPrefix . 'index.html',
                $componentName => $dirPrefix . 'components/' . $componentName . '.html',
                'Class ' . $shortClassName => $dirPrefix . 'classes/' . str_replace('\\', '/', $className) . '.html', 
            ],
            'className' => $shortClassName,
            'fullyQualifiedClassName' => $className,
            'classInfo' => $classInfo,
            'fullMethods' => $this->classInfoExtractor->getFullClassMethodList($className),
            'projectProps' => $this->projectProps,
            'dirPrefix' => '../../',
        ];
        
        
        $this->phpRenderer->clearVars();
        $content = $this->phpRenderer->render("data/theme/default/layout/class.php", $vars);
        
        $html = $this->renderMainLayout($content, 'Class ' . $className, $dirPrefix);
                
        if(!is_dir(dirname($outFile))) {
            mkdir(dirname($outFile), 0775, true);
        }
        
        file_put_contents($outFile, $html);
        
        array_unshift($this->siteUrls, [$this->projectProps['website'] . 'index.html', 1.0]);
    }
    
    /**
     * Renders main layout.
     */
    protected function renderMainLayout($content, $pageTitle, $dirPrefix = '', $langCode = 'en')
    {
        $vars = [
            'title' => $this->projectProps['title'],
            'keywords' => implode(',', $this->projectProps['keywords']),
            'pageTitle' => $pageTitle,
            'copyright' => $this->projectProps['copyright'],
            'links' => $this->projectProps['links'],
            'content' => $content,
            'dirPrefix' => $dirPrefix,
            'projectProps' => $this->projectProps
        ];
                
        $html = $this->phpRenderer->render("data/theme/default/layout/main.php", $vars);
        
        return $html;
    }
    
    /**
     * Generates the sitemap.xml file.
     */
    protected function generateSiteMap()
    {
        $this->logger->log("Generating sitemap.xml\n");
        
        $baseURL = $this->projectProps['website'];
        $siteMapGenerator = new SitemapGenerator($baseURL, $this->outDir);
        
        foreach ($this->siteUrls as $urlInfo) {
            $siteMapGenerator->addUrl($urlInfo[0], date('c'), 'monthly', $urlInfo[1]);
        }
        
        $siteMapGenerator->createSitemap();
        $siteMapGenerator->writeSitemap();
    }
    
    /**
     * Copies asset files to output directory.
     */
    protected function copyFiles()
    {
        $this->logger->log("Copying files\n");
        
        $count = 0;
        foreach ($this->filesToCopy as $srcFile=>$dstFile) {
            if(!is_dir(dirname($dstFile)))
                mkdir(dirname($dstFile), 0775, true);
            if(!is_readable($srcFile)) {
                $this->warnings[] = 'Failed to copy file: ' . $srcFile;
                $this->logger->log('Failed to copy file: ' . $srcFile . "\n");
            } else if(copy($srcFile, $dstFile)) {
                $this->logger->log("Copied file " . $srcFile . " to " . $dstFile . "\n");
                $count ++;
            }
        }
        
        $this->logger->log("$count files copied.\n");
    }
}

