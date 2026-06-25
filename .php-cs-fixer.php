<?php

declare(strict_types=1);

return (new PhpCsFixer\Config())
    ->setRules([
        '@PSR12'              => true,
        '@PHP82Migration'     => true,
        'array_syntax'        => ['syntax' => 'short'],
        'ordered_imports'     => ['sort_algorithm' => 'alpha'],
        'no_unused_imports'   => true,
        'declare_strict_types'=> true,
        'trailing_comma_in_multiline' => true,
    ])
    ->setFinder(
        PhpCsFixer\Finder::create()
            ->in([__DIR__ . '/src', __DIR__ . '/tests'])
    );
