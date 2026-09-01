<?php

namespace Tests\Unit;

use App\Support\WindowsPythonProbe;
use PHPUnit\Framework\TestCase;

class WindowsPythonProbeTest extends TestCase
{
    public function test_rejects_windowsapps_store_aliases(): void
    {
        $this->assertTrue(WindowsPythonProbe::isStoreAlias(
            'C:\\Users\\anthony\\AppData\\Local\\Microsoft\\WindowsApps\\python3.exe'
        ));
        $this->assertTrue(WindowsPythonProbe::isStoreAlias(
            'C:/Users/anthony/AppData/Local/Microsoft/WindowsApps/python.exe'
        ));
        $this->assertFalse(WindowsPythonProbe::isStoreAlias('C:\\Python312\\python.exe'));
        $this->assertFalse(WindowsPythonProbe::isStoreAlias('C:\\lorefire\\resources\\python\\runtime\\python.exe'));
        $this->assertFalse(WindowsPythonProbe::isStoreAlias(null));
    }

    public function test_rejects_store_stub_exit_and_empty_version(): void
    {
        $store = 'C:\\Users\\a\\AppData\\Local\\Microsoft\\WindowsApps\\python3.exe';

        $this->assertFalse(WindowsPythonProbe::isUsableInterpreter($store, '(3, 12)', 0));
        $this->assertFalse(WindowsPythonProbe::isUsableInterpreter('C:\\Python312\\python.exe', '', 0));
        $this->assertFalse(WindowsPythonProbe::isUsableInterpreter('C:\\Python312\\python.exe', '()', 0));
        $this->assertFalse(WindowsPythonProbe::isUsableInterpreter(
            'C:\\Python312\\python.exe',
            'Python was not found; run without arguments to install from the Microsoft Store.',
            9009
        ));
        $this->assertFalse(WindowsPythonProbe::isUsableInterpreter('C:\\Python312\\python.exe', '(3, 12)', 9009));
        $this->assertFalse(WindowsPythonProbe::isUsableInterpreter('C:\\Python312\\python.exe', '(3, 12)', 1));
    }

    public function test_accepts_real_cpython_version_tuple(): void
    {
        $this->assertTrue(WindowsPythonProbe::isUsableInterpreter(
            'C:\\Python312\\python.exe',
            '(3, 12)',
            0
        ));
        $this->assertTrue(WindowsPythonProbe::isUsableInterpreter(
            'C:\\lorefire\\resources\\python\\runtime\\python.exe',
            '(3, 9)',
            0
        ));
        $this->assertFalse(WindowsPythonProbe::isUsableInterpreter(
            'C:\\Python38\\python.exe',
            '(3, 8)',
            0
        ));
    }
}
