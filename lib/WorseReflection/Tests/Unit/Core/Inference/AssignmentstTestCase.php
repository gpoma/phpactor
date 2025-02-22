<?php

namespace Phpactor\WorseReflection\Tests\Unit\Core\Inference;

use PHPUnit\Framework\TestCase;
use Phpactor\WorseReflection\Core\Inference\Assignments;
use Phpactor\WorseReflection\Core\Inference\NodeContext;
use Phpactor\WorseReflection\Core\Inference\Variable;
use Phpactor\WorseReflection\Core\Inference\Symbol;
use Phpactor\WorseReflection\Core\Position;
use RuntimeException;

abstract class AssignmentstTestCase extends TestCase
{
    public function testAddByName(): void
    {
        $assignments = $this->assignments();
        $this->assertCount(0, $assignments->byName('hello'));

        $information = NodeContext::for(
            Symbol::fromTypeNameAndPosition(
                Symbol::VARIABLE,
                'hello',
                Position::fromStartAndEnd(0, 0)
            )
        );

        $assignments->add(0, Variable::fromSymbolContext($information));

        $this->assertEquals('hello', $assignments->byName('hello')->first()->name());
    }

    public function testMultipleByName(): void
    {
        $assignments = $this->assignments();

        $assignments->add(0, $this->createVariable('hello', 0, 0));
        $assignments->add(0, $this->createVariable('hello', 0, 0));

        $this->assertCount(2, $assignments->byName('hello'));
    }

    public function testLessThanEqualTo(): void
    {
        $assignments = $this->assignments();

        $assignments->add(0, $this->createVariable('hello', 0, 5));
        $assignments->add(5, $this->createVariable('hello', 5, 10));
        $assignments->add(10, $this->createVariable('hello', 10, 15));

        $this->assertCount(2, $assignments->byName('hello')->lessThanOrEqualTo(5));
    }

    public function testLessThan(): void
    {
        $assignments = $this->assignments();

        $assignments->add(0, $this->createVariable('hello', 0, 5));
        $assignments->add(5, $this->createVariable('hello', 5, 10));
        $assignments->add(10, $this->createVariable('hello', 10, 15));

        $this->assertCount(1, $assignments->byName('hello')->lessThan(5));
    }

    public function testGreaterThanOrEqualTo(): void
    {
        $assignments = $this->assignments();

        $assignments->add(0, $this->createVariable('hello', 0, 5));
        $assignments->add(5, $this->createVariable('hello', 5, 10));
        $assignments->add(10, $this->createVariable('hello', 10, 15));

        $this->assertCount(2, $assignments->byName('hello')->greaterThanOrEqualTo(5));
    }

    public function testGreaterThan(): void
    {
        $assignments = $this->assignments();

        $assignments->add(0, $this->createVariable('hello', 0, 5));
        $assignments->add(5, $this->createVariable('hello', 5, 10));
        $assignments->add(10, $this->createVariable('hello', 10, 15));

        $this->assertCount(1, $assignments->byName('hello')->greaterThan(5));
    }

    public function testThrowsExceptionIfIndexNotExist(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('No variable at index "5"');
        $assignments = $this->assignments();

        $assignments->add(0, $this->createVariable('hello', 0, 5));

        $this->assertCount(1, $assignments->atIndex(5));
    }

    abstract protected function assignments(): Assignments;

    private function createVariable(string $name, int $start, int $end): Variable
    {
        return Variable::fromSymbolContext(NodeContext::for(Symbol::fromTypeNameAndPosition(
            Symbol::VARIABLE,
            $name,
            Position::fromStartAndEnd($start, $end)
        )));
    }
}
