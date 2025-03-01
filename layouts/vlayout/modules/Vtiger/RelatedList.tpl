{*<!--
/*********************************************************************************
** The contents of this file are subject to the vtiger CRM Public License Version 1.0
* ("License"); You may not use this file except in compliance with the License
* The Original Code is:  vtiger CRM Open Source
* The Initial Developer of the Original Code is vtiger.
* Portions created by vtiger are Copyright (C) vtiger.
* All Rights Reserved.
*
********************************************************************************/
-->*}
{strip}
    <div class="relatedContainer">
        {assign var=RELATED_MODULE_NAME value=$RELATED_MODULE->get('name')}
        <input type="hidden" name="currentPageNum" value="{$PAGING->getCurrentPage()}" />
        <input type="hidden" name="relatedModuleName" class="relatedModuleName" value="{$RELATED_MODULE->get('name')}" />
        <input type="hidden" value="{$ORDER_BY}" id="orderBy">
        <input type="hidden" value="{$SORT_ORDER}" id="sortOrder">
        <input type="hidden" value="{$RELATED_ENTIRES_COUNT}" id="noOfEntries">
        <input type='hidden' value="{$PAGING->getPageLimit()}" id='pageLimit'>
        <input type='hidden' value="{$TOTAL_ENTRIES}" id='totalCount'>
        <div class="relatedHeader ">
            <div class="btn-toolbar row-fluid">
                <div class="span6">
                    {foreach item=RELATED_LINK from=$RELATED_LIST_LINKS['LISTVIEWBASIC']}
                        <div class="btn-group">
                            {assign var=IS_SELECT_BUTTON value={$RELATED_LINK->get('_selectRelation')}}
                            <button type="button" class="btn addButton
                                {if $IS_SELECT_BUTTON eq true} selectRelation{/if}"
                                {if $IS_SELECT_BUTTON eq true} 
                                    data-moduleName={$RELATED_LINK->get('_module')->get('name')} 
                                {/if}
                                {if ($RELATED_LINK->isPageLoadLink())}
                                    {if $RELATION_FIELD} 
                                        data-name="{$RELATION_FIELD->getName()}" 
                                    {/if}
                                    data-url="{$RELATED_LINK->getUrl()}"
                                {/if}
                                {if $IS_SELECT_BUTTON neq true} 
                                    name="addButton"
                                {/if}
                                >

                                {if $IS_SELECT_BUTTON eq false} 
                                    <i class="icon-plus icon-white">
                                    </i>
                                {/if}&nbsp;
                                <strong>
                                    {$RELATED_LINK->getLabel()}
                                </strong>
                            </button>
                        </div>
                    {/foreach}
                    &nbsp;
                </div>
                {if empty($moduleName)}
                    {assign var="moduleName" value=$MODULE}
                {/if}
                <div class="span6">
                    <div class="pull-right">
                        <span class="pageNumbers">
                            <span class="pageNumbersText">
                                {if !empty($RELATED_RECORDS)} 
                                    {$PAGING->getRecordStartRange()} {vtranslate('LBL_to', $RELATED_MODULE->get('name'))} {$PAGING->getRecordEndRange()}
                                {else}
                                    <span>
                                        &nbsp;
                                    </span>
                                {/if}
                            </span>
                            <span class="icon-refresh totalNumberOfRecords cursorPointer{if empty($RELATED_RECORDS)} hide{/if}">
                            </span>
                        </span>
                        <span class="btn-group">
                            <button class="btn" id="relatedListPreviousPageButton" {if !$PAGING->isPrevPageExists()} disabled {/if} type="button">
                                <span class="icon-chevron-left">
                                </span>
                            </button>
                            <button class="btn dropdown-toggle" type="button" id="relatedListPageJump" data-toggle="dropdown" {if $PAGE_COUNT eq 1} disabled {/if}>
                                <i class="vtGlyph vticon-pageJump" title="{vtranslate('LBL_LISTVIEW_PAGE_JUMP',$moduleName)}">
                                </i>
                            </button>
                            <ul class="listViewBasicAction dropdown-menu" id="relatedListPageJumpDropDown">
                                <li>
                                    <span class="row-fluid">
                                        <span class="span3"><span class="pull-right">{vtranslate('LBL_PAGE',$moduleName)}</span></span>
                                        <span class="span4">
                                            <input type="text" id="pageToJump" class="listViewPagingInput" value="{$PAGING->getCurrentPage()}"/>
                                        </span>
                                        <span class="span2 textAlignCenter">
                                            {vtranslate('LBL_OF',$moduleName)}
                                        </span>
                                        <span class="span3" id="totalPageCount">{$PAGE_COUNT}</span>
                                    </span>
                                </li>
                            </ul>
                            <button class="btn" id="relatedListNextPageButton" {if (!$PAGING->isNextPageExists()) or ($PAGE_COUNT eq 1)} disabled {/if} type="button">
                                <span class="icon-chevron-right"></span>
                            </button>
                        </span>
                    </div>
                </div>

            </div>
        </div>

        <div class="contents-topscroll">
            <div class="topscroll-div">
                &nbsp;
            </div>
        </div>

        <div class="relatedContents contents-bottomscroll">
            <div class="bottomscroll-div">
                {assign var=WIDTHTYPE value=$USER_MODEL->get('rowheight')}
                <table class="table table-bordered listViewEntriesTable">
                    <thead>
                        <tr class="listViewHeaders">
                            {foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                                <th {if $HEADER_FIELD@last} colspan="2" {/if} nowrap>
                                    {if $HEADER_FIELD->get('column') eq 'access_count' or $HEADER_FIELD->get('column') eq 'idlists' }
                                        <a href="javascript:void(0);" class="noSorting">{vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE->get('name'))}</a>
                                    {elseif $HEADER_FIELD->get('column') eq 'time_start'}
                                    {else}
                                        <a href="javascript:void(0);" class="relatedListHeaderValues" data-nextsortorderval="{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}{$NEXT_SORT_ORDER}{else}ASC{/if}" data-fieldname="{$HEADER_FIELD->get('column')}">{vtranslate($HEADER_FIELD->get('label'), $RELATED_MODULE->get('name'))}
                                            &nbsp;&nbsp;{if $COLUMN_NAME eq $HEADER_FIELD->get('column')}<img class="{$SORT_IMAGE}">{/if}
                                        </a>
                                    {/if}
                                </th>
                            {/foreach}
                        </tr>
                    </thead>
                    {if $RELATED_MODULE->isQuickSearchEnabled()}
                        <tr>
                            {foreach $RELATED_HEADERS AS $HEADER_FIELD}
                                {assign var=FIELD_UI_TYPE_MODEL value=$HEADER_FIELD->getUITypeModel()}
                                <td>
                                    {include file=vtemplate_path($FIELD_UI_TYPE_MODEL->getListSearchTemplateName(), $RELATED_MODULE->get('name'))
                                        FIELD_MODEL=$HEADER_FIELD SEARCH_INFO=$SEARCH_DETAILS[$HEADER_FIELD->getName()] USER_MODEL=$USER_MODEL}
                                </td>
                            {/foreach}
                            <td>
                                <button class="btn" data-trigger="listSearch">{vtranslate('LBL_SEARCH', $RELATED_MODULE->get('name'))}</button>
                            </td>
                        </tr>
                    {/if}
                    {foreach item=RELATED_RECORD from=$RELATED_RECORDS}
                        <tr style="background: linear-gradient({$RELATED_RECORD->getListViewColor()});" class="listViewEntries" data-id='{$RELATED_RECORD->getId()}' 
                            {if $RELATED_MODULE_NAME eq 'Calendar'}
                                {assign var=DETAILVIEWPERMITTED value=isPermitted($RELATED_MODULE->get('name'), 'DetailView', $RELATED_RECORD->getId())}
                                {if $DETAILVIEWPERMITTED eq 'yes'}
                                    data-recordUrl='{$RELATED_RECORD->getDetailViewUrl()}'
                                {/if}
                            {else}
                                data-recordUrl='{$RELATED_RECORD->getDetailViewUrl()}'
                            {/if}
                        >
                            {foreach item=HEADER_FIELD from=$RELATED_HEADERS}
                                {assign var=RELATED_HEADERNAME value=$HEADER_FIELD->get('name')}
                                <td class="{$WIDTHTYPE}" data-field-type="{$HEADER_FIELD->getFieldDataType()}" nowrap>
                                    {if $HEADER_FIELD->isNameField() eq true or $HEADER_FIELD->get('uitype') eq '4'}
                                        <a href="{$RELATED_RECORD->getDetailViewUrl()}">
                                            {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}
                                        </a>
                                    {elseif $RELATED_HEADERNAME eq 'access_count'}
                                        {$RELATED_RECORD->getAccessCountValue($PARENT_RECORD->getId())}
                                    {elseif $RELATED_HEADERNAME eq 'time_start'}
                                    {elseif $RELATED_HEADERNAME eq 'phone'}
                                        {assign var=SOFTPHONE value=berliSoftphones_Record_Model::getSoftphonePrefix()}
                                        {if $SOFTPHONE and $RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}
                                                {assign var=PHONE_FIELD_VALUE value=$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}
                                                {assign var=PHONE_NUMBER value=$PHONE_FIELD_VALUE|regex_replace:"/[-()\s]/":""}
                                                <a class="phoneField" data-value="{$PHONE_NUMBER}" record="{$RELATED_RECORD->getId()}" href="{$SOFTPHONE}{$PHONE_NUMBER}">
                                                    {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}
                                                </a>
                                        {else}
                                            {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}   
                                        {/if}
                                    {elseif $RELATED_HEADERNAME eq 'listprice' || $RELATED_HEADERNAME eq 'unit_price'}
                                        {CurrencyField::convertToUserFormat($RELATED_RECORD->get($RELATED_HEADERNAME), null, true)}
                                        {if $RELATED_HEADERNAME eq 'listprice'}
                                            {assign var="LISTPRICE" value=CurrencyField::convertToUserFormat($RELATED_RECORD->get($RELATED_HEADERNAME), null, true)}
                                        {/if}
                                    {else if $RELATED_HEADERNAME eq 'filename'}
                                        {$RELATED_RECORD->get($RELATED_HEADERNAME)}
                                    {else if $HEADER_FIELD->get('uitype') eq '19' or $HEADER_FIELD->get('uitype') eq '21'}
                                        {assign var="NOLINEBREAKS_DISPLAYVALUE" value=(str_replace(array('\n','\r','\r\n','\t','<br>','<br >','<br/>','<br />'),array('','','','','','','',''),$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)))|trim}
                                        <!-- we need the DisplayValue text without ' in it, because we need it to define data-content -->
                                        {assign var="NOQUO_DISPLAYVALUE" value=(str_replace("'","&rsquo;",$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)))|trim}
                                        {if $NOLINEBREAKS_DISPLAYVALUE neq '' && strlen($NOLINEBREAKS_DISPLAYVALUE) > 30 }
                                            <!--- RELATED_RECORD->getRawData()} -- rawData not exist --->
                                            {substr($NOLINEBREAKS_DISPLAYVALUE,0,30)}...
                                            <a style="margin-left: 2px;" href="#"  rel="popover" title="{vtranslate('Description', $MODULE)}"
                                             
                                                data-placement="bottom" data-trigger="hover" 
                                                data-content='{$NOQUO_DISPLAYVALUE}'>
                                                <i class="icon-info-sign"></i>
                                            </a>
                                        {else}
                                            {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}
                                        {/if}

                                    {else}
                                        {$RELATED_RECORD->getDisplayValue($RELATED_HEADERNAME)}   
                                    {/if}
                                    {if $RELATED_MODULE_NAME == 'Calendar' && $RELATED_HEADERNAME == "subject" && $RELATED_RECORD->get('description') && {$RELATED_RECORD->get('description')|trim} != '' }
                                        <i class="icon-info-sign" style="margin-left: 2px;" rel="popover" title="{vtranslate('Description', $MODULE)}" data-placement="bottom" data-trigger="hover" data-content="{$RELATED_RECORD->get('description')|escape:'html'}"></i>
                                    {/if}
                                    {if $HEADER_FIELD@last}
                                </td>
                                <td nowrap class="{$WIDTHTYPE}">
                                    <div class="pull-right actions">
                                        <span class="actionImages">
                                            {if $RELATED_MODULE_NAME eq 'Calendar'}
                                                {if $IS_EDITABLE && $RELATED_RECORD->get('taskstatus') neq 'Held' && $RELATED_RECORD->get('taskstatus') neq 'Completed'}
                                                    <a class="markAsHeld"><i title="{vtranslate('LBL_MARK_AS_HELD', $MODULE)}" class="icon-ok alignMiddle"></i></a>&nbsp;
                                                {/if}
                                                {if $IS_EDITABLE && $RELATED_RECORD->get('taskstatus') eq 'Held'}
                                                    <a class="holdFollowupOn"><i title="{vtranslate('LBL_HOLD_FOLLOWUP_ON', "Events")}" class="icon-flag alignMiddle"></i></a>&nbsp;
                                                {/if}
                                                {if $DETAILVIEWPERMITTED eq 'yes'}
                                                    <a href="{$RELATED_RECORD->getFullDetailViewUrl()}"><i title="{vtranslate('LBL_SHOW_COMPLETE_DETAILS', $MODULE)}" class="icon-th-list alignMiddle"></i></a>&nbsp;
                                                {/if}
                                            {else}
                                                <a href="{$RELATED_RECORD->getFullDetailViewUrl()}"><i title="{vtranslate('LBL_SHOW_COMPLETE_DETAILS', $MODULE)}" class="icon-th-list alignMiddle"></i></a>&nbsp;
                                            {/if}
                                            {if $IS_EDITABLE}
                                                {if $RELATED_MODULE_NAME eq 'PriceBooks'}
                                                    <a data-url="index.php?module=PriceBooks&view=ListPriceUpdate&record={$PARENT_RECORD->getId()}&relid={$RELATED_RECORD->getId()}&currentPrice={$LISTPRICE}"
                                                    class="editListPrice cursorPointer" data-related-recordid='{$RELATED_RECORD->getId()}' data-list-price={$LISTPRICE}>
                                                        <i class="icon-pencil alignMiddle" title="{vtranslate('LBL_EDIT', $MODULE)}"></i>
                                                    </a>
                                                {elseif $RELATED_MODULE_NAME eq 'Calendar'}
                                                    {if isPermitted($RELATED_MODULE->get('name'), 'EditView', $RELATED_RECORD->getId()) eq 'yes'}
                                                        <a href='{$RELATED_RECORD->getEditViewUrl()}'><i title="{vtranslate('LBL_EDIT', $MODULE)}" class="icon-pencil alignMiddle"></i></a>
                                                    {/if}
                                                {else}
                                                    <a href='{$RELATED_RECORD->getEditViewUrl()}'><i title="{vtranslate('LBL_EDIT', $MODULE)}" class="icon-pencil alignMiddle"></i></a>
                                                {/if}
                                            {/if}
                                            {if $IS_DELETABLE}
                                                {if $RELATED_MODULE_NAME eq 'Calendar'}
                                                    {if isPermitted($RELATED_MODULE->get('name'), 'Delete', $RELATED_RECORD->getId()) eq 'yes'}
                                                        <a class="relationDelete"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="icon-trash alignMiddle"></i></a>
                                                    {/if}
                                                {else}
                                                    <a class="relationDelete"><i title="{vtranslate('LBL_DELETE', $MODULE)}" class="icon-trash alignMiddle"></i></a>
                                                {/if}
                                            {/if}
                                        </span>
                                    </div>
                                </td>
                                    {else}
                                        
                                    {/if}
                            {/foreach}
                        </tr>
                    {/foreach}
                </table>
            </div>
        </div>
    </div>
    <script>
        jQuery().ready(function(){
            jQuery('[rel=popover]').popover();
        });
    </script>
{/strip}
