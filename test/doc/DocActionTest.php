<?php
namespace test\doc;

use PHPUnit\Framework\TestCase;
use test\AppTester;

class DocActionTest extends TestCase
{
    
    public function testRender()
    {
        AppTester::assertThatGet('/doc/7.0.1')
            ->ok()
            ->bodyContains('Designer Guide')
            ->bodyContains('Engine Guide');
    }
    
    public function testRedirectMajorToLatestMinor()
    {
        AppTester::assertThatGet('/doc/8')->redirect('/doc/8.0');
        AppTester::assertThatGet('/doc/7')->redirect('/doc/7.5');
    }
 
    public function testDoNotRedirectMinor()
    {
        AppTester::assertThatGet('/doc/8.0')->notFound();
        AppTester::assertThatGet('/doc/7.0')->notFound();
    }
}
