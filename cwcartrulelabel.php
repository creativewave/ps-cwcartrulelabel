<?php

require_once _PS_ROOT_DIR_.'/vendor/autoload.php';

class CWCartRuleLabel extends Module
{
    /**
     * Registered hooks.
     *
     * @var array
     */
    const HOOKS = [
        'actionAdminModulesOptionsModifier',
        'displayHeader',
        'displayProductPriceBlock',
    ];

    /**
     * Options fields.
     *
     * @var array
     */
    const OPTIONS = [
        'ORIGIN' => [
            'type'       => 'radio',
            'title'      => 'Cart rule label', /* ->l('Cart rule label') */
            'choices'    => [
                'Use cart rule name',        /* ->l('Use cart rule name') */
                'Use cart rule description', /* ->l('Use cart rule description') */
            ],
            'default'    => true,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
        'POSITION' => [
            'type'       => 'select',
            'title'      => 'Position of the cart rule label', /* ->l('Position of the cart rule label') */
            'list'       => [
                ['before_price', 'name' => 'Before product price'],        /* ->l('Before product price') */
                ['price',        'name' => 'Before unit price'],           /* ->l('Before unit price') */
                ['unit_price',   'name' => 'After unit price'],            /* ->l('After unit price') */
                ['weight',       'name' => 'After product delivery time'], /* ->l('After product delivery time') */
            ],
            'identifier' => 0,
            'default'    => 'unit_price',
            'validation' => 'isConfigName',
            'cast'       => 'strval',
        ],
        'ONLY_ACTIVE' => [
            'type'       => 'bool',
            'title'      => 'Only display active cart rules', /* ->l('Only display active cart rules') */
            'default'    => true,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
        'INCLUDE_GENERIC' => [
            'type'       => 'bool',
            'title'      => 'Display generic cart rules', /* ->l('Display generic cart rules') */
            'default'    => true,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
        'ONLY_IN_STOCK' => [
            'type'       => 'bool',
            'title'      => 'Only display cart rules in stock', /* ->l('Only display cart rules in stock') */
            'default'    => true,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
        'ONLY_FREE_SHIPPING' => [
            'type'       => 'bool',
            'title'      => 'Only display cart rules with free shipping', /* ->l('Only display cart rules with free shipping') */
            'default'    => false,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
        'ONLY_HIGHLIGHTED' => [
            'type'       => 'bool',
            'title'      => 'Only display highlighted cart rules', /* ->l('Only display highlighted cart rules') */
            'default'    => true,
            'validation' => 'isBool',
            'cast'       => 'intval',
        ],
    ];

    /**
     * @see ModuleCore
     */
    public $name    = 'cwcartrulelabel';
    public $tab     = 'pricing_promotion';
    public $version = '1.0.0';
    public $author  = 'Creative Wave';
    public $need_instance = 0;
    public $bootstrap     = true;
    public $ps_versions_compliancy = [
        'min' => '1.6',
        'max' => '1.6.99.99',
    ];

    /**
     * Initialize module.
     */
    public function __construct()
    {
        parent::__construct();

        $this->displayName      = $this->l('Cart Rule Label');
        $this->description      = $this->l('Display cart rules labels in categories.');
        $this->confirmUninstall = $this->l('Are you sure you want to uninstall?');
    }

    /**
     * Install module.
     */
    public function install(): bool
    {
        return parent::install()
               and $this->addHooks(static::HOOKS)
               and $this->getConfiguration()->setOptionsDefaultValues(array_keys(static::OPTIONS));
    }

    /**
     * Uninstall module.
     */
    public function uninstall(): bool
    {
        $this->_clearCache('*');
        $this->getConfiguration()->removeOptionsValues(array_keys(static::OPTIONS));

        return parent::uninstall();
    }

    /**
     * @see \CW\Module\Configuration::getContent()
     */
    public function getContent(): string
    {
        return $this->getConfiguration()->getContent();
    }

    /**
     * @see \CW\Module\Configuration::hookActionAdminModulesOptionsModifier()
     */
    public function hookActionAdminModulesOptionsModifier(array $params)
    {
        $this->getConfiguration()->hookActionAdminModulesOptionsModifier($params);
    }

    /**
     * Add CSS on category and product pages.
     */
    public function hookDisplayHeader(array $params): string
    {
        if (!($this->isPagePublicCategory() or $this->isPagePublicProduct())) {
            return '';
        }
        $this->context->controller->addCSS(__DIR__."/css/$this->name.css");

        return '';
    }

    /**
     * Display product cart rules labels in product price block.
     */
    public function hookDisplayProductPriceBlock(array $params): string
    {
        if (!$this->shouldDisplayLabels($params['type'])) {
            return '';
        }
        // Product is fetched as an object in product page.
        if (is_object($params['product'])) {
            $params['product'] = (array) $params['product'];
            $params['product']['id_product'] = $params['product']['id'];
        }
        $template_name = 'product-price-block.tpl';
        $id_cache = $this->getCacheId().'|'.$params['product']['id_product'];
        if (!$this->isCached($template_name, $id_cache)) {
            $this->setTemplateVars([
                'labels' => $this->getProductCartRulesLabels($params['product']),
            ]);
        }

        return $this->display(__FILE__, $template_name, $id_cache);
    }

    /**
     * Add hooks.
     */
    protected function addHooks(array $hooks): bool
    {
        return array_product(array_map([$this, 'registerHook'], $hooks));
    }

    /**
     * Get module configuration.
     */
    protected function getConfiguration(): CW\Module\Configuration
    {
        static $instance;

        return $instance ?? $instance = new CW\Module\Configuration($this);
    }

    /**
     * Get context language ID.
     */
    protected function getContextLanguageId(): int
    {
        return $this->context->language->id;
    }

    /**
     * Get context shop ID.
     */
    protected function getContextShopId(): int
    {
        return $this->context->shop->id;
    }

    /**
     * Get public controller name.
     */
    protected function getControllerPublicName(): string
    {
        return Dispatcher::getInstance()->getController();
    }

    /**
     * Get product cart rules labels.
     */
    protected function getProductCartRulesLabels(array $product): array
    {
        return array_column(
            $this->getProductCartRules($product),
            $this->getConfiguration()->getOptionValue('ORIGIN') ? 'description' : 'name'
        );
    }

    /**
     * Get product cart rules.
     */
    protected function getProductCartRules(array $product): array
    {
        return array_filter(
            $this->getCustomerCartRulesWithProductSelection(),
            $this->makeIsCartRuleMatchingWithProduct($product)
        );
    }

    /**
     * Get customer cart rules with a product selection.
     */
    protected function getCustomerCartRulesWithProductSelection(): array
    {
        return array_filter($this->getCustomerCartRules(), [$this, 'hasProductSelection']);
    }

    /**
     * Get customer cart rules.
     */
    protected function getCustomerCartRules(): array
    {
        static $customer_cart_rules = null;

        if (null === $customer_cart_rules) {
            $customer_cart_rules = CartRule::getCustomerCartRules(
                    $this->context->language->id,
                    $this->context->customer->id,
                    $this->getConfiguration()->getOptionValue('only_active'),
                    $this->getConfiguration()->getOptionValue('include_generic'),
                    $this->getConfiguration()->getOptionValue('only_in_stock'),
                    null, /* = $cart*/
                    $this->getConfiguration()->getOptionValue('only_free_shipping'),
                    $this->getConfiguration()->getOptionValue('only_highlighted')
            );
        }

        return $customer_cart_rules;
    }

    /**
     * Make a callback matching a cart rule against a product.
     *
     * @todo Handle attributes, manufacturers, and suppliers selection types.
     */
    protected function makeIsCartRuleMatchingWithProduct(array $product): callable
    {
        return function (array $cart_rule) use ($product) {
            $product_selection = $this->getProductSelection($cart_rule['id_cart_rule']);
            foreach ($product_selection as $selection) {
                foreach ($selection['product_rules'] as $rule) {
                    foreach ($rule['values'] as $value) {
                        if ('products' === $rule['type'] and $value === $product['id_product']) {
                            return true;
                        }
                        if ('categories' === $rule['type'] and $this->isPagePublicCategoryId($value)) {
                            return true;
                        }
                        // todo
                    }
                }
            }

            return false;
        };
    }

    /**
     * Get product selection.
     */
    protected function getProductSelection(int $id_cart_rule): array
    {
        return (new CartRule($id_cart_rule))->getProductRuleGroups();
    }

    /**
     * Get value from $_GET/$_POST.
     */
    protected function getValue(string $key, string $default = ''): string
    {
        return Tools::getValue($key, $default);
    }

    /**
     * Wether or not a rule has a product selection.
     */
    protected function hasProductSelection(array $rule): bool
    {
        return isset($rule['product_restriction']);
    }

    /**
     * Wether or not public category page is currently loading.
     */
    protected function isPagePublicCategory(): bool
    {
        return 'category' === $this->getControllerPublicName();
    }

    /**
     * Wether or not public category page ID is currently loading.
     */
    protected function isPagePublicCategoryId(int $id_category): bool
    {
        return $this->isPagePublicCategory()
               and (
                   $id_category === $this->getValue('id_category')
                   or $this->isSubCategoryOf($id_category, $this->getValue('id_category'))
               );
    }

    /**
     * Wether or not public product page is currently loading.
     */
    protected function isPagePublicProduct(): bool
    {
        return 'product' === $this->getControllerPublicName();
    }

    /**
     * Wether or not a category is a subcategory of another category.
     */
    protected function isSubCategoryOf(int $id_parent, int $id_child): bool
    {
        $children = Category::getChildren($id_parent, $this->getContextLanguageId(), true, $this->getContextShopId());
        $ids_categories = array_column($children, 'id_category');
        if (in_array($id_child, $ids_categories)) {
            return true;
        }
        foreach ($ids_categories as $id_category) {
            return Category::hasChildren($id_category, $this->getContextLanguageId(), true, $this->getContextShopId())
                   and $this->isSubCategoryOf($id_category, $id_child);
        }

        return false;
    }

    /**
     * Set template variables.
     */
    protected function setTemplateVars(array $vars): Smarty_Internal_Data
    {
        return $this->smarty->assign($vars);
    }

    /**
     * Wether or not labels should be displayed.
     */
    protected function shouldDisplayLabels(string $position): bool
    {
        if ('after_price' === $position and $this->isPagePublicProduct()) {
            return true;
        }

        return $this->isPagePublicCategory()
               and $position === $this->getConfiguration()->getOptionValue('position');
    }
}
