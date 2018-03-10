<?php
namespace PhpApiDocMaker;

class ComponentInfoExtractor
{
    private $logger;
    
    public function __construct($logger)
    {
        $this->logger = $logger;
    }
    
    /**
     * Extracts info about component from composer.json and composer.lock files. 
     */
    public function getComponentInfo($srcDir, $componentDir)
    {
        $this->logger->log("Extracting component info for the component " . $componentDir . "\n");
        
        if (!is_dir($srcDir)) {
            throw new \Exception("$srcDir is not a readable source directory");
        }
        
        if (!is_dir($srcDir . 'vendor/' . $componentDir)) {
            throw new \Exception($srcDir . 'vendor/' . $componentDir . " is not a readable component directory");
        }
        
        $composerJsonFile = $srcDir . '/vendor/' . $componentDir . '/' . 'composer.json';
                
        if (!is_readable($composerJsonFile)) {
            throw new \Exception("Error reading file: $composerJsonFile");
        }
        
        $json = file_get_contents($composerJsonFile);
        
        $data = json_decode($json, true);
        
        $name = $data['name'];
        $description = $data['description'];
        
        foreach ($data['autoload']['psr-4'] as $namespace=>$sourceDir) { break; }
        
        $namespace = trim($namespace, '\\');
        
        if (!is_readable($srcDir.'composer.lock')) {
            throw new \Exception("Error reading file: " . $srcDir.'composer.lock');
        }
        
        $json = file_get_contents($srcDir.'composer.lock');
        
        $data = json_decode($json, true);
        
        $version = '';
        
        foreach($data['packages'] as $package) {
            if ($package['name']!=$name)
                continue;
            
            $version = $package['version'];
            break;
        }
        
        return [
            'name' => $name,
            'description' => $description,
            'version' => $version,
            'namespace' => $namespace,
            'src_dir' => $srcDir . '/vendor/' . $componentDir . '/' . $sourceDir,
        ];
    }
 
    /**
     * Produces an HTML representation of the file tree.
     */
    public function getComponentFileTree($baseNamespace, $baseDir, $dir)
    {
        $baseNamespace2 = str_replace('\\', '/', $baseNamespace);
        
        $fileTree = '';
        
        $files = scandir($dir);

        $folderItems = [];
        $fileItems = [];
        
        foreach($files as $key => $value){
            
            $path = $dir.DIRECTORY_SEPARATOR.$value;
            
            if(!is_dir($path)) {
                $fileItems[] = $path;
            } else if($value != "." && $value != "..") {
                $folderItems[] = $path;
            }
        }
        
        sort($fileItems);
        sort($folderItems);
        
        foreach($folderItems as $folder){
            
            $fileTree .= '<div class="directory"><div class="dir-name">'.basename($folder).'</div>';
            $fileTree .= $this->getComponentFileTree($baseNamespace, $baseDir, $folder);
            $fileTree .= '</div>';
        }
        
        foreach($fileItems as $file){
            
            $url = '../../classes/' . $baseNamespace2 . substr($file, strlen($baseDir), -4) . '.html';
            
            $fileTree .= '<div class="file"><a href="'.$url.'">'.basename($file, '.php').'</a></div>';
        }
        
        return $fileTree;
    }
}