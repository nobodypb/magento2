<?php
/**
 * Copyright © 2015 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace Magento\GiftMessage\Ui\DataProvider\Product\Modifier;

use Magento\Catalog\Model\Locator\LocatorInterface;
use Magento\Catalog\Ui\DataProvider\Product\Form\Modifier\AbstractModifier;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Stdlib\ArrayManager;
use Magento\GiftMessage\Helper\Message;
use Magento\Store\Model\ScopeInterface;
use Magento\Ui\Component\Form\Element\Checkbox;
use Magento\Ui\Component\Form\Field;

/**
 * Class GiftMessageDataProvider
 */
class GiftMessage extends AbstractModifier
{
    const FIELD_MESSAGE_AVAILABLE = 'gift_message_available';

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var LocatorInterface
     */
    protected $locator;

    /**
     * @var ArrayManager
     */
    protected $arrayManager;

    /**
     * @param LocatorInterface $locator
     * @param ArrayManager $arrayManager
     * @param ScopeConfigInterface $scopeConfig
     */
    public function __construct(
        LocatorInterface $locator,
        ArrayManager $arrayManager,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->locator = $locator;
        $this->arrayManager = $arrayManager;
        $this->scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function modifyData(array $data)
    {
        $modelId = $this->locator->getProduct()->getId();
        $value = '';

        if (isset($data[$modelId][static::DATA_SOURCE_DEFAULT][static::FIELD_MESSAGE_AVAILABLE])) {
            $value = $data[$modelId][static::DATA_SOURCE_DEFAULT][static::FIELD_MESSAGE_AVAILABLE];
        }

        if ('' === $value) {
            $data[$modelId][static::DATA_SOURCE_DEFAULT][static::FIELD_MESSAGE_AVAILABLE] =
                $this->getValueFromConfig();
            $data[$modelId][static::DATA_SOURCE_DEFAULT]['use_config_' . static::FIELD_MESSAGE_AVAILABLE] = '1';
        }

        return $data;
    }


    /**
     * {@inheritdoc}
     */
    public function modifyMeta(array $meta)
    {
        return $this->customizeAllowGiftMessageField($meta);
    }

    /**
     * Customization of allow gift message field
     *
     * @param array $meta
     * @return array
     */
    protected function customizeAllowGiftMessageField(array $meta)
    {
        $groupCode = $this->getGroupCodeByField($meta, 'container_' . static::FIELD_MESSAGE_AVAILABLE);

        if (!$groupCode) {
            return $meta;
        }

        $containerPath = $this->getElementArrayPath($meta, 'container_' . static::FIELD_MESSAGE_AVAILABLE);
        $fieldPath = $this->getElementArrayPath($meta, static::FIELD_MESSAGE_AVAILABLE);
        $groupConfig = $this->arrayManager->get($containerPath, $meta);
        $fieldConfig = $this->arrayManager->get($fieldPath, $meta);

        $meta = $this->arrayManager->merge($containerPath, $meta, [
            'arguments' => [
                'data' => [
                    'config' => [
                        'formElement' => 'container',
                        'componentType' => 'container',
                        'component' => 'Magento_Ui/js/form/components/group',
                        'label' => $groupConfig['arguments']['data']['config']['label'],
                        'breakLine' => false,
                        'sortOrder' => $fieldConfig['arguments']['data']['config']['sortOrder'],
                        'dataScope' => '',
                    ],
                ],
            ],
        ]);
        $meta = $this->arrayManager->merge(
            $containerPath,
            $meta,
            [
                'children' => [
                    static::FIELD_MESSAGE_AVAILABLE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataScope' => static::FIELD_MESSAGE_AVAILABLE,
                                    'imports' => [
                                        'disabled' =>
                                            '${$.parentName}.use_config_'
                                            . static::FIELD_MESSAGE_AVAILABLE
                                            . ':checked',
                                    ],
                                    'additionalClasses' => 'admin__field-x-small',
                                    'formElement' => Checkbox::NAME,
                                    'componentType' => Field::NAME,
                                    'prefer' => 'toggle',
                                    'valueMap' => [
                                        'false' => '0',
                                        'true' => '1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                    'use_config_' . static::FIELD_MESSAGE_AVAILABLE => [
                        'arguments' => [
                            'data' => [
                                'config' => [
                                    'dataType' => 'number',
                                    'formElement' => Checkbox::NAME,
                                    'componentType' => Field::NAME,
                                    'description' => __('Use Config Settings'),
                                    'dataScope' => 'use_config_' . static::FIELD_MESSAGE_AVAILABLE,
                                    'valueMap' => [
                                        'false' => '0',
                                        'true' => '1',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]
        );

        return $meta;
    }

    /**
     * Get config value data
     *
     * @return string|null
     */
    protected function getValueFromConfig()
    {
        return $this->scopeConfig->getValue(
            Message::XPATH_CONFIG_GIFT_MESSAGE_ALLOW_ITEMS,
            ScopeInterface::SCOPE_STORE
        );
    }
}
