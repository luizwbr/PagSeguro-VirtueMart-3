<?php
defined('_JEXEC') or die();

/**
 *
 * @package	VirtueMart
 * @subpackage Plugins  - Elements
 * @author Valérie Isaksen
 * @link http://www.virtuemart.net
 * @copyright Copyright (c) 2004 - 2011 VirtueMart Team. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 * VirtueMart is free software. This version may have been modified pursuant
 * to the GNU General Public License, and as distributed it includes or
 * is derivative of works licensed under the GNU General Public License or
 * other free or open source software licenses.
 * @version $Id: $
 */
/*
 * This class is used by VirtueMart Payment or Shipment Plugins
 * which uses JParameter
 * So It should be an extension of JFormField
 * Those plugins cannot be configured througth the Plugin Manager anyway.
 */
class JFormFieldVmConfiguracao extends JFormField {

    /**
     * Element name
     * @access	protected
     * @var		string
     */
    public $type = 'Configuracao';

    protected function getInput() {
		// recupera informacao do pagamento ativo
		if(!class_exists('VirtueMartModelPaymentmethod'))require(JPATH_VM_ADMINISTRATOR.DS.'models'.DS.'paymentmethod.php');
		$pm = new VirtueMartModelPaymentmethod();
		$pagamento = $pm->getPayment();
		$url = JURI::root().'index.php?option=com_virtuemart&view=pluginresponse&task=plugin&tmpl=component&task=pluginresponsereceived&pm='.$pagamento->virtuemart_paymentmethod_id.'&nasp=1';
		$html = '<div style="height: 30px; background: #E6E6E6; padding: 4px; margin: 4px;">'.$url.'</div>';
		$html .= '<div><em>Copie esta url acima e <a href="https://www.moip.com.br/AdmMainMenuMyData.do?method=transactionnotification">configure neste link</a></em></div>';
		return $html;
    }

}