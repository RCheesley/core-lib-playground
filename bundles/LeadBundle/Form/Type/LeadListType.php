<?php

/*
 * @copyright   2014 Mautic Contributors. All rights reserved
 * @author      Mautic
 *
 * @link        http://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Form\Type;

use Mautic\LeadBundle\Model\ListModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormTypeInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class LeadListType extends AbstractType
{
    /**
     * @var ListModel
     */
    private $segmentModel;

    /**
     * @param ListModel $segmentModel
     */
    public function __construct(ListModel $segmentModel)
    {
        $this->segmentModel = $segmentModel;
    }

    /**
     * @param OptionsResolverInterface $resolver
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults([
            'choices' => function (Options $options) {
                $lists = (empty($options['global_only'])) ? $this->segmentModel->getUserLists() : $this->segmentModel->getGlobalLists();
                $lists = (empty($options['preference_center_only'])) ? $lists : $this->segmentModel->getPreferenceCenterLists();

                $choices = [];
                foreach ($lists as $l) {
                    $choices[$l['id']] = $l['name'];
                }

                return $choices;
            },
            'global_only'            => false,
            'preference_center_only' => false,
            'required'               => false,
        ]);
    }

    /**
     * @return null|string|FormTypeInterface
     */
    public function getParent()
    {
        return 'choice';
    }

    /**
     * @return string
     */
    public function getBlockPrefix()
    {
        return 'leadlist_choices';
    }
}
