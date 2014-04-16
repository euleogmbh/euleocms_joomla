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
 */

defined( 'JPATH_BASE' ) or die( 'Direct Access to this location is not allowed.' );

jimport('joomla.application.component.controller');
jimport('joomla.utilities.date');

JLoader::import( 'helpers.controllerHelper',JOOMFISH_ADMINPATH);

/**
 * The JoomFish Tasker manages the general tasks within the Joom!Fish admin interface
 *
 */
class TranslateController extends JController   {

	/** @var string		current used task */
	var $task=null;

	/** @var string		action within the task */
	var $act=null;

	/** @var array		int or array with the choosen list id */
	var $cid=null;

	/** @var string		file code */
	var $fileCode = null;

	/**
	 * @var object	reference to the Joom!Fish manager
	 * @access private
	 */
	var $_joomfishManager=null;

	var $_cart = null;
	
	var $_request = null;
	
	/**
	 * PHP 4 constructor for the tasker
	 *
	 * @return joomfishTasker
	 */
	function __construct( ){
		parent::__construct();
		$this->registerDefaultTask( 'showTranslate' );

		$this->act =  JRequest::getVar( 'act', '' );
		$this->task =  JRequest::getVar( 'task', '' );
		$this->cid =  JRequest::getVar( 'cid', array(0) );
		if (!is_array( $this->cid )) {
			$this->cid = array(0);
		}
		$this->fileCode =  JRequest::getVar( 'fileCode', '' );
		$this->_joomfishManager =& JoomFishManager::getInstance();

		$this->registerTask( 'connect', 'connectEuleo');
		
		$this->registerTask( 'overview', 'showTranslate' );

		$this->registerTask( 'euleo', 'exportEuleo');
		
		$this->registerTask( 'remove', 'removeEuleo');
		
		$this->registerTask( 'clearEuleo', 'clearEuleo');
		
		// Populate data used by controller
		global $mainframe;
		$this->_catid = $mainframe->getUserStateFromRequest('selected_catid', 'catid', '');
		$this->_select_language_id = $mainframe->getUserStateFromRequest('selected_lang','select_language_id', '-1');
		$this->_language_id =  JRequest::getVar( 'language_id', $this->_select_language_id );
		$this->_select_language_id = ($this->_select_language_id == -1 && $this->_language_id != -1) ? $this->_language_id : $this->_select_language_id;

		// Populate common data used by view
		// get the view
		$this->view = & $this->getView("translate");
		$model =& $this->getModel('translate');
		$this->view->setModel($model, true);

		// Assign data for view
		$this->view->assignRef('catid'   , $this->_catid);
		$this->view->assignRef('select_language_id',  $this->_select_language_id);
		$this->view->assignRef('task', $this->task);
		$this->view->assignRef('act', $this->act);
		
		
		/**
		 * initialize euleo-soap-api
		 * @var unknown_type
		 */
		require_once(JPATH_COMPONENT_ADMINISTRATOR . DS . 'classes/euleolib.php');

		$params = JComponentHelper::getParams('com_euleo');
		
		$customer = $params->get('customer');
		$usercode = $params->get('usercode');
		
		$request =& new EuleoRequest($customer, $usercode);
		$this->_request =& $request;
	
		if ($this->_request->connected()) {
			// we send a list of supported destination languages to euleo here
			$jfManager =& JoomFishManager::getInstance();
			$languages =& $jfManager->getLanguages();
			if ($languages) {
				$languageCodes = array();
				foreach ($languages as $language) {
					$languageCodes[] = $language->shortcode;
				}
				
				$languageList = implode(',', $languageCodes);
				
				if ($languageList) {
					$result = $request->setLanguageList($languageList);
					$unsupported = $result['unsupported'];
				}
			}
			
			$this->_cart = $request->getCart();
			$this->view->assignRef('cart', $this->_cart);
			$this->view->assignRef('unsupported', $unsupported);
		}
		
		$languageCode = $model->getLanguageCode($this->_select_language_id);
		$this->view->assignRef('languageCode', $languageCode);
	}

