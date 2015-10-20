{combine_script id='ppcredits' require='jquery' path='plugins/prepaid_credits/vendor/sweetalert/dist/sweetalert.min.js' load="footer"}

{combine_css path="plugins/prepaid_credits/ppcredits.css"}
{combine_css path="plugins/prepaid_credits/vendor/sweetalert/dist/sweetalert.css"}

{footer_script}
jQuery("#ppcreditsBuyPhoto a.buy").click(function(){
  var cost = jQuery(this).data("cost");
  var size = jQuery(this).data("size");

  if (jQuery("#ppcreditsBuyPhoto").data("credits_left") >= cost) {
    swal({
      title: "Buy this photo",
      text: "Do you want to use "+cost+" credits to download this photo?",
      showCancelButton: true,
      closeOnConfirm: false,
      showLoaderOnConfirm: true,
    },
    function(){
      jQuery.ajax({
        url: "ws.php?format=json&method=ppcredits.photo.buy",
        type:"POST",
        data: {
          image_id : jQuery("#ppcreditsBuyPhoto").data("image_id"),
          size : size
        },
        success:function(data) {
          var data = jQuery.parseJSON(data);
          if (data.stat == 'ok') {
            swal({
              title: "Thank you!",
              text: data.result.nb_credits+" credits taken from your account",
              showCancelButton: false,
            },
            function(){
              location.reload(true);
            });
          }
          else {
            alert("error on buying photo");
          }
        },
        error:function(XMLHttpRequest, textStatus, errorThrows) {
          alert("error while buying photo");
        }
      });
    });
  }
  else {
    swal({
      title: "Not enough credits!",
      text: 'No worry! <a href="profile.php">Buy more credits on your profile page.</a>',
      html: true
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
    <a class="buy" href="#" data-cost="{$size.nb_credits}" data-size="{$size.type}">{$size.label}</a>
  {/if}
  </li>
{/foreach}
</ul>
</div>
