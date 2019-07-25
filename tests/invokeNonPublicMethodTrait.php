<?php
namespace Combodo\StripeV3\Tests;


/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 25/07/19
 * Time: 10:31
 */

trait invokeNonPublicMethodTrait {

    /**
     * utility: perform a call over a private/protected method
     *
     * @param object $instance
     * @param string $methodName
     * @param array  $methodParams
     *
     * @return mixed the called function result
     *
     * @throws \ReflectionException
     */
    private function invokeNonPublicMethod(object $instance, string $methodName, array $methodParams)
    {
        $reflectionClass = new \ReflectionClass(get_class($instance));
        $reflectionMethod = $reflectionClass->getMethod($methodName);
        $reflectionMethod->setAccessible(true);

        //this call the private method being tested
        return $reflectionMethod->invokeArgs($instance, $methodParams);
    }
}