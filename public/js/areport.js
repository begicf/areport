/******/ (() => { // webpackBootstrap
/*!*********************************!*\
  !*** ./resources/js/areport.js ***!
  \*********************************/
$("#addRow").on('click', function () {
  var rowCount = $('#table tbody').find('tr').length;
  $('#table tbody>tr:last').clone(true).each(function () {
    $(this).find('td input, td select').each(function () {
      var name = $(this).attr('name').replace(/(c\d*r)\d*([^]*)/, "$1" + rowCount + "$2");
      var id = name.substring(0, name.indexOf('['));
      $(this).attr('name', name);
      if ($(this).attr('type') != 'hidden') {
        $(this).val('');
        $(this).attr('id', id);
      }
    });
  }).insertAfter('#table tbody>tr:last');
  rowCount++;
  return false;
});
$("#delRow").click(function () {
  var rowCount = $('#table tbody').find('tr').length;
  var $tbody = $("#table tbody");
  var $last = $tbody.find('tr:last');
  if ($last.is(':first-child')) {
    alert('You cannot delete the last one!');
  } else {
    $last.remove();
    rowCount--;
  }
});
/******/ })()
;