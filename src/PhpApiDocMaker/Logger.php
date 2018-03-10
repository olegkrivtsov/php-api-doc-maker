<?php
namespace PhpApiDocMaker;

/**
 * This class is responsible for logging messages.
 */
class Logger
{
    private $verbose = false;
    
    /**
     * Constructor. 
     */
    public function __construct($verbose)
    {
        $this->verbose = $verbose;
    }
    
    /**
     * Adds a message to log.
     */
    public function log($msg)
    {
        if ($this->verbose)
            echo $msg;
    }
}