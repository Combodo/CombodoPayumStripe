<?php
//
//$vendorSignature = '/vendor/';
//$lastVendorPos = strrpos(__DIR__, $vendorSignature);
//$subPath = substr(__DIR__, $lastVendorPos + strlen($vendorSignature));
//$tabSubPath = explode('/', $subPath);
//$nbDirToVendor = count($tabSubPath);
//$tabParentDirectoriesToVEndor = array_fill(0, $nbDirToVendor, '..');
//$relativePathToVendor = implode('/', $tabParentDirectoriesToVEndor);
//
//if (!$loader = @include __DIR__.'/'.$relativePathToVendor.'/../vendor/autoload.php') {
//    echo <<<EOM
//You must set up the project dependencies by running the following commands:
//
//    curl -s http://getcomposer.org/installer | php
//    php composer.phar install
//
//EOM;
//
//    exit(1);
//}
//
//$rc = new \ReflectionClass(\Payum\Core\GatewayInterface::class);
//$coreDir = dirname($rc->getFileName()).'/Tests';
//$loader->add('Payum\Core\Tests', $coreDir);
//
//
//
//$rc = new \ReflectionClass(\Combodo\StripeV3\StripeV3GatewayFactory::class);
//$localDir = dirname($rc->getFileName()).'../tests';
//$loader->add('Combodo\StripeV3\Tests', $localDir);
