<?php

namespace OroB2B\Bundle\RFPBundle\Datagrid\Action;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\DataGridBundle\Extension\Action\Actions\AbstractAction;

class RequestChangeStatusDialogAction extends AbstractAction
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var array
     */
    protected $requiredOptions = ['link'];

    /**
     * @var array
     */
    protected $defaultOptions = [
        'launcherOptions' => [
            'onClickReturnValue' => true,
            'runAction'          => true,
            'className'          => 'no-hash',
            'widget'             => [],
            'messages'           => [],
        ]
    ];

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        parent::__construct();

        $this->translator = $translator;
    }

    /**
     * {@inheritDoc}
     */
    public function getOptions()
    {
        $options = parent::getOptions();
        $finalOptions = array_replace_recursive($this->defaultOptions, $options->toArray());

        if (isset($finalOptions['widgetOptions']['options']['dialogOptions']['title'])) {
            $title = $this->translator->trans($finalOptions['widgetOptions']['options']['dialogOptions']['title']);

            $finalOptions['widgetOptions']['options']['dialogOptions']['title'] = $title;
        }

        $options->merge($finalOptions);

        return $options;
    }
}
