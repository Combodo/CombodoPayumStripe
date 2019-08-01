<?php
/**
 * Created by Bruno DA SILVA, working for Combodo
 * Date: 10/07/19
 * Time: 10:30
 */

namespace AppBundle\Form\Type\GatewayConfiguration;


use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class StripeV3GatewayConfigurationType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('publishable_key', TextType::class, [
                'label' => 'sylius.form.gateway_configuration.stripe.publishable_key',
                'constraints' => [
                    new NotBlank([
                        'message' => 'sylius.gateway_config.stripe.publishable_key.not_blank',
                        'groups' => 'sylius',
                    ]),
                ],
            ])
            ->add('secret_key', TextType::class, [
                'label' => 'sylius.form.gateway_configuration.stripe.secret_key',
                'constraints' => [
                    new NotBlank([
                        'message' => 'sylius.gateway_config.stripe.secret_key.not_blank',
                        'groups' => 'sylius',
                    ]),
                ],
            ])

            ->add('endpoint_secret', TextType::class, [
                'label' => 'sylius.form.gateway_configuration.stripe.endpoint_secret',
                'constraints' => [
                    new NotBlank([
                        'message' => 'sylius.gateway_config.stripe.secret_key.not_blank',
                        'groups' => 'sylius',
                    ]),
                ],
            ])
        ;
    }
}
