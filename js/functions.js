jQuery(function () {
  // find the Infusionpress form and set it to a var
  var infusionpressForm = jQuery('.infusionPressForm').find('.infusion-form');

  // find the Inf-field-email and set it to a var
  var infusionpressEmail = infusionpressForm.find('#inf_field_Email');

  // if the form exists remove the onsubmit attr and add the validation, and attempt to submit
  if(infusionpressForm.length){
    jQuery('.infusionPressForm').find('.infusion-form').removeAttr('onsubmit');

    // validate and submit if its all good
    infusionpressForm.validate();

    // if there is an email address, make it check for validity
    if(infusionpressEmail.length){
      infusionpressForm.validate({
        rules: {
          infusionpressEmail: {
            required: true,
            email: true
          }
        }
      });
    }

    // Get all the inputs into an array...
    var $inputs = jQuery('.infusionPressForm .infusion-form :input');

    // An array of just the ids...
    var ids = {};

    $inputs.each(function (index)
    {
       // For debugging purposes...
      //alert(index + ': ' + jQuery(this).attr('name'));

      ids[jQuery(this).attr('name')] = jQuery(this).attr('id');
      // get rid of the submit input button
      delete ids['undefined'];
    });


    infusionpressForm.on("submit", function (e) {
        e.preventDefault();
        var form = jQuery(this);
        //console.log("formInputs=" + JSON.stringify(form.serializeArray()) + "&formAction=" + form.attr('action'));
        var formInfo = {};
        formInfo = form.serialize();
        var formAction = form.attr('action');
        jQuery.ajax({
            url: wpBaseURL+'curl_it.php', // Get the action URL to send AJAX to
            type: "POST",
            data: formInfo + '&formAction=' + formAction, // get all form variables and field names as JSON object
            success: function(result){
                alert('we made it');
            },
            error: function(status, err){
                alert('whoops! something went awry');
            },
            complete: function(){
                alert('custom thank you popup');
                function clearForm(form) {
                  // grab all of the inputs for the form element that was passed in
                  jQuery(':input', form).each(function() {
                    var type = this.type;
                    var tag = this.tagName.toLowerCase(); // normalize case
                    // it's ok to reset the value attr of text inputs,
                    // password inputs, and textareas
                    if (type == 'text' || type == 'password' || tag == 'textarea')
                      this.value = "";
                    // checkboxes and radios need to have their checked state cleared
                    // but should *not* have their 'value' changed
                    else if (type == 'checkbox' || type == 'radio')
                      this.checked = false;
                    // select elements need to have their 'selectedIndex' property set to -1
                    // (this works for both single and multiple select elements)
                    else if (tag == 'select')
                      this.selectedIndex = -1;
                  });
                }
                clearForm(form);
            }
        });
    });

  }


});
