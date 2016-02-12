<?php

if (!defined('_VALID_MOS') && !defined('_JEXEC'))
    die('Direct Access to ' . basename(__FILE__) . ' is not allowed.');

/**
 * @version $Id: /pagseguro.php,v 1.4 2013/05/20 luizwbri
 *
 * a special type of 'cash on delivey': * 
 * @author Luiz F. Weber, Fábio Paiva
 * @co-author Max Milbers, Valérie Isaksen ( original plugin )
 * @version $Id: /home/components/com_virtuemart 5122 2011-12-18 22:24:49Z alatak $
 * @package VirtueMart
 * @subpackage payment
 * @copyright Copyright (C) 2004-2008 soeren - All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * See /administrator/components/com_virtuemart/COPYRIGHT.php for copyright notices and details.
 *
 * http://virtuemart.net
 */
 
if (!class_exists('vmPSPlugin'))
    require(JPATH_VM_PLUGINS . DS . 'vmpsplugin.php');

if (!class_exists('shopFunctions'))
    require(JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'shopfunctions.php');

if (!class_exists('PagSeguroLibrary')) {
    require('PagSeguroLibrary/PagSeguroLibrary.php');
}

class plgVmPaymentPagseguro_virtuemartbrasil extends vmPSPlugin {

    // instance of class
    public static $_this = false;

    function __construct(& $subject, $config) {
        //if (self::$_this)
        //   return self::$_this;
        parent::__construct($subject, $config);

        $this->_loggable = true;
        $this->tableFields = array_keys($this->getTableSQLFields());
        $varsToPush = $this->getVarsToPush ();
        $this->setConfigParameterable($this->_configTableFieldName, $varsToPush);
        // self::$_this = $this;
    }
    /**
     * Create the table for this plugin if it does not yet exist.
     * @author Valérie Isaksen
     */
    protected function getVmPluginCreateTableSQL() {
        return $this->createTableSQL('Payment Pagsegurobrasil Table');
    }

    /**
     * Fields to create the payment table
     * @return string SQL Fileds
     */
    function getTableSQLFields() {
        $SQLfields = array(
            'id' => 'bigint(15) unsigned NOT NULL AUTO_INCREMENT',
            'virtuemart_order_id' => 'int(11) UNSIGNED DEFAULT NULL',
            'order_number' => 'char(32) DEFAULT NULL',
            'virtuemart_paymentmethod_id' => 'mediumint(1) UNSIGNED DEFAULT NULL',
            'payment_name' => 'char(255) NOT NULL DEFAULT \'\' ',
            'payment_order_total' => 'decimal(15,5) NOT NULL DEFAULT \'0.00000\' ',
            'payment_currency' => 'char(3) ',
            'cost_per_transaction' => ' decimal(10,2) DEFAULT NULL ',
            'cost_percent_total' => ' decimal(10,2) DEFAULT NULL ',
            'tax_id' => 'smallint(11) DEFAULT NULL'
        );

        return $SQLfields;
    }
    
    /**
     * @param $name
     * @param $id
     * @param $data
     * @return bool
     */
    function plgVmDeclarePluginParamsPaymentVM3( &$data) {
        return $this->declarePluginParams('payment', $data);
    }

    function getPluginParams(){
        $db = JFactory::getDbo();
        $sql = "select virtuemart_paymentmethod_id from #__virtuemart_paymentmethods where payment_element = 'pagsegurobrasil'";
        $db->setQuery($sql);
        $id = (int)$db->loadResult();
        return $this->getVmPluginMethod($id);
    }

