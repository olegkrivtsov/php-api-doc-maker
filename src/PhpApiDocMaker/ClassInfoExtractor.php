<?php
namespace PhpApiDocMaker;

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\Trait_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpApiDocMaker\Utils;
use cebe\markdown\GithubMarkdown;

/**
 * This class is responsible for extracting PHP class information.
 */
class ClassInfoExtractor
{
    private $logger;
    
    private $phpParser;
    
    public $classIndex = [];
    
    public function __construct($logger)
    {
        $this->logger = $logger;
        $this->phpParser = (new ParserFactory)->create(ParserFactory::PREFER_PHP7);
    }
    
    /**
     * Scans component directory and extracts information about all PHP classes found in it.
     */
    public function extractPhpClassesForComponent($dir) 
    {
        $this->logger->log("Extracting class info for the component " . $dir . "\n");
        
        $files = Utils::getDirContents($dir);
        
        $classes = [];
        
        foreach ($files as $file) {
        
            $this->logger->log("Parsing PHP file: $file" . "\n");
            
            $classInfo = $this->getClassInfoFromFile($file);
            
            $className = $classInfo['namespace'] . '\\' . basename($file, '.php');
            
            $classes[$className] = $classInfo;
            
        }
        
        return $classes;
    }
    
    public function getClassInfoFromFile($file) 
    {
        $code = file_get_contents($file);
        
        $info = [
            'namespace' => null,
            'uses' => [],
            'classes' => [],
            'interfaces' => [],
        ];
        
        $ast = $this->phpParser->parse($code);

        $traverser = new NodeTraverser();

        $visitor = new class extends NodeVisitorAbstract {

            public $namespace = null;

            public $uses = [];

            public $class = [];

            public function enterNode(Node $node) {

                $parser = new \cebe\markdown\GithubMarkdown();
                $factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();

                if ($node instanceof Namespace_) {
                    
                    $this->namespace = implode('\\', $node->name->parts);
                    
                } else if ($node instanceof UseUse) {
                    
                    $className = implode('\\', $node->name->parts);
                    $alias = $node->alias?$node->alias->name:$className;
                    $this->uses[$alias] = $className;
                    
                } else if ($node instanceof Class_ || 
                           $node instanceof Interface_ || 
                           $node instanceof Trait_) {
                    
                    $classInfo = [];

                    $classInfo['type'] = 'class';
                    
                    if ($node instanceof Interface_)
                        $classInfo['type'] = 'interface';
                    else if ($node instanceof Trait_)
                        $classInfo['type'] = 'trait';
                    
                    $classInfo['name'] = $this->namespace . '\\' . $node->name->name;
                    $classInfo['extends'] = [];
                    $classInfo['implements'] = [];
                    $classInfo['constants'] = [];
                    $classInfo['properties'] = [];
                    $classInfo['methods'] = [];
                    $classInfo['summary'] = '';
                    $classInfo['description'] = '';
                    
                    try {
                        $docblock = $factory->create((string)$node->getDocComment());
                        $classInfo['summary'] = $docblock->getSummary();
                        $classInfo['description'] = $parser->parse($docblock->getDescription()->render());    
                    }
                    catch(\Exception $e) {
                        
                    }
                    
                    if (isset($node->extends) && is_array($node->extends)) {
                        foreach ($node->extends as $extends) {
                            $inheritedClass = implode('\\', $extends->parts);
                            if (isset($this->uses[$inheritedClass]))
                                $inheritedClass = $this->uses[$inheritedClass];
                            else 
                                $inheritedClass = $this->namespace . '\\' . $inheritedClass;
                            $classInfo['extends'][] = $inheritedClass;
                        }
                    }

                    if (isset($node->implements) && is_array($node->implements)) {
                        foreach ($node->implements as $implements) {
                            $inheritedClass = implode('\\', $implements->parts);
                            if (isset($this->uses[$inheritedClass]))
                                $inheritedClass = $this->uses[$inheritedClass];
                            else 
                                $inheritedClass = $this->namespace . '\\' . $inheritedClass;
                            $classInfo['implements'][] = $inheritedClass;
                        }
                    }

                    if (is_array($node->stmts)) {
                        foreach ($node->stmts as $classStmt) {
                            if ($classStmt instanceof ClassConst) {
                                foreach ($classStmt->consts as $const) {
                                    $constName = $const->name->name;
                                    $constValue = null;
                                    if ($const instanceof ClassConstFetch)
                                        $constValue = $const->class->name;
                                    else if ($const instanceof Const_)
                                        $constValue = $const->value->value;
                                    $classInfo['constants'][] = ['name'=>$constName, 'value'=>$constValue];
                                }
                            }

                            if ($classStmt instanceof Property) {
                                $isPublic = ($classStmt->flags & Class_::MODIFIER_PUBLIC)!=0;

                                if (!$isPublic)
                                    continue;

                                $summary = '';
                                $description = '';
                                try {
                                    $docblock = $factory->create((string)$classStmt->getDocComment());
                                    $summary = $docblock->getSummary();
                                    $description = $parser->parse($docblock->getDescription()->render());    
                                }
                                catch (\Exception $e) {
                                    
                                }
                                
                                $propName = $classStmt->props[0]->name->name;
                                $default = $classStmt->props[0]->default?$classStmt->props[0]->default:null;
                                $classInfo['properties'][] = [
                                    'name'=>$propName, 
                                    'default'=>$default,
                                    'summary'=>$summary,
                                    'description'=>$description,
                                ];
                            }

                            if ($classStmt instanceof ClassMethod) {
                                $isPublic = ($classStmt->flags & Class_::MODIFIER_PUBLIC)!=0;

                                if (!$isPublic)
                                    continue;

                                $methodName = $classStmt->name->name;

                                $summary = '';
                                $description = '';
                                try {
                                    $docblock = $factory->create((string)$classStmt->getDocComment());
                                    $summary = $docblock->getSummary();
                                    $description = $parser->parse($docblock->getDescription()->render());
                                }
                                catch (\Exception $e) {
                                    
                                }
                                
                                $methodInfo = [
                                    'name' => $methodName, 
                                    'returnType' => $classStmt->returnType,
                                    'params' => [],
                                    'summary' => $summary,
                                    'description' => $description,
                                ];

                                foreach ($classStmt->params as $param) {
                                    $methodInfo['params'][] = [
                                        'var' => $param->var->name,

                                    ];
                                }

                                $classInfo['methods'][] = $methodInfo;
                            }
                        }
                    }

                    $this->class = $classInfo;
                }
            }
        };

        $traverser->addVisitor($visitor);

        $ast = $traverser->traverse($ast);

        $info['uses'] = $visitor->uses;
        $info['namespace'] = $visitor->namespace;
        $info['class'] = $visitor->class;

        // Add class info to the class index
        $this->classIndex[$info['class']['name']] = $info;
        
        return $info;
    }
    
