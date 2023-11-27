<?php

if (!defined('_PS_VERSION_')) {
    exit;
}

class arpa3voucher extends Module
{
    protected $config_form = false;

    public $hook_lockers = array();

    public function __construct()
    {
        $this->name = 'arpa3voucher';
        $this->tab = 'administration';
        $this->version = '1.0.2';
        $this->author = 'Arpa3';

        /**
         * Set $this->bootstrap to true if your module is compliant with bootstrap (PrestaShop 1.6)
         */
        $this->bootstrap = true;

        parent::__construct();

        $this->displayName = $this->l('arpa3voucher');
        $this->description = $this->l('Promotions paniers Arpa3');
        $this->confirmUninstall = $this->l('Confirmer supression module ?');
        $this->ps_versions_compliancy = array('min' => '1.7', 'max' => _PS_VERSION_);

        $this->registerHook('actionFrontControllerAfterInit');
    }

    /**
     * Don't forget to create update methods if needed:
     * http://doc.prestashop.com/display/PS16/Enabling+the+Auto-Update
     */
    public function install()
    {
        include(dirname(__FILE__) . '/sql/install.php');
        return parent::install() &&
            $this->registerHook('header') &&
            $this->registerHook('backOfficeHeader') &&
            $this->registerHook('actionCartSave') &&
            $this->registerHook('actionValidateOrder') &&
            $this->registerHook('actionFrontControllerAfterInit');
    }

    public function uninstall()
    {
        include(dirname(__FILE__) . '/sql/install.php');
        return parent::uninstall();
    }

    /**
     * Load the configuration form
     */
    public function getContent()
    {
        $this->postProcess();

        $voucherList = Db::getInstance()->executeS('SELECT * FROM `' . _DB_PREFIX_ . 'arpa3voucher`');

        if ($voucherList) {
            $voucherList = array_map(function ($voucher) {
                $myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $voucher['date_from']);
                $newDateString = $myDateTime->format('Y-m-d');
                $voucher['date_from'] = $newDateString;
                $myDateTime = DateTime::createFromFormat('Y-m-d H:i:s', $voucher['date_to']);
                $newDateString = $myDateTime->format('Y-m-d');
                $voucher['date_to'] = $newDateString;
                return $voucher;
            }, $voucherList);
        }

        $this->postProcess();

        $this->context->smarty->assign('voucherList', $voucherList);
        $this->context->smarty->assign('productlist', $this->productlist());
        $this->context->smarty->assign('module_dir', $this->_path);

        $output = $this->context->smarty->fetch($this->local_path . 'views/templates/admin/configure.tpl');

