<?php

namespace Tests\Unit;

use App\Support\WindowsTimedCommandExit;
use PHPUnit\Framework\TestCase;

class WindowsTimedCommandExitTest extends TestCase
{
    public function test_null_exit_code_is_not_failure(): void
    {
        $this->assertSame(0, WindowsTimedCommandExit::resolve(null));
        $this->assertSame(0, WindowsTimedCommandExit::resolve(''));
        $this->assertFalse(WindowsTimedCommandExit::failed(null));
        $this->assertFalse(WindowsTimedCommandExit::failed(''));
    }

    public function test_null_after_successful_pip_output_is_success(): void
    {
        $output = "Successfully installed packaging-26.3 pip-26.2.1 setuptools-84.0.0 wheel-0.48.0\n";

        $this->assertTrue(WindowsTimedCommandExit::looksLikeSuccessOutput($output));
        $this->assertSame(0, WindowsTimedCommandExit::resolve(null, $output));
        $this->assertFalse(WindowsTimedCommandExit::failed(null, $output));
    }

    public function test_zero_is_success_and_nonzero_is_failure(): void
    {
        $this->assertSame(0, WindowsTimedCommandExit::resolve(0));
        $this->assertFalse(WindowsTimedCommandExit::failed(0));
        $this->assertSame(1, WindowsTimedCommandExit::resolve(1));
        $this->assertTrue(WindowsTimedCommandExit::failed(1));
        $this->assertSame(9009, WindowsTimedCommandExit::resolve(9009));
        $this->assertTrue(WindowsTimedCommandExit::failed(9009));
    }

    public function test_success_output_markers(): void
    {
        $this->assertTrue(WindowsTimedCommandExit::looksLikeSuccessOutput('Requirement already satisfied: pip'));
        $this->assertTrue(WindowsTimedCommandExit::looksLikeSuccessOutput('Successfully uninstalled pip-24.0'));
        $this->assertFalse(WindowsTimedCommandExit::looksLikeSuccessOutput(''));
        $this->assertFalse(WindowsTimedCommandExit::looksLikeSuccessOutput(null));
        $this->assertFalse(WindowsTimedCommandExit::looksLikeSuccessOutput('ERROR: No matching distribution'));
    }
}
