<?php
/*+*******************************************************************************
 *  The contents of this file are subject to the vtiger CRM Public License Version 1.0
 * ("License"); You may not use this file except in compliance with the License
 * The Original Code is:  vtiger CRM Open Source
 * The Initial Developer of the Original Code is vtiger.
 * Portions created by vtiger are Copyright (C) vtiger.
 * All Rights Reserved.
 *
 *********************************************************************************/


/**
 * Description of ListViewController
 *
 * @author MAK
 */
class ListViewController {
	/**
	 *
	 * @var QueryGenerator
	 */
	private $queryGenerator;
	/**
	 *
	 * @var PearDatabase
	 */
	private $db;
	private $nameList;
	private $typeList;
	private $ownerNameList;
	private $user;
	private $picklistValueMap;
	private $picklistRoleMap;
	private $headerSortingEnabled;
	private $fieldColorMap = array();
	private $moduleFieldInstances;
	public function __construct($db, $user, $generator) {
		$this->queryGenerator = $generator;
		$this->db = $db;
		$this->user = $user;
		$this->nameList = array();
		$this->typeList = array();
		$this->ownerNameList = array();
		$this->picklistValueMap = array();
		$this->picklistRoleMap = array();
		$this->headerSortingEnabled = true;
	}

	public function isHeaderSortingEnabled() {
		return $this->headerSortingEnabled;
	}

	public function setHeaderSorting($enabled) {
		$this->headerSortingEnabled = $enabled;
	}

	public function setupAccessiblePicklistValueList($name) {
		$isRoleBased = vtws_isRoleBasedPicklist($name);
		$this->picklistRoleMap[$name] = $isRoleBased;
		if ($this->picklistRoleMap[$name]) {
			$this->picklistValueMap[$name] = getAssignedPicklistValues($name,$this->user->roleid, $this->db);
		}
	}

	public function fetchNameList($field, $result) {
		$referenceFieldInfoList = $this->queryGenerator->getReferenceFieldInfoList();
		$fieldName = $field->getFieldName();
		$rowCount = $this->db->num_rows($result);

		$idList = array();
		for ($i = 0; $i < $rowCount; $i++) {
			$id = $this->db->query_result($result, $i, $field->getColumnName());
			if (!isset($this->nameList[$fieldName][$id])) {
				$idList[$id] = $id;
			}
		}

		$idList = array_keys($idList);
		if(count($idList) == 0) {
			return;
		}
		$moduleList = $referenceFieldInfoList[$fieldName];
		foreach ($moduleList as $module) {
			$meta = $this->queryGenerator->getMeta($module);
			if ($meta->isModuleEntity()) {
				if($module == 'Users') {
					$nameList = getOwnerNameList($idList);
				} else {
					//TODO handle multiple module names overriding each other.
					$nameList = getEntityName($module, $idList);
				}
			} else {
				$nameList = vtws_getActorEntityName($module, $idList);
			}
			$entityTypeList = array_intersect(array_keys($nameList), $idList);
			foreach ($entityTypeList as $id) {
				$this->typeList[$id] = $module;
			}
			if(empty($this->nameList[$fieldName])) {
				$this->nameList[$fieldName] = array();
			}
			foreach ($entityTypeList as $id) {
				$this->typeList[$id] = $module;
				$this->nameList[$fieldName][$id] = $nameList[$id];
			}
		}
	}

	public function getListViewHeaderFields() {
		$meta = $this->queryGenerator->getMeta($this->queryGenerator->getModule());
		$moduleFields = $this->queryGenerator->getModuleFields();
		$fields = $this->queryGenerator->getFields(); 
		$headerFields = array();
		foreach($fields as $fieldName) {
			if(array_key_exists($fieldName, $moduleFields)) {
				$headerFields[$fieldName] = $moduleFields[$fieldName];
			}
		}
		return $headerFields;
	}

