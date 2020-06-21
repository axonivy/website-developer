<?php
namespace test\domain\market;

use PHPUnit\Framework\Assert;
use PHPUnit\Framework\TestCase;
use app\domain\market\Market;
use app\domain\market\Product;

class MarketTest extends TestCase
{

    public function test_listed()
    {
        $products = Market::listed();
        Assert::assertGreaterThanOrEqual(1, count($products));
        foreach ($products as $product) {
            Assert::assertTrue($product->isListed());
        }
    }

    public function test_getProductByKey()
    {
        $portal = Market::getProductByKey('portal');
        Assert::assertEquals('portal', $portal->getKey());
    }

    public function test_getProductByKey_nonExisting()
    {
        $product = Market::getProductByKey('non-existing');
        Assert::assertNull($product);
    }
    
    public function test_getProductByKey_notListed()
    {
        $product = Market::getProductByKey('basic-workflow-ui');
        Assert::assertFalse($product->isListed());
    }
    
    public function test_all_sort()
    {
        $products = Market::all();
        $sorts = array_map(fn (Product $p) => $p->getSort(), $products);
        $count  = count($sorts);
        
        for ($i = 0; $i < $count - 1; $i++) {
            Assert::assertGreaterThan($sorts[$i], $sorts[$i + 1]);
        }
        Assert::assertEquals('portal', $products[0]->getKey());
    }
}
