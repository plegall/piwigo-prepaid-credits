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
{/html_style}

<h2>Prepaid Credits - {'History'|@translate}</h2>

<form action="{$F_ACTION}" method="GET">
<fieldset>
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

{footer_script}
var oTable = jQuery('#historyTable').dataTable({});
{/footer_script}

<table id="historyTable">
<thead>
<tr class="throw">
	<th class="dtc_date">{'Date'|@translate}</th>
	<th class="dtc_user">{'User'|@translate}</th>
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
<td>{$history.paid}</td>
<td>{$history.spent}</td>
<td class="historyDetails">{$history.details}</td>
</tr>
{/strip}
{/foreach}
</table>