    public function getFullExtends($className)
    {
        if (!isset($this->classIndex[$className])) 
            return [];
        
        $classInfo = $this->classIndex[$className];
        
        $fullExtends = [];
        
        foreach ($classInfo['class']['extends'] as $extends) {
            
            $parentExtends = $this->getFullExtends($extends);
            
            foreach ($parentExtends as $extends2) {
                $fullExtends[] = $extends2;
            }
        }
        
        return $fullExtends;
    }
    
    public function getFullImplements($className)
    {
        if (!isset($this->classIndex[$className])) 
            return [];
        
        $classInfo = $this->classIndex[$className];
        
        $fullImplements = [];
        
        foreach ($classInfo['class']['implements'] as $implements) {
            
            $parentImplements = $this->getFullImplements($implements);
            
            foreach ($parentImplements as $implements2) {
                $fullImplements[] = $implements2;
            }
        }
        
        return $fullImplements;
    }
    
    /**
     * Returns the list of inherited and owned methods for the class. 
     */
    public function getFullClassMethodList($className) 
    {
        $classInfo = $this->classIndex[$className];
        
        $methods = [];
        
        $extends = $this->getFullExtends($className);
        
        foreach ($extends as $inheritedClass) {
            $parentClassInfo = $this->classIndex[$inheritedClass];
        
            $inheritedMethods = $parentClassInfo['class']['methods'];
            
            foreach ($inheritedMethods as $inheritedMethod) {
                $inheritedMethod['defined_by'] = $inheritedClass;
                $methods[] = $inheritedMethod;
            }
        }
        
        foreach ($classInfo['class']['methods'] as $ownMethod) {
                $ownMethod['defined_by'] = $className;
                $methods[] = $ownMethod;
        }
        
        return $methods;
    }
}


