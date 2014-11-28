<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\InstallBundle\Configurator\Form;

use Mautic\InstallBundle\Configurator\Step\DoctrineStep;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\ChoiceList\ChoiceList;
use Symfony\Component\Form\FormBuilderInterface;

/**
 * Doctrine Form Type.
 *
 * @author Fabien Potencier <fabien@symfony.com>
 * @note   This class is based on Sensio\Bundle\DistributionBundle\Configurator\Form\DoctrineStepType
 */
class DoctrineStepType extends AbstractType
{
    /**
     * {@inheritdoc}
     */
    public function buildForm (FormBuilderInterface $builder, array $options)
    {
        $builder->add('driver', 'choice', array(
            'choices'     => DoctrineStep::getDrivers(),
            'expanded'    => false,
            'multiple'    => false,
            'label'       => 'mautic.install.form.database.driver',
            'label_attr'  => array('class' => 'control-label'),
            'empty_value' => false,
            'required'    => true,
            'attr'        => array(
                'class' => 'form-control'
            )
        ));

        $builder->add('host', 'text', array(
            'label'      => 'mautic.install.form.database.host',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('port', 'text', array(
            'label'      => 'mautic.install.form.database.port',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('name', 'text', array(
            'label'      => 'mautic.install.form.database.name',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('table_prefix', 'text', array(
            'label'      => 'mautic.install.form.database.table.prefix',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => false
        ));

        $builder->add('user', 'text', array(
            'label'      => 'mautic.install.form.database.user',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array('class' => 'form-control'),
            'required'   => true
        ));

        $builder->add('password', 'password', array(
            'label'      => 'mautic.install.form.database.password',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'    => 'form-control',
                'preaddon' => 'fa fa-lock'
            ),
            'required'   => true
        ));

        $builder->add('backup_tables', 'button_group', array(
            'choice_list' => new ChoiceList(
                array(false, true),
                array('mautic.core.form.no', 'mautic.core.form.yes')
            ),
            'label'       => 'mautic.install.form.existing_tables',
            'expanded'    => true,
            'empty_value' => false,
            'attr'        => array(
                'tooltip' => 'mautic.install.form.existing_tables_descr',
                'onchange' => 'MauticInstaller.toggleBackupPrefix();'
            )
        ));

        $builder->add('backup_prefix', 'text', array(
            'label'      => 'mautic.install.form.backup_prefix',
            'label_attr' => array('class' => 'control-label'),
            'attr'       => array(
                'class'    => 'form-control'
            ),
            'required'   => false
        ));

        $builder->add('buttons', 'form_buttons', array(
            'pre_extra_buttons' => array(
                array(
                    'name'  => 'next',
                    'label' => 'mautic.install.next.step',
                    'type'  => 'submit',
                    'attr'  => array(
                        'class' => 'btn btn-success pull-right btn-next',
                        'icon'  => 'fa fa-arrow-circle-right',
                        'onclick' => 'MauticInstaller.showWaitMessage(event);'
                    )
                )
            ),
            'apply_text'        => '',
            'save_text'         => '',
            'cancel_text'       => ''
        ));

        if (!empty($options['action'])) {
            $builder->setAction($options['action']);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function getName ()
    {
        return 'install_doctrine_step';
    }
}
