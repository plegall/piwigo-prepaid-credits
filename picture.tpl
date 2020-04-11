{combine_script id='common' load='footer' require='jquery' path='admin/themes/default/js/common.js'}
{combine_script id='jquery.confirm' load='footer' require='jquery' path='plugins/prepaid_credits/vendor/jquery-confirm.min.js'}
{combine_css path="plugins/prepaid_credits/vendor/jquery-confirm.min.css"}

{combine_css path="plugins/prepaid_credits/ppcredits.css"}

{footer_script}
jQuery("#ppcreditsBuyPhoto a.buy").click(function(){
  var $this = jQuery(this);
  var $loading = $this.closest('li').find('.loading');

  var cost = jQuery(this).data("cost");
  var size = jQuery(this).data("size");

  if (jQuery("#ppcreditsBuyPhoto").data("credits_left") >= cost) {
    jQuery.confirm({
      theme: 'modern',
      useBootstrap: false,
      title: "{'Buy this photo'|translate}",
      content: sprintf("{'Do you want to use %d credits to download this photo?'|translate}", cost),
      buttons: {
        confirm: {
          text: "{'yes, buy'|translate}",
          btnClass: 'btn-blue',
          action: function() {
            jQuery.ajax({
              url: "ws.php?format=json&method=ppcredits.photo.buy",
              type:"POST",
              data: {
                image_id : jQuery("#ppcreditsBuyPhoto").data("image_id"),
                size : size
              },
              beforeSend: function() {
                $loading.show();
              },
              success:function(data) {
                $loading.hide();

                var data = jQuery.parseJSON(data);
                if (data.stat == 'ok') {
                  jQuery.alert({
                    theme: 'modern',
                    useBootstrap: false,
                    title: "{'Thank you!'|translate}",
                    content: sprintf("{'%d credits taken from your account.'|translate}", Number(data.result.nb_credits))+' <a class="prepaid-credits-download" href="'+data.result.download_url+'">'+"{'Download now'|translate}"+'</a>',
                  });

                  $this.closest('li').html('<a class="download" href="'+data.result.download_url+'">'+data.result.size_label+'</a>');
                }
                else {
                  jQuery.alert("#1 {'error while buying photo'|translate}");
                }
              },
              error:function(XMLHttpRequest, textStatus, errorThrows) {
                jQuery.alert("#2 {'error while buying photo'|translate}");
              }
            });
          }
        },
        cancel: {
          text:"{'no, cancel'|translate}",
        }
      }
    });
  }
  else {
    jQuery.confirm({
      theme: 'modern',
      useBootstrap: false,
      title: "{'Not enough credits!'|translate}",
      content: '{$MISSING_CREDITS_SENTENCE|escape:javascript}',
    });
  }

  return false;
});
{/footer_script}

<div id="ppcreditsBuyPhoto" data-credits_left="{$CREDITS_LEFT}" data-image_id="{$current.id}">
{'Download this file'|@translate}
<ul>
{foreach from=$ppcredits_sizes item=size}
  <li>
  {if isset($size.download_url)}
    <a class="download" href="{$size.download_url}">{$size.type|translate}</a>
  {else}
    <a class="buy" href="#" data-cost="{$size.nb_credits}" data-size="{$size.type}">{$size.label}</a> <img class="loading" src="themes/default/images/ajax-loader-small.gif" style="display:none">
  {/if}
  </li>
{/foreach}
</ul>
</div>