	function connectEuleo() {
		$cmsroot = 'http://' . $_SERVER['HTTP_HOST'] . str_replace('/administrator/index.php', '/', ($_SERVER['SCRIPT_NAME']));
			
		parse_str($_SERVER['QUERY_STRING'], $queryString);
			
		$queryString['task'] = 'translate.overview';
		$queryString['option'] = 'com_euleo';
		$queryString = $queryString ? '?' . http_build_query($queryString) : '';
			
		$returnUrl = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['SCRIPT_NAME'] . $queryString;
		
		$params = JComponentHelper::getParams('com_euleo');
		$token = $this->_request->getRegisterToken($cmsroot, $returnUrl);
		
		if ($token) {
			$params->set('customer', '');
			$params->set('usercode', '');
			
			$params->set('token', $token);
			
			// Get a new database query instance
			$db = JFactory::getDBO();
				
			// Build the query
			$query = '
				UPDATE
					#__components AS a
				SET
					a.params = ' . $db->quote($params->toString()) . '
				WHERE
					a.option = "com_euleo"
			';

				
			// Execute the query
			$db->setQuery($query);
			$db->query();
			
			
			if ($_SERVER['SERVER_ADDR'] == '192.168.1.10') {
				$link = 'http://euleo/registercms/' . $token;
			} else {
				$link = 'https://www.euleo.com/registercms/' . $token;
			}
			
			header('Location: ' . $link);
			exit;
		}
	}
	
	/**
	 * presenting the translation dialog
	 *
	 */
	function showTranslate() {

		// If direct translation then close the modal window
		if ($direct = intval(JRequest::getVar("direct",0))){
			$this->modalClose($direct);
			return;
		}

		JoomfishControllerHelper::_setupContentElementCache();
		if( !JoomfishControllerHelper::_testSystemBotState() ) {;
		echo "<div style='font-size:16px;font-weight:bold;color:red'>".JText::_('MAMBOT_ERROR')."</div>";
		}


		$this->showTranslationOverview( $this->_select_language_id, $this->_catid );
	}

	/** Presentation of the content's that must be translated
	 */
	function showTranslationOverview( $language_id, $catid) {
		$db =& JFactory::getDBO();
		global $mainframe;

		$limit		= $mainframe->getUserStateFromRequest('global.list.limit', 'limit', $mainframe->getCfg('list_limit'), 'int');
		$limitstart = $mainframe->getUserStateFromRequest( "view{com_joomfish}limitstart", 'limitstart', 0 );
		$search = $mainframe->getUserStateFromRequest( "search{com_joomfish}", 'search', '' );
		$search = $db->getEscaped( trim( strtolower( $search ) ) );

		// Build up the rows for the table
		$rows=null;
		$total=0;
		$filterHTML=array();
		if( $language_id != -1 && isset($catid) && $catid!="" ) {
			$contentElement = $this->_joomfishManager->getContentElement( $catid );
			if (!$contentElement){
				$catid = "content";
				$contentElement = $this->_joomfishManager->getContentElement( $catid );
			}
			JLoader::import( 'models.TranslationFilter',JOOMFISH_ADMINPATH);
			$tranFilters = getTranslationFilters($catid,$contentElement);

			$total = $contentElement->countReferences($language_id, $tranFilters);

			if ($total<$limitstart){
				$limitstart = 0;
			}

			$db->setQuery( $contentElement->createContentSQL( $language_id, null, $limitstart, $limit,$tranFilters ) );
			$rows = $db->loadObjectList();
			if ($db->getErrorNum()) {
				echo $db->stderr();
				// should not stop the page here otherwise there is no way for the user to recover
				$rows = array();
			}

			// Manipulation of result based on further information
			for( $i=0; $i<count($rows); $i++ ) {
				JLoader::import( 'models.ContentObject',JPATH_COMPONENT_ADMINISTRATOR);
				$contentObject = new ContentObject( $language_id, $contentElement );
				$contentObject->readFromRow( $rows[$i] );
				$rows[$i] = $contentObject;
				
				$table =& $contentObject->getTable();
				
				$text = '';
				
				if ($table->Fields) {
					foreach ($table->Fields as $field) {
						if ($field->Translate) {
							if ($field->Name != 'publish_up' && $field->Name != 'publish_down') {
								$allowed = array('text', 'titletext', 'textarea', 'htmltext');
								
								if (in_array($field->Type, $allowed)) {
									$text .= strip_tags($field->originalValue);
								}
							}
						}
					}
				}
				
				$code = $table->Name . '_' . $rows[$i]->id;
				$texts[$code] = $text;
			}
			
			if ($texts) {
				$result = $this->_request->identifyLanguages($texts);
				
				$identifiedLanguages = $result['languages'];
			}
			
			foreach ($tranFilters as $tranFilter){
				$afilterHTML=$tranFilter->_createFilterHTML();
				if (isset($afilterHTML)) $filterHTML[$tranFilter->filterType] = $afilterHTML;
			}

		}

		// Create the pagination object
		jimport('joomla.html.pagination');
		$pageNav = new JPagination($total, $limitstart, $limit);

		// get list of element names
		$elementNames[] = JHTML::_('select.option',  '', JText::_('Please select') );
		//$elementNames[] = JHTML::_('select.option',  '-1', '- All Content elements' );
		// force reload to make sure we get them all
		$elements = $this->_joomfishManager->getContentElements(true);
		foreach( $elements as $key => $element )
		{
			$elementNames[] = JHTML::_('select.option',  $key, $element->Name );
		}
		$clist = JHTML::_('select.genericlist', $elementNames, 'catid', 'class="inputbox" size="1" onchange="if(document.getElementById(\'select_language_id\').value>=0) document.adminForm.submit();"', 'value', 'text', $catid );

		// get the view
		$this->view = & $this->getView("translate","html");

		// Set the layout
		$this->view->setLayout('default');

		
		// Assign data for view - should really do this as I go along
		$this->view->assignRef('rows'   , $rows);
		$this->view->assignRef('search'   , $search);
		$this->view->assignRef('pageNav'   , $pageNav);
		$this->view->assignRef('clist'   , $clist);
		$this->view->assignRef('language_id', $language_id);
		$this->view->assignRef('filterlist', $filterHTML);
		$this->view->assignRef('language_id', $language_id);
		$this->view->assignRef('identifiedLanguages', $identifiedLanguages);

		if (isset($_SESSION['unsupported']) && $_SESSION['unsupported']) {
			$this->view->assign('unsupported', $_SESSION['unsupported']);
			unset($_SESSION['unsupported']);
		}
		
		$this->view->display();
		//TranslateViewTranslate::showTranslationOverview( $rows, $search, $pageNav, $langlist, $clist, $catid ,$language_id,$filterHTML );
	}
	
