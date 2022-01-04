$(function() {
  $('.input-group.date').datepicker();
});

$('.invalid-feedback').each(function(){
  var str = $(this).text();
  if(str.trim().length)
  {
    $(this).prev().val('');
    console.log('not empty');
  }
  else {
    console.log('empty');
  }
}
);