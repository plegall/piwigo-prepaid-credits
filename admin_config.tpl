{combine_script id='common' load='footer' path='admin/themes/default/js/common.js'}

{footer_script}
jQuery(document).ready(function(){
  jQuery('input.size-enable').change(function(){
    jQuery(this).closest('tr').find('.sizeDetails').css('display', jQuery(this).is(':checked') ? 'inline' : 'none');
  });

  jQuery('input[name=sell_credits]').change(function(){
    jQuery('.sell_credits-enabled').toggle();
  });
});
{/footer_script}

{html_style}
input[type="number"] {
  width:40px;
  text-align:right;
}
{if !$sell_credits}
.sell_credits-enabled {
  display:none;
}
{/if}
{/html_style}

<h2>Prepaid Credits - {'Configuration'|translate}</h2>

<form method="post" action="" class="properties">
<fieldset>
  <legend>{'General'|translate}</legend>
  <ul>
    <li>{'Default cost'|translate} <input type="number" step="1" min="1" max="999" name="photo_cost" value="{$photo_cost}"> {'credit(s) per photo'|translate}</li>
    <li>
      {'Download available for'|translate} <input type="number" step="1" min="1" max="999" name="download_period_length" value="{$download_period_length}">
      <select name="download_period_unity" size="1">
        {html_options options=$download_period_unity_options selected=$download_period_unity_options_selected}
      </select>
    </li>
  </ul>
</fieldset>
<fieldset>
  <legend>{'Photo sizes'|translate}</legend>
    <table style="margin:0">
    {foreach from=$sizes item=d key=type}
      <tr>
        <td>
          <label>
            <span class="sizeEnable font-checkbox">
              <span class="icon-check"></span>
              <input type="checkbox" class="size-enable" name="size_{$type}_enabled" {if !empty($d)}checked="checked"{/if}>
            </span>
            {$type|translate}
          </label>
        </td>
        <td>
          <span class="sizeDetails" style="display:{if !empty($d)}inline{else}none{/if}">
            <input type="number" step="1" min="1" max="999" name="size_{$type}" value="{$d}"> {'times the base cost per photo'|translate}
          </span>
        </td>
      </tr>
    {/foreach}
    </table>
</fieldset>

<fieldset>
  <legend>{'Selling credits'|translate}</legend>
  <ul>
    <li>
      <label>
        <span class="font-checkbox">
          <span class="icon-check"></span>
          <input type="checkbox" name="sell_credits" {if $sell_credits}checked="checked"{/if}>
        </span>
        {'Users can buy credits'|translate}
      </label>
    </li>
    <li class="sell_credits-enabled">{'PayPal account'|translate} <input type="text" name="paypal_account" value="{$paypal_account}"></li>
    <li class="sell_credits-enabled">
      {'Price per credit'|translate} <input type="number" min="0.01" step="0.01" name="price_per_credit" value="{$price_per_credit}">
      <select name="currency">{html_options options=$currency_options selected=$currency_options_selected}</select>
    </li>
  </ul>
</fieldset>

<p class="formButtons"><input type="submit" name="save_config" value="{'Save Settings'|translate}"></p>