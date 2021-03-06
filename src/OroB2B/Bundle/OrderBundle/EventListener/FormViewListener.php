<?php

namespace OroB2B\Bundle\OrderBundle\EventListener;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\UIBundle\Event\BeforeListRenderEvent;
use Oro\Bundle\UIBundle\View\ScrollData;

use OroB2B\Bundle\AccountBundle\Entity\AccountUser;
use OroB2B\Bundle\AccountBundle\Entity\Account;

class FormViewListener
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @param TranslatorInterface $translator
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(
        TranslatorInterface $translator,
        DoctrineHelper $doctrineHelper
    ) {
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountUserView(BeforeListRenderEvent $event)
    {
        /** @var AccountUser $accountUser */
        $accountUser = $this->getEntityFromRequestId('OroB2BAccountBundle:AccountUser');
        if ($accountUser) {
            $template = $event->getEnvironment()->render(
                'OroB2BOrderBundle:AccountUser:orders_view.html.twig',
                ['entity' => $accountUser]
            );
            $this->addSalesOrdersBlock($event->getScrollData(), $template);
        }
    }

    /**
     * @param BeforeListRenderEvent $event
     */
    public function onAccountView(BeforeListRenderEvent $event)
    {
        /** @var Account $account */
        $account = $this->getEntityFromRequestId('OroB2BAccountBundle:Account');
        if ($account) {
            $template = $event->getEnvironment()->render(
                'OroB2BOrderBundle:Account:orders_view.html.twig',
                ['entity' => $account]
            );
            $this->addSalesOrdersBlock($event->getScrollData(), $template);
        }
    }

    /**
     * @param $className
     * @return null|object
     */
    protected function getEntityFromRequestId($className)
    {
        if (!$this->request) {
            return null;
        }

        $entityId = (int)$this->request->get('id');
        if (!$entityId) {
            return null;
        }

        $entity = $this->doctrineHelper->getEntityReference($className, $entityId);
        if (!$entity) {
            return null;
        }

        return $entity;
    }

    /**
     * @param Request $request
     */
    public function setRequest($request)
    {
        $this->request = $request;
    }

    /**
     * @param ScrollData $scrollData
     * @param string $html
     */
    protected function addSalesOrdersBlock(ScrollData $scrollData, $html)
    {
        $blockLabel = $this->translator->trans('orob2b.order.sales_orders.label');
        $blockId = $scrollData->addBlock($blockLabel);
        $subBlockId = $scrollData->addSubBlock($blockId);
        $scrollData->addSubBlockData($blockId, $subBlockId, $html);
    }
}
