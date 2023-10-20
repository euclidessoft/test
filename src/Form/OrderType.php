<?php

namespace App\Form;

use App\Entity\Order;
use App\Entity\Produit;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class OrderType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nomclient')
            ->add('phoneclient')
            ->add('emailclient')
            ->add('adresselivraison')
            ->add('quantite')
            ->add('produit', EntityType::class,
                array( 'class' => Produit::class,
                    'choice_label' => 'nom',
                    'multiple' => false,
                    'placeholder' => 'Selectionnez produit *'))
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Order::class,
        ]);
    }
}