	function exportEuleo()
	{
		$request =& $this->_request;
		
		// we get the cart here because we have to check, if theres already a request in it
		$cart =& $this->_cart;
		
		$cid =  JRequest::getVar( 'cid', array(0) );
		$model =& $this->view->getModel();
		$catid = $this->_catid;

		$defaultSrcLang = $model->getDefaultSrcLang();
		
		foreach( $cid as $cid_row ) {
			list($translation_id, $contentid, $language_id) = explode('|', $cid_row);
			$contentElement = $this->_joomfishManager->getContentElement( $catid );
			JLoader::import( 'models.ContentObject',JOOMFISH_ADMINPATH);
			$actContentObject = new ContentObject( $language_id, $contentElement );
			$actContentObject->loadFromContentID( $contentid );
			$table = $actContentObject->getTable();
			
			$code = $table->Name . '_' . $contentid;
			
			$languageCode = $model->getLanguageCode($language_id);
			if ($cart['request2languages'][$code] && !in_array($languageCode, $cart['requests2languages'][$code])) {
				$response = $request->addLanguage($code, $languageCode);
				
				if (isset($response['unsupported']) && $response['unsupported']) {
					$_SESSION['unsupported'] = $response['unsupported'];
				}
			} else {
				if ($table->Fields) {
					$row = array();
					$row['srclang'] = $defaultSrcLang;
					$row['languages'] = $languageCode;
					$row['fields'] = array();

					foreach ($table->Fields as $field) {
						if ($table->Name == 'poll_data') {
							if ($field->Name == 'text') {
								$row['title'] = $field->originalValue;
							}
						} else if ($field->Name == 'title') {
							$row['title'] = $field->originalValue;
						} else if ($field->Name == 'attribs') {
							// if the row has set the parameter "language", we use this as source language
							$params = new JParameter($field->originalValue);
							$languageCode = $params->get('language');
							
							if ($languageCode) {
								$shortCode = $model->getShortCodeByCode($languageCode);
								if ($shortCode) {
									$row['srclang'] = $shortCode;
								}
							}
						}
						if ($field->Translate) {
							if ($field->Name != 'publish_up' && $field->Name != 'publish_down') {
								$allowed = array('text', 'titletext', 'textarea', 'htmltext');
								
								if (in_array($field->Type, $allowed)) {
									if ($field->originalValue) {
									 	switch ($field->Type) {
									 		case 'text': case 'titletext':
									 			$type = 'text';
									 			break;
									 		case 'textarea':
									 			$type = 'textarea';
									 			break;
									 		case 'htmltext':
									 			$type = 'richtextarea';
									 			break;
									 		default:
									 			$type = 'text';
									 			break;
									 	}
									 	
									 	$row['fields'][$field->Name]['label'] = $field->Lable;
									 	$row['fields'][$field->Name]['value'] = $field->originalValue;
									 	$row['fields'][$field->Name]['type'] = $type;
									}
								}
							}
						}
					}
					
					$row['code'] = $code;
					$row['label'] = $table->Name;
					
					
					$link = $model->getPreviewLink($table->Name);
					if ($link) {
						$row['url'] = $link->original;
						$row['targeturl'] = $link->translation;
						
						$search = array('{id}');
						$replace = array($contentid);
						
						
						$linkPath = 'http://' . $_SERVER['HTTP_HOST'] . str_replace('/administrator/index.php', '/', ($_SERVER['SCRIPT_NAME']));
	
						$row['url'] = $linkPath . str_replace($search, $replace, $row['url']);
						$row['targeturl'] = $linkPath . str_replace($search, $replace, $row['targeturl']);
					}
					
					$description = $row['title'];
					if ($description == '') {
						$description = $row['code'];
					}
					$row['description'] = $row['label'] . ' "' . $description . '"';
					$request->addRow($row);
				}
			}
		}
		$result = $request->sendRows();
		
		if (isset($result['unsupported']) && $result['unsupported']) {
			$_SESSION['unsupported'] = $result['unsupported'];
		}
		
		if (! $result['errors']) {
			$this->setRedirect('index.php?option=com_euleo');
			$this->redirect();
		} else {
			echo '<p>Es ist ein Fehler aufgetreten:</p>';
			echo '<p class="error">' . $result['errors'] . '</p>';
		}
	}
	
