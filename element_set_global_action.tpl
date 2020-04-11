{html_style}{literal}
#specificPrice {display:none;}
{/literal}{/html_style}

{footer_script}{literal}
jQuery(document).ready(function() {
  function checkPriceOptions() {
    if (jQuery("input[name=price]:checked").val() == "specific") {
      jQuery("#specificPrice").show();
    }
    else {
      jQuery("#specificPrice").hide();
    }
  }

  checkPriceOptions();

  jQuery("input[name=price]").change(function() {
    checkPriceOptions();
  });
});
{/literal}{/footer_script}

<div id="ppcredits">
  <label><input type="radio" name="price" value="default" checked="checked"> {'follow default price (%s credits currently)'|translate:$PPCREDITS_DEFAULT_PRICE}</label>
  <br><label><input type="radio" name="price" value="specific"> {'specific fixed price'|translate}</label>
  <span id="specificPrice"><input name="nb_credits" type="number" value="{$PPCREDITS_DEFAULT_PRICE}" min="1" max="999"> {'credits'|translate}</span>
</div>