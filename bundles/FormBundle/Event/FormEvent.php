<?php
/**
 * @package     Mautic
 * @copyright   2014 Mautic, NP. All rights reserved.
 * @author      Mautic
 * @link        http://mautic.com
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\FormBundle\Event;

use Mautic\CoreBundle\Event\CommonEvent;
use Mautic\FormBundle\Entity\Form;

/**
 * Class FormEvent
 */
class FormEvent extends CommonEvent
{

    /**
     * @var Form
     */
    private $entity;

    /**
     * @var bool
     */
    private $isNew;

    /**
     * @param Form $form
     * @param bool $isNew
     */
    public function __construct(Form &$form, $isNew = false)
    {
        $this->entity =& $form;
        $this->isNew  = $isNew;
    }

    /**
     * Returns the Form entity
     *
     * @return Form
     */
    public function getForm()
    {
        return $this->entity;
    }

    /**
     * Sets the Form entity
     *
     * @param Form $form
     */
    public function setForm(Form $form)
    {
        $this->entity = $form;
    }
}
