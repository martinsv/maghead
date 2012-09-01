<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Metadata;

class MetadataCommand extends Command
{

    public function brief() { return 'set, get or list metadata.'; }

    public function usage() { 
        return 
              "\tlazy metadata\n"
            . "\tlazy metadata [key] [value]\n"
            . "\tlazy metadata [key]\n";
    }

    public function execute() 
    {
        CommandUtils::init_config_loader();

        $args = func_get_args();
        if(empty($args)) {
            $meta = new Metadata('default');
            printf("%26s | %-20s\n",'Key','Value');
            printf("%s\n", str_repeat('=',50));
            foreach( $meta as $key => $value ) {
                printf("%26s   %-20s\n", $key, $value);
            }
        }
        elseif( count($args) == 1 ) {
            $key = $args[0];
            $meta = new Metadata('default');
            $value = $meta[$key];
            $this->logger->info("$key = $value");
        }
        elseif( count($args) == 2 ) {
            list($key,$value) = $args;
            $this->logger->info("Setting metadata $key to $value.");
            $meta = new Metadata('default');
            $meta[$key] = $value;
        }
    }
}



