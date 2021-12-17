$('.delete_user').click(function(e){
  e.preventDefault();
  confirm('Are you sure ?');
  $(this).closest('tr').remove();
});
