<?php

declare(strict_types=1);
use pointybeard\Symphony\Extensions\Api_Framework;

class TransformerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * Create new Transformation object.
     **/
    public function testValidTraformationObjectCreation()
    {
        $t = new ApiFramework\Transformer();
        $this->assertTrue(($t instanceof ApiFramework\Transformer));
    }

    /**
     * Create a new Transform object.
     **/
    public function testValidCreateTransformation()
    {
        $test = function (array $input, array $attributes = []) {
            return true;
        };

        $action = function (array $input, array $attributes = []) {
            return $input;
        };

        $t = new ApiFramework\Transformation(
            $test,
            $action
        );

        $input = [1, 2, 3];
        $this->assertTrue($t->test($input));
        $this->assertEquals(count($input), count($t->action($input)));

        return $t;
    }

    /**
     * @depends testValidCreateTransformation
     */
    public function testAppendTransformation(ApiFramework\Transformation $t)
    {
        $transformer = new ApiFramework\Transformer();
        $this->assertTrue($transformer->append($t) instanceof ApiFramework\Transformer);
        $this->assertEquals(count($transformer->transformations()), 1);
    }
}
