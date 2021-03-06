<?php

/*
 * This file is part of PhpSpec, A php toolset to drive emergent
 * design by specification.
 *
 * (c) Marcello Duarte <marcello.duarte@gmail.com>
 * (c) Konstantin Kudryashov <ever.zet@gmail.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace PhpSpec\Console\Command;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;

/**
 * Command line command responsible to signal to generators we will need to
 * generate a new spec
 *
 * @Internal
 */
final class DescribeCommand extends Command
{
    protected function configure()
    {
        $this
            ->setName('describe')
            ->setDefinition(array(
                    new InputArgument('class', InputArgument::OPTIONAL, 'Class to describe'),
                ))
            ->setDescription('Creates a specification for a class')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command creates a specification for a class:

  <info>php %command.full_name% ClassName</info>

Will generate a specification ClassNameSpec in the spec directory.

  <info>php %command.full_name% Namespace/ClassName</info>

Will generate a namespaced specification Namespace\ClassNameSpec.
Note that / is used as the separator. To use \ it must be quoted:

  <info>php %command.full_name% "Namespace\ClassName"</info>

EOF
            )
        ;
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     *
     * @return int|null|void
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $container = $this->getApplication()->getContainer();
        $container->configure();

        if ($input->getArgument('class')) {
            $classname = $input->getArgument('class');
        } else {
            $questionHelper = $this->getApplication()->getHelperSet()->get('question');
            $question = new Question('<info>Enter class to describe: </info>');

            $question->setAutocompleterValues($this->getNamespaces());
            $classname = $questionHelper->ask($input, $output, $question);
        }

        $resource = $container->get('locator.resource_manager')->createResource($classname);

        $container->get('code_generator')->generate($resource, 'specification');
    }

    /**
     * Get suites namespaces.
     *
     * @return array
     */
    private function getNamespaces()
    {
        $container = $this->getApplication()->getContainer();
        $srcPaths = array();

        foreach ($container->getByTag('locator.locators') as $locator) {
            if ($locator instanceof \PhpSpec\Locator\PSR0\PSR0Locator) {
                $srcPaths[] = $locator->getSrcPath();
            }
        }

        return $container->get('console.autocomplete_provider')->getNamespaces($srcPaths);
    }
}