	function getListViewRecords($focus, $module, $result) {
		global $listview_max_textlength, $theme, $default_charset;

		// to check the owner of task, we need currentUserId
		$currentUser = Users_Record_Model::getCurrentUserModel();
		$currentUserId = $currentUser->getId();
	
		require('user_privileges/user_privileges_'.$this->user->id.'.php');
		$fields = $this->queryGenerator->getFields();
		$meta = $this->queryGenerator->getMeta($this->queryGenerator->getModule());

		$moduleFields = $this->queryGenerator->getModuleFields();
		$accessibleFieldList = array_keys($moduleFields);
		$listViewFields = array_intersect($fields, $accessibleFieldList);

		$referenceFieldList = $this->queryGenerator->getReferenceFieldList();
		foreach ($referenceFieldList as $fieldName) {
			if (in_array($fieldName, $listViewFields)) {
				$field = $moduleFields[$fieldName];
				$this->fetchNameList($field, $result);
			}
		}

		$db = PearDatabase::getInstance();
		$rowCount = $db->num_rows($result);
		$ownerFieldList = $this->queryGenerator->getOwnerFieldList();
		if ($module == 'Contacts') {
			$ownerFieldList[] = 'Accounts.assigned_user_id';
		}
		foreach ($ownerFieldList as $fieldName) {
			if (in_array($fieldName, $listViewFields)) {
				$field = $moduleFields[$fieldName];
				$idList = array();
				for ($i = 0; $i < $rowCount; $i++) {
					if ($fieldName == 'Accounts.assigned_user_id') {
						$id = $this->db->query_result($result, $i, 'accountsassigned_user_id');
					} else {
						$id = $this->db->query_result($result, $i, $field->getColumnName());
					}
					if (!isset($this->ownerNameList[$fieldName][$id])) {
						$idList[] = $id;
					}
				}
				if(count($idList) > 0) {
					if(!is_array($this->ownerNameList[$fieldName])) {
						$this->ownerNameList[$fieldName] = getOwnerNameList($idList);
					} 
					else {
						//array_merge API loses key information so need to merge the arrays
						// manually.
						$newOwnerList = getOwnerNameList($idList);
						foreach ($newOwnerList as $id => $name) {
							$this->ownerNameList[$fieldName][$id] = $name;
						}
					}
				}
			}
		}

		foreach ($listViewFields as $fieldName) {
			$field = $moduleFields[$fieldName];
			if(!$is_admin && ($field->getFieldDataType() == 'picklist' ||
					$field->getFieldDataType() == 'multipicklist')) {
				$this->setupAccessiblePicklistValueList($fieldName);
			}
		}
        
        $moduleInstance = Vtiger_Module_Model::getInstance("PBXManager");
        if($moduleInstance && $moduleInstance->isActive()) {
            $outgoingCallPermission = PBXManager_Server_Model::checkPermissionForOutgoingCall();
        }

		$useAsterisk = get_use_asterisk($this->user->id);

		$data = array();
		for ($i = 0; $i < $rowCount; ++$i) {
			//Getting the recordId
			if($module != 'Users') {
				$baseTable = $meta->getEntityBaseTable();
				$moduleTableIndexList = $meta->getEntityTableIndexList();
				$baseTableIndex = $moduleTableIndexList[$baseTable];

				$recordId = $db->query_result($result,$i,$baseTableIndex);
			}else {
				$recordId = $db->query_result($result,$i,"id");
			}
			$row = array();

			// here we dont know the row owmer and if it is a task in the row. We set it later.
			$isTaskPresent = false;
			$owneridnow = 0;

			foreach ($listViewFields as $fieldName) {
				$field = $moduleFields[$fieldName];
				$uitype = $field->getUIType();
                
                // crm-now: fetch by alias for columns with prefixed tablename for combined customview
                if (strpos($fieldName,".")>0) {  
                    $colname = strtolower(str_replace(".","",$fieldName));
                }
                else {
                    $colname = $field->getColumnName();
                }
                
				$rawValue = $this->db->query_result($result, $i, $colname);
        
				if(in_array($uitype,array(15,33,16))){
					$value = html_entity_decode($rawValue,ENT_QUOTES,$default_charset);
				} else {
					$value = $rawValue;
				}

				if($module == 'Documents' && $fieldName == 'filename') {
					$downloadtype = $db->query_result($result,$i,'filelocationtype');
					$fileName = $db->query_result($result,$i,'filename');

					$downloadType = $db->query_result($result,$i,'filelocationtype');
					$status = $db->query_result($result,$i,'filestatus');
					$fileIdQuery = "select attachmentsid from vtiger_seattachmentsrel where crmid=?";
					$fileIdRes = $db->pquery($fileIdQuery,array($recordId));
					$fileId = $db->query_result($fileIdRes,0,'attachmentsid');
					if ($fileName != '' && $status == 1) {
						if ($downloadType == 'I') {
							$value = '<a onclick="Javascript:Documents_Index_Js.updateDownloadCount(\'index.php?module=Documents&action=UpdateDownloadCount&record=' . $recordId . '\');"' .
								' href="index.php?module=Documents&action=DownloadFile&record=' . $recordId . '&fileid=' . $fileId . '"' .
								' title="' . getTranslatedString('LBL_DOWNLOAD_FILE', $module) . '" class="pdf-link" data-pdf-preview="index.php?module=Documents&action=DownloadFile&record=' . $recordId . '&fileid=' . $fileId . '">' .
								textlength_check($value) .
								'</a>';
						} elseif ($downloadType == 'E') {
							$value = '<a onclick="Javascript:Documents_Index_Js.updateDownloadCount(\'index.php?module=Documents&action=UpdateDownloadCount&record=' . $recordId . '\');"' .
								' href="' . $fileName . '" target="_blank"' .
								' title="' . getTranslatedString('LBL_DOWNLOAD_FILE', $module) . '' . $fileName . '">' .
								textlength_check($value) .
								'</a>';
						} else {
							$value = ' --';
						}
					}
					$value = $fileicon.$value;
				} elseif($module == 'Documents' && $fieldName == 'filesize') {
					$downloadType = $db->query_result($result,$i,'filelocationtype');
					if($downloadType == 'I') {
						$filesize = $value;
						if($filesize < 1024)
							$value=$filesize.' B';
						elseif($filesize > 1024 && $filesize < 1048576)
							$value=round($filesize/1024,2).' KB';
						else if($filesize > 1048576)
							$value=round($filesize/(1024*1024),2).' MB';
					} else {
						$value = ' --';
					}
				} elseif( $module == 'Documents' && $fieldName == 'filestatus') {
					if($value == 1)
						$value=getTranslatedString('yes',$module);
					elseif($value == 0)
						$value=getTranslatedString('no',$module);
					else
						$value='--';
				} elseif( $module == 'Documents' && $fieldName == 'filetype') {
					$downloadType = $db->query_result($result,$i,'filelocationtype');
					if($downloadType == 'E' || $downloadType != 'I') {
						$value = '--';
					}
				} elseif ($field->getUIType() == '27') {
					if ($value == 'I') {
						$value = getTranslatedString('LBL_INTERNAL',$module);
					}elseif ($value == 'E') {
						$value = getTranslatedString('LBL_EXTERNAL',$module);
					}else {
						$value = ' --';
					}
				}elseif ($field->getFieldDataType() == 'picklist') {
					//not check for permissions for non admin users for status and activity type field

					// Only if we have a task , we need to set on task subject and description to 'blocked' // $module == 'Calendar' &&
					if($fieldName == 'activitytype' && $value == 'Task' ){	
						$isTaskPresent = true; 
					} 

                    if($module == 'Calendar' && ($fieldName == 'taskstatus' || $fieldName == 'eventstatus' || $fieldName == 'activitytype')) {
                        $value = Vtiger_Language_Handler::getTranslatedString($value,$module);
						$value = textlength_check($value);
                    }
					else if ($value != '' && !$is_admin && $this->picklistRoleMap[$fieldName] &&
							!in_array($value, $this->picklistValueMap[$fieldName]) && strtolower($value) != '--none--' && strtolower($value) != 'none' ) {
						$value = "<font color='red'>". Vtiger_Language_Handler::getTranslatedString('LBL_NOT_ACCESSIBLE',
								$module)."</font>";
					} else {
						$value =  Vtiger_Language_Handler::getTranslatedString($value,$module);
						$value = textlength_check($value);
					}
				}elseif($field->getFieldDataType() == 'date' || $field->getFieldDataType() == 'datetime') {
					if($value != '' && $value != '0000-00-00') {
						$fieldDataType = $field->getFieldDataType();
						if($module == 'Calendar' &&($fieldName == 'date_start' || $fieldName == 'due_date')) {
                            if($fieldName == 'date_start') {
								$timeField = 'time_start';
							}else if($fieldName == 'due_date') {
								$timeField = 'time_end';
							}
                            $timeFieldValue = $this->db->query_result($result, $i, $timeField);
                            if(!empty($timeFieldValue)){
                                $value .= ' '. $timeFieldValue;
                                //TO make sure it takes time value as well
                                $fieldDataType = 'datetime';
                            }
						}
						if($fieldDataType == 'datetime') {
							$value = Vtiger_Datetime_UIType::getDateTimeValue($value);
						} else if($fieldDataType == 'date') {
							$date = new DateTimeField($value);
							$value = $date->getDisplayDate();
						}
					} elseif ($value == '0000-00-00') {
						$value = '';
					}
				} elseif($field->getFieldDataType() == 'time') {
					if(!empty($value)){
						$userModel = Users_Privileges_Model::getCurrentUserModel();
						if($userModel->get('hour_format') == '12'){
							$value = Vtiger_Time_UIType::getTimeValueInAMorPM($value);
						} else {
							$value = Vtiger_Time_UIType::getDisplayTimeValue($value);
						}
					}
				} elseif($field->getFieldDataType() == 'currency') {
					if($value != '') {
						if($field->getUIType() == 72) {
							if($fieldName == 'unit_price') {
								$currencyId = getProductBaseCurrency($recordId,$module);
								$cursym_convrate = getCurrencySymbolandCRate($currencyId);
								$currencySymbol = $cursym_convrate['symbol'];
							} else {
								$currencyInfo = getInventoryCurrencyInfo($module, $recordId);
								$currencySymbol = $currencyInfo['currency_symbol'];
							}
							$value = CurrencyField::convertToUserFormat($value, null, true);
							$row['currencySymbol'] = $currencySymbol;
//							$value = CurrencyField::appendCurrencySymbol($currencyValue, $currencySymbol);
						} else {
							if (!empty($value)) {
								$value = CurrencyField::convertToUserFormat($value);
							}
						}
					}
				} elseif($field->getFieldDataType() == 'url') {
                    $matchPattern = "^[\w]+:\/\/^";
                    preg_match($matchPattern, $rawValue, $matches);
                    if(!empty ($matches[0])){
                        $value = '<a class="urlField cursorPointer" href="'.$rawValue.'" target="_blank">'.textlength_check($value).'</a>';
                    }else{
                        $value = '<a class="urlField cursorPointer" href="http://'.$rawValue.'" target="_blank">'.textlength_check($value).'</a>';
                    }
				} elseif ($field->getFieldDataType() == 'email') {
					global $current_user;
					if($current_user->internal_mailer == 1){
						//check added for email link in user detailview
						$value = "<a class='emailField' onclick=\"Vtiger_Helper_Js.getInternalMailer($recordId,".
						"'$fieldName','$module');\">".textlength_check($value)."</a>";
					} else {
						$value = '<a class="emailField" href="mailto:'.$rawValue.'">'.textlength_check($value).'</a>';
					}
				} elseif($field->getFieldDataType() == 'boolean') {
					if ($value === 'on') {
						$value = 1;
					} else if ($value == 'off') {
						$value = 0;
					}
					if($value == 1) {
						$value = getTranslatedString('yes',$module);
					} elseif($value == 0) {
						$value = getTranslatedString('no',$module);
					} else {
						$value = '--';
					}
				} elseif($field->getUIType() == 98) {
					$value = '<a href="index.php?module=Roles&parent=Settings&view=Edit&record='.$value.'">'.textlength_check(getRoleName($value)).'</a>';
				} elseif($field->getFieldDataType() == 'multipicklist') {
					if(!$is_admin && $value != '') {
						$valueArray = ($value != "") ? explode(' |##| ',$value) : array();
						$notaccess = '<font color="red">'.getTranslatedString('LBL_NOT_ACCESSIBLE',
								$module)."</font>";
						$tmp = '';
						$tmpArray = array();
						foreach($valueArray as $index => $val) {
							//crm-now: added for special char like ä, ö, ü
							$val=html_entity_decode($val, ENT_QUOTES | ENT_HTML5, 'UTF-8');
							if(!$listview_max_textlength ||
									!(strlen(preg_replace("/(<\/?)(\w+)([^>]*>)/i","",$tmp)) >
											$listview_max_textlength)) {
								if (!$is_admin && $this->picklistRoleMap[$fieldName] &&
										!in_array(trim($val), $this->picklistValueMap[$fieldName])) {
									$tmpArray[] = $notaccess;
									$tmp .= ', '.$notaccess;
								} else {
									$tmpArray[] = getTranslatedString($val,$module);
									$tmp .= ', '.$val;
								}
							} else {
								$tmpArray[] = '...';
								$tmp .= '...';
							}
						}
						$value = implode(', ', $tmpArray);
						$value = textlength_check($value);
					} 
					else {
						if (!empty($value)) {
							$value_arr = explode("|##|", $value);

							foreach ($value_arr as $key => $content) {
								$value_arr[$key] = Vtiger_Language_Handler::getTranslatedString(trim($content), $module);;
							}
							$value = implode(', ', $value_arr);
						}
						else {
							$value = '';
						}
					}
				} elseif ($field->getFieldDataType() == 'skype') {
					$value = ($value != "") ? "<a href='skype:$value?call'>".textlength_check($value)."</a>" : "";
				} elseif ($field->getUIType() == 11) {
					$SoftphonePrefix = berliSoftphones_Record_Model:: getSoftphonePrefix();
					if($SoftphonePrefix && !empty($value)) {
						$replaced = preg_replace('/[-()\s]/', '', $value);
						$value = '<a class="phoneField" data-value="'.$replaced.'" record="'.$recordId.'" href="'.$SoftphonePrefix.($replaced).'">'.textlength_check($value).'</a>';
					}
 					
                    else if($outgoingCallPermission && !empty($value)) {
                        $phoneNumber = preg_replace('/[-()\s+]/', '',$value);
                        $value = '<a class="phoneField" data-value="'.$phoneNumber.'" record="'.$recordId.'" onclick="Vtiger_PBXManager_Js.registerPBXOutboundCall(\''.$phoneNumber.'\', '.$recordId.')">'.textlength_check($value).'</a>';
                    }else {
                        $value = textlength_check($value);
                    }
				} elseif($field->getFieldDataType() == 'reference') {
					$referenceFieldInfoList = $this->queryGenerator->getReferenceFieldInfoList();
					$moduleList = $referenceFieldInfoList[$fieldName];
					if(count($moduleList) == 1) {
						$parentModule = $moduleList[0];
					} else {
						$parentModule = $this->typeList[$value];
					}
					if(!empty($value) && !empty($this->nameList[$fieldName]) && !empty($parentModule) && (isRecordExists($value) || ($module == 'Documents' && $fieldName == 'folderid') || $fieldName == 'smcreatorid')) {
						$parentMeta = $this->queryGenerator->getMeta($parentModule);
						$value = textlength_check($this->nameList[$fieldName][$value]);
						if ($module == 'Documents' && $fieldName == 'folderid') {
							$value = getTranslatedString($value, $module);
						}
						if ($parentMeta->isModuleEntity() && $parentModule != "Users") {
							$value = "<a href='?module=$parentModule&view=Detail&".
							"record=$rawValue' title='".getTranslatedString($parentModule, $parentModule)."'>$value</a>";
						}
					} else {
						$value = '--';
					}
				} elseif($field->getFieldDataType() == 'owner') {

					// first we need to know the id of the owner now
					$owneridnow = $value;

					$value = textlength_check($this->ownerNameList[$fieldName][$value]);
				} elseif ($field->getUIType() == 25) {
					//TODO clean request object reference.
					$contactId=$_REQUEST['record'];
					$emailId=$this->db->query_result($result,$i,"activityid");
					$result1 = $this->db->pquery("SELECT access_count FROM vtiger_email_track WHERE ".
							"crmid=? AND mailid=?", array($contactId,$emailId));
					$value=$this->db->query_result($result1,0,"access_count");
					if(!$value) {
						$value = 0;
					}
				} elseif($field->getUIType() == 8){
					if (!empty($value)) {
						$temp_val = html_entity_decode($value, ENT_QUOTES, $default_charset);
						$decodedValue = json_decode($temp_val, true);
						if ($decodedValue !== null) {
							$value = vt_suppressHTMLTags(implode(',', $decodedValue));
						}
					}
				} 
				elseif($field->getUIType() == 7){
					if(!empty($value)){
						$currencyFieldObject = new CurrencyField($value);
						$currencyFieldObject->initialize();
						$separator = $currencyFieldObject->decimalSeparator;
						$sepPos = strpos($value, $separator);
						if ($sepPos !== false) {
							$value = $currencyFieldObject->convertToUserFormat($value, null, true);
							$value = substr($value, 0, strpos($value, $separator));
						}
					}
				}
				else {
					$value = textlength_check($value);
				}

//				// vtlib customization: For listview javascript triggers
//				$value = "$value <span type='vtlib_metainfo' vtrecordid='{$recordId}' vtfieldname=".
//					"'{$fieldName}' vtmodule='$module' style='display:none;'></span>";
//				// END
				$row[$fieldName] = $value;
				if (in_array($uitype, Settings_ListViewColors_IndexAjax_View::getSupportedUITypes())) {
					if (!isset($this->fieldColorMap[$fieldName]) || !isset($this->fieldColorMap[$fieldName][$rawValue])) {
						$rowListColor = Vtiger_Functions::getListViewColor($fieldName,$rawValue,$module, $this->moduleFieldInstances);
						$this->fieldColorMap[$fieldName][$rawValue] = $rowListColor;
					}
					$row['fieldcolor'][] = $this->fieldColorMap[$fieldName][$rawValue];
				}
			}


			// only if the 1 row are completed we have the needed information about it.
			// not Admin and it is Task, so we need to check owner and currentUser
			if(  !$is_admin && $isTaskPresent){ 
				// not the owner, so set 'subject' and 'description' to "blocked"
				if( $currentUserId != $owneridnow  ){
					foreach($row as $keyrow => $item){
						if( $keyrow == 'subject' ){
							$row[$keyrow] = vtranslate('Busy','Events');
						}
						if( $keyrow == 'description' ){
							$row[$keyrow] = vtranslate('Busy','Events');
						}
					}
				}
			}

			$data[$recordId] = $row;
			
		}
		return $data;
	}
	
}
?>