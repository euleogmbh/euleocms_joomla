<?php
require_once(JPATH_COMPONENT_ADMINISTRATOR . DS . 'classes' . DS . 'euleolib.php');

if ($id = JRequest::getVar('categoryid', false, 'GET', 'int')) {
	$db =& JFactory::getDBO();
	$db->setQuery('SELECT * FROM #__categories WHERE id = ' . $db->quote($id));
	$category =& $db->loadObject(false);
	
	if (is_numeric($category->section)) {
		$category->section = 'com_content';
	}
	
	if (is_string($category->section) && substr($category->section, 0, 3) == 'com' && $category->section != 'com_banner') {
		if ($category->section == 'com_contact_details') {
			$category->section = 'com_contact';
		}
		
		if (! $lang = JRequest::getVar('lang')) {
			$params = JComponentHelper::getParams('com_languages');
			$lang = $params->get('site');
			
			$db->setQuery('SELECT shortcode FROM #__languages WHERE code = ' . $db->quote($lang));
			$lang = $db->loadObject(false);
			
			$lang = $lang->shortcode;
		}
		
		$link = 'index.php?previewTranslation=true&option=' . $category->section . '&view=category&id=' . $id . '&lang=' . $lang;
		header('Location: ' . $link);
		exit;
	}
	
	echo JText::_('NO PREVIEW AVAILABLE');
} else {
	$params = JComponentHelper::getParams('com_euleo');
	
	$token = $params->get('token');
	
	if ($token) {
		$request =& new EuleoRequest('', '');
		
		$data = $request->install($token);
		
		if ($data['customer'] && $data['usercode']) {
			$params->set('customer', $data['customer']);
			$params->set('usercode', $data['usercode']);
			$params->set('token', '');
			
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
			
			echo 'callback response';
		}
	} else {
		$customer = $params->get('customer');
		$usercode = $params->get('usercode');
		
		$request =& new EuleoRequest($customer, $usercode);
		
		$rows =& $request->getRows();
		
		$jfManager =& JoomFishManager::getInstance();
		
		$languages =& $jfManager->getLanguages();
		if ($languages) {
			$languageIds = array();
			for ($i = 0; $i < count($languages); $i ++) {
				$languageIds[$languages[$i]->shortcode] = $languages[$i]->id;
			}
		}
		JLoader::import( 'models.ContentObject', JPATH_COMPONENT_ADMINISTRATOR);
		if ($rows) {
			$confirmDelivery = array();
			foreach ($rows as $row) {
				if ($row['fields']) {
					preg_match('/(.*?)_(\d+)/i', $row['id'], $matches);
					
					$catid = $matches[1];
					
					$contentid = $matches[2];
					
					$language_id = $languageIds[$row['lang']];
					if ($language_id) {
						$data = array();
						
						$contentElement =& $jfManager->getContentElement($catid);
						$actContentObject =& new ContentObject( $language_id, $contentElement );
					
						$actContentObject->loadFromContentID( $contentid );
					
						$storeOriginalText = ($jfManager->getCfg('storageOfOriginal') == 'md5') ? false : true;
						
						$table = $actContentObject->getTable();
						
						$fields = array();
						
						foreach ($row['fields'] as $field) {
							$data['refField_' . $field['name']] = $field['value'];
							$data['published_' . $field['name']] = $row['ready'];
						}
						
						foreach ($table->Fields as $field) {
							if ($field->translationContent) {
								$data['id_' . $field->Name] = $field->translationContent->id;
							}
							
							
							$data['origText_' . $field->Name] = $field->originalValue;
							$data['origValue_' . $field->Name] = md5($field->originalValue);
						
							// if the field is excluded from euleo translation, we store the original
							// value to show the check in the overview
							if (!$data['refField_' . $field->Name]) {
								if ($field->Name == 'publish_up' ||
								    $field->Name == 'publish_down' ||
								    !$field->Translate) {
								    	$data['refField_' . $field->Name] = $field->originalValue;
								}
							}
							
							if (!$data['published_' . $field->Name]) {
								$data['published_' . $field->Name] = 0;
							}
						}
						
						$actContentObject->bind($data, '', '', true, $storeOriginalText);
						
						if ($actContentObject->store() == null)	{
							JPluginHelper::importPlugin('joomfish');
							$dispatcher =& JDispatcher::getInstance();
							$dispatcher->trigger('onAfterTranslationSave', array($data));
							
							if ($row['ready']) {
								$confirmDelivery[$row['translationid']] = $row['translationid'];
							}
						}
						else {
							$message = JText::_('Error saving translation');
						}
					} else {
						$message = JText::_('Language not active');
					}
				}
				
			}
	
			if ($confirmDelivery) {
				$request->confirmDelivery($confirmDelivery);
			}
			
			echo 'callback response';
		}
	}
	
	// this is a callback, so dont send any other output
	exit;
}