    /**
     *
     *
     * @author Valérie Isaksen
     */
    function plgVmConfirmedOrder($cart, $order) {

        if (!($method = $this->getVmPluginMethod($order['details']['BT']->virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $lang = JFactory::getLanguage();
        $filename = 'com_virtuemart';
        $lang->load($filename, JPATH_ADMINISTRATOR);
        $vendorId = 0;

        $html = "";

        if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
        $this->getPaymentCurrency($method);
        // END printing out HTML Form code (Payment Extra Info)
        $q = 'SELECT `currency_code_3` FROM `#__virtuemart_currencies` WHERE `virtuemart_currency_id`="' . $method->payment_currency . '" ';
        $db = &JFactory::getDBO();
        $db->setQuery($q);
        $currency_code_3 = $db->loadResult();
        $paymentCurrency = CurrencyDisplay::getInstance($method->payment_currency);
        $totalInPaymentCurrency = round($paymentCurrency->convertCurrencyTo($method->payment_currency, $order['details']['BT']->order_total, false), 2);
        $cd = CurrencyDisplay::getInstance($cart->pricesCurrency);

        $this->_virtuemart_paymentmethod_id = $order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['payment_name'] = $this->renderPluginName($method);
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
        $dbValues['cost_per_transaction'] = (!empty($method->cost_per_transaction)?$method->cost_per_transaction:0);
        $dbValues['cost_percent_total'] = (!empty($method->cost_percent_total)?$method->cost_percent_total:0);
        $dbValues['payment_currency'] = $currency_code_3;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $this->storePSPluginInternalData($dbValues);

        $html = $this->retornaHtmlPagamento( $order, $method, 1);
        
        JFactory::getApplication()->enqueueMessage(utf8_encode(
            "Seu pedido foi realizado com sucesso. voc&ecirc; ser&aacute; direcionado para o site do Pagseguro, onde efetuar&aacute o pagamento da sua compra."
        ));

        $novo_status = $method->status_aguardando;
        return $this->processConfirmedOrderPaymentResponse(1, $cart, $order, $html, $dbValues['payment_name'], $novo_status);

    }
    
    function retornaHtmlPagamento( $order, $method, $redir ) {
        $lang = JFactory::getLanguage();
        $filename = 'com_virtuemart';
        $lang->load($filename, JPATH_ADMINISTRATOR);
        $vendorId = 0;

        if (isset($order["details"]["ST"])) {
            $endereco = "ST";
        } else {
            $endereco = "BT";
        }

        $dbValues = array();
        $dbValues['payment_name'] = $this->renderPluginName($method);

        $html = '<table>' . "\n";
        $html .= $this->getHtmlRow('STANDARD_PAYMENT_INFO', $dbValues['payment_name']);
        if (!empty($payment_info)) {
            $lang = & JFactory::getLanguage();
            if ($lang->hasKey($method->payment_info)) {
                $payment_info = JTExt::_($method->payment_info);
            } else {
                $payment_info = $method->payment_info;
            }
            $html .= $this->getHtmlRow('STANDARD_PAYMENTINFO', $payment_info);
        }
        if (!class_exists('CurrencyDisplay'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'helpers' . DS . 'currencydisplay.php' );
        $currency = CurrencyDisplay::getInstance('', $order['details']['BT']->virtuemart_vendor_id);
        $html .= $this->getHtmlRow('STANDARD_ORDER_NUMBER', $order['details']['BT']->order_number);
        $html .= $this->getHtmlRow('STANDARD_AMOUNT', $currency->priceDisplay($order['details']['BT']->order_total));
        $html .= '</table>' . "\n";


        // configuração dos campos
        $campo_complemento = $method->campo_complemento;
        $campo_numero   = $method->campo_numero;

        $html .= '<form id="frm_pagseguro" action="https://pagseguro.uol.com.br/v2/checkout/payment.html" method="post" >    ';
        $html .= '  <input type="hidden" name="receiverEmail" value="' . $method->email_cobranca . '"  />
                    <input type="hidden" name="currency" value="BRL"  />
                    <input type="hidden" name="tipo" value="CP"  />
                    <input type="hidden" name="encoding" value="utf-8"  />';

        if (isset($order["details"][$endereco]) and isset($order["details"][$endereco]->$campo_complemento)) {
            $complemento = $order["details"][$endereco]->$campo_complemento;
        } else {
            $complemento = '';
        }

        if (isset($order["details"][$endereco]) and isset($order["details"][$endereco]->$campo_numero)) {
            $numero = $order["details"][$endereco]->$campo_numero;
        } else {
            $numero = '';
        }


        $html .= '<input name="reference" type="hidden" value="'.($order["details"][$endereco]->order_number!=''?$order["details"][$endereco]->order_number:$order["details"]["BT"]->order_number).'">';


        $html .= '<input type="hidden" name="senderName" value="' . ($order["details"][$endereco]->first_name!=''?$order["details"][$endereco]->first_name:$order["details"]["BT"]->first_name) . ' ' . ($order["details"][$endereco]->last_name!=''?$order["details"][$endereco]->last_name:$order["details"]["BT"]->last_name) . '"  />
        <input type="hidden" name="shippingType" value="' . $method->tipo_frete . '"  />
        <input type="hidden" name="shippingAddressPostalCode" value="' . ($order["details"][$endereco]->zip!=''?$order["details"][$endereco]->zip:$order["details"]["BT"]->zip) . '"  />
        <input type="hidden" name="shippingAddressStreet" value="' . ($order["details"][$endereco]->address_1!=''?$order["details"][$endereco]->address_1:$order["details"]["BT"]->address_1) . ' ' . ($order["details"][$endereco]->address_2!=''?$order["details"][$endereco]->address_2:$order["details"]["BT"]->address_2) . '"  />
        <input type="hidden" name="shippingAddressNumber" value="'.$numero.'"  />
        <input type="hidden" name="shippingAddressComplement" value="'.$complemento.'"  />
        <input type="hidden" name="shippingAddressCity" value="' . ($order["details"][$endereco]->city!=''?$order["details"][$endereco]->city:$order["details"]["BT"]->city) . '"  />';    
        $cod_estado = (!empty($order["details"][$endereco]->virtuemart_state_id)?$order["details"][$endereco]->virtuemart_state_id:$order["details"]["BT"]->virtuemart_state_id);     
        $estado = ShopFunctions::getStateByID($cod_estado, "state_2_code");             
        $html.='
        <input type="hidden" name="shippingAddressState" value="' . $estado . '"  />
        <input type="hidden" name="shippingAddressCountry" value="BRA"  />
        <input type="hidden" name="senderAreaCode" value=""  />
        <input type="hidden" name="senderPhone" value="' . ($order["details"][$endereco]->phone_1!=''?$order["details"][$endereco]->phone_1:$order["details"]["BT"]->phone_1) . '"  />
        <input type="hidden" name="senderEmail" value="' . ($order["details"][$endereco]->email!=''?$order["details"][$endereco]->email:$order["details"]["BT"]->email) . '"  />';
        
        // total do frete
        // configurado para passar o frete do total da compra
        if (!empty($order["details"]["BT"]->order_shipment)) {
            $html .= '<input type="hidden" name="itemShippingCost1" value="' . number_format(round (($order["details"][$endereco]->order_shipment!=''?$order["details"][$endereco]->order_shipment:$order["details"]["BT"]->order_shipment),2),2,'.','') . '">';
        } else {
            $html .= '<input type="hidden" name="itemShippingCost1" value="0">';
        }

        // Cupom de Desconto 
        $desconto_pedido = $order["details"]['BT']->coupon_discount;

        // desconto do produto
        $html .= '<input type="hidden" name="extraAmount" value="'.number_format(round($desconto_pedido, 2),2,".","").'" />'; 

        $order_subtotal = $order['details']['BT']->order_subtotal;
        if(!class_exists('VirtueMartModelCustomfields'))require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'customfields.php');      
        if(!class_exists('VirtueMartModelProduct'))require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'product.php'); 

        $i = 0;
        $product_model = VmModel::getModel('product');
        foreach ($order['items'] as $p) {   
            $i ++;
            $valor_produto = $p->product_final_price;
            // desconto do pedido
            $valor_item = $valor_produto;
            $pr = $product_model->getProduct($p->virtuemart_product_id);
            $product_attribute = strip_tags(VirtueMartModelCustomfields::CustomsFieldOrderDisplay($p,'FE'));
            $html .='<input type="hidden" name="itemId' . $i . '" value="' . $p->virtuemart_order_item_id . '">
                <input type="hidden" name="itemDescription' . $i . '" value="' . $p->order_item_name . '">
                <input type="hidden" name="itemQuantity' . $i . '" value="' . $p->product_quantity . '">
                <input type="hidden" name="itemAmount' . $i . '" value="' .number_format(round( $p->product_final_price ,2),2,'.','').'">
                <input type="hidden" name="itemWeight' . $i . '" value="1">';            
        }

        $url                  = JURI::root();
        $url_lib              = $url.DS.'plugins'.DS.'vmpayment'.DS.'pagseguro_virtuemartbrasil'.DS;
        $url_imagem_pagamento = $url_lib . 'imagens'.DS.'pagseguro.gif';

        // segundos para redirecionar para o Pagseguro
        if ($redir) {
            // segundos para redirecionar para o Pagseguro
            $segundos = $method->segundos_redirecionar;
            $html .= '<br/><br/>Você ser&aacute; direcionado para a tela de pagamento em '.$segundos.' segundo(s), ou então clique logo abaixo:<br />';
            $html .= '<script>setTimeout(\'document.getElementById("frm_pagseguro").submit();\','.$segundos.'000);</script>';
        }

        $html .= '<div align="center"><br /><input type="image" value="Clique aqui para efetuar o pagamento" src="'.$url_imagem_pagamento.'" /></div>';
        $html .= '</form>';     
        return $html;
    }

    /**
     * Display stored payment data for an order
     *
     */
    function plgVmOnShowOrderBEPayment($virtuemart_order_id, $virtuemart_payment_id) {
        if (!$this->selectedThisByMethodId($virtuemart_payment_id)) {
            return null; // Another method was selected, do nothing
        }

        $db = JFactory::getDBO();
        $q = 'SELECT * FROM `' . $this->_tablename . '` '
                . 'WHERE `virtuemart_order_id` = ' . $virtuemart_order_id;
        $db->setQuery($q);
        if (!($paymentTable = $db->loadObject())) {
            vmWarn(500, $q . " " . $db->getErrorMsg());
            return '';
        }
        $this->getPaymentCurrency($paymentTable);

        $html = '<table class="adminlist">' . "\n";
        $html .=$this->getHtmlHeaderBE();
        $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_NAME', $paymentTable->payment_name);
        $html .= $this->getHtmlRowBE('STANDARD_PAYMENT_TOTAL_CURRENCY', $paymentTable->payment_order_total . ' ' . $paymentTable->payment_currency);
        $html .= '</table>' . "\n";
        return $html;
    }   

    function getCosts(VirtueMartCart $cart, $method, $cart_prices) {
        if (preg_match('/%$/', $method->cost_percent_total)) {
            $cost_percent_total = substr($method->cost_percent_total, 0, -1);
        } else {
            $cost_percent_total = $method->cost_percent_total;
        }
        return ($method->cost_per_transaction + ($cart_prices['salesPrice'] * $cost_percent_total * 0.01));
    }

       
   function setCartPrices (VirtueMartCart $cart, &$cart_prices, $method, $progressive=true) {
        if ($method->modo_calculo_desconto == '2') {
            return parent::setCartPrices($cart, $cart_prices, $method, false);            
        } else {
            return parent::setCartPrices($cart, $cart_prices, $method, true);
        }
    }

    /**
     * Check if the payment conditions are fulfilled for this payment method
     * @author: Valerie Isaksen
     *
     * @param $cart_prices: cart prices
     * @param $payment
     * @return true: if the conditions are fulfilled, false otherwise
     *
     */
    protected function checkConditions($cart, $method, $cart_prices) {

        $address = (($cart->ST == 0) ? $cart->BT : $cart->ST);
        $method->min_amount = (!empty($method->min_amount)?$method->min_amount:0);
        $method->max_amount = (!empty($method->max_amount)?$method->max_amount:0);
        
        $amount = $cart_prices['salesPrice'];
        $amount_cond = ($amount >= $method->min_amount AND $amount <= $method->max_amount
                OR
         ($method->min_amount <= $amount AND ($method->max_amount == 0) ));
        if (!$amount_cond) {
            return false;
        }
        $countries = array();
        if (!empty($method->countries)) {
            if (!is_array($method->countries)) {
                $countries[0] = $method->countries;
            } else {
                $countries = $method->countries;
            }
        }

        // probably did not gave his BT:ST address
        if (!is_array($address)) {
            $address = array();
            $address['virtuemart_country_id'] = 0;
        }

        if (!isset($address['virtuemart_country_id']))
            $address['virtuemart_country_id'] = 0;
        if (count($countries) == 0 || in_array($address['virtuemart_country_id'], $countries) || count($countries) == 0) {
            return true;
        }

        return false;
    }

    /*
     * We must reimplement this triggers for joomla 1.7
     */

    /**
     * Create the table for this plugin if it does not yet exist.
     * This functions checks if the called plugin is active one.
     * When yes it is calling the standard method to create the tables
     * @author Valérie Isaksen
     *
     */
    function plgVmOnStoreInstallPaymentPluginTable($jplugin_id) {
        return $this->onStoreInstallPluginTable($jplugin_id);
    }

    /**
     * This event is fired after the payment method has been selected. It can be used to store
     * additional payment info in the cart.
     *
     * @author Max Milbers
     * @author Valérie isaksen
     *
     * @param VirtueMartCart $cart: the actual cart
     * @return null if the payment was not selected, true if the data is valid, error message if the data is not vlaid
     *
     */
    public function plgVmOnSelectCheckPayment(VirtueMartCart $cart) {
        return $this->OnSelectCheck($cart);
    }

    /**
     * plgVmDisplayListFEPayment
     * This event is fired to display the pluginmethods in the cart (edit shipment/payment) for exampel
     *
     * @param object $cart Cart object
     * @param integer $selected ID of the method selected
     * @return boolean True on succes, false on failures, null when this plugin was not selected.
     * On errors, JError::raiseWarning (or JError::raiseError) must be used to set a message.
     *
     * @author Valerie Isaksen
     * @author Max Milbers
     */
    public function plgVmDisplayListFEPayment(VirtueMartCart $cart, $selected = 0, &$htmlIn) {
        return $this->displayListFE($cart, $selected, $htmlIn);
    }

    /*
     * plgVmonSelectedCalculatePricePayment
     * Calculate the price (value, tax_id) of the selected method
     * It is called by the calculator
     * This function does NOT to be reimplemented. If not reimplemented, then the default values from this function are taken.
     * @author Valerie Isaksen
     * @cart: VirtueMartCart the current cart
     * @cart_prices: array the new cart prices
     * @return null if the method was not selected, false if the shiiping rate is not valid any more, true otherwise
     *
     *
     */

    public function plgVmonSelectedCalculatePricePayment(VirtueMartCart $cart, array &$cart_prices, &$cart_prices_name) {
        return $this->onSelectedCalculatePrice($cart, $cart_prices, $cart_prices_name);
    }

    function plgVmgetPaymentCurrency($virtuemart_paymentmethod_id, &$paymentCurrencyId) {

        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }
        $this->getPaymentCurrency($method);

        $paymentCurrencyId = $method->payment_currency;
    }

    /**
     * plgVmOnCheckAutomaticSelectedPayment
     * Checks how many plugins are available. If only one, the user will not have the choice. Enter edit_xxx page
     * The plugin must check first if it is the correct type
     * @author Valerie Isaksen
     * @param VirtueMartCart cart: the cart object
     * @return null if no plugin was found, 0 if more then one plugin was found,  virtuemart_xxx_id if only one plugin is found
     *
     */
    function plgVmOnCheckAutomaticSelectedPayment(VirtueMartCart $cart, array $cart_prices = array()) {
        return $this->onCheckAutomaticSelected($cart, $cart_prices);
    }

    /**
     * This method is fired when showing the order details in the frontend.
     * It displays the method-specific data.
     *
     * @param integer $order_id The order ID
     * @return mixed Null for methods that aren't active, text (HTML) otherwise
     * @author Max Milbers
     * @author Valerie Isaksen
     */
    public function plgVmOnShowOrderFEPayment($virtuemart_order_id, $virtuemart_paymentmethod_id, &$payment_name) {
        $orderModel = VmModel::getModel('orders');
        $orderDetails = $orderModel->getOrder($virtuemart_order_id);
        if (!($method = $this->getVmPluginMethod($orderDetails['details']['BT']->virtuemart_paymentmethod_id))) {
            return false;
        }
    
        $view = JRequest::getVar('view');
        // somente retorna se estiver como transa��o pendente
        if ($method->status_aguardando == $orderDetails['details']['BT']->order_status and $view == 'orders' and $orderDetails['details']['BT']->virtuemart_paymentmethod_id == $virtuemart_paymentmethod_id) {
            JFactory::getApplication()->enqueueMessage(utf8_encode(
                "O pagamento deste pedido consta como Pendente de pagamento ainda. Clicando no bot&atilde; logo abaixo, voc&ecirc; ser&aacute; redirecionado para o site do Pagseguro, onde efetuar&aacute; o pagamento da sua compra.")
            );
            $redir = 0;
            $html = $this->retornaHtmlPagamento( $orderDetails, $method, $redir );
            echo $html;
        }

        $this->onShowOrderFE($virtuemart_order_id, $virtuemart_paymentmethod_id, $payment_name);
    }

    /**
     * This event is fired during the checkout process. It can be used to validate the
     * method data as entered by the user.
     *
     * @return boolean True when the data was valid, false otherwise. If the plugin is not activated, it should return null.
     * @author Max Milbers

      public function plgVmOnCheckoutCheckDataPayment(  VirtueMartCart $cart) {
      return null;
      }
     */

    /**
     * This method is fired when showing when priting an Order
     * It displays the the payment method-specific data.
     *
     * @param integer $_virtuemart_order_id The order ID
     * @param integer $method_id  method used for this order
     * @return mixed Null when for payment methods that were not selected, text (HTML) otherwise
     * @author Valerie Isaksen
     */
    function plgVmonShowOrderPrintPayment($order_number, $method_id) {
        return $this->onShowOrderPrint($order_number, $method_id);
    }

    function plgVmDeclarePluginParamsPayment($name, $id, &$data) {
        return $this->declarePluginParams('payment', $name, $id, $data);
    }

    function plgVmSetOnTablePluginParamsPayment($name, $id, &$table) {
        return $this->setOnTablePluginParams($name, $id, $table);
    }

    //Notice: We only need to add the events, which should work for the specific plugin, when an event is doing nothing, it should not be added

    /**
     * Save updated order data to the method specific table
     *
     * @param array $_formData Form data
     * @return mixed, True on success, false on failures (the rest of the save-process will be
     * skipped!), or null when this method is not actived.
     * @author Oscar van Eijk
     *
      public function plgVmOnUpdateOrderPayment(  $_formData) {
      return null;
      }

      /**
     * Save updated orderline data to the method specific table
     *
     * @param array $_formData Form data
     * @return mixed, True on success, false on failures (the rest of the save-process will be
     * skipped!), or null when this method is not actived.
     * @author Oscar van Eijk
     *
      public function plgVmOnUpdateOrderLine(  $_formData) {
      return null;
      }

      /**
     * plgVmOnEditOrderLineBE
     * This method is fired when editing the order line details in the backend.
     * It can be used to add line specific package codes
     *
     * @param integer $_orderId The order ID
     * @param integer $_lineId
     * @return mixed Null for method that aren't active, text (HTML) otherwise
     * @author Oscar van Eijk
     *
      public function plgVmOnEditOrderLineBEPayment(  $_orderId, $_lineId) {
      return null;
      }

      /**
     * This method is fired when showing the order details in the frontend, for every orderline.
     * It can be used to display line specific package codes, e.g. with a link to external tracking and
     * tracing systems
     *
     * @param integer $_orderId The order ID
     * @param integer $_lineId
     * @return mixed Null for method that aren't active, text (HTML) otherwise
     * @author Oscar van Eijk
     *
      public function plgVmOnShowOrderLineFE(  $_orderId, $_lineId) {
      return null;
      }

      /**
     * This event is fired when the  method notifies you when an event occurs that affects the order.
     * Typically,  the events  represents for payment authorizations, Fraud Management Filter actions and other actions,
     * such as refunds, disputes, and chargebacks.
     *
     * NOTE for Plugin developers:
     *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
     *
     * @param $return_context: it was given and sent in the payment form. The notification should return it back.
     * Used to know which cart should be emptied, in case it is still in the session.
     * @param int $virtuemart_order_id : payment  order id
     * @param char $new_status : new_status for this order id.
     * @return mixed Null when this method was not selected, otherwise the true or false
     *
     * @author Valerie Isaksen
     *
     *
      public function plgVmOnPaymentNotification() {
      return null;
      }
      */
      function plgVmOnPaymentNotification() {
        
         header("Status: 200 OK");
        if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
        $pagseguro_data = $_REQUEST;

        $this->logInfo('pagseguro_data ' . implode('   ', $pagseguro_data), 'message');

        $qps = 'SELECT `virtuemart_paymentmethod_id` FROM `#__virtuemart_paymentmethods` WHERE `payment_element`="pagseguro" ';
        $dbps = &JFactory::getDBO();
        $dbps->setQuery($qps);
        $psmethod_id = $dbps->loadResult();
                
        $psmethod = $this->getVmPluginMethod($psmethod_id);
        if (!$this->selectedThisElement($psmethod->payment_element)) {
            return false;
        }

        // dados do pagseguro
        $email_cobranca = $psmethod->email_cobranca;
        $token          = $psmethod->token;


        $credentials = new PagSeguroAccountCredentials($email_cobranca, $token);

        $type = $pagseguro_data['notificationType'];
        $code = $pagseguro_data['notificationCode'];

        if ($type === 'transaction') {
            $transaction = PagSeguroNotificationService::checkTransaction($credentials, $code);
        } else {
            return false;            
        }

        // dados do pedido
        $referencia = $transaction->getReference();
        $PSdataRef = explode('|||', $referencia);
        $order_number = $PSdataRef[0];
        $return_context = $PSdataRef[1];
        $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);

        if (!$virtuemart_order_id) {
            return;
        }
        $vendorId = 0;
        $payment = $this->getDataByOrderId($virtuemart_order_id);
        if (!$payment) {
            $this->logInfo('getDataByOrderId payment not found: exit ', 'ERROR');
            return null;
        }

        $method = $this->getVmPluginMethod($payment->virtuemart_paymentmethod_id);
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }

