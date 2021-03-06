<?php

namespace Maghead\Schema;

use CLIFramework\Logger;
use Maghead\Config;
use Maghead\Utils\ClassUtils;
use ReflectionObject;
use ReflectionClass;

class SchemaUtils
{
    public static function printSchemaClasses(array $classes, Logger $logger = null)
    {
        if (!$logger) {
            $c = ServiceContainer::getInstance();
            $logger = $c['logger'];
        }
        $logger->info('Schema classes:');
        foreach ($classes as $class) {
            $logger->info($logger->formatter->format($class, 'green'), 1);
        }
    }

    /*
    static public function find_schema_parents(array $classes)
    {
        $parents = [];
        foreach ($classes as $class) {
            $schema = new $class; // declare schema
            foreach ($schema->relations as $relKey => $rel ) {
                if (!isset($rel['foreign_schema'])) {
                    continue;
                }
                $foreignClass = ltrim($rel['foreign_schema'],'\\');
                $schema = new $foreignClass;
                if ($rel->type == Relationship::BELONGS_TO) {
                    $parents[$class][] = $foreignClass;
                } else if ($rel->type == Relationship::HAS_ONE || $rel->type == Relationship::HAS_MANY) {
                    $parents[$foreignClass][] = $class;
                }
            }
        }
        return $parents;
    }
     */

    public static function buildSchemaMap(array $schemas)
    {
        $schemaMap = [];
        // map table names to declare schema objects
        foreach ($schemas as $schema) {
            $schemaMap[$schema->getTable()] = $schema;
        }

        return $schemaMap;
    }

    /**
     * Get referenced schema classes and put them in order.
     *
     * @param string[] schema objects
     */
    public static function expandSchemaClasses(array $classes)
    {
        $map = array();
        $schemas = array();
        foreach ($classes as $class) {
            $schema = new $class(); // declare schema

            if ($refs = $schema->getReferenceSchemas()) {
                foreach ($refs as $refClass => $v) {
                    if (isset($map[$refClass])) {
                        continue;
                    }
                    $schemas[] = new $refClass();
                    $map[$refClass] = true;
                }
            }

            if ($schema instanceof TemplateSchema) {
                $expandedSchemas = $schema->provideSchemas();
                foreach ($expandedSchemas as $expandedSchema) {
                    if (isset($map[get_class($expandedSchema)])) {
                        continue;
                    }
                    $schemas[] = $expandedSchema;
                    $map[get_class($expandedSchema)] = true;
                }
            } else {
                if (isset($map[$class])) {
                    continue;
                }
                $schemas[] = $schema;
                $map[$class] = true;
            }
        }

        return $schemas;
    }

    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param string[] $classes class list.
     */
    public static function filterBuildableSchemas(array $schemas)
    {
        $list = array();
        foreach ($schemas as $schema) {
            // skip abstract classes.
            if ($schema instanceof DynamicSchemaDeclare
                || $schema instanceof MixinDeclareSchema
                || (!$schema instanceof SchemaDeclare && !$schema instanceof DeclareSchema)
            ) {
                continue;
            }

            $rf = new ReflectionObject($schema);
            if ($rf->isAbstract()) {
                continue;
            }
            $list[] = $schema;
        }

        return $list;
    }

    public static function findSchemasByPaths(array $paths = null, Logger $logger = null)
    {
        if ($paths && !empty($paths)) {
            $finder = new SchemaFinder($paths, $logger);
            $finder->find();
        }

        return SchemaLoader::loadDeclaredSchemas();
    }

    /**
     *
     * @param Config       $config
     * @param Logger       $logger
     */
    public static function findSchemasByConfig(Config $config, Logger $logger = null)
    {
        // load class from class map
        if ($classMap = $config->getClassMap()) {
            foreach ($classMap as $file => $class) {
                if (!is_integer($file) && is_string($file)) {
                    require $file;
                }
            }
        }
        $paths = $config->getSchemaPaths();
        return self::findSchemasByPaths($paths, $logger);
    }

    /**
     * Returns schema objects.
     *
     * @return array schema objects
     */
    public static function findSchemasByArguments(Config $config, array $args, Logger $logger = null)
    {
        $classes = self::argumentsToSchemaObjects($args);

        // filter file path argumets
        $paths = array_filter($args, 'file_exists');
        if (empty($paths)) {
            $paths = $config->getSchemaPaths();
        }

        if (!empty($paths)) {
            $finder = new SchemaFinder($paths);
            $finder->find();
        }

        // load class from class map
        if ($classMap = $config->getClassMap()) {
            foreach ($classMap as $file => $class) {
                if (is_numeric($file)) {
                    continue;
                }
                require_once $file;
            }
        }
        return SchemaLoader::loadDeclaredSchemas();
    }

    public static function argumentsToSchemaObjects(array $args)
    {
        $classes = ClassUtils::filterExistingClasses($args);
        $classes = array_unique($classes);
        $classes = self::filterSchemaClasses($classes);
        return self::instantiateSchemaClasses($classes);
    }

    public static function instantiateSchemaClasses(array $classes)
    {
        return array_map(function ($class) {
            return new $class();
        }, $classes);
    }

    public static function getLoadedDeclareSchemaClasses()
    {
        $classes = get_declared_classes();

        return self::filterSchemaClasses($classes);
    }

    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param string[] $classes class list.
     */
    public static function filterSchemaClasses(array $classes)
    {
        $list = array();
        foreach ($classes as $class) {
            // skip abstract classes.
            if (
              !is_subclass_of($class, 'Maghead\Schema\DeclareSchema', true)
              || is_a($class, 'Maghead\Schema\DynamicSchemaDeclare', true)
              || is_a($class, 'Maghead\Schema\MixinDeclareSchema', true)
              || is_a($class, 'Maghead\Schema\MixinSchemaDeclare', true)
              || is_subclass_of($class, 'Maghead\Schema\MixinDeclareSchema', true)
            ) {
                continue;
            }
            $rf = new ReflectionClass($class);
            if ($rf->isAbstract()) {
                continue;
            }
            $list[] = $class;
        }

        return $list;
    }

    public static function filterDeclareSchemaClasses(array $classes)
    {
        return array_filter(function ($class) {
              return is_subclass_of($class, 'Maghead\Schema\DeclareSchema', true);
        }, $classes);
    }

}
