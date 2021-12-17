function showTab(n) {
  // This function will display the specified tab of the form...
  var x = document.getElementsByClassName("tab");
  x[n].style.display = "block";
  //... and fix the Previous/Next buttons:
  if(n == 0) {
    document.getElementById("prevBtn").style.display = "none";
  } else {
    document.getElementById("prevBtn").style.display = "block";
  }
  var nextBtn = document.getElementById("nextBtn");
  if(n == (x.length - 1))
  {
    setTimeout(function(){
      nextBtn.setAttribute("type", "submit");
    }, 2000);
    nextBtn.innerText = "Submit";
    nextBtn.removeAttribute("onclick");
  }
  else {
    nextBtn.setAttribute("type", "button");
    nextBtn.innerText = "Next";
    nextBtn.setAttribute("onclick", "nextPrev(1)");
  }
  //... and run a function that will display the correct step indicator:
  fixStepIndicator(n);
}

function nextPrev(n) {
  // This function will figure out which tab to display
  var x = document.getElementsByClassName("tab");
  // Exit the function if any field in the current tab is invalid:
  validateForm(n);
}

function validateForm(n) {
  // This function deals with validation of the form fields
  var x, y, valid = true;
  x = document.getElementsByClassName("tab");
  y = x[currentTab].getElementsByClassName("form-control");
  // Check every input field in the current tab:
  //TODO : if non-valid, set valid to false
  if(currentTab == 0)
  {
    $.ajax({
      type: 'POST',
      url: '/validate-step-0',
      data:
      {
        email: $('#registration_form_email').val(),
        password: $('#registration_form_password_first').val()
      },
      dataType: 'json',
      success: function(data)
      {
        var emailErrorMessage = data.emailErrorMessage;
        var passwordErrorMessage = data.passwordErrorMessage;
        var valid1 = handleResponse(emailErrorMessage, '#registration_form_email');
        var valid2 = handleResponse(passwordErrorMessage, '#registration_form_password_first');
        var valid3 = handleResponse(passwordErrorMessage, '#registration_form_password_second');
        if($('#registration_form_password_first').val() != $('#registration_form_password_second').val())
          valid3 = handleResponse('The password fields must match.', '#registration_form_password_second');
        valid = valid1 && valid2 && valid3;
        finalStep(valid, n);
      },
      error: function(errorMessage)
      {
          alert(errorMessage);
      }
    });
  }
  else if (currentTab == 1)
  {
    $.ajax({
      type: 'POST',
      url: '/validate-step-1',
      data:
      {
        username: $('#registration_form_username').val(),
        first_name: $('#registration_form_first_name').val(),
        last_name: $('#registration_form_last_name').val()
      },
      dataType: 'json',
      success: function(data)
      {
        var usernameErrorMessage = data.usernameErrorMessage;
        var firstNameErrorMessage = data.firstNameErrorMessage;
        var lastNameErrorMessage = data.lastNameErrorMessage;
        var valid1 = handleResponse(usernameErrorMessage, '#registration_form_username');
        var valid2 = handleResponse(firstNameErrorMessage, '#registration_form_first_name');
        var valid3 = handleResponse(lastNameErrorMessage, '#registration_form_last_name');
        valid = valid1 && valid2 && valid3;
        finalStep(valid, n);
      },
      error: function(errorMessage)
      {
        alert(errorMessage);
      }
    });
  }
  else if(currentTab == 2)
  {
    $.ajax({
      type: 'POST',
      url: '/validate-step-2',
      data:
      {
        occupation: $('#registration_form_occupation').val(),
        profile_picture: $('#registration_form_profile_picture').val(),
        CV: $('#registration_form_CV').val()
      },
      dataType: 'json',
      success: function(data)
      {
        var occupationErrorMessage = data.occupationErrorMessage;
        var profilePictureErrorMessage = data.profilePictureErrorMessage;
        var CVErrorMessage = data.CVErrorMessage;
        var valid1 = handleResponse(occupationErrorMessage, '#registration_form_occupation');
        var valid2 = handleResponse(profilePictureErrorMessage, '#registration_form_profile_picture');
        var valid3 = handleResponse(CVErrorMessage, '#registration_form_CV');
        valid = valid1 && valid2 && valid3;
        finalStep(valid, n);
      },
      error: function(errorMessage)
      {
        alert(errorMessage);
      }
    });
  }
}

function finalStep(valid, n)
{
  var z= document.getElementsByName("registration_form")[0];
  var x = document.getElementsByClassName("tab");
  // If the valid status is true, mark the step as finished and valid:
  if (valid) {
    document.getElementsByClassName("step")[currentTab].classList.add("finish");
    z.classList.remove("was-validated");
    // Hide the current tab:
    x[currentTab].style.display = "none";
    // Increase or decrease the current tab by 1:
    currentTab = currentTab + n;
  }
  else
  {
    z.classList.add("was-validated");
  }
  //Display the correct tab:
  showTab(currentTab);
}

function fixStepIndicator(n) {
  // This function removes the "active" class of all steps...
  var i, x = document.getElementsByClassName("step");
  for (i = 0; i < x.length; i++) {
    x[i].classList.remove("active");
  }
  //... and adds the "active" class on the current step:
  x[n].classList.add("active");
}

//Handling of the display of the message
function handleResponse(message, field)
{
  if(message != 'fine')
  {
    $(field).addClass('is-invalid');
    $(field).parent().find('.invalid-feedback').text(message);
    return false;
  }
  return true;
}

$('input').on('blur', function(){
  $(this).removeClass("is-invalid");
});