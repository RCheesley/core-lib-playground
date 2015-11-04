<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\UserBundle\Security\Authentication;

use Mautic\CoreBundle\Factory\MauticFactory;
use Mautic\UserBundle\Event\LogoutEvent;
use Mautic\UserBundle\UserEvents;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Logout\LogoutHandlerInterface;

class LogoutHandler implements LogoutHandlerInterface
{

    /**
     * @var MauticFactory
     */
    private $factory;

    public function __construct(MauticFactory $factory)
    {
        $this->factory = $factory;
    }
    /**
     * {@inheritdoc}
     *
     * @param Request $request
     *
     * @return Response never null
     */
    public function logout(Request $request, Response $response, TokenInterface $token)
    {
        /** @var \Mautic\UserBundle\Model\UserModel $userModel */
        $userModel = $this->factory->getModel('user');
        $userModel->setOnlineStatus('offline');

        $dispatcher = $this->factory->getDispatcher();
        if ($dispatcher->hasListeners(UserEvents::USER_LOGOUT)) {
            $event = new LogoutEvent($this->factory->getUser(), $request);
            $dispatcher->dispatch(UserEvents::USER_LOGOUT, $event);
        }

        // Clear session
        $session = $request->getSession();
        $session->clear();

        if (isset($event)) {
            $sessionItems = $event->getPostSessionItems();
            foreach ($sessionItems as $key => $value) {
                $session->set($key, $value);
            }
        }
        // Note that a logout occurred
        $session->set('post_logout', true);
    }
}