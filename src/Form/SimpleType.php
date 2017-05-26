<?php
namespace PhpSolution\NelmioApiDocYamlBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * Class SimpleType
 */
class SimpleType extends AbstractType
{
    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if (!array_key_exists('fields', $options)) {
            return;
        }
        foreach ($options['fields'] as $field) {
            if (array_key_exists('name', $field) && array_key_exists('type', $field)) {
                $builder->add(
                    $field['name'],
                    $field['type'],
                    isset($field['options']) ? $field['options'] : []
                );
            }
        }
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return '';
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver
            ->setDefault('fields', [])
            ->setAllowedTypes('fields', 'array')
            ->setRequired(['fields']);
    }
}