        return $output;
    }

    /**
     * Create the form that will be displayed in the configuration of your module.
     */
    protected function renderForm()
    {
        $helper = new HelperForm();

        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $helper->module = $this;
        $helper->default_form_language = $this->context->language->id;
        $helper->allow_employee_form_lang = Configuration::get('PS_BO_ALLOW_EMPLOYEE_FORM_LANG', 0);

        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submitarpa3voucherModule';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false)
            . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');

        $helper->tpl_vars = array(
            'fields_value' => $this->getConfigFormValues(), /* Add values for your inputs */
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm(array($this->getConfigForm()));
    }

    /**
     * Create the structure of your form.
     */
    protected function getConfigForm()
    {
        return array(
            'form' => array(
                'legend' => array(
                    'title' => $this->l('Settings'),
                    'icon' => 'icon-cogs',
                ),
                'input' => array(
                    array(
                        'type' => 'switch',
                        'label' => $this->l('Live mode'),
                        'name' => 'ARPA3VOUCHER_LIVE_MODE',
                        'is_bool' => true,
                        'desc' => $this->l('Use this module in live mode'),
                        'values' => array(
                            array(
                                'id' => 'active_on',
                                'value' => true,
                                'label' => $this->l('Enabled')
                            ),
                            array(
                                'id' => 'active_off',
                                'value' => false,
                                'label' => $this->l('Disabled')
                            )
                        ),
                    ),
                    array(
                        'col' => 3,
                        'type' => 'text',
                        'prefix' => '<i class="icon icon-envelope"></i>',
                        'desc' => $this->l('Enter a valid email address'),
                        'name' => 'ARPA3VOUCHER_ACCOUNT_EMAIL',
                        'label' => $this->l('Email'),
                    ),
                    array(
                        'type' => 'password',
                        'name' => 'ARPA3VOUCHER_ACCOUNT_PASSWORD',
                        'label' => $this->l('Password'),
                    ),
                ),
                'submit' => array(
                    'title' => $this->l('Save'),
                ),
            ),
        );
    }

    /**
     * Set values for the inputs.
     */
    protected function getConfigFormValues()
    {
        return array(
            'ARPA3VOUCHER_LIVE_MODE' => Configuration::get('ARPA3VOUCHER_LIVE_MODE', true),
            'ARPA3VOUCHER_ACCOUNT_EMAIL' => Configuration::get('ARPA3VOUCHER_ACCOUNT_EMAIL', 'contact@prestashop.com'),
            'ARPA3VOUCHER_ACCOUNT_PASSWORD' => Configuration::get('ARPA3VOUCHER_ACCOUNT_PASSWORD', null),
        );
    }

    /**
     * Save form data.
     */
    protected function postProcess()
    {
        if (Tools::isSubmit('create')) {
            $product_id = (int)Tools::getValue('chooseproduct');
            $product_gift_id = (int)Tools::getValue('choose-product-gift');
            $id_duplicate = $this->duplicateproduct($product_gift_id);
            $datefrom = Tools::getValue('date_from') . ' 00:00:00';
            $dateto = Tools::getValue('date_to') . ' 00:00:00';
            $taux = (int)Tools::getValue('taux');
            $product_gift_quantity = (int)Tools::getValue('product-gift-quantity');

            $res = Db::getInstance()->insert($this->name, [
                'taux' => $taux,
                'id_product_cible' => $product_id,
                'id_product_gift_virtual' => $id_duplicate,
                'id_product_gift_real' => $product_gift_id,
                'product_gift_quantity' => $product_gift_quantity,
                'date_from' => $datefrom,
                'date_to' => $dateto,
            ]);

            // Rafraichissement de la page
            Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL);
        }

        if (Tools::isSubmit('update')) {
            $rowid = (int)Tools::getValue('id_arpa3voucher');
            $taux = (int)Tools::getValue('taux');
            $product_gift_quantity = (int)Tools::getValue('product-gift-quantity');

            $datefrom = Tools::getValue('date_from');
            $dateto = Tools::getValue('date_to');
            $columns = [
                'taux' => $taux,
                'product_gift_quantity' => $product_gift_quantity,
                'date_from' => $datefrom,
                'date_to' => $dateto,
            ];

            Db::getInstance()->update($this->name, $columns, 'id = ' . $rowid);
            //Rafraichissement de la page
            Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL);
        }
        if (Tools::isSubmit('delete')) {
            $rowid = (int)Tools::getValue('id_arpa3voucher');
            $giftproduct = (int)Tools::getValue('giftid');
            //Suppréssion du produit cadeau
            $delete = new Product($giftproduct);
            $delete->delete();
            //Suppréssion de la promotion
            Db::getInstance()->executeS('DELETE FROM `' . _DB_PREFIX_ . 'arpa3voucher` where id=' . $rowid);
            //Rafraichissement de la page
            Tools::redirect(isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : NULL);
        }
    }

    /**
     * Add the CSS & JavaScript files you want to be loaded in the BO.
     */
    public function hookBackOfficeHeader()
    {
        $this->context->controller->addJS($this->_path . 'views/js/back.js');
        $this->context->controller->addCSS($this->_path . 'views/css/back.css');
    }

    /**
     * Add the CSS & JavaScript files you want to be added on the FO.
     */
    public function hookHeader()
    {
        $this->context->controller->addJS($this->_path . '/views/js/front.js');
        $this->context->controller->addCSS($this->_path . '/views/css/front.css');
    }

    public function hookActionCartSave($params)
    {
        if (!$this->active) {
            return false;
        }
        $cart = $params['cart'];

        Context::getContext()->cart = $cart;
        Context::getContext()->currency = new Currency((int)$cart->id_currency);

        if (Context::getContext()->cart->id) {

            $configutation_promos = $this->getArpa3vouchers();

            // get fresh cart products [id_product => quantity]

            $cart_products = array_column($cart->getProducts(true, false), 'quantity', 'id_product');

            foreach ($configutation_promos as $promo_name => $configutation_promo) {
                // Verification que le produit offert existe bien
                $checkoffert = ProductCore::getProductName($configutation_promo['id_product_gift_virtual']);
                if ($checkoffert != false) {
                    $cart_need_gift = in_array($configutation_promo['id_product_cible'], array_keys($cart_products));
                    if ($cart_need_gift) {
                        $now = date('Y-m-d H:i:s');
                        if ($now >= $configutation_promo['date_from']) {
                            // ajout du produit offert
                            if (!in_array('add_' . $promo_name, $this->hook_lockers)) { //check verrou
                                array_push($this->hook_lockers, 'add_' . $promo_name); // ajout verrou

                                if(isset($cart_products[$configutation_promo['id_product_gift_virtual']])) {
                                    // retire le produit offert
                                    $cart->updateQty(
                                        $cart_products[$configutation_promo['id_product_gift_virtual']],
                                        $configutation_promo['id_product_gift_virtual'],
                                        null,
                                        false,
                                        'down'
                                    );
                                }

                                if ($now <= $configutation_promo['date_to']) {

                                    // calcul quantité à ajouter en produit offert
                                    $quantite_produit_cible = $cart_products[$configutation_promo['id_product_cible']];

                                    $cangift = StockAvailable::getQuantityAvailableByProduct($configutation_promo['id_product_gift_real']);
                                    $cancible = StockAvailable::getQuantityAvailableByProduct($configutation_promo['id_product_cible']);

                                    // nombre de promo sur le produit
                                    $quantite_promo = intval($quantite_produit_cible / $configutation_promo['taux']);
                                    // quantité à ajouter au panier
                                    $quantite_produit_offert = $quantite_promo * intval($configutation_promo['product_gift_quantity']);

                                    /*
                                if ($cangift <= $quantite_produit_offert) {
                                    //$quantite_produit_offert = $cangift;
                                    $this->context->controller->errors[] = 'Quantité max disponible atteinte pour le produit : ' . $checkoffert;
                                }

                                if($cancible <= $quantite_produit_offert + $quantite_produit_cible){
                                    $this->context->controller->errors[] = 'Quantité max disponible atteinte pour le produit : '.ProductCore::getProductName($configutation_promo['id_product_cible']);
                                }
                                */

                                    $cart->updateQty(
                                        $quantite_produit_offert,
                                        $configutation_promo['id_product_gift_virtual'],
                                        null,
                                        false,
                                        'up',
                                        0,
                                        null,
                                        false
                                    );
                                }
                            }
                        }
                    } else {
                        $need_delete = in_array($configutation_promo['id_product_gift_virtual'], array_keys($cart_products));
                        if ($need_delete) {
                            $cart->deleteProduct($configutation_promo['id_product_gift_virtual']);
                            $cart->update();
                        }
                    }
                }
            }
        }
    }

    /**
     * Appel du hook du module au chargement de la page panier / checkout
     */
    public function hookActionFrontControllerAfterInit()
    {
        if (in_array(Context::getContext()->controller->php_self, ['cart', 'order'])) {
            $params['cart'] = Context::getContext()->cart;
            $this->hookActionCartSave($params);
        }
    }

    public function hookActionValidateOrder($params)
    {
        $order = $params['order'];
        $cartorders = [];
        foreach ($order->product_list as $list) {
            $cartorders[$list["id_product"]] = $list["quantity"];
        }
        $configutation_promos = $this->getArpa3vouchers();

        foreach ($configutation_promos as $promo_name => $configutation_promo) {

            if (array_key_exists($configutation_promo['id_product_gift_virtual'], $cartorders)) {
                $cible = $cartorders[$configutation_promo['id_product_cible']];
                $offert = $cartorders[$configutation_promo['id_product_gift_virtual']];
                // On décrémente le stock des offert sur le produit cible.
                StockAvailable::updateQuantity($configutation_promo['id_product_cible'], 0, -(int)$offert);
            }
        }
    }

    public function productlist()
    {
        $id_lang = (int)Context::getContext()->language->id;
        $start = 0;
        $limit = 10000;
        $order_by = 'id_product';
        $order_way = 'ASC';
        $id_category = false;
        $only_active = true;
        $context = null;
        $array = [];
        $productslist = ProductCore::getProducts($id_lang, $start, $limit, $order_by, $order_way, $id_category, $only_active, $context);
        foreach ($productslist as $product) {
            if ($product["visibility"] !== "none") {
                $array[(int)$product["id_product"]] = $product["name"];
            }
        }

        return $array;
    }

    public function getArpa3vouchers()
    {
        $now = date("Y-m-d");
        $list = new DbQuery();
        $list->select('*');
        $list->from($this->name);

        return Db::getInstance()->executeS($list);
    }

    public function duplicateproduct($product_id)
    {
        if (Validate::isLoadedObject($product = new Product((int)$product_id))) {
            $id_product_old = $product->id;
            if (empty($product->price) && Shop::getContext() == Shop::CONTEXT_GROUP) {
                $shops = ShopGroup::getShopsFromGroup(Shop::getContextShopGroupID());
                foreach ($shops as $shop) {
                    if ($product->isAssociatedToShop($shop['id_shop'])) {
                        $product_price = new Product($id_product_old, false, null, $shop['id_shop']);
                        $product->price = $product_price->price;
                    }
                }
            }
            unset(
                $product->id,
                $product->id_product
            );
            $languages = Language::getLanguages();

            foreach ($languages as $lang) {
                $product->name[$lang['id_lang']] = $product->name[$lang['id_lang']] . " ( OFFERT )";
            }

            $product->indexed = 0;
            $product->active = 1;
            $product->price = 0;
            $product->visibility = "none";
            if (
                $product->add()
                && Category::duplicateProductCategories($id_product_old, $product->id)
                && Product::duplicateSuppliers($id_product_old, $product->id)
                && ($combination_images = Product::duplicateAttributes($id_product_old, $product->id)) !== false
                && GroupReduction::duplicateReduction($id_product_old, $product->id)
                && Product::duplicateAccessories($id_product_old, $product->id)
                && Product::duplicateFeatures($id_product_old, $product->id)
                && Product::duplicateSpecificPrices($id_product_old, $product->id)
                && Pack::duplicate($id_product_old, $product->id)
                && Product::duplicateCustomizationFields($id_product_old, $product->id)
                && Product::duplicateTags($id_product_old, $product->id)
                && Product::duplicateDownload($id_product_old, $product->id)
            ) {
                if ($product->hasAttributes()) {
                    Product::updateDefaultAttribute($product->id);
                }
                // Mise à jour des stocks a 100 000
                StockAvailable::setQuantity((int)$product->id, 0, 100000, (int)Configuration::get('PS_SHOP_DEFAULT'));
                // Mise à jour du nom produit

                if (!Tools::getValue('noimage') && !Image::duplicateProductImages($id_product_old, $product->id, $combination_images)) {
                    //$this->errors[] = $this->trans('An error occurred while copying the image.', array(), 'Admin.Notifications.Error');
                } else {
                    Hook::exec('actionProductAdd', array('id_product_old' => $id_product_old, 'id_product' => (int)$product->id, 'product' => $product));
                    if (in_array($product->visibility, array('both', 'search')) && Configuration::get('PS_SEARCH_INDEXATION')) {
                        Search::indexation(false, $product->id);
                    }
                    //$this->redirect_after = self::$currentIndex . (Tools::getIsset('id_category') ? '&id_category=' . (int) Tools::getValue('id_category') : '') . '&conf=19&token=' . $this->token;
                }
            } else {
                //$this->errors[] = $this->trans('An error occurred while creating an object.', array(), 'Admin.Notifications.Error');
            }
        }
        return $product->id;
    }
}