        $this->logInfo('Notification: pagseguro_data ' . implode(' | ', $pagseguro_data), 'message');

        $ps_status = $transaction->getStatus();
        $payment_status = $ps_status->getValue();

        $order = array();
        $new_status = '';
        if ($payment_status == 1 || $payment_status == 2) {
            $new_status = $method->status_aguardando;
            $order['order_status'] = $new_status;
            $order['customer_notified'] = 0;
            $desc_status = "Aguardando pagamento";
        } elseif ($payment_status == 3) {
            $new_status = $method->status_aprovado;
            $order['order_status'] = $new_status;
            $order['customer_notified'] =1;
            $desc_status = "Pago";
        } elseif ($payment_status == 4) {
            $new_status = $method->status_disponivel;
            $order['order_status'] = $new_status;
            $order['customer_notified'] =0;
            $desc_status = "Completo";
        } elseif ($payment_status == 5) {
            $new_status = $method->status_analise;
            $order['order_status'] = $new_status;
            $order['customer_notified'] =0;
            $desc_status = "Disputa";
        } elseif ($payment_status == 6) {
            $new_status = $method->status_devolvida;
            $order['order_status'] = $new_status;
            $order['customer_notified'] =0;
            $desc_status = "Devolvida";
        } elseif ($payment_status == 7) {
            $new_status = $method->status_cancelado;
            $order['order_status'] = $new_status;
            $order['customer_notified'] =1;
            $desc_status = "Cancelada";
        }

