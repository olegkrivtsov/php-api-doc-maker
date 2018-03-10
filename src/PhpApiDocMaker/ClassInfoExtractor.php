<?php
namespace PhpApiDocMaker;

use PhpParser\Error;
use PhpParser\NodeDumper;
use PhpParser\ParserFactory;
use PhpParser\Node;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\Interface_;
use PhpParser\Node\Stmt\ClassConst;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Namespace_;
use PhpParser\Node\Stmt\UseUse;
use PhpParser\Node\Stmt\Property;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpApiDocMaker\Utils;

/**
 * This class is responsible for extracting PHP class information.
 */
class ClassInfoExtractor
{
    private $logger;
    
    private $phpParser;
    
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
        
        try {
            $ast = $this->phpParser->parse($code);

            $traverser = new NodeTraverser();

            $visitor = new class extends NodeVisitorAbstract {

                public $namespace = null;

                public $uses = [];

                public $class = [];

                public function enterNode(Node $node) {

                    $factory  = \phpDocumentor\Reflection\DocBlockFactory::createInstance();
                    
                    if ($node instanceof Namespace_) {
                        $this->namespace = implode('\\', $node->name->parts);
                    } else if ($node instanceof UseUse) {
                        $className = implode('\\', $node->name->parts);
                        $alias = $node->alias?$node->alias->name:$className;
                        $this->uses[$alias] = $className;
                    } else if ($node instanceof Class_) {
                        $classInfo = [];

                        $classInfo['type'] = 'class';
                        $classInfo['name'] = $this->namespace . '\\' . $node->name->name;
                        $classInfo['extends'] = [];
                        $classInfo['implements'] = [];
                        $classInfo['constants'] = [];
                        $classInfo['properties'] = [];
                        $classInfo['methods'] = [];

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

                                    $propName = $classStmt->props[0]->name->name;
                                    $default = $classStmt->props[0]->default?$classStmt->props[0]->default:null;
                                    $classInfo['properties'][] = ['name'=>$propName, 'default'=>$default];
                                }

                                if ($classStmt instanceof ClassMethod) {
                                    $isPublic = ($classStmt->flags & Class_::MODIFIER_PUBLIC)!=0;

                                    if (!$isPublic)
                                        continue;

                                    $methodName = $classStmt->name->name;

                                    $methodInfo = [
                                        'name' => $methodName, 
                                        'returnType' => $classStmt->returnType,
                                        'params' => []
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
                        
                    } else if ($node instanceof Interface_) {
                        
                        $classInfo = [];

                        $classInfo['type'] = 'interface';
                        $classInfo['name'] = $this->namespace . '\\' . $node->name->name;
                        $classInfo['extends'] = [];
                        $classInfo['constants'] = [];
                        $classInfo['properties'] = [];
                        $classInfo['methods'] = [];

                        if (is_array($node->extends)) {
                            foreach ($node->extends as $extends) {
                                $classInfo['extends'][] = implode('\\', $extends->parts);
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

                                    $propName = $classStmt->props[0]->name->name;
                                    $default = $classStmt->props[0]->default?$classStmt->props[0]->default:null;
                                    $classInfo['properties'][] = ['name'=>$propName, 'default'=>$default];
                                }

                                if ($classStmt instanceof ClassMethod) {
                                    $isPublic = ($classStmt->flags & Class_::MODIFIER_PUBLIC)!=0;

                                    if (!$isPublic)
                                        continue;

                                    $methodName = $classStmt->name->name;

                                    $methodInfo = [
                                        'name' => $methodName, 
                                        'returnType' => $classStmt->returnType,
                                        'params' => []
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


        } catch (Error $error) {
            $this->logger->log("PHP parse error: {$error->getMessage()} in file $file");
        }
        
        return $info;
    }
}


