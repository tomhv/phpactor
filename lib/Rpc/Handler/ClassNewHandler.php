<?php

namespace Phpactor\Rpc\Handler;

use Phpactor\Rpc\Handler;
use Phpactor\Application\ClassNew;
use Phpactor\Rpc\Editor\Input\TextInput;
use Phpactor\Rpc\Editor\Input\ChoiceInput;
use Phpactor\Rpc\Editor\StackAction;
use Phpactor\Rpc\Editor\InputCallbackAction;
use Phpactor\Rpc\ActionRequest;
use Phpactor\Application\Exception\FileAlreadyExists;
use Phpactor\Rpc\Editor\OpenFileAction;
use Phpactor\Rpc\Editor\EchoAction;
use Phpactor\Rpc\Editor\Input\ConfirmInput;

class ClassNewHandler extends AbstractClassGenerateHandler
{
    protected function generate(array $arguments)
    {
        return $this->classGenerator->generate($arguments['new_path'], $arguments['variant'], (bool) $arguments['overwrite']);
    }

    public function name(): string
    {
        return 'class_new';
    }

    public function newMessage(): string
    {
        return 'Create at: ';
    }
}

