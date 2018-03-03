<?php
namespace PhpApiDocMaker;

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;

class ApiDocGenerator
{
    private $optVerbose = false;
    
    private $srcDir;
    
    private $outDir;
    
    private $phpRenderer;
    
    private $phpParser;
    
    private $projectProps;
    
    private $filesToCopy = [];
    
    private $siteUrls = [];
    
    private $warnings = [];
    
    public function __construct()
    {
        $this->phpRenderer = new PhpRenderer();
        
        $this->phpParser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
    
    /**
     * Returns the list of warnings.
     * @return type
     */
    public function getWarnings() 
    {
        return $this->warnings;
    }
    
    /**
     * Adds a message to log.
     */
    protected function log($msg)
    {
        if ($this->optVerbose)
            echo $msg;
    }
    
    /**
     * Generates the API documentation.
     */
    public function generate($srcDir, $optVerbose)
    {
        $this->srcDir = $srcDir;
        $this->optVerbose = $optVerbose;
        
        // Append slash to dir name, if not exists.
        if(substr($srcDir, -1)!='/')
            $srcDir .= '/';
            
        // Check if directory exists.
        if(!is_dir($srcDir))
            throw new \Exception("Passed argument is not a directory: $srcDir\n");
        
        $this->log("Starting API doc generation\n");
        
        // Save directory name.
        $this->srcDir = $srcDir;
        
        echo $srcDir;
        
        $this->outDir = $srcDir . 'html/';
        
        // Read 'php-api-doc-maker.json' file.
        $this->getProjectProps();

        // Process components.
        $this->processComponents();
        
        // Generate sitemap.xml.
        $this->generateSiteMap();
        
        // Get list of asset files
        $themeAssetsDir = 'data/theme/default/assets';
        $assetFiles = $this->getDirContents($themeAssetsDir); 
        
        foreach ($assetFiles as $fileName) {
            $dstFileName = substr($fileName, strlen($themeAssetsDir));
            $dstFileName = $this->outDir . 'assets/' . $dstFileName;
            $this->filesToCopy[$fileName] = $dstFileName;
        }
        
        $faviconImage = $this->bookDir . 'manuscript/favicon.ico';
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
        $this->log("Reading php-api-doc-maker.json file\n");
        
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
     * Generates the sitemap.xml file.
     */
    protected function generateSiteMap()
    {
        $this->log("Generating sitemap.xml\n");
        
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
        $this->log("Copying files\n");
        
        $count = 0;
        foreach ($this->filesToCopy as $srcFile=>$dstFile) {
            if(!is_dir(dirname($dstFile)))
                mkdir(dirname($dstFile), 0775, true);
            if(!is_readable($srcFile)) {
                $this->warnings[] = 'Failed to copy file: ' . $srcFile;
                $this->log('Failed to copy file: ' . $srcFile . "\n");
            } else if(copy($srcFile, $dstFile)) {
                $this->log("Copied file " . $srcFile . " to " . $dstFile . "\n");
                $count ++;
            }
        }
        
        $this->log("$count files copied.\n");
    }
    
    /**
     * Recursively scans directory for files and subdirectories. 
     */
    private function getDirContents($dir, &$results = array())
    {
        $files = scandir($dir);

        foreach($files as $key => $value){
            $path = $dir.DIRECTORY_SEPARATOR.$value;
            if(!is_dir($path)) {
                $results[] = $path;
            } else if($value != "." && $value != "..") {
                $this->getDirContents($path, $results);                
            }
        }

        return $results;
    }
    
    private function processComponents()
    {
        foreach ($this->projectProps['components'] as $componentDir) {
            $classes = $this->extractPhpClassesForComponent($this->srcDir.'vendor/'.$componentDir);
        }
    }
    
    /**
     * Scans component directory and extracts information about all PHP classes found in it.
     */
    private function extractPhpClassesForComponent($dir) 
    {
        $files = $this->getDirContents($dir);
        
        $info = [
            'namespace' => null,
            'uses' => [],
            'classes' => [],
        ];
        
        foreach ($files as $file) {
        
            $this->log("Parsing PHP file: $file");
            
            $code = file_get_contents($file);
            
            try {
                $ast = $this->phpParser->parse($code);
   
                $traverser = new NodeTraverser();
                
                $visitor = new class extends NodeVisitorAbstract {
                    
                    public $namespace = null;
                    
                    public $uses = [];
                    
                    public $classes = [];
                    
                    public function enterNode(Node $node) {
                        
                        if ($node instanceof Namespace_) {
                            $this->namespace = implode('\\', $node->name->parts);
                        }
                        
                        if ($node instanceof UseUse) {
                            $className = implode('\\', $node->name->parts);
                            $alias = $node->alias?$node->alias:$className;
                            $this->uses[$alias] = $className;
                        }
                        
                        if ($node instanceof Class_) {
                            $classInfo = [];
                            
                            $classInfo['name'] = $this->namespace . '\\' . $node->name->name;
                            $classInfo['extends'] = [];
                            $classInfo['implements'] = [];
                            
                            if (is_array($node->extends)) {
                                foreach ($node->extends as $extends) {
                                    $classInfo['extends'][] = implode('\\', $extends->parts);
                                }
                            }
                            
                            if (is_array($node->implements)) {
                                foreach ($node->implements as $implements) {
                                    $classInfo['implements'][] = implode('\\', $implements->parts);
                                }
                            }
                                    
                            $this->classes[] = $classInfo;
                        }
                    }
                };
                
                $traverser->addVisitor($visitor);

                $ast = $traverser->traverse($ast);

                $info['uses'] = $visitor->uses;
                $info['namespace'] = $visitor->namespace;
                $info['classes'] = $visitor->classes;
                
                
            } catch (Error $error) {
                $this->log("PHP parse error: {$error->getMessage()} in file $file");
            }

            print_r($info);
            exit;
            
        }
        
        return $classes;
    }
}

