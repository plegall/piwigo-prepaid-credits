jQuery(document).ready(function() {
  function update_money_amount() {
    var price_per_credit = jQuery("#money_amount").data('unitprice');
    var nb_credits = jQuery("input[name=nb_credits]").val();
    var amount = sprintf('%.2f', price_per_credit * nb_credits);

    jQuery("#money_amount").html(amount);
  }

  update_money_amount();
  jQuery("input[name=nb_credits]").change(function() {
    update_money_amount();
  });

  jQuery("#paypalButton").click(function(e){
    jQuery.ajax({
      url: "ws.php?format=json&method=ppcredits.paypal.create",
      type:"POST",
      data: {
        nb_credits : jQuery("input[name=nb_credits]").val()
      },
      beforeSend: function() {
        jQuery("#paypal_form .errors").hide();
        jQuery(".loading").show();
      },
      success:function(data) {
        jQuery("#paypal_form .loading").hide();

        var data = jQuery.parseJSON(data);
        if (data.stat == 'ok') {
          jQuery("input[name=amount]").val(data.result.amount);
          jQuery("input[name=custom]").val(data.result.order_uuid);

          jQuery("#paypalForm").trigger('submit');
        }
        else {
          alert("error on creating the order");
        }
      },
      error:function(XMLHttpRequest, textStatus, errorThrows) {
        alert("error while creating the order");
      }
    });
  });

});