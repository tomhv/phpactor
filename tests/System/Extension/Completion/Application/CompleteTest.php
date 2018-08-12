<?php

namespace Phpactor\Tests\System\Extension\Completion\Application;

use Phpactor\Extension\Completion\Application\Complete;
use Phpactor\TestUtils\ExtractOffset;
use Phpactor\Tests\System\SystemTestCase;

class CompleteTest extends SystemTestCase
{
    /**
     * @dataProvider provideComplete
     */
    public function testComplete(string $source, array $expected)
    {
        $result = $this->complete($source);

        foreach ($expected as $index => $expectedSuggestion) {
            $this->assertArraySubset($expectedSuggestion, $result['suggestions'][$index]);
        }
    }

    public function provideComplete()
    {
        return [
            'Public property' => [
                <<<'EOT'
<?php

class Foobar
{
    public $foo;
}

$foobar = new Foobar();
$foobar-><>

EOT
        , [
                    [
                        'type' => 'property',
                        'name' => 'foo',
                        'short_description' => 'pub $foo',
                    ]
                ]
            ],
            'Private property' => [
                <<<'EOT'
<?php

class Foobar
{
    private $foo;
}

$foobar = new Foobar();
$foobar-><>

EOT
        ,
            [ ]
            ],
            'Public property access' => [
                <<<'EOT'
<?php

class Barar
{
    public $bar;
}

class Foobar
{
    /**
     * @var Barar
     */
    public $foo;
}

$foobar = new Foobar();
$foobar->foo-><>

EOT
               , [
                    [
                        'type' => 'property',
                        'name' => 'bar',
                        'short_description' => 'pub $bar',
                    ]
                ]
            ],
            'Public method with parameters' => [
                <<<'EOT'
<?php

class Foobar
{
    public function foo(string $zzzbar = 'bar', $def): Barbar
    {
    }
}

$foobar = new Foobar();
$foobar-><>

EOT
                , [
                    [
                        'type' => 'method',
                        'name' => 'foo',
                        'short_description' => 'pub foo(string $zzzbar = \'bar\', $def): Barbar',
                    ]
                ]
            ],
            'Public method multiple return types' => [
                <<<'EOT'
<?php

class Foobar
{
    /**
     * @return Foobar|Barbar
     */
    public function foo()
    {
    }
}

$foobar = new Foobar();
$foobar-><>

EOT
                , [
                    [
                        'type' => 'method',
                        'name' => 'foo',
                        'short_description' => 'pub foo(): Foobar|Barbar',
                    ]
                ]
            ],
            'Private method' => [
                <<<'EOT'
<?php

class Foobar
{
    private function foo(): Barbar
    {
    }
}

$foobar = new Foobar();
$foobar-><>

EOT
                , [
                ]
            ],
            'Static property' => [
                <<<'EOT'
<?php

class Foobar
{
    public static $foo;
}

$foobar = new Foobar();
$foobar::<>

EOT
                , [
                    [
                        'type' => 'property',
                        'name' => 'foo',
                        'short_description' => 'pub static $foo',
                    ]
                ]
            ],
            'Static property with previous arrow accessor' => [
                <<<'EOT'
<?php

class Foobar
{
    public static $foo;

    /**
     * @var Foobar
     */
    public $me;
}

$foobar = new Foobar();
$foobar->me::<>

EOT
                , [
                    [
                        'type' => 'property',
                        'name' => 'foo',
                        'short_description' => 'pub static $foo',
                    ],
                    [
                        'type' => 'property',
                        'name' => 'me',
                        'short_description' => 'pub $me: Foobar',
                    ]
                ]
            ],
            'Partially completed' => [
                <<<'EOT'
<?php

class Foobar
{
    public static $foobar;
    public static $barfoo;
}

$foobar = new Foobar();
$foobar::f<>

EOT
                , [
                    [
                        'type' => 'property',
                        'name' => 'foobar',
                        'short_description' => 'pub static $foobar',
                    ]
                ]
            ],
            'Partially completed' => [
                <<<'EOT'
<?php

class Foobar
{
    const FOOBAR = 'foobar';
    const BARFOO = 'barfoo';
}

$foobar = new Foobar();
$foobar::<>

EOT
                , [
                    [
                        'type' => 'constant',
                        'name' => 'BARFOO',
                        'short_description' => 'const BARFOO',
                    ],
                    [
                        'type' => 'constant',
                        'name' => 'FOOBAR',
                        'short_description' => 'const FOOBAR',
                    ],
                ],
            ],
            'Accessor on new line' => [
                <<<'EOT'
<?php

class Foobar
{
    public $foobar;
}

$foobar = new Foobar();
$foobar
    -><>

EOT
                , [
                    [
                        'type' => 'property',
                        'name' => 'foobar',
                        'short_description' => 'pub $foobar',
                    ],
                ],
            ]
        ];
    }

    private function complete(string $source)
    {
        list($source, $offset) = ExtractOffset::fromSource($source);
        $complete = $this->container()->get('application.complete');
        $result = $complete->complete($source, $offset);

        return $result;
    }

    /**
     * @dataProvider provideErrors
     */
    public function testErrors(string $source, array $expected)
    {
        $results = $this->complete($source);
        $this->assertEquals($expected, $results['issues']);
    }

    public function provideErrors()
    {
        return [
            [
                <<<'EOT'
<?php

$asd = 'asd';
$asd-><>
EOT
                ,
                [
                    'Cannot complete members on scalar value (string)',
                ]
            ],
            [
                <<<'EOT'
<?php

$asd-><>
EOT
                ,
                [
                    'Variable "asd" is undefined',
                ]
            ],
            [
                <<<'EOT'
<?php

$asd = new BooBar();
$asd-><>
EOT
                ,
                [
                    'Could not find class "BooBar"',
                ]
            ],
            [
                <<<'EOT'
<?php

class Foobar
{
    public $foobar;
}

$foobar = new Foobar();
$foobar->barbar-><>;
EOT
                ,
                [
                    'Class "Foobar" has no properties named "barbar"',
                ]
            ]
        ];
    }
}