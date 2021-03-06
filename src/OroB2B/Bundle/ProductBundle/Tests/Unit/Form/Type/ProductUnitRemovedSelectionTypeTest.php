<?php

namespace OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type;

use Symfony\Component\Form\ChoiceList\View\ChoiceView;
use Symfony\Component\Form\FormView;
use Symfony\Component\Form\PreloadedExtension;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Testing\Unit\Form\Type\Stub\EntityType;
use Oro\Component\Testing\Unit\FormIntegrationTestCase;

use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Form\Type\ProductUnitSelectionType;
use OroB2B\Bundle\ProductBundle\Formatter\ProductUnitLabelFormatter;
use OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface;
use OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitHolderType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitRemovedSelectionType;
use OroB2B\Bundle\ProductBundle\Tests\Unit\Form\Type\Stub\StubProductUnitSelectionType;

class ProductUnitRemovedSelectionTypeTest extends FormIntegrationTestCase
{
    /**
     * @var ProductUnitRemovedSelectionType
     */
    protected $formType;

    /**
     * @var \PHPUnit_Framework_MockObject_MockObject|TranslatorInterface
     */
    protected $translator;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->translator = $this->getMock('Symfony\Component\Translation\TranslatorInterface');

        $this->translator
            ->expects(static::any())
            ->method('trans')
            ->willReturnCallback(function ($id, array $params) {
                return isset($params['{title}']) ? $id . ':' . $params['{title}'] : $id;
            })
        ;
        $productUnitLabelFormatter = new ProductUnitLabelFormatter($this->translator);
        $this->formType = new ProductUnitRemovedSelectionType($productUnitLabelFormatter, $this->translator);
        $this->formType->setEntityClass('OroB2B\Bundle\ProductBundle\Entity\ProductUnit');