        $order['comments'] = 'O status do seu pedido '.$order_number.' no Pagseguro foi atualizado: '.$desc_status;


        $this->_virtuemart_paymentmethod_id = $order['details']['BT']->virtuemart_paymentmethod_id;
        $dbValues['payment_name'] = $this->renderPluginName($method);
        $dbValues['order_number'] = $order['details']['BT']->order_number;
        $dbValues['virtuemart_paymentmethod_id'] = $this->_virtuemart_paymentmethod_id;
        $dbValues['cost_per_transaction'] = (!empty($method->cost_per_transaction)?$method->cost_per_transaction:0);
        $dbValues['cost_percent_total'] = (!empty($method->cost_percent_total)?$method->cost_percent_total:0);
        $dbValues['payment_currency'] = $currency_code_3;
        $dbValues['payment_order_total'] = $totalInPaymentCurrency;
        $dbValues['tax_id'] = $method->tax_id;
        $this->storePSPluginInternalData($dbValues);

        $this->logInfo('plgVmOnPaymentNotification return new_status:' . $new_status, 'message');

        if ($virtuemart_order_id) {
            // send the email only if payment has been accepted
            if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );
            $modelOrder = new VirtueMartModelOrders();
            $orderitems = $modelOrder->getOrder($virtuemart_order_id);
            $nb_history = count($orderitems['history']);

