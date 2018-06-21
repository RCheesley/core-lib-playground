<?php

/*
 * @copyright   2018 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CampaignBundle\EventListener;

use Mautic\CampaignBundle\CampaignEvents;
use Mautic\CampaignBundle\Entity\Campaign;
use Mautic\CampaignBundle\Entity\Event;
use Mautic\CampaignBundle\Entity\EventRepository;
use Mautic\CampaignBundle\Event\CampaignBuilderEvent;
use Mautic\CampaignBundle\Event\CampaignEvent;
use Mautic\CampaignBundle\Event\PendingEvent;
use Mautic\CampaignBundle\Executioner\EventExecutioner;
use Mautic\CampaignBundle\Form\Type\CampaignEventJumpToEventType;
use Mautic\CampaignBundle\Membership\MembershipManager;
use Mautic\CampaignBundle\Model\CampaignModel;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class CampaignActionJumpToEventSubscriber implements EventSubscriberInterface
{
    /**
     * @var EventRepository
     */
    private $eventRepository;

    /**
     * @var EventExecutioner
     */
    private $eventExecutioner;

    /**
     * CampaignActionJumpToEvent constructor.
     *
     * @param EventRepository  $eventRepository
     * @param EventExecutioner $eventExecutioner
     */
    public function __construct(EventRepository $eventRepository, EventExecutioner $eventExecutioner)
    {
        $this->eventRepository = $eventRepository;
        $this->eventExecutioner = $eventExecutioner;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            CampaignEvents::CAMPAIGN_ON_BUILD      => ['onCampaignBuild', 0],
            CampaignEvents::ON_EVENT_JUMP_TO_EVENT => ['onJumpToEvent', 0],
        ];
    }


    /**
     * Add event triggers and actions.
     *
     * @param CampaignBuilderEvent $event
     */
    public function onCampaignBuild(CampaignBuilderEvent $event)
    {
        // Add action to jump to another event in the campaign flow.
        $event->addAction('campaign.jump_to_event', [
            'label'                  => 'mautic.campaign.event.jump_to_event',
            'description'            => 'mautic.campaign.event.jump_to_event_descr',
            'formType'               => CampaignEventJumpToEventType::class,
            'template'               => 'MauticCampaignBundle:Event:jump.html.php',
            'batchEventName'         => CampaignEvents::ON_EVENT_JUMP_TO_EVENT,
            'connectionRestrictions' => [
                'target' => [
                    Event::TYPE_DECISION  => ['none'],
                    Event::TYPE_ACTION    => ['none'],
                    Event::TYPE_CONDITION => ['none'],
                ],
            ],
        ]);
    }

    /**
     * Process campaign.jump_to_event actions.
     *
     * @param PendingEvent $campaignEvent
     */
    public function onJumpToEvent(PendingEvent $campaignEvent)
    {
        foreach ($campaignEvent->getPending() as $log) {
            $event      = $log->getEvent();
            $jumpTarget = $this->getJumpTargetForEvent($event, 'e.id');

            if ($event->getType() !== 'campaign.jump_to_event' || $jumpTarget === null) {
                continue;
            }

            $this->eventExecutioner->executeForContacts($jumpTarget, $campaignEvent->getContacts());
        }
    }

    /**
     * Update campaign events.
     *
     * This block specifically handles the campaign.jump_to_event properties
     * to ensure that it has the actual ID and not the temp_id as the
     * target for the jump.
     *
     * @param CampaignEvent $campaignEvent
     */
    public function processCampaignEventsAfterSave(CampaignEvent $campaignEvent)
    {
        $campaign = $campaignEvent->getCampaign();
        $events   = $campaign->getEvents();
        $toSave   = [];

        foreach ($events as $event) {
            if ($event->getType() !== 'campaign.jump_to_event') {
                continue;
            }

            $jumpTarget = $this->getJumpTargetForEvent($event);

            if ($jumpTarget !== null) {
                $event->setProperties(array_merge(
                    $event->getProperties(),
                    [
                        'jumpToTarget' => $jumpTarget->getId(),
                    ]
                ));

                $toSave[] = $event;
            }
        }

        if (count($toSave)) {
            $this->eventRepository->saveEntities($toSave);
        }
    }

    /**
     * Inspect a jump event and get its target.
     *
     * @param Event $event
     * @param mixed $column
     *
     * @return null|Event
     */
    private function getJumpTargetForEvent(Event $event, $column = 'e.tempId')
    {
        $properties  = $event->getProperties();
        $jumpToEvent = $this->eventRepository->getEntities([
            'ignore_paginator' => true,
            'filter'           => [
                'force' => [
                    [
                        'column' => $column,
                        'value'  => $properties['jumpToEvent'],
                        'expr'   => 'eq',
                    ],
                    [
                        'column' => 'e.campaign',
                        'value'  => $event->getCampaign(),
                        'expr'   => 'eq',
                    ],
                ],
            ],
        ]);

        if (count($jumpToEvent)) {
            return $jumpToEvent[0];
        }

        return null;
    }
}
