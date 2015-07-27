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
 
class JFormFieldVMRadiocard extends JFormField {
/**
     * Element name
     *
     * @access	protected
     * @var		string
     */
    public $type = 'Radiocard';

 
    function fetchTooltip($label, $description, &$xmlElement, $control_name='', $name='') {

		$nome_imagem = str_replace('cartao_','',$name);
		$img_cartao = '<img src="'.JURI::root().$xmlElement->_attributes['path'].DS.'imagens'.DS.$nome_imagem.'_cartao.jpg" border="0"/>';

        $output = '<label id="'.$control_name.$name.'-lbl" for="'.$control_name.$name.'"';
        if ($description) {
            $output .= ' class="hasTip" title="'.JText::_($label).'::'.JText::_($description).'">';
        } else {
            $output .= '>';
        }
        //$output .= JText::_($label).'</label>';
        $output .= $img_cartao.'</label>';

        return $output;
    }

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
				$option->class .= 'cb-disable ';
			} else {
				$option->class .= 'cb-enable ';
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

		$retorno = "<div class='field switch'>".implode($html)." </div>";
		// End the radio field output.
		//$html[] = '</fieldset>';		
		return $retorno;
    }
}
/* eof */