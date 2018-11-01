<?php
use Symphony\ApiFramework\Lib;

class TransformerTest extends \PHPUnit_Framework_TestCase
{

    /**
     * Create new Transformation object
     **/
    public function testValidTraformationObjectCreation()
    {
        $t = new Lib\Transformer();
        $this->assertTrue(($t instanceof Lib\Transformer));
    }

    /**
     * Create a new Transform object
     **/
    public function testValidCreateTransformation()
    {
        $test = function (array $input, array $attributes=[]) {
            return true;
        };

        $action = function (array $input, array $attributes=[]) {
            return $input;
        };

        $t = new Lib\Transformation(
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
    public function testAppendTransformation(Lib\Transformation $t)
    {
        $transformer = new Lib\Transformer();
        $this->assertTrue($transformer->append($t) instanceof Lib\Transformer);
        $this->assertEquals(count($transformer->transformations()), 1);
    }
}
