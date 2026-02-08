<?php

require_once __DIR__ . '/../vendor/autoload.php';

use App\Lib\Commands\AbstractCommand;

try {
    $commandName = getInput();
    
    $commandInstance = getCommandInstance($commandName);

    checkCommandInstance($commandInstance);
    
    $commandInstance->execute();
    
    exit(0);
} catch(\Exception $e) {
    echo $e->getMessage();
    exit(1);
}

function getInput(): string {
    $inputs = getopt('c:');

    if(!isset($inputs['c'])) {
        throw new \Exception('You must provide a command to execute' . PHP_EOL);
    }

    return $inputs['c'];
    
}

function getCommandLibPrefix(): string {
    return 'App\\Lib\\Commands\\';
}

function getCommandPrefix(): string {
    return 'App\\Commands\\';
}

function getCommandInstance(string $commandName): AbstractCommand {
    if(class_exists(getCommandLibPrefix() . $commandName)) {
        return new (getCommandLibPrefix() . $commandName)();
    }

    if(class_exists(getCommandPrefix() . $commandName)) {
        return new (getCommandPrefix() . $commandName)();
    }

    throw new \Exception('Command not found');
}

function checkCommandInstance($commandInstance): void {
    if(is_subclass_of($commandInstance, AbstractCommand::class) === false) {
        throw new \Exception('Command not found 2' . PHP_EOL);
    }
}


?>
