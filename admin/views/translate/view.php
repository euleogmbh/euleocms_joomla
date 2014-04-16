<?php
/**
 * Joom!Fish extension for Euleo® Soap-API
 * Copyright (C) 2010 Euleo GmbH
 *
 * All rights reserved. The Joom!Fish project is a set of extentions for
 * the content management system Joomla!. It enables Joomla!
 * to manage multi lingual sites especially in all dynamic information
 * which are stored in the database.
 * 
 * The Euleo® extension transfers translateable content to Euleo® where
 * it will be translated and proofread by professional, certified translators.
 * After translation it will be transferred back to your CMS automatically.
 *
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA  02111-1307,USA.
 *
 * The "GNU General Public License" (GPL) is available at
 * http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * -----------------------------------------------------------------------------
 * $Id: view.php 1344 2009-06-18 11:50:09Z akede $
 * @package joomfish
 * @subpackage Views
 *
*/
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

JLoader::import( 'views.default.view',JOOMFISH_ADMINPATH);

/**
 * View class for translation overview
 *
 * @static
 * @package		Joom!Fish
 * @subpackage	translation
 * @since 2.0
 */
class TranslateViewTranslate extends JoomfishViewDefault
{
	/**
	 * Setting up special general attributes within this view
	 * These attributes are independed of the specifc view
	 */
	function _initialize($layout="overview") {
// 		JToolBarHelper::preferences('com_euleo', '180', '400');

		JHTML::stylesheet( 'euleo.css', 'administrator/components/com_euleo/assets/css/' );
		// get list of active languages
		$langOptions[] = JHTML::_('select.option',  '-1', JText::_('Select Language') );
		// Get data from the model
		$langActive = $this->get('Languages');		// all languages even non active once
		$defaultLang = $this->get('DefaultLanguage');
		$params = JComponentHelper::getParams('com_euleo');
		$showDefaultLanguageAdmin = $params->get("showDefaultLanguageAdmin", true);

		if ( count($langActive)>0 ) {
			foreach( $langActive as $language )
			{
				if($language->code != $defaultLang || $showDefaultLanguageAdmin) {
					$langOptions[] = JHTML::_('select.option',  $language->id, $language->name );
				}
			}
		}
		if ($layout == "overview" || $layout == "default"){
			$langlist = JHTML::_('select.genericlist', $langOptions, 'select_language_id', 'class="inputbox" size="1" onchange="if(document.getElementById(\'catid\').value.length>0) document.adminForm.submit();"', 'value', 'text', $this->select_language_id );
		}
		else {
			$confirm="";
			if ($this->actContentObject->language_id!=0){
				$confirm="onchange=\"confirmChangeLanguage('".$this->actContentObject->language."','".$this->actContentObject->language_id."')\"";
			}

			$langlist = JHTML::_('select.genericlist', $langOptions, 'language_id', 'class="inputbox" size="1" '.$confirm, 'value', 'text', $this->actContentObject->language_id );
		}
		$this->assignRef('langlist'   , $langlist);
	}
	/**
	 * Control Panel display function
	 *
	 * @param template $tpl
	 */
	function display($tpl = null)
	{
		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('JOOMFISH_TITLE') . ' :: ' .JText::_('TITLE_TRANSLATION'));

		// Set  page title
		JToolBarHelper::title( JText::_( 'TITLE_TRANSLATION' ), 'jftranslations' );

		$layout = $this->getLayout();

		$this->_initialize($layout);
		if (method_exists($this,$layout)){
			$this->$layout($tpl);
		} else {
			$this->overview($tpl);
		}

		JHTML::_('behavior.tooltip');
		parent::display($tpl);
	}


	function overview($tpl = null)
	{
		// browser title
		$document =& JFactory::getDocument();
		$document->setTitle(JText::_('EULEO_OVERVIEW'));

		// set page title
		JToolBarHelper::title( JText::_( 'EULEO_SELECT' ), 'logo' );

		if ($this->cart) {
			// Set toolbar items for the page
			JToolBarHelper::divider();
			JToolBarHelper::custom('translate.clearEuleo', 'delete', 'delete', 'CLEAR CART', false);
			JToolBarHelper::custom('translate.removeEuleo', 'remove', 'remove', 'REMOVE FROM CART', true);
			JToolBarHelper::custom('translate.exportEuleo', 'add', 'add', 'ADD TO CART', true);
			JToolBarHelper::custom('translate.connect', 'connect', 'connect', 'RECONNECT', false);
		} else {
			JToolBarHelper::custom('translate.connect', 'connect', 'connect', 'CONNECT', false);
		}
	}
}