	function removeEuleo()
	{
		$request =& $this->_request;
		
		// we get the cart here because we have to check, if theres already a request in it
		$cart =& $this->_cart;
		
		$cid =  JRequest::getVar( 'cid', array(0) );
		$model =& $this->view->getModel();
		$catid = $this->_catid;

		foreach( $cid as $cid_row ) {
			list($translation_id, $contentid, $language_id) = explode('|', $cid_row);
			
			$contentElement = $this->_joomfishManager->getContentElement( $catid );
			JLoader::import( 'models.ContentObject',JOOMFISH_ADMINPATH);
			$actContentObject = new ContentObject( $language_id, $contentElement );
			$actContentObject->loadFromContentID( $contentid );
			$table = $actContentObject->getTable();
			
			$code = $table->Name . '_' . $contentid;
			
			$languageCode = $model->getLanguageCode($language_id);

			$result = $request->removeLanguage($code, $languageCode);
		}

		$this->setRedirect('index.php?option=com_euleo');
		$this->redirect();
	}
	
	function showCart()
	{
		require_once(JPATH_COMPONENT_ADMINISTRATOR . DS . 'classes/euleolib.php');
		
		$params = JComponentHelper::getParams('com_euleo');
		
		$customer = $params->get('customer');
		$usercode = $params->get('usercode');
		
		$request = new EuleoRequest($customer, $usercode);
		
		$request->startEuleoWebsite();
	}
	
	function clearEuleo()
	{
		require_once(JPATH_COMPONENT_ADMINISTRATOR . DS . 'classes/euleolib.php');
		
		$params = JComponentHelper::getParams('com_euleo');
		
		$customer = $params->get('customer');
		$usercode = $params->get('usercode');
		
		$request = new EuleoRequest($customer, $usercode);
		
		$request->clearCart();
		
		$this->setRedirect('index.php?option=com_euleo');
		$this->redirect();
	}
}
