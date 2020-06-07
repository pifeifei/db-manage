<?php


namespace Pff\DatabaseManage\Driver\Traits;


use Symfony\Component\Console\Input\ArgvInput as SymfonyArgvInput;
use Symfony\Component\Console\Input\Input as SymfonyInput;
use Symfony\Component\Console\Output\Output as SymfonyOutput;
use Symfony\Component\Console\Style\SymfonyStyle;

trait SymfonyCommand
{
    /**
     * @return SymfonyArgvInput|SymfonyInput
     */
    protected function input()
    {
        return $this->manager->getApplication()->input();
    }

    /**
     * @return SymfonyOutput|SymfonyStyle
     */
    protected function output()
    {
        return $this->manager->getApplication()->output();
    }

    public function hasForce()
    {
        return $this->input()->hasOption('force');
    }
}
