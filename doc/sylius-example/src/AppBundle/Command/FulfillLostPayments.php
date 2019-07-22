<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 22/07/19
 * Time: 16:21
 */

namespace AppBundle\Command;


use Combodo\StripeV3\Request\HandleLostPayments;
use Payum\Core\Payum;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class FulfillLostPayments extends Command
{
    // the name of the command (the part after "bin/console")
    protected static $defaultName = 'payum:stripev3:fulfill-lost-payments';
    /**
     * @var Payum
     */
    private $payum;
    /**
     * @var LoggerInterface
     */
    private $logger;

    public function __construct(Payum $payum, LoggerInterface $logger)
    {
        parent::__construct(static::$defaultName);

        $this->payum  = $payum;
        $this->logger = $logger;
    }

    protected function configure()
    {
        $this
            // the short description shown while running
            ->setDescription('pull event from Stripe and check if we have missed some in our database')

            ->addArgument('gateway-name', InputArgument::REQUIRED, 'The gateway name associated with the token ("stripe_checkout_v3" in the documentation examples)')
//            ->addOption('min_ctime', null, InputOption::VALUE_OPTIONAL, 'What is the max age filter for the events about to be fetched (use strtotime compatible format)?', '-1 day')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gatewayName = $input->getArgument('gateway-name');

        $io = new SymfonyStyle($input, $output);
        $message = sprintf('starting %s for gateway "%s"', $this->getName(), $gatewayName);
        $io->title($message);

        $gateway = $this->payum->getGateway($gatewayName);

        $request = new HandleLostPayments();
        $gateway->execute($request);

        $message = sprintf(
            'Found %d lost payments and %d already processed ones',
            $request->getLostRetrievedCounter(),
            $request->getParsedValidCounter()
        );

        $this->logger->info($message);
        $io->comment($message);
    }
}