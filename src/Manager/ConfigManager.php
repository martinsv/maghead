<?php

namespace Maghead\Manager;

use Maghead\Config;
use Maghead\ConfigLoader;
use Maghead\DSN\DSNParser;
use Maghead\DSN\DSN;
use PDO;

class ConfigManager
{
    protected $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function addNode($nodeId, $dsnstr, $opts = array())
    {
        $dsnParser = new DSNParser();
        $dsn = $dsnParser->parse($dsnstr);

        // The data source array to be added to the config array
        $node = [];
        $node['driver'] = $dsn->getDriver();


        if ($host = $dsn->getHost()) {
            $node['host'] = $host;
        } else if (isset($opts['host'])) {
            $node['host'] = $opts['host'];
            $dsn->setAttribute('host', $opts['host']);
        }

        if ($port = $dsn->getPort()) {
            $node['port'] = $port;
        } else if (isset($opts['port'])) {
            $node['port'] = $opts['port'];
            $dsn->setAttribute('port', $opts['port']);
        }

        // MySQL only attribute
        if ($dbname = $dsn->getAttribute('dbname')) {
            $node['database'] = $dbname;
        } else if (isset($opts['dbname'])) {
            $node['database'] = $opts['dbname'];
            $dsn->setAttribute('dbname', $opts['dbname']);
        }

        if (isset($opts['user'])) {
            $node['user'] = $opts['user'];
        }
        if (isset($opts['password'])) {
            $node['pass'] = $opts['password'];
        }

        $node['dsn'] = $dsn->__toString();

        switch ($dsn->getDriver()) {
            case 'mysql':
                // $this->logger->debug('Setting connection options: PDO::MYSQL_ATTR_INIT_COMMAND');
                $node['connection_options'] = [PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8'];
            break;
        }
        $this->config['data_source']['nodes'][$nodeId] = $node;
    }

    public function save($file = null)
    {
        return ConfigLoader::writeToSymbol($this->config, $file);
    }

}
