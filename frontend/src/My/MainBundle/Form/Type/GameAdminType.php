<?php
namespace My\MainBundle\Form\Type;

use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class GameAdminType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        parent::buildForm($builder, $options);

        $builder
            ->add('name')
            ->add('isRunning')
            ->add('movementTimeout')
            ->add('economyTimeout')
        ;
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(array(
            'data_class' => 'My\MainBundle\Entity\Game',
            'cascade_validation' => true,
        ));
    }

    public function getName()
    {
        return 'game_admin';
    }
}