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
 
class JFormFieldVMRadio extends JFormField {
/**
     * Element name
     *
     * @access	protected
     * @var		string
     */
    public $type = 'Radio';

    protected function getInput() {	
	
		$html = array();
		// Initialize some field attributes.
		//$class = $node->element['class'] ? ' class="radio ' . (string) $node->element['class'] . '"' : ' class="radio"';
		// Start the radio field output.		
		//$html[] = '<fieldset id="' . $this->id . '"' . $class . '>';
		// Initialize variables.
		$options = array();
		foreach ($node->option as $option) {
			// Create a new option object based on the <option /> element.
			$tmp = JHtml::_(
				'select.option', (string) $option->_attributes['value'], trim((string) $option->_data), 'value', 'text'
			);
			// Set some option attributes.
			//$tmp->class = (string) $class;
			// Set some JavaScript option attributes.
			//$tmp->onclick = (string) $option['onclick'];
			// Add the option object to the result set.
			$options[] = $tmp;
		}
		rsort($options);
		 
		// Build the radio field output.
		foreach ($options as $i => $option)
		{
		


			// Initialize some option attributes.
			if ((string)$option->value == '0') {
				$option->class .= 'btn btn-success ';
			} else {
				$option->class .= 'btn btn-success active ';
			}

			if ((string) $option->value == (string) $value) {
				$checked = ' checked="checked"';
				$option->class .= 'selected ';
			} else {
				$checked = '';
			}
			$class = !empty($option->class) ? ' class="' . $option->class . ' '.$node->_attributes['class'].'"' : '';
			$disabled = !empty($option->disable) ? ' disabled="disabled"' : '';

			// Initialize some JavaScript option attributes.
			$onclick = !empty($option->onclick) ? ' onclick="' . $option->onclick . '"' : '';

			$html[] = '<input type="radio" id="id_' . $name . $i . '" name="' . $name . '"' . ' value="'
				. htmlspecialchars($option->value, ENT_COMPAT, 'UTF-8') . '"' . $checked . $class . $onclick . $disabled . '/>';
				
			$html[] = '<label for="id_' . $name . $i . '"' . $class . '><span>'
				. JText::alt($option->text, preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname)) . '</span></label>';
		}

/*
<fieldset id="jform_pago" class="radio btn-group" style="border:0 none;"><input type="radio" name="jform[pago]" id="jform_pago_0" value="0" rel="{&quot;color&quot;:&quot;danger&quot;}" class="validate[required]" checked="checked">
<label for="jform_pago_0" class="btn active btn-danger"><i style="margin-right:5px;" class="icomoon-cancel "></i>Não</label>
<input type="radio" name="jform[pago]" id="jform_pago_1" value="1" rel="{&quot;color&quot;:&quot;success&quot;}" class="validate[required]">
<label for="jform_pago_1" class="btn"><i style="margin-right:5px;" class="icomoon-ok "></i>Sim</label>
</fieldset>
*/

		$retorno = "<fieldset class='radio btn-group'>".implode($html)."</fieldset>";
		// End the radio field output.
		//$html[] = '</fieldset>';		
		return $retorno;
    }
}
/* eof */