            $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
            if ($nb_history == 1) {
                if (!class_exists('shopFunctionsF'))
                    require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
                shopFunctionsF::sentOrderConfirmedEmail($orderitems);
                $this->logInfo('Notification, sentOrderConfirmedEmail ' . $order_number. ' '. $new_status, 'message');
            }
        }

        //// remove vmcart
        $this->emptyCart($return_context);

    }

      /**
     * plgVmOnPaymentResponseReceived
     * This event is fired when the  method returns to the shop after the transaction
     *
     *  the method itself should send in the URL the parameters needed
     * NOTE for Plugin developers:
     *  If the plugin is NOT actually executed (not the selected payment method), this method must return NULL
     *
     * @param int $virtuemart_order_id : should return the virtuemart_order_id
     * @param text $html: the html to display
     * @return mixed Null when this method was not selected, otherwise the true or false
     *
     * @author Valerie Isaksen
     *
     *
      function plgVmOnPaymentResponseReceived(, &$virtuemart_order_id, &$html) {
      return null;
      }
     */
         // retorno da transacao para o pedido espec�fico
     function plgVmOnPaymentResponseReceived(&$html) {

        // the payment itself should send the parameter needed.
        $virtuemart_paymentmethod_id = JRequest::getInt('pm', 0);

        $vendorId = 0;
        if (!($method = $this->getVmPluginMethod($virtuemart_paymentmethod_id))) {
            return null; // Another method was selected, do nothing
        }
        if (!$this->selectedThisElement($method->payment_element)) {
            return false;
        }
        if (!class_exists('VirtueMartCart'))
                require(JPATH_VM_SITE . DS . 'helpers' . DS . 'cart.php');
        $payment_data = JRequest::get('post');
        $payment_name = $this->renderPluginName($method);
        $html = $this->_getPaymentResponseHtml($payment_data, $payment_name);

        if (!empty($payment_data)) {
            vmdebug('plgVmOnPaymentResponseReceived', $payment_data);
            $order_number = $payment_data['invoice'];
            $return_context = $payment_data['custom'];
            if (!class_exists('VirtueMartModelOrders'))
            require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

            $virtuemart_order_id = VirtueMartModelOrders::getOrderIdByOrderNumber($order_number);
            $payment_name = $this->renderPluginName($method);
            $html = $this->_getPaymentResponseHtml($payment_data, $payment_name);

            if ($virtuemart_order_id) {

            // send the email ONLY if payment has been accepted
            if (!class_exists('VirtueMartModelOrders'))
                require( JPATH_VM_ADMINISTRATOR . DS . 'models' . DS . 'orders.php' );

            $modelOrder = new VirtueMartModelOrders();
            $orderitems = $modelOrder->getOrder($virtuemart_order_id);
            $nb_history = count($orderitems['history']);
            //vmdebug('history', $orderitems);
            if (!class_exists('shopFunctionsF'))
                require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
            if ($nb_history == 1) {
                if (!class_exists('shopFunctionsF'))
                require(JPATH_VM_SITE . DS . 'helpers' . DS . 'shopfunctionsf.php');
                shopFunctionsF::sentOrderConfirmedEmail($orderitems);
                $this->logInfo('plgVmOnPaymentResponseReceived, sentOrderConfirmedEmail ' . $order_number, 'message');
                $order['order_status'] = $orderitems['items'][$nb_history - 1]->order_status;
                $order['virtuemart_order_id'] = $virtuemart_order_id;
                $order['customer_notified'] = 0;
                $order['comments'] = JText::sprintf('VMPAYMENT_PAYPAL_EMAIL_SENT');
                $modelOrder->updateStatusForOneOrder($virtuemart_order_id, $order, true);
            }
            }
        }
        //We delete the old stuff
        // get the correct cart / session
        $cart = VirtueMartCart::getCart();
        $cart->emptyCart();
        return true;
    }     

}

// No closing tag
