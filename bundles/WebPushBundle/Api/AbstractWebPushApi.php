<?php
/**
 * @package     Mautic
 * @copyright   2016 Mautic Contributors. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.org
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\WebPushBundle\Api;

use Joomla\Http\Http;
use Joomla\Http\Response;
use Mautic\CoreBundle\Factory\MauticFactory;

abstract class AbstractWebPushApi
{
    /**
     * @var string
     */
    protected $apiUrl;

    /**
     * @var MauticFactory
     */
    protected $factory;

    /**
     * @var Http
     */
    protected $http;

    /**
     * @param MauticFactory $factory
     * @param Http $http
     */
    public function __construct(MauticFactory $factory, Http $http)
    {
        $this->factory = $factory;
        $this->http = $http;
    }

    /**
     * @param string $endpoint One of "apps", "players", or "notifications"
     * @param string $data JSON encoded array of data to send
     *
     * @return Response
     */
    abstract public function send($endpoint, $data);
}