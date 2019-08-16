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
use Symfony\Component\Routing\RouterInterface;

class FulfillLostPayments extends Command
{
    const MIN_CTIME = '-1 day';
    /**
     * @var string ENV_HOST_PROVIDER The env var name containing the host of your store.
     * As this command run in a CLI env. Your host cannot be automatically computed as usually from the http request
     * **YOU DEFINITELY NEED TO OVERWRITE THIS VALUE**
     */
    const ENV_HOST_PROVIDER = 'APP_STORE_HOST';
    /**
     * @var int ENV_HTTPS_PROVIDER whether https is in use or not
     * As this command run in a CLI env. Your host cannot be automatically computed as usually from the http request
     * **YOU DEFINITELY NEED TO OVERWRITE THIS VALUE**
     */
    const ENV_HTTPS_PROVIDER = 'ENABLE_HTTPS';

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
    /**
     * @var RouterInterface
     */
    private $router;

    public function __construct(Payum $payum, LoggerInterface $logger, RouterInterface $router)
    {
        parent::__construct(static::$defaultName);

        $this->payum  = $payum;
        $this->logger = $logger;
        $this->router = $router;
    }

    protected function configure()
    {
        $this
            // the short description shown while running
            ->setDescription('pull event from Stripe and check if we have missed some in our database')

            ->addArgument('gateway-name', InputArgument::REQUIRED, 'The gateway name associated with the token ("stripe_checkout_v3" in the documentation examples)')
            ->addOption('min_ctime', null, InputOption::VALUE_OPTIONAL, 'What is the max age filter for the events about to be fetched (use strtotime compatible format)?', self::MIN_CTIME)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $gatewayName = $input->getArgument('gateway-name');

        $io = new SymfonyStyle($input, $output);
        $message = sprintf('starting %s for gateway "%s"', $this->getName(), $gatewayName);
        $io->title($message);

        $this->force_request_context();

        $gateway = $this->payum->getGateway($gatewayName);

        $minCtime = $input->getOption('min_ctime');
        $request = new HandleLostPayments($minCtime);
        $gateway->execute($request);

        $message = sprintf(
            '%s Found %d lost payments and %d already processed ones',
            date('Y-m-d H:i:s'),
            $request->getLostRetrievedCounter(),
            $request->getParsedValidCounter()
        );

        $this->logger->info($message);
        $io->comment($message);
    }

    /**
     * Enforce scheme and host into the router
     * As this command run in a CLI env. Your host cannot be automatically computed as usually from the http request
     *
     * Note: this code rely on env vars, but you could easily rely on a specific command line arguments combined with parse_url ;p
     */
    protected function force_request_context(): void
    {
        if (isset($forceRequestContext)) {
            $context = $this->router->getContext();

            if ($enableHttps = getenv(static::ENV_HTTPS_PROVIDER)) {
                if ('true' == $enableHttps) {
                    $context->setScheme('https');
                } else {
                    $context->setScheme('http');
                }
            }

            if ($host = getenv(static::ENV_HOST_PROVIDER)) {
                $context->setHost($host);
            }

            $this->router->setContext($context);
        }
    }
}