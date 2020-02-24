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
      title: "Buy this photo",
      content: "Do you want to use "+cost+" credits to download this photo?",
      buttons: {
        confirm: {
          text: "yes, buy",
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
                    title: "Thank you!",
                    content: data.result.nb_credits+' credits taken from your account. <a class="prepaid-credits-download" href="'+data.result.download_url+'">Download now</a>',
                  });

                  $this.closest('li').html('<a class="download" href="'+data.result.download_url+'">'+data.result.size_label+'</a>');
                }
                else {
                  jQuery.alert("error on buying photo");
                }
              },
              error:function(XMLHttpRequest, textStatus, errorThrows) {
                alert("error while buying photo");
              }
            });
          }
        },
        cancel: {
          text:"no, cancel",
        }
      }
    });
  }
  else {
    jQuery.confirm({
      title: "Not enough credits!",
      content: 'No worry! <a href="profile.php">Buy more credits on your profile page.</a>',
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