        parent::setUp();
    }

    public function testGetName()
    {
        static::assertEquals(ProductUnitRemovedSelectionType::NAME, $this->formType->getName());
    }

    public function testGetParent()
    {
        static::assertEquals(ProductUnitSelectionType::NAME, $this->formType->getParent());
    }

    public function testConfigureOptions()
    {
        /** @var \PHPUnit_Framework_MockObject_MockObject|OptionsResolver $resolver */
        $resolver = $this->getMock('Symfony\Component\OptionsResolver\OptionsResolver');
        $resolver->expects(static::once())
            ->method('setDefaults')
            ->with(static::isType('array'))
            ->willReturnCallback(
                function (array $options) {
                    static::assertEquals(
                        [
                            'class' => 'OroB2B\Bundle\ProductBundle\Entity\ProductUnit',
                            'property' => 'code',
                            'compact' => false,
                            'required' => true,
                            'empty_label' => 'orob2b.product.productunit.removed',
                        ],
                        $options
                    );
                }
            );

        $this->formType->configureOptions($resolver);
    }

    /**
     * @param array $inputData
     * @param array $expectedData
     * @param boolean $withParent
     *
     * @dataProvider finishViewProvider
     */
    public function testFinishView(array $inputData = [], array $expectedData = [], $withParent = true)
    {
        $form = $this->factory->create($this->formType, null, $inputData['options']);

        if ($withParent) {
            $formParent = $this->factory->create(new StubProductUnitHolderType(), $inputData['productUnitHolder']);
        } else {
            $formParent = null;
        }

        $form->setParent($formParent);

        $view = new FormView();
        $this->formType->finishView($view, $form, $form->getConfig()->getOptions());

        if (isset($view->vars['choices'])) {
            $choices = [];
            /* @var $choice ChoiceView */
            foreach ($view->vars['choices'] as $choice) {
                $choices[$choice->value] = $choice->label;
            }
            $view->vars['choices'] = $choices;
        }

        foreach ($expectedData as $field => $value) {
            if (!isset($view->vars[$field])) {
                $view->vars[$field] = null;
            }
            static::assertEquals($value, $view->vars[$field]);
        }
    }

    /**
     * @return array
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function finishViewProvider()
    {
        $precision = new ProductUnitPrecision();
        $unit = new ProductUnit();
        $unit->setCode('code');
        $precision->setUnit($unit);

        return [
            'empty item' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => null,
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => null,
                ],
            ],
            'without parent form' => [
                'inputData' => [
                    'options' => [],
                    'productHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder(1, 'sku', null)
                    ),
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => null
                ],
                false
            ],
            'filled item' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder(1, 'sku', null)
                    ),
                ],
                'expectedData' => [
                    'empty_value' => null,
                    'choices' => [],
                ],
            ],
            'existing product' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder(1, 'sku', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'empty_value' => 'orob2b.product.productunit.removed:sku',
                    'choices' => [
                        'code' => 'orob2b.product_unit.code.label.full'
                    ],
                ],
            ],
            'existing product and compact mode' => [
                'inputData' => [
                    'options' => [
                        'compact' => true,
                    ],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        new ProductUnit(),
                        $this->createProductHolder(1, 'sku', (new Product())->addUnitPrecision($precision))
                    ),
                ],
                'expectedData' => [
                    'empty_value' => 'orob2b.product.productunit.removed:sku',
                    'choices' => [
                        'code' => 'orob2b.product_unit.code.label.short'
                    ],
                ],
            ],
            'deleted product' => [
                'inputData' => [
                    'options' => [],
                    'productUnitHolder' => $this->createProductUnitHolder(
                        1,
                        'sku',
                        null,
                        $this->createProductHolder(1, 'sku', null)
                    ),
                ],
                'expectedData' => [
                    'empty_value' => 'orob2b.product.productunit.removed:sku',
                    'choices' => [],
                ],
            ],
        ];
    }

    /**
     * @param int $id
     * @param $productUnitCode
     * @param ProductUnit $productUnit
     * @param ProductHolderInterface $productHolder
     * @return ProductUnitHolderInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected function createProductUnitHolder(
        $id,
        $productUnitCode,
        ProductUnit $productUnit = null,
        ProductHolderInterface $productHolder = null
    ) {
        /* @var $productUmitHolder \PHPUnit_Framework_MockObject_MockObject|ProductUnitHolderInterface */
        $productUnitHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductUnitHolderInterface');
        $productUnitHolder
            ->expects(static::any())
            ->method('getEntityIdentifier')
            ->willReturn($id);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnit')
            ->willReturn($productUnit);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductUnitCode')
            ->willReturn($productUnitCode);
        $productUnitHolder
            ->expects(static::any())
            ->method('getProductHolder')
            ->willReturn($productHolder);

        return $productUnitHolder;
    }

    /**
      * @param int $id
      * @param string $productSku
      * @param Product $product
      * @return \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface
      */
    protected function createProductHolder($id, $productSku, Product $product = null)
    {
        /* @var $productHolder \PHPUnit_Framework_MockObject_MockObject|ProductHolderInterface */
        $productHolder = $this->getMock('OroB2B\Bundle\ProductBundle\Model\ProductHolderInterface');
        $productHolder
            ->expects(static::any())
            ->method('getId')
            ->willReturn($id)
        ;
        $productHolder
            ->expects(static::any())
            ->method('getProduct')
            ->willReturn($product)
        ;
        $productHolder
            ->expects(static::any())
            ->method('getProductSku')
            ->willReturn($productSku)
        ;

        return $productHolder;
    }

    /**
     * {@inheritdoc}
     */
    protected function getExtensions()
    {
        $productUnitSelectionType = new StubProductUnitSelectionType([1], ProductUnitSelectionType::NAME);
        $productUnitRemovedSelectionType = new StubProductUnitRemovedSelectionType();
        $entityType = new EntityType(['1']);

        return [
            new PreloadedExtension(
                [
                    $productUnitSelectionType->getName() => $productUnitSelectionType,
                    $productUnitRemovedSelectionType->getName() => $productUnitRemovedSelectionType,
                    $entityType->getName() => $entityType,
                ],
                []
            ),
            $this->getValidatorExtension(true),
        ];
    }
}
