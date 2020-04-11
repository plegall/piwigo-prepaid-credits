{combine_script id='common' require='jquery' load='footer' path='admin/themes/default/js/common.js'}
{combine_script id='ppcredits' require='jquery' path='plugins/prepaid_credits/credits.js' load="footer"}

{html_style}{literal}
.loading {display:none;}
#paypalForm {display:none;}
.ppcreditsBuyCredits legend {text-transform:capitalize;}
{/literal}{/html_style}

<fieldset class="ppcreditsBuyCredits">
  <legend>{'credits'|translate}</legend>
  <p>{'You have %d credits left'|translate:$CREDITS_LEFT}</p>
{if $SELL_CREDITS}
  <p>{'Buy'|translate} <input name="nb_credits" type="number" value="{$NB_CREDITS}" min="1" max="999" step="1"> {'credits'|translate} (<span data-unitprice="{$PRICE_PER_CREDIT}" id="money_amount">{$MONEY_AMOUNT}</span> {$CURRENCY})
    <input type="submit" value="{'Pay on Paypal.com'|translate}" id="paypalButton">
    <img class="loading" src="themes/default/images/ajax-loader-small.gif">
  </p>

<form action="https://www.paypal.com/cgi-bin/webscr" method="post" id="paypalForm">
  <input type="hidden" name="cmd" value="_xclick">
{*
  <input type="hidden" name="lc" value="{$PENDING_ORDER.country_code}">
  <input type="hidden" name="country" value="{$PENDING_ORDER.country_code}">
*}
  <input type="hidden" name="custom" value="{* filled with javascript *}">
  <input type="hidden" name="business" value="{$PAYPAL_ACCOUNT}">
  <input type="hidden" name="item_name" value="Piwigo Prepaid Credits">
  <input type="hidden" name="amount" value="{* filled with javascript *}">
  <input type="hidden" name="currency_code" value="{$CURRENCY}">
  <input type="hidden" name="return" value="{$RETURN_URL}" />
  <input type="hidden" name="cancel_return" value="{$RETURN_URL}&amp;cancel=1" />
  <input type="hidden" name="notify_url" value="{$IPN_URL}" />
</form>
{/if}
</fieldset>


<fieldset class="ppcreditsHistory">
  <legend>{'Credits history'|translate}</legend>

  <ul>
{foreach from=$history_lines item=line}
    <li><span title="{$line.occured_on_string}">{$line.since}</span>, {$line.details}</li>
{/foreach}
  </ul>
</fieldset>