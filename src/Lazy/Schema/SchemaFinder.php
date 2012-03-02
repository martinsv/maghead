<?php
namespace Lazy\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use ReflectionClass;

class SchemaFinder
{
    public $paths = array();

    public function in($path)
    {
        $this->paths[] = $path;
    }

    public function addPath($path)
    {
        $this->paths[] = $path;
    }

    public function loadFiles()
    {
        foreach( $this->paths as $path ) {
            if( is_file($path) ) {
                require_once $path;
            }
            else {
                // directory iterator
                $rdi   = new RecursiveDirectoryIterator($path);
                $rii   = new RecursiveIteratorIterator($rdi);
                $regex = new RegexIterator($rii, '/Schema\.php$/i', RecursiveRegexIterator::GET_MATCH);
                foreach( $regex as $k => $files ) {
                    foreach( $files as $file ) {
                        require_once $file;
                    }
                }
            }
        }
    }

    public function getSchemaClasses()
    {
        $list = array();
        $classes = get_declared_classes();
        foreach( $classes as $class ) {
            $rf = new ReflectionClass( $class );

            // skip abstract classes.
            if( $rf->isAbstract() ) {
                continue;
            }

            if( is_a( $class, 'Lazy\Schema\MixinSchemaDeclare' ) 
                || $class == 'Lazy\Schema\MixinSchemaDeclare' 
                || is_subclass_of( $class, '\Lazy\Schema\MixinSchemaDeclare' ) ) 
            {
                continue;
            }

            if( is_subclass_of( $class, '\Lazy\Schema\SchemaDeclare' ) ) {
                $list[] = $class;
            }
        }

        $schemas = array();
        foreach( $list as $class ) {
            $schema = new $class;
            $refs = $schema->getReferenceSchemas();
            foreach( $refs as $ref => $v )
                $schemas[] = $ref;
            $schemas[] = $class;
        }
        return array_unique($schemas);
    }

}

