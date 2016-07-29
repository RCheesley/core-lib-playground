<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\LeadBundle\Helper;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\LeadBundle\Entity\Lead;
use Mautic\LeadBundle\Entity\UtmTag;
use Mautic\LeadBundle\Entity\PointsChangeLog;

/**
 * Class FormEventHelper
 *
 * @package Mautic\LeadBundle\Helper
 */
class FormEventHelper
{
    /**
     * @param Lead          $lead
     * @param MauticFactory $factory
     * @param               $action
     * @param               $config
     * @param               $form
     */
    public static function changePoints (Lead $lead, MauticFactory $factory, $action, $config, $form)
    {
        $model = $factory->getModel('lead');

        //create a new points change event
        $event = new PointsChangeLog();
        $event->setType('form');
        $event->setEventName($form->getId() . ":" . $form->getName());
        $event->setActionName($action->getName());
        $event->setIpAddress($factory->getIpAddress());
        $event->setDateAdded(new \DateTime());

        $event->setLead($lead);

        $oldPoints = $lead->getPoints();

        switch ($config['operator']) {
            case 'plus':
                $lead->addToPoints($config['points']);
                break;
            case 'minus':
                $lead->subtractFromPoints($config['points']);
                break;
            case 'times':
                $lead->multiplyPoints($config['points']);
                break;
            case 'divide':
                $lead->dividePoints($config['points']);
                break;
            default:
                throw new \UnexpectedValueException('Invalid operator');
        }

        $newPoints = $lead->getPoints();

        $event->setDelta($newPoints - $oldPoints);
        $lead->addPointsChangeLog($event);

        $model->saveEntity($lead, false);
    }

    /**
     * @param $action
     * @param $factory
     */
    public static function changeLists ($action, $factory)
    {
        $properties = $action->getProperties();

        /** @var \Mautic\LeadBundle\Model\LeadModel $leadModel */
        $leadModel  = $factory->getModel('lead');
        $lead       = $leadModel->getCurrentLead();
        $addTo      = $properties['addToLists'];
        $removeFrom = $properties['removeFromLists'];

        if (!empty($addTo)) {
            $leadModel->addToLists($lead, $addTo);
        }

        if (!empty($removeFrom)) {
            $leadModel->removeFromLists($lead, $removeFrom);
        }
    }


}
