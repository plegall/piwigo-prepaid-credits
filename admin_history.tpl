{combine_script id='common' load='footer' path='admin/themes/default/js/common.js'}
{combine_script id='jquery.dataTables' load='footer' path='themes/default/js/plugins/jquery.dataTables.js'}

{html_style}
.sorting { background: url({$ROOT_URL}themes/default/js/plugins/datatables/images/sort_both.png) no-repeat center right; cursor:pointer; }
.sorting_asc { background: url({$ROOT_URL}themes/default/js/plugins/datatables/images/sort_asc.png) no-repeat center right; }
.sorting_desc { background: url({$ROOT_URL}themes/default/js/plugins/datatables/images/sort_desc.png) no-repeat center right; }

.sorting, .sorting_asc, .sorting_desc { 
	padding: 3px 18px 3px 10px;
}
.sorting_asc_disabled { background: url({$ROOT_URL}themes/default/js/plugins/datatables/images/sort_asc_disabled.png) no-repeat center right; }
.sorting_desc_disabled { background: url({$ROOT_URL}themes/default/js/plugins/datatables/images/sort_desc_disabled.png) no-repeat center right; }

.dtBar {
	text-align:left;
	padding: 10px 0 10px 20px
}
.dtBar DIV{
	display:inline;
	padding-right: 5px;
}

.dataTables_paginate A {
	padding-left: 3px;
}

.historyDetails {
  text-align:left;
}

.filter input[type="submit"] {
  padding:3px 10px;
}
{/html_style}

<h2>Prepaid Credits - {'History'|@translate}</h2>

<form action="{$F_ACTION}" method="GET">
<fieldset class="filter">
  <legend>{'Filter'|@translate}</legend>
	<label>{'User'|@translate}
		<select name="user">
      <option value="-1">------------</option>
			{html_options options=$user_options selected=$user_options_selected}
		</select>
	</label>

	<input type="submit" value="{'Submit'|@translate}">
	<input type="hidden" name="page" value="plugin-prepaid_credits-history">
</fieldset>
</form>

{footer_script}{literal}
var oTable = jQuery('#historyTable').dataTable({
  language: {
    processing: "{/literal}{'Loading...'|translate|escape:'javascript'}{literal}",
    lengthMenu: sprintf("{/literal}{'Show %s lines'|translate|escape:'javascript'}{literal}", '_MENU_'),
    zeroRecords: "{/literal}{'No matching line found'|translate|escape:'javascript'}{literal}",
    info: sprintf("{/literal}{'Showing %s to %s of %s lines'|translate|escape:'javascript'}{literal}", '_START_', '_END_', '_TOTAL_'),
    infoEmpty: "{/literal}{'No matching line found'|translate|escape:'javascript'}{literal}",
    infoFiltered: sprintf("{/literal}{'(filtered from %s total lines)'|translate|escape:'javascript'}{literal}", '_MAX_'),
    search: '<span class="icon-search"></span>'+"{/literal}{'Search'|translate|escape:'javascript'}{literal}",
    loadingRecords: "{/literal}{'Loading...'|translate|escape:'javascript'}{literal}",
    paginate: {
        first:    "{/literal}{'First'|translate|escape:'javascript'}{literal}",
        previous: '← '+"{/literal}{'Previous'|translate|escape:'javascript'}{literal}",
        next:     "{/literal}{'Next'|translate|escape:'javascript'}{literal}"+' →',
        last:     "{/literal}{'Last'|translate|escape:'javascript'}{literal}",
    }
  }
});
{/literal}{/footer_script}

<table id="historyTable">
<thead>
<tr class="throw">
	<th class="dtc_date">{'Date'|@translate}</th>
	<th class="dtc_user">{'User'|@translate}</th>
	<th class="dtc_user">{'Email address'|@translate}</th>
	<th class="dtc_stat">{'Credits paid'|@translate}</th>
	<th class="dtc_stat">{'Credits spent'|@translate}</th>
	<th class="dtc_stat">{'Details'|@translate}</th>
</tr>
</thead>

{foreach from=$history_lines item=history}
{strip}
<tr>
<td>{$history.occured_on}</td>
<td>{$history.user}</td>
<td>{$history.user_email}</td>
<td>{$history.paid}</td>
<td>{$history.spent}</td>
<td class="historyDetails">{$history.details}</td>
</tr>
{/strip}
{/foreach}
</table>