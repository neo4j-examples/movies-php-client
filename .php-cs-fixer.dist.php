<?php

declare(strict_types=1);

/*
 * This file is part of the Neo4j PHP Client and Driver package.
 *
 * (c) Nagels <https://nagels.tech>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use PhpCsFixer\Config;

$header = <<<'EOF'
This file is part of the Neo4j PHP Movies Examples Project.

(c) Nagels <https://nagels.tech>

For the full copyright and license information, please view the LICENSE
file that was distributed with this source code.
EOF;

try {
    $finder = PhpCsFixer\Finder::create()
        ->in(__DIR__)
        ->exclude('vendor');

} catch (Throwable $e) {
    echo $e->getMessage()."\n";

    exit(1);
}

return (new Config())
    ->setRiskyAllowed(true)
    ->setRules([
        '@Symfony' => true,
    ])
    ->setFinder($finder)
;
