<?php
declare(strict_types=1);

namespace Pixelant\PxaProductManager\Controller;

use Pixelant\PxaProductManager\Domain\Model\DTO\CategoryDemand;
use Pixelant\PxaProductManager\Domain\Model\DTO\DemandInterface;
use Pixelant\PxaProductManager\Domain\Model\DTO\ProductDemand;
use TYPO3\CMS\Core\Utility\ArrayUtility;
use TYPO3\CMS\Core\Utility\Exception\MissingArrayPathException;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use TYPO3\CMS\Extbase\SignalSlot\Dispatcher;

/**
 * @package Pixelant\PxaProductManager\Controller
 */
abstract class AbstractController extends ActionController
{
    /**
     * @var Dispatcher
     */
    protected Dispatcher $dispatcher;

    /**
     * @param Dispatcher $dispatcher
     */
    public function injectDispatcher(Dispatcher $dispatcher)
    {
        $this->dispatcher = $dispatcher;
    }

    /**
     * Create demand object using settings
     *
     * @param array $settings
     * @param string $className
     * @return DemandInterface
     */
    protected function createDemandFromSettings(array $settings, string $className): DemandInterface
    {
        /** @var DemandInterface $demand */
        $demand = GeneralUtility::makeInstance($className);

        if (! empty($settings['limit'])) {
            $demand->setLimit((int)$settings['limit']);
        }
        if (! empty($settings['offSet'])) {
            $demand->setOffSet((int)$settings['offSet']);
        }
        if (! empty($settings['demand']['orderByAllowed'])) {
            $demand->setOrderByAllowed($settings['demand']['orderByAllowed']);
        }
        if (! empty($settings['orderBy'])) {
            $demand->setOrderBy($settings['orderBy']);
        }
        if (! empty($settings['orderDirection'])) {
            $demand->setOrderDirection($settings['orderDirection']);
        }

        $this->dispatcher->dispatch(__CLASS__, 'AfterDemandCreationBeforeReturn', [$demand, $settings]);

        return $demand;
    }

    /**
     * Create categories demand
     *
     * @param array $settings
     * @param string $className
     * @return DemandInterface
     */
    protected function createCategoriesDemand(
        array $settings,
        string $className = CategoryDemand::class
    ): DemandInterface {
        $settings = array_merge($settings, $this->settings['categoriesOrderings'] ?? []);
        $className = $this->readFromSettings('demand.objects.categoryDemand', $className);

        $demand = $this->createDemandFromSettings($settings, $className);
        if (! empty($settings['navigation']['hideCategoriesWithoutProducts'])) {
            $demand->setHideCategoriesWithoutProducts((bool)$settings['navigation']['hideCategoriesWithoutProducts']);
        }
        if (! empty($settings['onlyVisibleInNavigation'])) {
            $demand->setOnlyVisibleInNavigation((bool)$settings['onlyVisibleInNavigation']);
        }
        if (! empty($settings['parent'])) {
            $demand->setParent($settings['parent']);
        }

        return $demand;
    }

    /**
     * Create products demand
     *
     * @param array $settings
     * @param string $className
     * @return DemandInterface
     */
    protected function createProductsDemand(
        array $settings,
        string $className = ProductDemand::class
    ): DemandInterface {
        $settings = array_merge($settings, $this->settings['productOrderings'] ?? []);
        $className = $this->readFromSettings('demand.objects.productDemand', $className);

        $demand = $this->createDemandFromSettings($settings, $className);
        if (! empty($settings['categories'])) {
            $demand->setCategories($settings['categories']);
        }
        if (! empty($settings['categoryConjunction'])) {
            $demand->setCategoryConjunction($settings['categoryConjunction']);
        }

        return $demand;
    }

    /**
     * Read from settings by path or return default value
     *
     * @param string $path
     * @param mixed $default
     * @return mixed
     */
    protected function readFromSettings(string $path, $default = null)
    {
        try {
            $value = ArrayUtility::getValueByPath($this->settings, $path, '.');
        } catch (MissingArrayPathException $exception) {
            $value = null;
        }

        return $value ?: $default;
    }
}
