<?php

declare(strict_types=1);

namespace Application\Test\Arch\Main;

use PHPat\Selector\Selector;
use PHPat\Test\Builder\Rule;
use PHPat\Test\PHPat;

final class BasicArchitectureTest
{
    public function testCoreNamespaceIsOnlyUsedFromTestNamespace(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Core')),
            )
            ->excluding(
                Selector::inNamespace('Application\\Test'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Core')),
            )
            ->because(
                __METHOD__,
            );
    }

    public function testMigrationsNamespaceIsNotUsedFromAnywhereElse(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Migrations')),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Migrations')),
            )
            ->because(
                __METHOD__,
            );
    }

    public function testTestNamespaceIsNotUsedFromAnywhereElse(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Test')),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Test')),
            )
            ->because(
                __METHOD__,
            );
    }

    public function testMainModuleIsNotUsedFromAnywhereElse(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Core\\Main')),
            )
            ->excluding(
                Selector::inNamespace('Application\\Test\\Unit\\Main'),
                Selector::inNamespace('Application\\Test\\Integration\\Main'),
                Selector::classname('Application\\Test\\TestApplicationFactory'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Core\\Main')),
            )
            ->because(
                __METHOD__,
            );
    }

    public function testUtilModulesOnlyUsesComposerDependencies(): Rule
    {
        return PHPat::rule()
            ->classes(
                Selector::inNamespace('Application\\Core\\Util'),
            )
            ->canOnlyDependOn()
            ->classes(
                Selector::NOT(Selector::inNamespace('Application\\Core')),
                Selector::inNamespace('Application\\Core\\Util'),
            )
            ->because(
                __METHOD__,
            );
    }
}
