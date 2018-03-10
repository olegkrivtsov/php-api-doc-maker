<?php
namespace PhpApiDocMaker;

class Application
{
    public function run($argc, $argv)
    {
        // Parse command-line arguments
        $optVerbose = false;
        $srcDir = null;
        try {
            
            $numArg = 0;
            foreach ($argv as $arg) {
                $numArg ++;
                if ($numArg == 1)
                    continue;
                
                if ($arg=='-v' || $arg=='--verbose') {
                    $optVerbose = true;
                } else if (substr($arg, 0, 1)=='-') {
                    throw new \Exception("Unknown option encountered: $arg");
                } else if($srcDir==null) {
                    $srcDir = $arg;
                } else {
                    throw new \Exception("Unexpected argument encountered: $arg");
                }                
            }
        } catch (\Exception $ex) {
            $this->printUsage();
            echo "Error: " . $ex->getMessage() . "\n";
            return 1;
        }
        
        // Generate the API documentation
        try {
            
            $generator = new ApiDocGenerator($optVerbose);
            $generator->generate($srcDir);
                
        } catch (\Exception $ex) {
            echo "Error: " . $ex->getMessage() . "\n";
            return 1;
        }
        
        $numWarnings = count($generator->getWarnings());
        if ($numWarnings!=0) {
            echo "Warnings:\n";
            echo "=========\n";
            foreach ($generator->getWarnings() as $warning) {
                echo "Warning: $warning\n";
            }            
        }
        
        echo "Done (0 errors; $numWarnings warnings)\n";
        return 0;
    }
    
    public function printUsage()
    {
        echo "Usage: php php-api-doc-maker.php [options] <src_dir>\n";
        echo "options:\n";
        echo "  -v | --verbose  Print verbose info to stdout.\n";
        echo "\n";
    }
}
