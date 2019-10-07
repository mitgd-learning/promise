<?php

namespace React\Promise;

use Exception;
use React\Promise\Exception\CompositeException;
use React\Promise\Exception\LengthException;

class FunctionSomeTest extends TestCase
{
    /** @test */
    public function shouldRejectWithLengthExceptionWithEmptyInputArray()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                self::callback(function ($exception) {
                    return $exception instanceof LengthException &&
                           'Input array must contain at least 1 item but contains only 0 items.' === $exception->getMessage();
                })
            );

        some(
            [],
            1
        )->then($this->expectCallableNever(), $mock);
    }

    /** @test */
    public function shouldRejectWithLengthExceptionWithInputArrayContainingNotEnoughItems()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->with(
                self::callback(function ($exception) {
                    return $exception instanceof LengthException &&
                           'Input array must contain at least 4 items but contains only 3 items.' === $exception->getMessage();
                })
            );

        some(
            [1, 2, 3],
            4
        )->then($this->expectCallableNever(), $mock);
    }

    /** @test */
    public function shouldResolveValuesArray()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->with(self::identicalTo([1, 2]));

        some(
            [1, 2, 3],
            2
        )->then($mock);
    }

    /** @test */
    public function shouldResolvePromisesArray()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->with(self::identicalTo([1, 2]));

        some(
            [resolve(1), resolve(2), resolve(3)],
            2
        )->then($mock);
    }

    /** @test */
    public function shouldResolveSparseArrayInput()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->with(self::identicalTo([null, 1]));

        some(
            [null, 1, null, 2, 3],
            2
        )->then($mock);
    }

    /**
     * @test
     * @group 123
     */
    public function shouldRejectIfAnyInputPromiseRejectsBeforeDesiredNumberOfInputsAreResolved()
    {
        $exception2 = new Exception();
        $exception3 = new Exception();

        $compositeException = new CompositeException(
            [1 => $exception2, 2 => $exception3],
            'Too many promises rejected.'
        );

        $mock = $this->createCallableMock();
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->with($compositeException);

        some(
            [resolve(1), reject($exception2), reject($exception3)],
            2
        )->then($this->expectCallableNever(), $mock);
    }


    /** @test */
    public function shouldResolveWithEmptyArrayIfHowManyIsLessThanOne()
    {
        $mock = $this->createCallableMock();
        $mock
            ->expects(self::once())
            ->method('__invoke')
            ->with(self::identicalTo([]));

        some(
            [1],
            0
        )->then($mock);
    }

    /** @test */
    public function shouldCancelInputArrayPromises()
    {
        $promise1 = new Promise(function () {}, $this->expectCallableOnce());
        $promise2 = new Promise(function () {}, $this->expectCallableOnce());

        some([$promise1, $promise2], 1)->cancel();
    }

    /** @test */
    public function shouldCancelOtherPendingInputArrayPromisesIfEnoughPromisesFulfill()
    {
        $deferred = new Deferred($this->expectCallableNever());
        $deferred->resolve();

        $promise2 = new Promise(function () {}, $this->expectCallableNever());

        some([$deferred->promise(), $promise2], 1);
    }

    /** @test */
    public function shouldNotCancelOtherPendingInputArrayPromisesIfEnoughPromisesReject()
    {
        $deferred = new Deferred($this->expectCallableNever());
        $deferred->reject(new Exception());

        $promise2 = new Promise(function () {}, $this->expectCallableNever());

        some([$deferred->promise(), $promise2], 2);
    }
}
